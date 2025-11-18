/**
 * Table Operations Module
 * Handles all table CRUD operations
 */

const TableOperations = {
    /**
     * Load tables for current database
     */
    load: function() {
        if (!window.State.currentDatabase) return;

        $.ajax({
            url: '../api/?action=getTables&database=' + encodeURIComponent(window.State.currentDatabase),
            method: 'GET',
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    // Tables are now shown per-database subsection
                }
            },
            error: (xhr) => {
                window.Utils.showToast('Error loading tables: ' + xhr.responseText, 'error');
            }
        });
    },

    /**
     * Load tables for a specific database
     */
    loadForDatabase: function(databaseName, callback) {
        $.ajax({
            url: '../api/?action=getTables&database=' + encodeURIComponent(databaseName),
            method: 'GET',
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    if (typeof callback === 'function') {
                        callback(response.tables);
                    }
                }
            },
            error: (xhr) => {
                window.Utils.showToast('Error loading tables for ' + databaseName + ': ' + xhr.responseText, 'error');
            }
        });
    },

    /**
     * Create a new table
     */
    create: function() {
        const name = $('#newTableName').val().trim();
        const columns = $('#newTableColumns').val().trim();
        const engine = $('#newTableEngine').val();

        if (!name || !columns) {
            window.Utils.showToast('Please enter table name and columns', 'warning');
            return;
        }

        $.ajax({
            url: '../api/',
            method: 'POST',
            data: {
                action: 'createTable',
                database: window.State.currentDatabase,
                name: name,
                columns: columns,
                engine: engine
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    window.Utils.showToast('Table created successfully!', 'success');
                    window.ModalManager.close('createTableModal');
                    TableOperations.load();
                    window.DatabaseOperations.load(); // Refresh stats
                } else {
                    window.Utils.showToast('Error: ' + response.error, 'error');
                }
            },
            error: (xhr) => {
                let errorMessage = 'Unknown error';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMessage = response.error || 'Unknown error';
                } catch (e) {
                    errorMessage = xhr.responseText || 'Unknown error';
                }
                window.Utils.showToast('Error: ' + errorMessage, 'error');
            }
        });
    },

    /**
     * Delete a table
     */
    delete: function(tableName, databaseName = null, fromSubsection = false) {
        const dbName = databaseName || window.State.currentDatabase;
        
        window.ModalManager.showConfirmDialog({
            title: 'Delete Table',
            message: `Are you sure you want to delete the table "${tableName}" from database "${dbName}"? This action cannot be undone!`,
            confirmText: 'Delete',
            confirmClass: 'btn-danger'
        }, function onConfirm() {
            $.ajax({
                url: '../api/',
                method: 'POST',
                data: {
                    action: 'deleteTable',
                    database: dbName,
                    name: tableName
                },
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        window.Utils.showToast('Table deleted successfully!', 'success');
                        
                        if (fromSubsection) {
                            // Remove the table from the subsection
                            $(`.database-table-item[data-table="${tableName}"]`).remove();
                            // Update the table count in the database item
                            window.UIRenderer.updateDatabaseTableCount(dbName);
                        } else {
                            // Clear selection if the deleted table was selected
                            if (window.State.selectedTable === tableName) {
                                window.State.selectedTable = '';
                            }
                            TableOperations.load();
                        }
                        
                        // Refresh the main database list stats
                        window.DatabaseOperations.load();
                    } else {
                        window.Utils.showToast('Error: ' + response.error, 'error');
                    }
                },
                error: (xhr) => {
                    const response = JSON.parse(xhr.responseText);
                    window.Utils.showToast('Error: ' + (response.error || 'Unknown error'), 'error');
                }
            });
        });
    },

    /**
     * Rename a table
     */
    rename: function() {
        const databaseName = ($('#renameTableDatabase').val() || '').trim() || window.State.currentDatabase;
        const oldName = ($('#renameTableCurrentName').val() || '').trim();
        const newName = ($('#renameTableNewName').val() || '').trim();

        if (!databaseName || !oldName) {
            window.Utils.showToast('Missing table information. Please try again.', 'error');
            return;
        }

        if (!newName) {
            window.Utils.showToast('Please enter a new table name', 'warning');
            return;
        }

        if (!/^[a-zA-Z0-9_]+$/.test(newName)) {
            window.Utils.showToast('Table names can only contain letters, numbers, and underscores', 'warning');
            return;
        }

        if (newName === oldName) {
            window.Utils.showToast('Please enter a different table name', 'warning');
            return;
        }

        $('#confirmRenameTableBtn').prop('disabled', true).text('⏳ Renaming...');

        $.ajax({
            url: '../api/',
            method: 'POST',
            data: {
                action: 'renameTable',
                database: databaseName,
                oldName: oldName,
                newName: newName
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    window.Utils.showToast('Table renamed successfully!', 'success');
                    window.ModalManager.close('renameTableModal');

                    // Ensure the renamed table's database stays selected
                    window.State.currentDatabase = databaseName;

                    // Refresh database list to reflect new name
                    window.DatabaseOperations.load();
                } else {
                    window.Utils.showToast('Error: ' + response.error, 'error');
                }
            },
            error: (xhr) => {
                let errorMessage = 'Unknown error';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMessage = response.error || 'Unknown error';
                } catch (e) {
                    errorMessage = xhr.responseText || 'Unknown error';
                }
                window.Utils.showToast('Error: ' + errorMessage, 'error');
            },
            complete: () => {
                $('#confirmRenameTableBtn').prop('disabled', false).text('💾 Rename');
            }
        });
    },

    /**
     * View table structure (navigate to table structure page)
     */
    viewStructure: function(tableName, databaseName = null) {
        const dbName = databaseName || window.State.currentDatabase;
        
        // Set both database and table in session before navigating
        $.ajax({
            url: '../api/',
            method: 'POST',
            data: {
                action: 'setCurrentDatabase',
                database: dbName
            },
            dataType: 'json',
            success: () => {
                // Set the current table in session
                $.ajax({
                    url: '../api/',
                    method: 'POST',
                    data: {
                        action: 'setCurrentTable',
                        table: tableName
                    },
                    dataType: 'json',
                    success: () => {
                        window.UIRenderer.updateDatabaseBadge(dbName, tableName);
                        window.location.href = '../table_structure/';
                    },
                    error: () => {
                        window.UIRenderer.updateDatabaseBadge(dbName, tableName);
                        window.location.href = '../table_structure/';
                    }
                });
            },
            error: () => {
                window.UIRenderer.updateDatabaseBadge(dbName, tableName);
                window.location.href = '../table_structure/';
            }
        });
    },

    /**
     * View table data (navigate to table data page)
     */
    viewData: function(tableName, databaseName = null) {
        const dbName = databaseName || window.State.currentDatabase;
        
        // Set both database and table in session before navigating
        $.ajax({
            url: '../api/',
            method: 'POST',
            data: {
                action: 'setCurrentDatabase',
                database: dbName
            },
            dataType: 'json',
            success: () => {
                // Set the current table in session
                $.ajax({
                    url: '../api/',
                    method: 'POST',
                    data: {
                        action: 'setCurrentTable',
                        table: tableName
                    },
                    dataType: 'json',
                    success: () => {
                        window.UIRenderer.updateDatabaseBadge(dbName, tableName);
                        window.location.href = `../data_manager/?table=${encodeURIComponent(tableName)}&database=${encodeURIComponent(dbName)}`;
                    },
                    error: () => {
                        window.UIRenderer.updateDatabaseBadge(dbName, tableName);
                        window.location.href = `../data_manager/?table=${encodeURIComponent(tableName)}&database=${encodeURIComponent(dbName)}`;
                    }
                });
            },
            error: () => {
                window.UIRenderer.updateDatabaseBadge(dbName, tableName);
                window.location.href = `../data_manager/?table=${encodeURIComponent(tableName)}&database=${encodeURIComponent(dbName)}`;
            }
        });
    }
};

// Export for use in other modules
if (typeof window !== 'undefined') {
    window.TableOperations = TableOperations;
    // Also expose functions globally for backward compatibility
    window.loadTables = TableOperations.load;
    window.loadTablesForDatabase = TableOperations.loadForDatabase;
    window.createTable = TableOperations.create;
    window.deleteTable = TableOperations.delete;
    window.renameTable = TableOperations.rename;
    window.viewTableStructure = TableOperations.viewStructure;
    window.viewTableData = TableOperations.viewData;
}


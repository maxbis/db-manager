/**
 * Database Operations Module
 * Handles all database CRUD operations
 */

const DatabaseOperations = {
    /**
     * Load all databases from the API
     */
    load: function() {
        $('#loading').addClass('active');

        $.ajax({
            url: '../api/?action=getDatabases',
            method: 'GET',
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    window.State.databases = response.databases;
                    window.UIRenderer.displayDatabases();
                    window.UIRenderer.populateDatabaseSelect();
                    
                    // If there's a current database set from session, restore its state
                    if (window.State.currentDatabase) {
                        console.log('Restoring state for currentDatabase:', window.State.currentDatabase);
                        
                        // Update visual state of database items
                        $('.database-item').removeClass('active');
                        const $currentDbItem = $(`.database-item[data-database="${window.State.currentDatabase}"]`);
                        $currentDbItem.addClass('active');
                        console.log('Active class added to:', $currentDbItem.length, 'items');
                        
                        // Update database badges
                        let badgesUpdated = 0;
                        $('.database-name').each(function() {
                            const $this = $(this);
                            const $badge = $this.find('.badge-current');
                            const itemDbName = $this.closest('.database-item').data('database');
                            if (itemDbName === window.State.currentDatabase && !$badge.length) {
                                $this.append('<span class="badge-current" title="Currently selected">Current</span>');
                                badgesUpdated++;
                            } else if (itemDbName !== window.State.currentDatabase && $badge.length) {
                                $badge.remove();
                            }
                        });
                        console.log('Badges updated:', badgesUpdated);
                        
                        // Auto-expand the current database's tables section
                        const expandIndicator = $currentDbItem.find('.expand-indicator');
                        const tablesSubsection = $(`.database-tables-subsection[data-database="${window.State.currentDatabase}"]`);
                        
                        if (expandIndicator.length && tablesSubsection.length) {
                            expandIndicator.addClass('expanded');
                            tablesSubsection.addClass('expanded');
                            
                            // Function to scroll database into view
                            const scrollToDatabase = function() {
                                setTimeout(function() {
                                    const elementOffset = $currentDbItem.offset().top;
                                    const windowHeight = $(window).height();
                                    const elementHeight = $currentDbItem.outerHeight();
                                    const scrollPosition = elementOffset - (windowHeight / 2) + (elementHeight / 2);
                                    
                                    $('html, body').animate({
                                        scrollTop: scrollPosition
                                    }, 500);
                                }, 100);
                            };
                            
                            // Load tables if not already loaded
                            const tablesGrid = tablesSubsection.find('.database-tables-grid');
                            if (tablesGrid.children().length === 0) {
                                window.TableOperations.loadForDatabase(window.State.currentDatabase, function(tables) {
                                    window.UIRenderer.displayTablesInSubsection(window.State.currentDatabase, tables);
                                    scrollToDatabase();
                                });
                            } else {
                                tablesSubsection.show();
                                scrollToDatabase();
                            }
                        }
                        
                        window.UIRenderer.updateButtonStates();
                    } else {
                        console.log('No currentDatabase set, skipping state restoration');
                        window.UIInteractions.closeAllExpandedDatabases();
                    }
                    
                    window.UIRenderer.updateStats();
                }
                $('#loading').removeClass('active');
                $('#dashboardContent').show();
                $('#emptyState').hide();
            },
            error: (xhr) => {
                window.Utils.showToast('Error loading databases: ' + xhr.responseText, 'error');
                $('#loading').removeClass('active');
            }
        });
    },

    /**
     * Create a new database
     */
    create: function() {
        const name = $('#newDatabaseName').val().trim();
        const charset = $('#newDatabaseCharset').val();
        const collation = $('#newDatabaseCollation').val();

        if (!name) {
            window.Utils.showToast('Please enter a database name', 'warning');
            return;
        }

        $.ajax({
            url: '../api/',
            method: 'POST',
            data: {
                action: 'createDatabase',
                name: name,
                charset: charset,
                collation: collation
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    window.Utils.showToast('Database created successfully!', 'success');
                    window.ModalManager.close('createDatabaseModal');
                    DatabaseOperations.load();
                } else {
                    window.Utils.showToast('Error: ' + response.error, 'error');
                }
            },
            error: (xhr) => {
                const response = JSON.parse(xhr.responseText);
                window.Utils.showToast('Error: ' + (response.error || 'Unknown error'), 'error');
            }
        });
    },

    /**
     * Delete a database
     */
    delete: function(databaseName) {
        window.ModalManager.showConfirmDialog({
            title: 'Delete Database',
            message: `Are you sure you want to delete the database "${databaseName}"? This action cannot be undone!`,
            confirmText: 'Delete',
            confirmClass: 'btn-danger'
        }, function onConfirm() {
            $.ajax({
                url: '../api/',
                method: 'POST',
                data: {
                    action: 'deleteDatabase',
                    name: databaseName
                },
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        window.Utils.showToast('Database deleted successfully!', 'success');
                        if (window.State.currentDatabase === databaseName) {
                            window.State.currentDatabase = '';
                            $('#databaseSelect').val('').trigger('change');
                        }
                        DatabaseOperations.load();
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
     * Export a database
     */
    export: function() {
        const databaseName = $('#exportDatabaseName').val();
        const fileName = $('#exportFileName').val().trim();
        const includeCreateDatabase = $('#exportCreateDatabase').is(':checked');
        const dataOnly = $('#exportDataOnly').is(':checked');

        if (!fileName) {
            window.Utils.showToast('Please enter a file name', 'warning');
            return;
        }

        window.Utils.showToast('Exporting database...', 'warning');

        $.ajax({
            url: '../api/',
            method: 'POST',
            data: {
                action: 'exportDatabase',
                name: databaseName,
                includeCreateDatabase: includeCreateDatabase,
                dataOnly: dataOnly
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    // Create download link
                    const blob = new Blob([response.sql], { type: 'application/sql' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = fileName.endsWith('.sql') ? fileName : `${fileName}.sql`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);

                    window.Utils.showToast('Database exported successfully!', 'success');
                    window.ModalManager.close('exportDatabaseModal');
                } else {
                    window.Utils.showToast('Error: ' + response.error, 'error');
                }
            },
            error: (xhr) => {
                const response = JSON.parse(xhr.responseText);
                window.Utils.showToast('Error: ' + (response.error || 'Unknown error'), 'error');
            }
        });
    },

    /**
     * Export all databases
     */
    exportAll: function() {
        const filename = $('#exportAllFilename').val().trim();
        const includeCreateDatabase = $('#exportAllIncludeCreateDatabase').is(':checked');
        const dataOnly = $('#exportAllDataOnly').is(':checked');

        if (!filename) {
            window.Utils.showToast('Please enter a filename', 'error');
            return;
        }

        // Show loading state
        $('#confirmExportAllBtn').prop('disabled', true).text('📦 Exporting...');

        // Close modal immediately
        window.ModalManager.close('exportAllDatabasesModal');

        // Create a form to submit the request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../api/';

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'exportAllDatabases';

        const filenameInput = document.createElement('input');
        filenameInput.type = 'hidden';
        filenameInput.name = 'filename';
        filenameInput.value = filename;

        const includeCreateInput = document.createElement('input');
        includeCreateInput.type = 'hidden';
        includeCreateInput.name = 'includeCreateDatabase';
        includeCreateInput.value = includeCreateDatabase ? 'true' : 'false';

        const dataOnlyInput = document.createElement('input');
        dataOnlyInput.type = 'hidden';
        dataOnlyInput.name = 'dataOnly';
        dataOnlyInput.value = dataOnly ? 'true' : 'false';

        form.appendChild(actionInput);
        form.appendChild(filenameInput);
        form.appendChild(includeCreateInput);
        form.appendChild(dataOnlyInput);

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);

        // Reset button after a delay
        setTimeout(() => {
            $('#confirmExportAllBtn').prop('disabled', false).text('📦 Export All');
        }, 2000);
    },

    /**
     * Import a database
     */
    import: function() {
        const fileInput = document.getElementById('importFile');
        const file = fileInput.files[0];
        const targetDatabase = $('#importTargetDatabase').val();
        const dropExisting = $('#importDropExisting').is(':checked');

        if (!file) {
            window.Utils.showToast('Please select a SQL file', 'warning');
            return;
        }

        if (!targetDatabase) {
            window.Utils.showToast('Please select a target database', 'warning');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'importDatabase');
        formData.append('file', file);
        formData.append('database', targetDatabase);
        formData.append('dropExisting', dropExisting);

        window.Utils.showToast('Importing database...', 'warning');

        $.ajax({
            url: '../api/',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    window.Utils.showToast('Database imported successfully!', 'success');
                    window.ModalManager.close('importDatabaseModal');
                    DatabaseOperations.load();
                } else {
                    window.Utils.showToast('Error: ' + response.error, 'error');
                }
            },
            error: (xhr) => {
                const response = JSON.parse(xhr.responseText);
                window.Utils.showToast('Error: ' + (response.error || 'Unknown error'), 'error');
            }
        });
    }
};

// Export for use in other modules
if (typeof window !== 'undefined') {
    window.DatabaseOperations = DatabaseOperations;
    // Also expose functions globally for backward compatibility
    window.loadDatabases = DatabaseOperations.load;
    window.createDatabase = DatabaseOperations.create;
    window.deleteDatabase = DatabaseOperations.delete;
    window.exportDatabase = DatabaseOperations.export;
    window.exportAllDatabases = DatabaseOperations.exportAll;
    window.importDatabase = DatabaseOperations.import;
}


/**
 * Table Operations Module
 * Handles loading tables and table structure
 */

const TableOperations = {
    /**
     * Load all tables
     */
    loadTables: function() {
        $.ajax({
            url: '../api/?action=getTables',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const select = $('#tableSelect');
                    select.empty();
                    select.append('<option value="">-- Choose a table --</option>');
                    
                    response.tables.forEach(function(table) {
                        // Handle both old format (string) and new format (object)
                        const tableName = typeof table === 'string' ? table : table.name;
                        const tableType = typeof table === 'object' ? table.type : 'BASE TABLE';
                        const label = tableType === 'VIEW' ? `${tableName} 👁️ (view)` : tableName;
                        
                        select.append(`<option value="${tableName}" data-type="${tableType}">${label}</option>`);
                    });
                    
                    // Select the current table from session
                    if (window.State.currentTable) {
                        const tableNames = response.tables.map(t => typeof t === 'string' ? t : t.name);
                        if (tableNames.includes(window.State.currentTable)) {
                            select.val(window.State.currentTable).trigger('change');
                        }
                    }
                }
                $('#loading').removeClass('active');
            },
            error: function(xhr) {
                window.Utils.showError('Error loading tables: ' + xhr.responseText);
                $('#loading').removeClass('active');
            }
        });
    },

    /**
     * Load table structure
     */
    loadTableStructure: function() {
        $('#loading').addClass('active');
        $('#tableStructure').hide();
        
        $.ajax({
            url: '../api/?action=getTableInfo&table=' + encodeURIComponent(window.State.currentTable),
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    window.State.tableInfo = response;
                    window.UIRenderer.displayTableInfo();
                    window.UIRenderer.displayStructureTable();
                    $('#tableStructure').show();
                    $('#emptyState').hide();
                    
                    // Disable add column button for views
                    if (window.State.tableInfo.isView) {
                        $('#addColumnBtn').hide();
                    } else {
                        $('#addColumnBtn').show();
                    }
                }
                $('#loading').removeClass('active');
            },
            error: function(xhr) {
                window.Utils.showError('Error loading table structure');
                $('#loading').removeClass('active');
            }
        });
    }
};

// Export for use in other modules
if (typeof window !== 'undefined') {
    window.TableOperations = TableOperations;
}


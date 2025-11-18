/**
 * UI Interactions Module
 * Handles UI interaction logic (expand/collapse, selection, etc.)
 */

const UIInteractions = {
    /**
     * Toggle database tables expand/collapse
     */
    toggleDatabaseTables: function(databaseName) {
        const expandIndicator = $(`.expand-indicator[aria-label*="${databaseName}"]`);
        const tablesSubsection = $(`.database-tables-subsection[data-database="${databaseName}"]`);
        
        if (tablesSubsection.hasClass('expanded')) {
            // Collapse this database
            tablesSubsection.slideUp(200, function() {
                $(this).removeClass('expanded');
            });
            expandIndicator.removeClass('expanded');
        } else {
            // Close any other expanded databases first (but not this one)
            UIInteractions.closeAllExpandedDatabases(databaseName);
            
            // Expand the selected database
            expandIndicator.addClass('expanded');
            tablesSubsection.addClass('expanded');
            
            // Select this database as the current database (without triggering change event)
            window.State.currentDatabase = databaseName;
            $('#databaseSelect').val(databaseName);
            
            // Update visual state manually
            $('.database-item').removeClass('active');
            $(`.database-item[data-database="${databaseName}"]`).addClass('active');
            
            // Update database badges manually
            $('.database-name').each(function() {
                const $this = $(this);
                const $badge = $this.find('.badge-current');
                const itemDbName = $this.closest('.database-item').data('database');
                if (itemDbName === databaseName && !$badge.length) {
                    $this.append('<span class="badge-current" title="Currently selected">Current</span>');
                } else if (itemDbName !== databaseName && $badge.length) {
                    $badge.remove();
                }
            });
            
            // Update session cache
            $.ajax({
                url: '../api/',
                method: 'POST',
                data: {
                    action: 'setCurrentDatabase',
                    database: databaseName
                },
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        window.UIRenderer.updateDatabaseBadge(databaseName);
                    }
                }
            });
            
            // Load tables if not already loaded
            const tablesGrid = tablesSubsection.find('.database-tables-grid');
            if (tablesGrid.children().length === 0) {
                window.TableOperations.loadForDatabase(databaseName, function(tables) {
                    window.UIRenderer.displayTablesInSubsection(databaseName, tables);
                });
            } else {
                tablesSubsection.slideDown(200);
            }
            
            // Update button states and stats
            window.UIRenderer.updateButtonStates();
            window.UIRenderer.updateStats();
        }
    },

    /**
     * Close all expanded databases (optionally exclude a specific database)
     */
    closeAllExpandedDatabases: function(excludeDatabase = null) {
        $('.database-tables-subsection.expanded').each(function() {
            const databaseName = $(this).data('database');
            
            // Skip the excluded database
            if (excludeDatabase && databaseName === excludeDatabase) {
                return;
            }
            
            const expandIndicator = $(`.expand-indicator[aria-label*="${databaseName}"]`);
            
            $(this).slideUp(200, function() {
                $(this).removeClass('expanded');
            });
            expandIndicator.removeClass('expanded');
        });
    },

    /**
     * Select database
     */
    selectDatabase: function(databaseName) {
        window.State.currentDatabase = databaseName;
        $('#databaseSelect').val(databaseName).trigger('change');
        
        // Update visual state of database items
        $('.database-item').removeClass('active');
        $(`.database-item[data-database="${databaseName}"]`).addClass('active');
        
        // Update database badges
        $('.database-name').each(function() {
            const $this = $(this);
            const $badge = $this.find('.badge-current');
            const itemDbName = $this.closest('.database-item').data('database');
            if (itemDbName === databaseName && !$badge.length) {
                $this.append('<span class="badge-current" title="Currently selected">Current</span>');
            } else if (itemDbName !== databaseName && $badge.length) {
                $badge.remove();
            }
        });
    },

    /**
     * Select table
     */
    selectTable: function(tableName) {
        window.State.selectedTable = tableName;

        // Update visual selection
        $('.table-item').removeClass('selected');
        $(`.table-item[data-table="${tableName}"]`).addClass('selected');

        window.UIRenderer.updateButtonStates();
    }
};

// Export for use in other modules
if (typeof window !== 'undefined') {
    window.UIInteractions = UIInteractions;
    // Also expose functions globally for backward compatibility
    window.toggleDatabaseTables = UIInteractions.toggleDatabaseTables;
    window.closeAllExpandedDatabases = UIInteractions.closeAllExpandedDatabases;
    window.selectDatabase = UIInteractions.selectDatabase;
    window.selectTable = UIInteractions.selectTable;
}


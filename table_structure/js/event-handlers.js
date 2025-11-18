/**
 * Event Handlers Module
 * Sets up all event bindings
 */

const EventHandlers = {
    /**
     * Initialize all event handlers
     */
    init: function() {
        // Get current table from session first
        $.ajax({
            url: '../api/?action=getCurrentTable',
            method: 'GET',
            dataType: 'json',
            success: (response) => {
                if (response.success && response.table) {
                    window.State.currentTable = response.table;
                    console.log('Restored current table from session:', window.State.currentTable);
                } else {
                    console.log('No table in session to restore');
                }
                // Then load tables (which will select the current one if set)
                window.TableOperations.loadTables();
            },
            error: (err) => {
                console.error('Error getting current table:', err);
                // If error, just load tables without pre-selection
                window.TableOperations.loadTables();
            }
        });

        // Table select change handler
        $('#tableSelect').change(function() {
            window.State.currentTable = $(this).val();
            
            // Update session cache
            $.ajax({
                url: '../api/',
                method: 'POST',
                data: {
                    action: 'setCurrentTable',
                    table: window.State.currentTable
                },
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        window.Utils.updateDatabaseBadge();
                    }
                }
            });
            
            if (window.State.currentTable) {
                window.TableOperations.loadTableStructure();
                $('#addColumnBtn').show();
            } else {
                window.Utils.showEmptyState();
                $('#addColumnBtn').hide();
            }
        });

        // Add column button
        $('#addColumnBtn').click(function() {
            window.ColumnForm.openAddModal();
        });

        // Save column button
        $('#saveColumnBtn').click(function() {
            window.ColumnOperations.save();
        });

        // Delete column button
        $('#deleteColumnBtn').click(function() {
            window.ColumnOperations.delete();
        });

        // Close modal on outside click
        $(document).click(function(e) {
            if ($(e.target).hasClass('modal')) {
                window.Utils.closeModal($(e.target).attr('id'));
            }
        });

        // Smooth page transitions
        $('.nav-link').click(function(e) {
            const href = $(this).attr('href');
            
            // Don't apply transition if it's the current page
            if ($(this).hasClass('active')) {
                e.preventDefault();
                return;
            }
            
            e.preventDefault();
            $('body').addClass('page-transitioning');
            
            // Navigate after fade out
            setTimeout(function() {
                window.location.href = href;
            }, 200);
        });
    }
};

// Export for use in other modules
if (typeof window !== 'undefined') {
    window.EventHandlers = EventHandlers;
}


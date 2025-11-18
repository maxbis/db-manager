/**
 * Event Handlers Module
 * Sets up all event bindings and initialization
 */

const EventHandlers = {
    /**
     * Initialize all event handlers
     */
    init: function() {
        // Get current database from session first
        $.ajax({
            url: '../api/?action=getCurrentDatabase',
            method: 'GET',
            dataType: 'json',
            success: (response) => {
                if (response.success && response.database !== undefined && response.database !== null && response.database !== '') {
                    window.State.currentDatabase = response.database;
                    console.log('Restored current database from session:', window.State.currentDatabase);
                } else {
                    console.log('No database in session to restore');
                }
                // Then load databases (which will select the current one if set)
                window.DatabaseOperations.load();
            },
            error: (err) => {
                console.error('Error getting current database:', err);
                // If error, just load databases without pre-selection
                window.DatabaseOperations.load();
            }
        });

        // Event handlers
        $('#refreshBtn').click(() => {
            window.DatabaseOperations.load();
        });

        $('#databaseSelect').change(function () {
            window.State.currentDatabase = $(this).val();
            
            // Close any expanded databases when selecting from dropdown
            window.UIInteractions.closeAllExpandedDatabases();
            
            // Update visual state of database items
            $('.database-item').removeClass('active');
            if (window.State.currentDatabase) {
                $(`.database-item[data-database="${window.State.currentDatabase}"]`).addClass('active');
            }
            
            if (window.State.currentDatabase) {
                // Update session cache so header shows correct database
                $.ajax({
                    url: '../api/',
                    method: 'POST',
                    data: {
                        action: 'setCurrentDatabase',
                        database: window.State.currentDatabase
                    },
                    dataType: 'json',
                    success: (response) => {
                        if (response.success) {
                            window.UIRenderer.updateDatabaseBadge(window.State.currentDatabase);
                        }
                    }
                });

                window.UIRenderer.updateButtonStates();
                window.UIRenderer.updateStats();
            } else {
                window.UIRenderer.updateButtonStates();
                window.UIRenderer.updateStats();
            }
        });

        $('#createDatabaseBtn').click(() => {
            window.ModalManager.open('createDatabaseModal');
        });

        // Create Table from menu item
        $('#createTableMenuItem').click(() => {
            if (!window.State.currentDatabase) return;
            window.ModalManager.open('createTableModal');
        });

        $('#exportDatabaseBtn').click(() => {
            if (window.State.currentDatabase) {
                window.ModalManager.openExportModal(window.State.currentDatabase);
            }
        });

        $('#importDatabaseBtn').click(() => {
            window.ModalManager.open('importDatabaseModal');
        });

        $('#exportAllDatabasesBtn').click(() => {
            window.ModalManager.open('exportAllDatabasesModal');
        });

        // Actions dropdown open/close (for stats section)
        (function(){
            const $dropdown = $('#statsActions');
            const $menu = $dropdown.find('.dropdown-menu');
            $dropdown.find('.dropdown-toggle').on('click', function(e){
                e.stopPropagation();
                // Close all database action dropdowns
                $('.db-actions-dropdown .dropdown-menu').removeClass('show');
                // Toggle stats dropdown
                $menu.toggleClass('show');
            });
        })();

        // Database actions dropdowns - delegated event handler
        $(document).on('click', '.db-actions-dropdown .dropdown-toggle', function(e){
            e.stopPropagation();
            const $this = $(this);
            const $menu = $this.siblings('.dropdown-menu');
            const isOpen = $menu.hasClass('show');
            
            // Close all dropdowns (including stats)
            $('#statsActions .dropdown-menu').removeClass('show');
            $('.db-actions-dropdown .dropdown-menu').removeClass('show');
            
            // Toggle this one
            if (!isOpen) {
                $menu.addClass('show');
            }
        });

        // Close all dropdowns when clicking anywhere on document
        $(document).on('click', function(){
            $('#statsActions .dropdown-menu').removeClass('show');
            $('.db-actions-dropdown .dropdown-menu').removeClass('show');
        });

        // Database dropdown menu item handlers
        $(document).on('click', '.db-create-table-btn', function(e){
            e.stopPropagation();
            const dbName = $(this).data('database');
            // Select the database first
            window.UIInteractions.selectDatabase(dbName);
            // Then open the create table modal
            setTimeout(() => {
                window.ModalManager.open('createTableModal');
            }, 100);
            // Close the dropdown
            $('.db-actions-dropdown .dropdown-menu').removeClass('show');
        });

        $(document).on('click', '.db-export-btn', function(e){
            e.stopPropagation();
            const dbName = $(this).data('database');
            window.ModalManager.openExportModal(dbName);
            // Close the dropdown
            $('.db-actions-dropdown .dropdown-menu').removeClass('show');
        });

        $(document).on('click', '.db-delete-btn', function(e){
            e.stopPropagation();
            const dbName = $(this).data('database');
            window.DatabaseOperations.delete(dbName);
            // Close the dropdown
            $('.db-actions-dropdown .dropdown-menu').removeClass('show');
        });

        // Table rename button (per table row)
        $(document).on('click', '.table-rename-btn', function(e){
            e.stopPropagation();
            const $btn = $(this);
            const tableName = $btn.data('table');
            const databaseName = $btn.data('database') || window.State.currentDatabase;
            window.ModalManager.openRenameTableModal(tableName, databaseName);
        });

        $('#confirmCreateDatabaseBtn').click(() => {
            window.DatabaseOperations.create();
        });

        $('#confirmCreateTableBtn').click(() => {
            // Build columns DDL from builder rows
            const columnsDDL = window.ColumnBuilder.buildColumnsDDL();
            if (columnsDDL) {
                // Set the textarea value with the built columns
                $('#newTableColumns').val(columnsDDL);
                window.TableOperations.create();
            }
        });

        $('#confirmRenameTableBtn').click(() => {
            window.TableOperations.rename();
        });

        $('#confirmImportBtn').click(() => {
            window.DatabaseOperations.import();
        });

        $('#confirmExportBtn').click(() => {
            window.DatabaseOperations.export();
        });

        $('#confirmExportAllBtn').click(() => {
            window.DatabaseOperations.exportAll();
        });

        // Search & sort handlers
        const debouncedFilter = window.Utils.debounce(() => {
            window.State.dbSearchQuery = ($('#dbSearchInput').val() || '').toLowerCase();
            window.UIRenderer.displayDatabases();
        }, 250);
        $('#dbSearchInput').on('input', debouncedFilter);
        $('#dbSortSelect').on('change', function(){
            window.State.dbSortMode = $(this).val();
            window.UIRenderer.displayDatabases();
        });

        // Initialize column builder with one default row
        window.ColumnBuilder.addRow();
        $('#addColumnRowBtn').on('click', () => { 
            window.ColumnBuilder.addRow(); 
        });

        // Drag & drop reordering for column rows
        window.ColumnBuilder.enableDragAndDrop();

        // Close modal on outside click (for custom form modals)
        $(document).click(function (e) {
            if ($(e.target).hasClass('modal')) {
                window.ModalManager.close($(e.target).attr('id'));
            }
        });

        // Smooth page transitions
        $('.nav-link').click(function (e) {
            const href = $(this).attr('href');

            // Don't apply transition if it's the current page
            if ($(this).hasClass('active')) {
                e.preventDefault();
                return;
            }

            e.preventDefault();
            $('body').addClass('page-transitioning');

            // Navigate after fade out
            setTimeout(() => {
                window.location.href = href;
            }, 200);
        });
    }
};

// Export for use in other modules
if (typeof window !== 'undefined') {
    window.EventHandlers = EventHandlers;
}


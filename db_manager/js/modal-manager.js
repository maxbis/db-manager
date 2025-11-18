/**
 * Modal Management Module
 * Handles all modal operations
 */

const ModalManager = {
    /**
     * Open a modal by ID
     */
    open: function(modalId) {
        $('#' + modalId).addClass('active');

        // Populate import target database dropdown
        if (modalId === 'importDatabaseModal') {
            const select = $('#importTargetDatabase');
            select.empty();
            select.append('<option value="">-- Select database --</option>');
            window.State.databases.forEach(function (db) {
                select.append(`<option value="${db.name}">${db.name}</option>`);
            });
        }
    },

    /**
     * Close a modal by ID
     */
    close: function(modalId) {
        $('#' + modalId).removeClass('active');

        // Clear form fields
        if (modalId === 'createDatabaseModal') {
            $('#newDatabaseName').val('');
        } else if (modalId === 'createTableModal') {
            $('#newTableName').val('');
            $('#newTableColumns').val('');
            // Clear and reset column builder
            $('#columnsBuilder .column-rows').empty();
            if (window.ColumnBuilder) {
                window.ColumnBuilder.addRow();
            }
        } else if (modalId === 'renameTableModal') {
            $('#renameTableDatabase').val('');
            $('#renameTableCurrentName').val('');
            $('#renameTableNewName').val('');
            $('#confirmRenameTableBtn').prop('disabled', false).text('💾 Rename');
        } else if (modalId === 'exportDatabaseModal') {
            $('#exportDatabaseName').val('');
            $('#exportFileName').val('');
            $('#exportCreateDatabase').prop('checked', true);
            $('#exportDataOnly').prop('checked', false);
        } else if (modalId === 'importDatabaseModal') {
            $('#importFile').val('');
            $('#importTargetDatabase').val('');
            $('#importDropExisting').prop('checked', false);
        }
    },

    /**
     * Show confirm dialog
     */
    showConfirmDialog: function(options, onConfirm) {
        const { title, message, confirmText = 'Confirm', confirmClass = '' } = options || {};
        $('#confirmActionTitle').text(title || 'Confirm Action');
        $('#confirmActionMessage').text(message || 'Are you sure?');
        const $confirmBtn = $('#confirmActionConfirmBtn');
        $confirmBtn.text(confirmText);
        // reset classes
        $confirmBtn.removeClass('btn-success btn-warning btn-danger');
        if (confirmClass) {
            $confirmBtn.addClass(confirmClass);
        }

        // Clean previous handlers
        $confirmBtn.off('click');
        $('#confirmActionCancelBtn').off('click');

        // Bind actions
        $('#confirmActionCancelBtn').on('click', function () {
            ModalManager.close('confirmActionModal');
        });
        $confirmBtn.on('click', function () {
            ModalManager.close('confirmActionModal');
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
        });

        // Open
        ModalManager.open('confirmActionModal');
    },

    /**
     * Open export modal
     */
    openExportModal: function(databaseName) {
        $('#exportDatabaseName').val(databaseName);
        $('#exportFileName').val(`${databaseName}_export_${new Date().toISOString().split('T')[0]}`);
        $('#exportCreateDatabase').prop('checked', true);
        $('#exportDataOnly').prop('checked', false);
        ModalManager.open('exportDatabaseModal');
    },

    /**
     * Open rename table modal
     */
    openRenameTableModal: function(tableName, databaseName = null) {
        const dbName = databaseName || window.State.currentDatabase;
        if (!dbName) {
            Utils.showToast('Please select a database first', 'warning');
            return;
        }

        $('#renameTableDatabase').val(dbName);
        $('#renameTableCurrentName').val(tableName);
        $('#renameTableNewName').val(tableName);

        // Reset button state in case of previous errors
        $('#confirmRenameTableBtn').prop('disabled', false).text('💾 Rename');

        ModalManager.open('renameTableModal');

        // Focus the input after modal opens
        setTimeout(function() {
            const input = document.getElementById('renameTableNewName');
            if (input) {
                input.focus();
                input.select();
            }
        }, 100);
    }
};

// Export for use in other modules
if (typeof window !== 'undefined') {
    window.ModalManager = ModalManager;
    // Also expose functions globally for inline handlers
    window.openModal = ModalManager.open;
    window.closeModal = ModalManager.close;
    window.openExportModal = ModalManager.openExportModal;
    window.openRenameTableModal = ModalManager.openRenameTableModal;
}


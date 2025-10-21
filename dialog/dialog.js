/**
 * Generic Dialog Component
 * Reusable across all applications in db-manager
 * 
 * Usage Examples:
 * 
 * 1. Simple Confirm Dialog:
 *    Dialog.confirm({
 *        title: 'Delete Record',
 *        message: 'Are you sure you want to delete this record?',
 *        confirmText: 'Delete',
 *        confirmClass: 'btn-danger',
 *        onConfirm: function() {
 *            // Handle confirmation
 *        }
 *    });
 * 
 * 2. Alert Dialog:
 *    Dialog.alert({
 *        title: 'Success',
 *        message: 'Operation completed successfully!',
 *        confirmText: 'OK'
 *    });
 * 
 * 3. Custom Dialog:
 *    Dialog.show({
 *        title: 'Custom Dialog',
 *        body: '<div>Custom HTML content</div>',
 *        buttons: [
 *            { text: 'Cancel', class: 'btn-secondary', action: Dialog.close },
 *            { text: 'Save', class: 'btn-success', action: function() { ... } }
 *        ]
 *    });
 */

const Dialog = (function() {
    let currentDialog = null;
    let currentConfig = null;

    /**
     * Initialize the dialog component
     */
    function init() {
        // Close dialog when clicking outside
        document.addEventListener('click', function(e) {
            const dialogOverlay = document.getElementById('genericDialog');
            if (e.target === dialogOverlay && currentConfig?.closeOnOutsideClick !== false) {
                close();
            }
        });

        // Close dialog on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && currentConfig?.closeOnEscape !== false) {
                close();
            }
        });
    }

    /**
     * Show a confirmation dialog
     * @param {Object} options - Dialog configuration
     */
    function confirm(options) {
        const config = {
            title: options.title || 'Confirm Action',
            message: options.message || 'Are you sure?',
            confirmText: options.confirmText || 'Confirm',
            cancelText: options.cancelText || 'Cancel',
            confirmClass: options.confirmClass || 'btn-primary',
            cancelClass: options.cancelClass || 'btn-secondary',
            icon: options.icon || '⚠️',
            onConfirm: options.onConfirm || null,
            onCancel: options.onCancel || null,
            closeOnOutsideClick: options.closeOnOutsideClick !== false,
            closeOnEscape: options.closeOnEscape !== false
        };

        currentConfig = config;

        // Update dialog content
        document.getElementById('dialogTitle').textContent = config.title;
        
        const bodyHtml = config.icon 
            ? `<div class="dialog-message-container"><span class="dialog-icon">${config.icon}</span><div class="dialog-message-text">${config.message}</div></div>`
            : `<p id="dialogMessage">${config.message}</p>`;
        document.getElementById('dialogBody').innerHTML = bodyHtml;

        // Update buttons
        const footer = document.getElementById('dialogFooter');
        footer.innerHTML = `
            <button class="${config.cancelClass} dialog-btn-cancel" id="dialogCancelBtn">${config.cancelText}</button>
            <button class="${config.confirmClass} dialog-btn-confirm" id="dialogConfirmBtn">${config.confirmText}</button>
        `;

        // Bind button actions
        document.getElementById('dialogCancelBtn').onclick = function() {
            close();
            if (config.onCancel) config.onCancel();
        };

        document.getElementById('dialogConfirmBtn').onclick = function() {
            close();
            if (config.onConfirm) config.onConfirm();
        };

        // Show dialog
        show();
    }

    /**
     * Show an alert dialog (only OK button)
     * @param {Object} options - Dialog configuration
     */
    function alert(options) {
        const config = {
            title: options.title || 'Alert',
            message: options.message || '',
            confirmText: options.confirmText || 'OK',
            confirmClass: options.confirmClass || 'btn-primary',
            icon: options.icon || 'ℹ️',
            onConfirm: options.onConfirm || null,
            closeOnOutsideClick: options.closeOnOutsideClick !== false,
            closeOnEscape: options.closeOnEscape !== false
        };

        currentConfig = config;

        // Update dialog content
        document.getElementById('dialogTitle').textContent = config.title;
        
        const bodyHtml = config.icon 
            ? `<div class="dialog-message-container"><span class="dialog-icon">${config.icon}</span><div class="dialog-message-text">${config.message}</div></div>`
            : `<p id="dialogMessage">${config.message}</p>`;
        document.getElementById('dialogBody').innerHTML = bodyHtml;

        // Update buttons (only confirm button)
        const footer = document.getElementById('dialogFooter');
        footer.innerHTML = `
            <button class="${config.confirmClass} dialog-btn-confirm" id="dialogConfirmBtn">${config.confirmText}</button>
        `;

        // Bind button action
        document.getElementById('dialogConfirmBtn').onclick = function() {
            close();
            if (config.onConfirm) config.onConfirm();
        };

        // Show dialog
        show();
    }

    /**
     * Show a custom dialog
     * @param {Object} options - Dialog configuration
     */
    function custom(options) {
        const config = {
            title: options.title || 'Dialog',
            body: options.body || '',
            buttons: options.buttons || [],
            closeOnOutsideClick: options.closeOnOutsideClick !== false,
            closeOnEscape: options.closeOnEscape !== false,
            width: options.width || null
        };

        currentConfig = config;

        // Update dialog content
        document.getElementById('dialogTitle').textContent = config.title;
        document.getElementById('dialogBody').innerHTML = config.body;

        // Update buttons
        const footer = document.getElementById('dialogFooter');
        if (config.buttons.length > 0) {
            footer.innerHTML = '';
            config.buttons.forEach(function(btn) {
                const button = document.createElement('button');
                button.className = btn.class || 'btn-primary';
                button.textContent = btn.text || 'Button';
                button.onclick = function() {
                    if (btn.closeOnClick !== false) {
                        close();
                    }
                    if (btn.action) btn.action();
                };
                footer.appendChild(button);
            });
        }

        // Set custom width if specified
        const container = document.querySelector('#genericDialog .dialog-container');
        if (config.width) {
            container.style.maxWidth = config.width;
        } else {
            container.style.maxWidth = '';
        }

        // Show dialog
        show();
    }

    /**
     * Show the dialog
     */
    function show() {
        const dialog = document.getElementById('genericDialog');
        dialog.classList.add('active');
        // Focus the dialog for accessibility
        dialog.focus();
    }

    /**
     * Close the dialog
     */
    function close() {
        const dialog = document.getElementById('genericDialog');
        dialog.classList.remove('active');
        currentConfig = null;
        
        // Reset custom width
        const container = document.querySelector('#genericDialog .dialog-container');
        container.style.maxWidth = '';
    }

    /**
     * Check if dialog is open
     */
    function isOpen() {
        return document.getElementById('genericDialog').classList.contains('active');
    }

    // Initialize on load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Public API
    return {
        confirm: confirm,
        alert: alert,
        custom: custom,
        show: show,
        close: close,
        isOpen: isOpen
    };
})();
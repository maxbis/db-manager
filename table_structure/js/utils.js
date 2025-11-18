/**
 * Utility Functions Module
 * Common helper functions used throughout the application
 */

const Utils = {
    /**
     * Show empty state
     */
    showEmptyState: function() {
        $('#tableStructure').hide();
        $('#emptyState').show();
        $('#loading').removeClass('active');
    },

    /**
     * Show error message
     */
    showError: function(message) {
        alert('Error: ' + message);
    },

    /**
     * Show success message
     */
    showSuccess: function(message) {
        alert('Success: ' + message);
    },

    /**
     * Close modal
     */
    closeModal: function(modalId = 'columnModal') {
        $('#' + modalId).removeClass('active');
        if (modalId === 'columnModal') {
            window.State.currentEditColumn = null;
        } else if (modalId === 'viewSourceModal') {
            // Remove the dynamically created modal from DOM
            setTimeout(function() {
                $('#' + modalId).remove();
            }, 300); // Wait for animation to complete
        }
    },

    /**
     * Update database badge in header
     */
    updateDatabaseBadge: function() {
        const databaseBadge = document.querySelector('.control-group span span');
        if (databaseBadge) {
            const databaseName = databaseBadge.textContent.replace('🗄️ ', '');
            const tableName = $('#tableSelect').val();
            
            let displayText = '🗄️ ' + databaseName;
            if (tableName) {
                // Extract just the database name (remove any existing table part)
                const dbName = databaseName.split(' - ')[0];
                displayText = '🗄️ ' + dbName + ' -  ' + tableName;
            }
            databaseBadge.textContent = displayText;
        }
    },

    /**
     * Show view source
     */
    showViewSource: function() {
        if (!window.State.currentTable) {
            this.showError('No table selected');
            return;
        }

        $.ajax({
            url: '../api/',
            method: 'GET',
            data: { 
                action: 'getViewSource',
                table: window.State.currentTable
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    // Create modal for view source
                    const modalHtml = `
                        <div class="modal active" id="viewSourceModal">
                            <div class="modal-content" style="max-width: 90%; max-height: 90%;">
                                <div class="modal-header">
                                    <h2>🔍 View Source: ${response.viewName}</h2>
                                    <button class="modal-close" onclick="window.Utils.closeModal('viewSourceModal')">&times;</button>
                                </div>
                                <div class="modal-body">
                                    <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; padding: 15px; margin-bottom: 15px;">
                                        <h4 style="margin-bottom: 10px; color: var(--color-primary);">📝 CREATE VIEW Statement:</h4>
                                        <pre style="background: #ffffff; border: 1px solid #e9ecef; border-radius: 4px; padding: 15px; margin: 0; overflow-x: auto; white-space: pre-wrap; font-family: 'Courier New', monospace; font-size: 13px; line-height: 1.4; color: var(--color-text-primary);">${response.createStatement}</pre>
                                    </div>
                                    <div style="background: var(--color-warning-pale); border: 1px solid var(--color-warning-light); border-radius: 6px; padding: 12px;">
                                        <p style="margin: 0; color: var(--color-warning); font-weight: 600;">💡 <strong>Tip:</strong> You can copy this SQL and run it in the SQL Query Builder to recreate this view.</p>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button class="btn-secondary" onclick="window.Utils.closeModal('viewSourceModal')">Close</button>
                                    <button class="btn-primary" onclick="window.Utils.copyViewSource('${response.createStatement.replace(/'/g, "\\'")}')">📋 Copy SQL</button>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    $('body').append(modalHtml);
                    
                    // Add click-outside-to-close functionality
                    $('#viewSourceModal').click(function(e) {
                        if (e.target === this) {
                            window.Utils.closeModal('viewSourceModal');
                        }
                    });
                }
            },
            error: (xhr) => {
                let errorMsg = 'Error loading view source';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.error) {
                        errorMsg += ': ' + response.error;
                    }
                } catch (e) {
                    errorMsg += ': ' + xhr.responseText;
                }
                this.showError(errorMsg);
            }
        });
    },

    /**
     * Copy view source to clipboard
     */
    copyViewSource: function(sql) {
        navigator.clipboard.writeText(sql).then(() => {
            this.showSuccess('SQL copied to clipboard!');
        }).catch(() => {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = sql;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            this.showSuccess('SQL copied to clipboard!');
        });
    },

    /**
     * Measure maximum used length for a VARCHAR column
     */
    measureColumnMaxLength: function(columnName, $typeElement) {
        if (!window.State.currentTable) {
            this.showError('No table selected');
            return;
        }
        
        if (!$typeElement) {
            $typeElement = $(`.field-type-clickable[data-column="${columnName}"]`);
        }
        
        // Get defined length from data attribute
        const definedLength = $typeElement.data('defined-length') || '';
        const originalText = $typeElement.text();
        
        // Show loading state
        $typeElement.text('Measuring...').css('opacity', '0.6');
        
        $.ajax({
            url: '../api/',
            method: 'GET',
            data: {
                action: 'getColumnMaxLength',
                table: window.State.currentTable,
                column: columnName
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    const maxLength = response.maxLength || 0;
                    
                    // Display as VARCHAR(defined/max)
                    const newDisplay = `VARCHAR(${definedLength}/${maxLength})`;
                    $typeElement.text(newDisplay).css('opacity', '1');
                } else {
                    this.showError('Error: ' + (response.error || 'Failed to measure column length'));
                    $typeElement.text(originalText).css('opacity', '1');
                }
            },
            error: (xhr) => {
                let errorMsg = 'Error measuring column length';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.error) {
                        errorMsg += ': ' + response.error;
                    }
                } catch (e) {
                    errorMsg += ': ' + xhr.responseText;
                }
                this.showError(errorMsg);
                $typeElement.text(originalText).css('opacity', '1');
            }
        });
    }
};

// Export for use in other modules
if (typeof window !== 'undefined') {
    window.Utils = Utils;
}


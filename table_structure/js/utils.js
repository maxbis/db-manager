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
        this.showToast('Error: ' + message, 'error');
    },

    /**
     * Show success message
     */
    showSuccess: function(message) {
        this.showToast(message, 'success');
    },

    /**
     * Store original title attribute for later restoration
     */
    storeOriginalTitle: function($element) {
        if (!$element || $element.length === 0) return;
        if (typeof $element.data('original-title') === 'undefined') {
            $element.data('original-title', $element.attr('title') || '');
        }
    },

    /**
     * Restore original title attribute if previously stored
     */
    restoreOriginalTitle: function($element) {
        if (!$element || $element.length === 0) return;
        const originalTitle = $element.data('original-title');
        if (typeof originalTitle !== 'undefined') {
            if (originalTitle) {
                $element.attr('title', originalTitle);
            } else {
                $element.removeAttr('title');
            }
        }
    },

    /**
     * Show toast notification
     */
    showToast: function(message, type = 'success') {
        const toast = $('#toast');
        const toastMessage = $('#toastMessage');
        const toastCloseBtn = $('#toastCloseBtn');
        
        if (toast.length === 0) {
            // Fallback to alert if toast element doesn't exist
            alert((type === 'error' ? 'Error: ' : 'Success: ') + message);
            return;
        }
        
        // Set message content
        toastMessage.text(message);
        
        // Remove previous classes
        toast.removeClass('success error warning');
        toast.addClass(type);
        toast.addClass('active');
        
        // Set up close button functionality
        toastCloseBtn.off('click').on('click', () => {
            this.closeToast();
        });
        
        // Auto-hide after 4 seconds
        const duration = 4000;
        
        // Clear any existing timeout
        if (window.toastTimeout) {
            clearTimeout(window.toastTimeout);
        }
        
        // Set new timeout
        window.toastTimeout = setTimeout(() => {
            this.closeToast();
        }, duration);
    },

    /**
     * Close toast notification
     */
    closeToast: function() {
        const toast = $('#toast');
        toast.removeClass('active');
        
        if (window.toastTimeout) {
            clearTimeout(window.toastTimeout);
            window.toastTimeout = null;
        }
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
     * Calculate suggested VARCHAR length: maxLength * 1.2, rounded up to next multiple of 10
     */
    calculateSuggestedLength: function(maxLength) {
        if (!maxLength || maxLength === 0) return 0;
        const withBuffer = maxLength * 1.2;
        return Math.ceil(withBuffer / 10) * 10;
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
        
        // Ensure we can restore title later
        this.storeOriginalTitle($typeElement);
        
        // Get defined length from data attribute
        const definedLength = parseInt($typeElement.data('defined-length') || '0', 10);
        // Get original type from data attribute (set when element is created)
        const originalType = $typeElement.attr('data-original-type') || $typeElement.data('original-type') || $typeElement.text();
        
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
                    
                    // Calculate suggested length
                    const suggestedLength = this.calculateSuggestedLength(maxLength);
                    
                    // Store max length and suggested length in data attributes
                    $typeElement.data('max-length', maxLength);
                    $typeElement.data('suggested-length', suggestedLength);
                    // Store original display for restoration (ensure we have it from attribute)
                    if (!$typeElement.attr('data-original-type')) {
                        $typeElement.attr('data-original-type', originalType);
                    }
                    
                    // Display as VARCHAR(defined/max)
                    const newDisplay = `VARCHAR(${definedLength}/${maxLength})`;
                    $typeElement.text(newDisplay).css('opacity', '1');
                    // Remove title to prevent overlap with tooltip
                    $typeElement.removeAttr('title');
                    
                    // Only show tooltip if suggested length is different from defined length
                    if (suggestedLength > 0 && suggestedLength !== definedLength) {
                        this.showLengthAdjustmentTooltip($typeElement, columnName, suggestedLength, definedLength);
                    }
                } else {
                    this.showError(response.error || 'Failed to measure column length');
                    $typeElement.text(originalType).css('opacity', '1');
                    this.restoreOriginalTitle($typeElement);
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
                $typeElement.text(originalType).css('opacity', '1');
                this.restoreOriginalTitle($typeElement);
            }
        });
    },

    /**
     * Show tooltip with suggested length and apply button
     */
    showLengthAdjustmentTooltip: function($typeElement, columnName, suggestedLength, definedLength) {
        // Remove any existing tooltip
        $typeElement.find('.varchar-adjust-tooltip').remove();
        
        // Create tooltip
        const tooltip = $(`
            <div class="varchar-adjust-tooltip">
                <div class="tooltip-content">
                    <div class="tooltip-text">Suggested: VARCHAR(${suggestedLength})</div>
                    <button class="tooltip-apply-btn" data-column="${columnName}" data-suggested-length="${suggestedLength}">
                        Apply
                    </button>
                </div>
            </div>
        `);
        
        // Append to type element
        $typeElement.append(tooltip);
        
        // Position tooltip
        this.positionTooltip($typeElement, tooltip);
        
        // Handle apply button click
        tooltip.find('.tooltip-apply-btn').click((e) => {
            e.stopPropagation();
            this.applyVarcharLengthChange(columnName, suggestedLength, $typeElement);
        });
        
        // Show tooltip immediately
        tooltip.addClass('visible');
        
        // Keep tooltip visible on hover (use mouseenter/mouseleave on the type element)
        // Also handle hover on the tooltip itself to keep it visible
        const showTooltip = () => tooltip.addClass('visible');
        const hideTooltip = () => {
            // Small delay to allow moving mouse to tooltip
            setTimeout(() => {
                if (!tooltip.is(':hover') && !$typeElement.is(':hover')) {
                    tooltip.removeClass('visible');
                }
            }, 100);
        };
        
        $typeElement.on('mouseenter', showTooltip);
        $typeElement.on('mouseleave', hideTooltip);
        tooltip.on('mouseenter', showTooltip);
        tooltip.on('mouseleave', hideTooltip);
    },

    /**
     * Position tooltip relative to the type element
     */
    positionTooltip: function($typeElement, $tooltip) {
        // Position below the element, centered
        // The tooltip is already positioned absolutely within the relatively positioned type element
        $tooltip.css({
            top: '100%',
            marginTop: '5px',
            left: '50%',
            transform: 'translateX(-50%)',
            zIndex: 1000
        });
    },

    /**
     * Apply VARCHAR length change
     */
    applyVarcharLengthChange: function(columnName, newLength, $typeElement) {
        // Find the column info from state
        const column = window.State.tableInfo.columns.find(col => col.name === columnName);
        if (!column) {
            this.showError('Column not found');
            return;
        }
        
        // Create form data with only the type change
        const formData = {
            name: column.name,
            type: `VARCHAR(${newLength})`,
            default: column.default !== null ? column.default : null,
            null: column.null,
            primary: column.key === 'PRI',
            unique: column.key === 'UNI',
            index: column.key === 'MUL',
            auto_increment: column.extra && column.extra.toLowerCase().includes('auto_increment'),
            extra: column.extra || ''
        };
        
        // Add foreign key if exists
        if (column.foreignKey) {
            formData.foreignKey = {
                referenced_table: column.foreignKey.referenced_table,
                referenced_column: column.foreignKey.referenced_column,
                update_rule: column.foreignKey.update_rule,
                delete_rule: column.foreignKey.delete_rule
            };
        }
        
        // Generate SQL
        const sqlQuery = window.ColumnOperations.generateSQL(formData, column);
        
        if (!sqlQuery) {
            this.showError('No changes to apply');
            return;
        }
        
        // Remove tooltip
        $typeElement.find('.varchar-adjust-tooltip').remove();
        
        // Show loading state
        $typeElement.text('Applying...').css('opacity', '0.6');
        
        // Execute SQL via AJAX
        $.ajax({
            url: '../api/',
            method: 'POST',
            data: {
                action: 'executeQuery',
                query: sqlQuery
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    // Show success message
                    const message = response.message || `Column '${columnName}' updated to VARCHAR(${newLength})`;
                    this.showToast(message, 'success');
                    
                    // Restore original display format (e.g., VARCHAR(100))
                    const originalDisplay = $typeElement.attr('data-original-type') || $typeElement.data('original-type') || `VARCHAR(${$typeElement.data('defined-length')})`;
                    $typeElement.text(originalDisplay).css('opacity', '1');
                    this.restoreOriginalTitle($typeElement);
                    
                    // Clear stored data
                    $typeElement.removeData('max-length');
                    $typeElement.removeData('suggested-length');
                    $typeElement.removeData('original-display');
                    
                    // Reload table structure to reflect the change
                    window.TableOperations.loadTableStructure();
                } else {
                    this.showError(response.error || 'Failed to update column');
                    // Restore original display on error
                    const originalDisplay = $typeElement.attr('data-original-type') || $typeElement.data('original-type') || `VARCHAR(${$typeElement.data('defined-length')})`;
                    $typeElement.text(originalDisplay).css('opacity', '1');
                    this.restoreOriginalTitle($typeElement);
                }
            },
            error: (xhr) => {
                let errorMsg = 'Error updating column';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.error) {
                        errorMsg += ': ' + response.error;
                    }
                } catch (e) {
                    errorMsg += ': ' + xhr.responseText;
                }
                this.showToast(errorMsg, 'error');
                
                // Restore original display on error
                const originalDisplay = $typeElement.attr('data-original-type') || $typeElement.data('original-type') || `VARCHAR(${$typeElement.data('defined-length')})`;
                $typeElement.text(originalDisplay).css('opacity', '1');
                this.restoreOriginalTitle($typeElement);
            }
        });
    }
};

// Export for use in other modules
if (typeof window !== 'undefined') {
    window.Utils = Utils;
}


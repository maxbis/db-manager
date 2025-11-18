/**
 * Column Form Module
 * Handles column form building and foreign key management
 */

const ColumnForm = {
    /**
     * Open add column modal
     */
    openAddModal: function() {
        window.State.currentEditColumn = null;
        $('#modalTitle').text('➕ Add New Column');
        $('#deleteColumnBtn').hide();
        this.buildForm(null);
        $('#columnModal').addClass('active');
    },

    /**
     * Open edit column modal
     */
    openEditModal: function(columnName) {
        const column = window.State.tableInfo.columns.find(col => col.name === columnName);
        if (!column) return;
        
        window.State.currentEditColumn = column;
        $('#modalTitle').text('✏️ Edit Column: ' + columnName);
        $('#deleteColumnBtn').show();
        this.buildForm(column);
        $('#columnModal').addClass('active');
    },

    /**
     * Build column form
     */
    buildForm: function(column) {
        const modalBody = $('#modalBody');
        modalBody.empty();
        
        const isNew = !column;
        
        modalBody.html(`
            <div class="form-group">
                <label for="fieldName">Column Name:</label>
                <input type="text" id="fieldName" name="name" value="${column ? column.name : ''}">
                ${column ? `
                <p id="renameWarning" style="display: none; margin-top: 8px; color: var(--color-warning, #b45309); font-size: 13px; line-height: 1.4;">
                    ⚠️ Renaming a column can break queries, foreign keys, or application code that references the old name.
                </p>
                ` : ''}
            </div>
            
            ${isNew ? `
            <div class="form-group">
                <label for="fieldPosition">Position:</label>
                <select id="fieldPosition" name="position">
                    <option value="end">At the end (default)</option>
                    <option value="first">At the beginning</option>
                    ${window.State.tableInfo ? window.State.tableInfo.columns.map((col, index) => 
                        `<option value="after_${col.name}">After ${col.name}</option>`
                    ).join('') : ''}
                </select>
            </div>
            ` : ''}
            
            <div class="form-row">
                <div class="form-group">
                    <label for="fieldType">Data Type:</label>
                    <select id="fieldType" name="type">
                        ${column ? `<option value="--" selected>-- Don't change --</option>` : ''}
                        <option value="VARCHAR(255)" ${!column && 'selected'}>VARCHAR(255)</option>
                        <option value="INT" ${column && column.type === 'INT' ? 'selected' : ''}>INT</option>
                        <option value="TINYINT" ${column && column.type === 'TINYINT' ? 'selected' : ''}>TINYINT</option>
                        <option value="BIGINT" ${column && column.type === 'BIGINT' ? 'selected' : ''}>BIGINT</option>
                        <option value="TEXT" ${column && column.type === 'TEXT' ? 'selected' : ''}>TEXT</option>
                        <option value="DATETIME" ${column && column.type === 'DATETIME' ? 'selected' : ''}>DATETIME</option>
                        <option value="DATE" ${column && column.type === 'DATE' ? 'selected' : ''}>DATE</option>
                        <option value="TIME" ${column && column.type === 'TIME' ? 'selected' : ''}>TIME</option>
                        <option value="DECIMAL(10,2)" ${column && column.type.startsWith('DECIMAL') ? 'selected' : ''}>DECIMAL(10,2)</option>
                        <option value="FLOAT" ${column && column.type === 'FLOAT' ? 'selected' : ''}>FLOAT</option>
                        <option value="BOOLEAN" ${column && column.type === 'BOOLEAN' ? 'selected' : ''}>BOOLEAN</option>
                        <option value="ENUM" ${column && column.type.startsWith('ENUM') ? 'selected' : ''}>ENUM</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="fieldDefault">Default Value:</label>
                    <input type="text" id="fieldDefault" name="default" value="${column ? (column.default || '') : ''}" placeholder="Leave empty for NULL">
                </div>
            </div>
            
            <div class="form-group">
                <label>Column Attributes:</label>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="attrNull" name="null" ${column && column.null ? 'checked' : ''}>
                        <label for="attrNull">Allow NULL</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="attrPrimary" name="primary" ${column && column.key === 'PRI' ? 'checked' : ''}>
                        <label for="attrPrimary">Primary Key</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="attrUnique" name="unique" ${column && column.key === 'UNI' ? 'checked' : ''}>
                        <label for="attrUnique">Unique</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="attrIndex" name="index" ${column && column.key === 'MUL' ? 'checked' : ''}>
                        <label for="attrIndex">Index</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="attrAutoIncrement" name="auto_increment" ${column && column.extra.toLowerCase().includes('auto_increment') ? 'checked' : ''}>
                        <label for="attrAutoIncrement" >Auto Increment</label>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="fieldExtra">
                    Extra Attributes:
                    <span class="info-button" title="Click for help">i
                        <div class="info-tooltip">
                            <strong>Common Extra Attributes:</strong><br>
                            • <code>COMMENT 'description'</code> - Column comment<br>
                            • <code>ON UPDATE CURRENT_TIMESTAMP</code> - Auto-update timestamps<br>
                            • <code>CHARACTER SET utf8mb4</code> - Character set<br>
                            • <code>COLLATE utf8mb4_unicode_ci</code> - Collation<br>
                            • <code>GENERATED ALWAYS AS (expression)</code> - Generated columns<br>
                            • <code>ZEROFILL</code> - Zero-padded numbers<br>
                            • <code>UNSIGNED</code> - Unsigned numbers<br>
                            • <code>STORED</code> or <code>VIRTUAL</code> - For generated columns
                        </div>
                    </span>
                </label>
                <textarea id="fieldExtra" name="extra" placeholder="Additional MySQL attributes like 'COMMENT \'description\'' or 'ON UPDATE CURRENT_TIMESTAMP'">${column ? (column.extra || '') : ''}</textarea>
            </div>
            
            <div class="form-group" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" id="hasForeignKey" name="has_foreign_key" title="Add foreign key constraint" style="margin: 0; width: auto;" ${column && column.foreignKey ? 'checked' : ''}>
                    <label for="hasForeignKey" style="font-weight: 600; margin: 0; cursor: pointer;">Foreign Key</label>
                </div>
                <div id="foreignKeySection" style="display: ${column && column.foreignKey ? 'block' : 'none'}; margin-top: 15px;">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fkReferencedTable">Referenced Table:</label>
                            <select id="fkReferencedTable" name="fk_referenced_table">
                                <option value="">-- Select table --</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="fkReferencedColumn">Referenced Column:</label>
                            <select id="fkReferencedColumn" name="fk_referenced_column">
                                <option value="">-- Select column --</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fkUpdateRule">ON UPDATE:</label>
                            <select id="fkUpdateRule" name="fk_update_rule">
                                <option value="RESTRICT" ${column && column.foreignKey && column.foreignKey.update_rule === 'RESTRICT' ? 'selected' : ''}>RESTRICT</option>
                                <option value="CASCADE" ${column && column.foreignKey && column.foreignKey.update_rule === 'CASCADE' ? 'selected' : ''}>CASCADE</option>
                                <option value="SET NULL" ${column && column.foreignKey && column.foreignKey.update_rule === 'SET NULL' ? 'selected' : ''}>SET NULL</option>
                                <option value="NO ACTION" ${column && column.foreignKey && column.foreignKey.update_rule === 'NO ACTION' ? 'selected' : ''}>NO ACTION</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="fkDeleteRule">ON DELETE:</label>
                            <select id="fkDeleteRule" name="fk_delete_rule">
                                <option value="RESTRICT" ${column && column.foreignKey && column.foreignKey.delete_rule === 'RESTRICT' ? 'selected' : ''}>RESTRICT</option>
                                <option value="CASCADE" ${column && column.foreignKey && column.foreignKey.delete_rule === 'CASCADE' ? 'selected' : ''}>CASCADE</option>
                                <option value="SET NULL" ${column && column.foreignKey && column.foreignKey.delete_rule === 'SET NULL' ? 'selected' : ''}>SET NULL</option>
                                <option value="NO ACTION" ${column && column.foreignKey && column.foreignKey.delete_rule === 'NO ACTION' ? 'selected' : ''}>NO ACTION</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        `);

        if (!isNew && column) {
            const originalName = column.name;
            const $fieldName = $('#fieldName');
            const $warning = $('#renameWarning');

            $fieldName.on('input', function() {
                if ($(this).val() !== originalName) {
                    $warning.show();
                } else {
                    $warning.hide();
                }
            });

            $fieldName.trigger('input');
        }
        
        // Load tables for foreign key selection
        this.loadTablesForForeignKey();
        
        // Handle foreign key checkbox
        $('#hasForeignKey').change(function() {
            if ($(this).is(':checked')) {
                $('#foreignKeySection').show();
            } else {
                $('#foreignKeySection').hide();
            }
        });
        
        // Handle referenced table change
        $('#fkReferencedTable').change(function() {
            const tableName = $(this).val();
            if (tableName) {
                ColumnForm.loadColumnsForForeignKey(tableName);
            } else {
                $('#fkReferencedColumn').empty().append('<option value="">-- Select column --</option>');
            }
        });
        
        // Set initial foreign key values if editing existing column with FK
        if (column && column.foreignKey) {
            $('#hasForeignKey').prop('checked', true);
            $('#foreignKeySection').show();
            // Populate referenced table
            setTimeout(function() {
                $('#fkReferencedTable').val(column.foreignKey.referenced_table);
                if (column.foreignKey.referenced_table) {
                    ColumnForm.loadColumnsForForeignKey(column.foreignKey.referenced_table, column.foreignKey.referenced_column);
                }
            }, 100);
        }
    },

    /**
     * Load tables for foreign key selection
     */
    loadTablesForForeignKey: function() {
        $.ajax({
            url: '../api/?action=getTablesForForeignKey',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const select = $('#fkReferencedTable');
                    // Don't clear existing selection if editing
                    const currentValue = select.val();
                    response.tables.forEach(function(table) {
                        // Don't include current table in the list
                        if (table !== window.State.currentTable) {
                            select.append(`<option value="${table}">${table}</option>`);
                        }
                    });
                    // Restore selection if it was set
                    if (currentValue) {
                        select.val(currentValue);
                    }
                }
            },
            error: function(xhr) {
                console.error('Error loading tables for foreign key:', xhr);
            }
        });
    },

    /**
     * Load columns from a table for foreign key reference
     */
    loadColumnsForForeignKey: function(tableName, selectedColumn = null) {
        $.ajax({
            url: '../api/?action=getTableColumns&table=' + encodeURIComponent(tableName),
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const select = $('#fkReferencedColumn');
                    select.empty();
                    select.append('<option value="">-- Select column --</option>');
                    
                    response.columns.forEach(function(col) {
                        // Prefer primary keys and unique columns
                        const isKey = col.key === 'PRI' || col.key === 'UNI';
                        const option = `<option value="${col.name}" ${isKey ? 'style="font-weight: bold;"' : ''} ${selectedColumn && col.name === selectedColumn ? 'selected' : ''}>${col.name} (${col.type})${isKey ? ' ⭐' : ''}</option>`;
                        select.append(option);
                    });
                }
            },
            error: function(xhr) {
                console.error('Error loading columns for foreign key:', xhr);
            }
        });
    }
};

// Export for use in other modules
if (typeof window !== 'undefined') {
    window.ColumnForm = ColumnForm;
}


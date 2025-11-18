/**
 * Table Structure Module - JavaScript
 * Handles table structure viewing and editing functionality
 */

// Global state
let currentTable = '';
let tableInfo = null;
let currentEditColumn = null;

// Initialize
$(document).ready(function() {
    // Get current table from session first
    $.ajax({
        url: '../api/?action=getCurrentTable',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.table) {
                currentTable = response.table;
                console.log('Restored current table from session:', currentTable);
            } else {
                console.log('No table in session to restore');
            }
            // Then load tables (which will select the current one if set)
            loadTables();
        },
        error: function(err) {
            console.error('Error getting current table:', err);
            // If error, just load tables without pre-selection
            loadTables();
        }
    });

    // Update database badge in header
    function updateDatabaseBadge() {
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
    }
    
    $('#tableSelect').change(function() {
        currentTable = $(this).val();
        
        // Update session cache
        $.ajax({
            url: '../api/',
            method: 'POST',
            data: {
                action: 'setCurrentTable',
                table: currentTable
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    updateDatabaseBadge();
                }
            }
        });
        
        if (currentTable) {
            loadTableStructure();
            $('#addColumnBtn').show();
        } else {
            showEmptyState();
            $('#addColumnBtn').hide();
        }
    });

    $('#addColumnBtn').click(function() {
        openAddColumnModal();
    });

    $('#saveColumnBtn').click(function() {
        saveColumn();
    });

    $('#deleteColumnBtn').click(function() {
        deleteColumn();
    });
});

// Load all tables
function loadTables() {
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
                if (currentTable) {
                    const tableNames = response.tables.map(t => typeof t === 'string' ? t : t.name);
                    if (tableNames.includes(currentTable)) {
                        select.val(currentTable).trigger('change');
                    }
                }
            }
            $('#loading').removeClass('active');
        },
        error: function(xhr) {
            showError('Error loading tables: ' + xhr.responseText);
            $('#loading').removeClass('active');
        }
    });
}

// Load table structure
function loadTableStructure() {
    $('#loading').addClass('active');
    $('#tableStructure').hide();
    
    $.ajax({
        url: '../api/?action=getTableInfo&table=' + encodeURIComponent(currentTable),
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                tableInfo = response;
                displayTableInfo();
                displayStructureTable();
                $('#tableStructure').show();
                $('#emptyState').hide();
                
                // Disable add column button for views
                if (tableInfo.isView) {
                    $('#addColumnBtn').hide();
                } else {
                    $('#addColumnBtn').show();
                }
            }
            $('#loading').removeClass('active');
        },
        error: function(xhr) {
            showError('Error loading table structure');
            $('#loading').removeClass('active');
        }
    });
}

// Display table information
function displayTableInfo() {
    const tableInfoDiv = $('#tableInfo');
    const statsGrid = $('#statsGrid');
    
    // Count different types of columns
    const totalColumns = tableInfo.columns.length;
    const primaryKeys = tableInfo.columns.filter(col => col.key === 'PRI').length;
    const nullableColumns = tableInfo.columns.filter(col => col.null).length;
    const autoIncrementColumns = tableInfo.columns.filter(col => col.extra.toLowerCase().includes('auto_increment')).length;
    
    const typeIcon = tableInfo.isView ? '👁️' : '📊';
    const typeLabel = tableInfo.isView ? 'View' : 'Table';
    const viewWarning = tableInfo.isView ? '<p style="color: var(--color-warning); font-weight: 600;">⚠️ Read-only VIEW - Structure cannot be modified</p>' : '';
    const viewSourceBtn = tableInfo.isView ? '<button id="viewSourceBtn" class="btn-primary" style="margin-top: 10px; padding: 8px 16px; font-size: 14px;">🔍 View Source</button>' : '';
    
    tableInfoDiv.html(`
        <h2>${typeIcon} ${typeLabel}: ${currentTable}</h2>
        ${viewWarning}
        ${viewSourceBtn}
        <p><strong>Type:</strong> ${tableInfo.tableType}</p>
        <p><strong>Primary Key:</strong> ${tableInfo.primaryKey || 'None'}</p>
        <p><strong>Total Columns:</strong> ${totalColumns}</p>
    `);
    
    statsGrid.html(`
        <div class="stat-card">
            <h3>${totalColumns}</h3>
            <p>Total Columns</p>
        </div>
        <div class="stat-card">
            <h3>${primaryKeys}</h3>
            <p>Primary Keys</p>
        </div>
        <div class="stat-card">
            <h3>${nullableColumns}</h3>
            <p>Nullable Fields</p>
        </div>
        <div class="stat-card">
            <h3>${autoIncrementColumns}</h3>
            <p>Auto Increment</p>
        </div>
    `);
}

// Display structure table
function displayStructureTable() {
    const tbody = $('#structureBody');
    tbody.empty();
    
    // Define all possible attributes
    const allAttributes = [
        { key: 'primary', text: 'PRIMARY', class: 'primary' },
        { key: 'unique', text: 'UNIQUE', class: 'unique' },
        { key: 'index', text: 'INDEX', class: 'index' },
        { key: 'required', text: 'NOTNULL', class: 'required' },
        { key: 'auto_increment', text: 'A.I.', class: 'auto-increment' },
        { key: 'foreign_key', text: 'FK', class: 'foreign-key' }
    ];
    
    tableInfo.columns.forEach(function(col) {
        // Determine which attributes are applicable
        const applicableAttributes = [];
        applicableAttributes.push(col.key === 'PRI' ? 'primary' : null);
        applicableAttributes.push(col.key === 'UNI' ? 'unique' : null);
        applicableAttributes.push(col.key === 'MUL' ? 'index' : null);
        applicableAttributes.push(!col.null ? 'required' : null);
        applicableAttributes.push(col.extra.toLowerCase().includes('auto_increment') ? 'auto_increment' : null);
        applicableAttributes.push(col.foreignKey ? 'foreign_key' : null);
        
        // Create attribute buttons - all attributes, dimmed if not applicable
        const attributesHtml = allAttributes.map(attr => {
            const isApplicable = applicableAttributes.includes(attr.key);
            const dimmedClass = isApplicable ? '' : 'dimmed';
            // Always show just "FK" in the badge, full reference is in the tooltip
            const badgeText = attr.text;
            return `<span class="attribute-badge ${attr.class} ${dimmedClass}" title="${col.foreignKey ? `References ${col.foreignKey.referenced_table}.${col.foreignKey.referenced_column} (${col.foreignKey.update_rule}/${col.foreignKey.delete_rule})` : ''}">${badgeText}</span>`;
        }).join('');
        
        // Format type display - make VARCHAR clickable for measurement
        let typeDisplay = col.type;
        let typeClass = 'field-type';
        if (col.baseType === 'varchar') {
            typeClass += ' field-type-clickable';
            const typeTitle = 'Click to measure maximum used length in this column';
            // Extract and store the defined length for later use
            const lengthMatch = col.type.match(/VARCHAR\((\d+)\)/i);
            const definedLength = lengthMatch ? lengthMatch[1] : '';
            typeDisplay = `<span class="${typeClass}" data-column="${col.name}" data-defined-length="${definedLength}" data-original-type="${col.type}" title="${typeTitle}">${col.type}</span>`;
        } else {
            typeDisplay = `<span class="${typeClass}">${col.type}</span>`;
        }
        
        const row = `
            <tr data-column-name="${col.name}">
                <td><strong>${col.name}</strong></td>
                <td>${typeDisplay}</td>
                <td>${col.null ? 'YES' : 'NO'}</td>
                <td>${col.key || ''}</td>
                <td>${col.default !== null ? col.default : '<em>NULL</em>'}</td>
                <td>${col.extra || ''}</td>
                <td><div class="field-attributes">${attributesHtml}</div></td>
                <td></td>
            </tr>
        `;
        tbody.append(row);
    });
    
    // Add click handlers to rows (only for tables, not views)
    if (!tableInfo.isView) {
        tbody.find('tr').click(function() {
            const columnName = $(this).data('column-name');
            openEditColumnModal(columnName);
        });
    } else {
        // For views, change cursor to indicate non-clickable
        tbody.find('tr').css('cursor', 'default');
    }
    
    // Add click handler for View Source button
    $('#viewSourceBtn').click(function() {
        showViewSource();
    });
    
    // Add click handlers for clickable VARCHAR types (stop propagation to prevent row click)
    tbody.find('.field-type-clickable').click(function(e) {
        e.stopPropagation();
        const columnName = $(this).data('column');
        measureColumnMaxLength(columnName, $(this));
    });
}

// Open add column modal
function openAddColumnModal() {
    currentEditColumn = null;
    $('#modalTitle').text('➕ Add New Column');
    $('#deleteColumnBtn').hide();
    buildColumnForm(null);
    $('#columnModal').addClass('active');
}

// Open edit column modal
function openEditColumnModal(columnName) {
    const column = tableInfo.columns.find(col => col.name === columnName);
    if (!column) return;
    
    currentEditColumn = column;
    $('#modalTitle').text('✏️ Edit Column: ' + columnName);
    $('#deleteColumnBtn').show();
    buildColumnForm(column);
    $('#columnModal').addClass('active');
}

// Build column form
function buildColumnForm(column) {
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
                ${tableInfo ? tableInfo.columns.map((col, index) => 
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
    loadTablesForForeignKey();
    
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
            loadColumnsForForeignKey(tableName);
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
                loadColumnsForForeignKey(column.foreignKey.referenced_table, column.foreignKey.referenced_column);
            }
        }, 100);
    }
}

// Load tables for foreign key selection
function loadTablesForForeignKey() {
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
                    if (table !== currentTable) {
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
}

// Load columns from a table for foreign key reference
function loadColumnsForForeignKey(tableName, selectedColumn = null) {
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

// Save column
function saveColumn() {
    const formData = {
        name: $('#fieldName').val(),
        type: $('#fieldType').val(),
        default: $('#fieldDefault').val() || null,
        null: $('#attrNull').is(':checked'),
        primary: $('#attrPrimary').is(':checked'),
        unique: $('#attrUnique').is(':checked'),
        index: $('#attrIndex').is(':checked'),
        auto_increment: $('#attrAutoIncrement').is(':checked'),
        extra: $('#fieldExtra').val()
    };
    
    // Add foreign key data if checkbox is checked
    if ($('#hasForeignKey').is(':checked')) {
        formData.foreignKey = {
            referenced_table: $('#fkReferencedTable').val(),
            referenced_column: $('#fkReferencedColumn').val(),
            update_rule: $('#fkUpdateRule').val(),
            delete_rule: $('#fkDeleteRule').val()
        };
        
        // Validate foreign key fields
        if (!formData.foreignKey.referenced_table || !formData.foreignKey.referenced_column) {
            alert('Please select both a referenced table and column for the foreign key');
            return;
        }
    }
    
    // Add position for new columns
    if (!currentEditColumn) {
        formData.position = $('#fieldPosition').val();
    }
    
    if (!formData.name) {
        alert('Please enter a column name');
        return;
    }
    
    // Generate SQL query instead of executing
    const sqlQuery = generateColumnSQL(formData, currentEditColumn);
    
    // Redirect to SQL Query Builder with the generated SQL
    const queryParam = encodeURIComponent(sqlQuery);
    const tableParam = encodeURIComponent(currentTable);
    // Set table in session before navigating
    $.ajax({
        url: '../api/',
        method: 'POST',
        data: {
            action: 'setCurrentTable',
            table: currentTable
        },
        dataType: 'json',
        success: function() {
            window.location.href = `../query_builder/?sql=${queryParam}`;
        },
        error: function() {
            // If setting table fails, still navigate with URL parameter as fallback
            window.location.href = `../query_builder/?table=${tableParam}&sql=${queryParam}`;
        }
    });
}

// Generate SQL for column modification
function generateColumnSQL(formData, editColumn) {
    let sql = '';
    
    // Determine the type to use - if "--" is selected and editing existing column, use original type
    let columnType = formData.type;
    if (editColumn && formData.type === '--') {
        columnType = editColumn.type;
    }
    
    // Build column definition
    let columnDef = `\`${formData.name}\` ${columnType}`;
    
    // Add NOT NULL or NULL
    if (!formData.null) {
        columnDef += ' NOT NULL';
    } else {
        columnDef += ' NULL';
    }
    
    // Add DEFAULT value
    if (formData.default !== null && formData.default !== '') {
        // Check if default should be quoted (non-numeric types)
        if (columnType.toUpperCase().includes('INT') || 
            columnType.toUpperCase().includes('DECIMAL') || 
            columnType.toUpperCase().includes('FLOAT') ||
            columnType.toUpperCase().includes('DOUBLE')) {
            columnDef += ` DEFAULT ${formData.default}`;
        } else {
            columnDef += ` DEFAULT '${formData.default.replace(/'/g, "''")}'`;
        }
    }
    
    // Add AUTO_INCREMENT
    if (formData.auto_increment) {
        columnDef += ' AUTO_INCREMENT';
    }
    
    // Add extra attributes
    if (formData.extra && formData.extra.trim() !== '') {
        columnDef += ' ' + formData.extra.trim();
    }
    
    if (editColumn) {
        const isRenaming = editColumn.name !== formData.name;
        
        // Check if column definition actually changed
        // If type is "--", use original type, so it won't be considered changed
        const typeChanged = formData.type !== '--' && columnType !== editColumn.type;
        const nullChanged = formData.null !== editColumn.null;
        
        // Compare default values
        // Normalize: treat null, undefined, and empty string as "no default"
        const oldDefault = editColumn.default !== null && editColumn.default !== undefined && String(editColumn.default).trim() !== '' ? String(editColumn.default).trim() : null;
        const newDefault = formData.default !== null && formData.default !== undefined && String(formData.default).trim() !== '' ? String(formData.default).trim() : null;
        const defaultChanged = oldDefault !== newDefault;
        
        // Compare auto_increment
        const oldAutoIncrement = editColumn.extra ? editColumn.extra.toLowerCase().includes('auto_increment') : false;
        const autoIncrementChanged = formData.auto_increment !== oldAutoIncrement;
        
        // Compare extra attributes (excluding auto_increment which we handle separately)
        const oldExtra = editColumn.extra ? editColumn.extra.replace(/auto_increment/gi, '').trim() : '';
        const newExtra = formData.extra ? formData.extra.replace(/auto_increment/gi, '').trim() : '';
        const extraChanged = oldExtra !== newExtra;
        
        // Check if column definition needs to be modified
        // If "-- Don't change --" is selected, ignore type changes and only check other fields
        const strictMode = formData.type === '--';
        const columnDefChanged = isRenaming || 
            (!strictMode && typeChanged) ||  // Only check typeChanged if not in strict mode
            nullChanged || 
            defaultChanged || 
            autoIncrementChanged || 
            extraChanged;
        
        const warningComment = isRenaming
            ? [
                '-- WARNING: Column rename detected.',
                `-- Old name: ${editColumn.name}`,
                `-- New name: ${formData.name}`,
                '-- Ensure dependent queries, views, and application code are updated before executing.'
            ].join('\n')
            : '';

        // Only generate MODIFY/CHANGE statement if column definition actually changed
        if (columnDefChanged) {
            if (isRenaming) {
                sql = `ALTER TABLE \`${currentTable}\` CHANGE COLUMN \`${editColumn.name}\` ${columnDef};`;
            } else {
                sql = `ALTER TABLE \`${currentTable}\` MODIFY COLUMN ${columnDef};`;
            }
        }
        
        // Add index modifications if needed
        const indexSQL = [];
        
        // Handle PRIMARY KEY
        if (formData.primary && editColumn.key !== 'PRI') {
            indexSQL.push(`ALTER TABLE \`${currentTable}\` ADD PRIMARY KEY (\`${formData.name}\`);`);
        } else if (!formData.primary && editColumn.key === 'PRI') {
            indexSQL.push(`ALTER TABLE \`${currentTable}\` DROP PRIMARY KEY;`);
        }
        
        // Handle UNIQUE
        if (formData.unique && editColumn.key !== 'UNI') {
            indexSQL.push(`ALTER TABLE \`${currentTable}\` ADD UNIQUE (\`${formData.name}\`);`);
        } else if (!formData.unique && editColumn.key === 'UNI') {
            indexSQL.push(`ALTER TABLE \`${currentTable}\` DROP INDEX \`${formData.name}\`;`);
        }
        
        // Handle INDEX (regular index, not PRIMARY or UNIQUE)
        // Only add/remove index if not PRIMARY or UNIQUE (they already create indexes)
        const currentHasIndex = editColumn.key === 'MUL';
        const wantsRegularIndex = formData.index && !formData.primary && !formData.unique;
        
        if (wantsRegularIndex && !currentHasIndex) {
            indexSQL.push(`ALTER TABLE \`${currentTable}\` ADD INDEX (\`${formData.name}\`);`);
        } else if (!wantsRegularIndex && currentHasIndex && !formData.primary && !formData.unique) {
            // Drop index - MySQL typically names the index after the column if created with ADD INDEX(column)
            // However, it might have a different name, so we try the column name first
            indexSQL.push(`ALTER TABLE \`${currentTable}\` DROP INDEX \`${formData.name}\`;`);
        }
        
        // Handle FOREIGN KEY
        const hasOldFK = editColumn.foreignKey;
        const hasNewFK = formData.foreignKey && formData.foreignKey.referenced_table && formData.foreignKey.referenced_column;
        
        if (hasOldFK && !hasNewFK) {
            // Drop existing foreign key
            const fkName = editColumn.foreignKey.constraint_name || `fk_${editColumn.name}`;
            indexSQL.push(`ALTER TABLE \`${currentTable}\` DROP FOREIGN KEY \`${fkName}\`;`);
        } else if (hasNewFK) {
            // Generate constraint name
            const fkName = hasOldFK ? editColumn.foreignKey.constraint_name : `fk_${formData.name}`;
            
            // Check if FK changed
            const fkChanged = !hasOldFK || 
                editColumn.foreignKey.referenced_table !== formData.foreignKey.referenced_table ||
                editColumn.foreignKey.referenced_column !== formData.foreignKey.referenced_column ||
                editColumn.foreignKey.update_rule !== formData.foreignKey.update_rule ||
                editColumn.foreignKey.delete_rule !== formData.foreignKey.delete_rule;
            
            if (fkChanged) {
                // Drop old FK if exists
                if (hasOldFK) {
                    const oldFkName = editColumn.foreignKey.constraint_name || `fk_${editColumn.name}`;
                    indexSQL.push(`ALTER TABLE \`${currentTable}\` DROP FOREIGN KEY \`${oldFkName}\`;`);
                }
                
                // Add new FK
                const updateRule = formData.foreignKey.update_rule || 'RESTRICT';
                const deleteRule = formData.foreignKey.delete_rule || 'RESTRICT';
                indexSQL.push(`ALTER TABLE \`${currentTable}\` ADD CONSTRAINT \`${fkName}\` FOREIGN KEY (\`${formData.name}\`) REFERENCES \`${formData.foreignKey.referenced_table}\` (\`${formData.foreignKey.referenced_column}\`) ON UPDATE ${updateRule} ON DELETE ${deleteRule};`);
            }
        }
        
        if (indexSQL.length > 0) {
            if (sql) {
                sql = sql + '\n\n' + indexSQL.join('\n');
            } else {
                sql = indexSQL.join('\n');
            }
        }

        if (warningComment) {
            sql = warningComment + '\n' + sql;
        }
        
        // If no SQL was generated at all, return empty string
        if (!sql) {
            return '';
        }

    } else {
        // ADD new column
        sql = `ALTER TABLE \`${currentTable}\` ADD COLUMN ${columnDef}`;
        
        // Add position
        const position = formData.position || 'end';
        if (position === 'first') {
            sql += ' FIRST';
        } else if (position.startsWith('after_')) {
            const afterColumn = position.substring(6);
            sql += ` AFTER \`${afterColumn}\``;
        }
        
        sql += ';';
        
        // Add index if needed
        const indexSQL = [];
        if (formData.primary) {
            indexSQL.push(`ALTER TABLE \`${currentTable}\` ADD PRIMARY KEY (\`${formData.name}\`);`);
        }
        if (formData.unique) {
            indexSQL.push(`ALTER TABLE \`${currentTable}\` ADD UNIQUE (\`${formData.name}\`);`);
        }
        // Add regular index only if not PRIMARY or UNIQUE (they already create indexes)
        if (formData.index && !formData.primary && !formData.unique) {
            indexSQL.push(`ALTER TABLE \`${currentTable}\` ADD INDEX (\`${formData.name}\`);`);
        }
        
        // Add foreign key if specified
        if (formData.foreignKey && formData.foreignKey.referenced_table && formData.foreignKey.referenced_column) {
            const fkName = `fk_${formData.name}`;
            const updateRule = formData.foreignKey.update_rule || 'RESTRICT';
            const deleteRule = formData.foreignKey.delete_rule || 'RESTRICT';
            indexSQL.push(`ALTER TABLE \`${currentTable}\` ADD CONSTRAINT \`${fkName}\` FOREIGN KEY (\`${formData.name}\`) REFERENCES \`${formData.foreignKey.referenced_table}\` (\`${formData.foreignKey.referenced_column}\`) ON UPDATE ${updateRule} ON DELETE ${deleteRule};`);
        }
        
        if (indexSQL.length > 0) {
            sql = sql + '\n\n' + indexSQL.join('\n');
        }
    }
    
    return sql;
}

// Delete column
function deleteColumn() {
    if (!currentEditColumn) return;
    
    // Generate SQL query for column deletion
    const warningComment = [
        '-- WARNING: Column drop operation.',
        `-- Column to drop: ${currentEditColumn.name}`,
        '-- Verify no queries, views, or application code still rely on this column before executing.'
    ].join('\n');
    const sqlQuery = `${warningComment}\nALTER TABLE \`${currentTable}\` DROP COLUMN \`${currentEditColumn.name}\`;`;
    
    // Redirect to SQL Query Builder with the generated SQL
    const queryParam = encodeURIComponent(sqlQuery);
    const tableParam = encodeURIComponent(currentTable);
    // Set table in session before navigating
    $.ajax({
        url: '../api/',
        method: 'POST',
        data: {
            action: 'setCurrentTable',
            table: currentTable
        },
        dataType: 'json',
        success: function() {
            window.location.href = `../query_builder/?sql=${queryParam}`;
        },
        error: function() {
            // If setting table fails, still navigate with URL parameter as fallback
            window.location.href = `../query_builder/?table=${tableParam}&sql=${queryParam}`;
        }
    });
}

// Close modal
function closeModal(modalId = 'columnModal') {
    $('#' + modalId).removeClass('active');
    if (modalId === 'columnModal') {
        currentEditColumn = null;
    } else if (modalId === 'viewSourceModal') {
        // Remove the dynamically created modal from DOM
        setTimeout(function() {
            $('#' + modalId).remove();
        }, 300); // Wait for animation to complete
    }
}

// Show empty state
function showEmptyState() {
    $('#tableStructure').hide();
    $('#emptyState').show();
    $('#loading').removeClass('active');
}

// Show error
function showError(message) {
    alert('Error: ' + message);
}

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

// Show view source
function showViewSource() {
    if (!currentTable) {
        showError('No table selected');
        return;
    }

    $.ajax({
        url: '../api/',
        method: 'GET',
        data: { 
            action: 'getViewSource',
            table: currentTable
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Create modal for view source
                const modalHtml = `
                    <div class="modal active" id="viewSourceModal">
                        <div class="modal-content" style="max-width: 90%; max-height: 90%;">
                            <div class="modal-header">
                                <h2>🔍 View Source: ${response.viewName}</h2>
                                <button class="modal-close" onclick="closeModal('viewSourceModal')">&times;</button>
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
                                <button class="btn-secondary" onclick="closeModal('viewSourceModal')">Close</button>
                                <button class="btn-primary" onclick="copyViewSource('${response.createStatement.replace(/'/g, "\\'")}')">📋 Copy SQL</button>
                            </div>
                        </div>
                    </div>
                `;
                
                $('body').append(modalHtml);
                
                // Add click-outside-to-close functionality
                $('#viewSourceModal').click(function(e) {
                    if (e.target === this) {
                        closeModal('viewSourceModal');
                    }
                });
            }
        },
        error: function(xhr) {
            let errorMsg = 'Error loading view source';
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.error) {
                    errorMsg += ': ' + response.error;
                }
            } catch (e) {
                errorMsg += ': ' + xhr.responseText;
            }
            showError(errorMsg);
        }
    });
}

// Copy view source to clipboard
function copyViewSource(sql) {
    navigator.clipboard.writeText(sql).then(function() {
        showSuccess('SQL copied to clipboard!');
    }).catch(function() {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = sql;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showSuccess('SQL copied to clipboard!');
    });
}

// Measure maximum used length for a VARCHAR column
function measureColumnMaxLength(columnName, $typeElement) {
    if (!currentTable) {
        showError('No table selected');
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
            table: currentTable,
            column: columnName
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const maxLength = response.maxLength || 0;
                
                // Display as VARCHAR(defined/max)
                const newDisplay = `VARCHAR(${definedLength}/${maxLength})`;
                $typeElement.text(newDisplay).css('opacity', '1');
            } else {
                showError('Error: ' + (response.error || 'Failed to measure column length'));
                $typeElement.text(originalText).css('opacity', '1');
            }
        },
        error: function(xhr) {
            let errorMsg = 'Error measuring column length';
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.error) {
                    errorMsg += ': ' + response.error;
                }
            } catch (e) {
                errorMsg += ': ' + xhr.responseText;
            }
            showError(errorMsg);
            $typeElement.text(originalText).css('opacity', '1');
        }
    });
}

// Show success message (simple alert for now, can be enhanced with toast)
function showSuccess(message) {
    alert('Success: ' + message);
}


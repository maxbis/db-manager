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
    loadTables();
    
    // Update navigation links with current table
    function updateNavLinks() {
        const selectedTable = $('#tableSelect').val();
        if (selectedTable) {
            $('.nav-link').each(function() {
                const baseUrl = $(this).attr('href').split('?')[0];
                $(this).attr('href', baseUrl + '?table=' + encodeURIComponent(selectedTable));
            });
        }
    }

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
        updateNavLinks();
        updateDatabaseBadge();
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
                
                // Check for table parameter in URL and select it
                const urlParams = new URLSearchParams(window.location.search);
                const tableParam = urlParams.get('table');
                if (tableParam) {
                    const tableNames = response.tables.map(t => typeof t === 'string' ? t : t.name);
                    if (tableNames.includes(tableParam)) {
                        select.val(tableParam).trigger('change');
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
                    showError('📖 This is a database VIEW - Structure cannot be modified');
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
        { key: 'required', text: 'NOT NULL', class: 'required' },
        { key: 'auto_increment', text: 'AUTO_INCREMENT', class: 'auto-increment' }
    ];
    
    tableInfo.columns.forEach(function(col) {
        // Determine which attributes are applicable
        const applicableAttributes = [];
        applicableAttributes.push(col.key === 'PRI' ? 'primary' : null);
        applicableAttributes.push(col.key === 'UNI' ? 'unique' : null);
        applicableAttributes.push(col.key === 'MUL' ? 'index' : null);
        applicableAttributes.push(!col.null ? 'required' : null);
        applicableAttributes.push(col.extra.toLowerCase().includes('auto_increment') ? 'auto_increment' : null);
        
        // Create attribute buttons - all attributes, dimmed if not applicable
        const attributesHtml = allAttributes.map(attr => {
            const isApplicable = applicableAttributes.includes(attr.key);
            const dimmedClass = isApplicable ? '' : 'dimmed';
            return `<span class="attribute-badge ${attr.class} ${dimmedClass}">${attr.text}</span>`;
        }).join('');
        
        const row = `
            <tr data-column-name="${col.name}">
                <td><strong>${col.name}</strong></td>
                <td><span class="field-type">${col.type}</span></td>
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
            <input type="text" id="fieldName" name="name" value="${column ? column.name : ''}" ${isNew ? '' : 'readonly'}>
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
                    <option value="VARCHAR(255)" ${column && column.type.startsWith('VARCHAR') ? 'selected' : ''}>VARCHAR(255)</option>
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
                    <input type="checkbox" id="attrAutoIncrement" name="auto_increment" ${column && column.extra.toLowerCase().includes('auto_increment') ? 'checked' : ''}>
                    <label for="attrAutoIncrement">Auto Increment</label>
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
    `);
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
        auto_increment: $('#attrAutoIncrement').is(':checked'),
        extra: $('#fieldExtra').val()
    };
    
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
    window.location.href = `../query_builder/?table=${tableParam}&sql=${queryParam}`;
}

// Generate SQL for column modification
function generateColumnSQL(formData, editColumn) {
    let sql = '';
    
    // Build column definition
    let columnDef = `\`${formData.name}\` ${formData.type}`;
    
    // Add NOT NULL or NULL
    if (!formData.null) {
        columnDef += ' NOT NULL';
    } else {
        columnDef += ' NULL';
    }
    
    // Add DEFAULT value
    if (formData.default !== null && formData.default !== '') {
        // Check if default should be quoted (non-numeric types)
        if (formData.type.toUpperCase().includes('INT') || 
            formData.type.toUpperCase().includes('DECIMAL') || 
            formData.type.toUpperCase().includes('FLOAT') ||
            formData.type.toUpperCase().includes('DOUBLE')) {
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
        // MODIFY existing column
        sql = `ALTER TABLE \`${currentTable}\` MODIFY COLUMN ${columnDef};`;
        
        // If column name changed, we need CHANGE instead of MODIFY
        if (editColumn.name !== formData.name) {
            sql = `ALTER TABLE \`${currentTable}\` CHANGE COLUMN \`${editColumn.name}\` ${columnDef};`;
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
        
        if (indexSQL.length > 0) {
            sql = sql + '\n\n' + indexSQL.join('\n');
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
    const sqlQuery = `ALTER TABLE \`${currentTable}\` DROP COLUMN \`${currentEditColumn.name}\`;`;
    
    // Redirect to SQL Query Builder with the generated SQL
    const queryParam = encodeURIComponent(sqlQuery);
    const tableParam = encodeURIComponent(currentTable);
    window.location.href = `../query_builder/?table=${tableParam}&sql=${queryParam}`;
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


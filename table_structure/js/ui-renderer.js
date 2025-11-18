/**
 * UI Renderer Module
 * Handles rendering table information and structure display
 */

const UIRenderer = {
    /**
     * Display table information
     */
    displayTableInfo: function() {
        const tableInfoDiv = $('#tableInfo');
        const statsGrid = $('#statsGrid');
        
        // Count different types of columns
        const totalColumns = window.State.tableInfo.columns.length;
        const primaryKeys = window.State.tableInfo.columns.filter(col => col.key === 'PRI').length;
        const nullableColumns = window.State.tableInfo.columns.filter(col => col.null).length;
        const autoIncrementColumns = window.State.tableInfo.columns.filter(col => col.extra.toLowerCase().includes('auto_increment')).length;
        
        const typeIcon = window.State.tableInfo.isView ? '👁️' : '📊';
        const typeLabel = window.State.tableInfo.isView ? 'View' : 'Table';
        const viewWarning = window.State.tableInfo.isView ? '<p style="color: var(--color-warning); font-weight: 600;">⚠️ Read-only VIEW - Structure cannot be modified</p>' : '';
        const viewSourceBtn = window.State.tableInfo.isView ? '<button id="viewSourceBtn" class="btn-primary" style="margin-top: 10px; padding: 8px 16px; font-size: 14px;">🔍 View Source</button>' : '';
        
        tableInfoDiv.html(`
            <h2>${typeIcon} ${typeLabel}: ${window.State.currentTable}</h2>
            ${viewWarning}
            ${viewSourceBtn}
            <p><strong>Type:</strong> ${window.State.tableInfo.tableType}</p>
            <p><strong>Primary Key:</strong> ${window.State.tableInfo.primaryKey || 'None'}</p>
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
    },

    /**
     * Display structure table
     */
    displayStructureTable: function() {
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
        
        window.State.tableInfo.columns.forEach(function(col) {
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
        if (!window.State.tableInfo.isView) {
            tbody.find('tr').click(function() {
                const columnName = $(this).data('column-name');
                window.ColumnForm.openEditModal(columnName);
            });
        } else {
            // For views, change cursor to indicate non-clickable
            tbody.find('tr').css('cursor', 'default');
        }
        
        // Add click handler for View Source button
        $('#viewSourceBtn').click(function() {
            window.Utils.showViewSource();
        });
        
        // Add click handlers for clickable VARCHAR types (stop propagation to prevent row click)
        tbody.find('.field-type-clickable').click(function(e) {
            e.stopPropagation();
            const columnName = $(this).data('column');
            window.Utils.measureColumnMaxLength(columnName, $(this));
        });
    }
};

// Export for use in other modules
if (typeof window !== 'undefined') {
    window.UIRenderer = UIRenderer;
}


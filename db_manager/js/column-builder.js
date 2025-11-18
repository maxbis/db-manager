/**
 * Column Builder Module
 * Handles the column builder UI and drag-and-drop functionality
 */

const ColumnBuilder = {
    /**
     * Add a column row to the builder
     */
    addRow: function() {
        const commonTypes = [
            { v: 'INT', label: 'INT' },
            { v: 'BIGINT', label: 'BIGINT' },
            { v: 'VARCHAR', label: 'VARCHAR' },
            { v: 'TEXT', label: 'TEXT' },
            { v: 'DATE', label: 'DATE' },
            { v: 'DATETIME', label: 'DATETIME' },
            { v: 'TIMESTAMP', label: 'TIMESTAMP' },
            { v: 'BOOLEAN', label: 'BOOLEAN' }
        ];

        const index = $('#columnsBuilder .column-row').length + 1;
        const row = $(`
            <div class="column-row">
                <div class="row-line" style="justify-content: space-between;">
                    <span class="col-badge"><span class="drag-handle" title="Drag to reorder">↕</span> Column ${index}</span>
                    <button type="button" class="btn-danger remove-col" style="padding:4px 8px; font-size:11px;">✖</button>
                </div>
                <div class="row-line" style="margin-top:6px;">
                    <input type="text" class="col-name" placeholder="column_name" style="flex:1 1 240px; min-width:140px; padding:6px 8px; border:1px solid var(--color-border-input); border-radius:8px;">
                    <select class="col-type" style="flex:0 0 120px; min-width:80px; padding:6px 8px; border:1px solid var(--color-border-input); border-radius:8px;">
                        ${commonTypes.map(t => `<option value="${t.v}">${t.label}</option>`).join('')}
                    </select>
                    <input type="text" class="col-length" placeholder="len" style="flex:0 0 80px; min-width:70px; padding:6px 8px; border:1px solid var(--color-border-input); border-radius:8px;">
                </div>
                <div class="row-line" style="margin-top:6px;">
                    <select class="col-default-mode" style="flex:0 0 200px; padding:6px 8px; border:1px solid var(--color-border-input); border-radius:8px;">
                        <option value="none">Default: None</option>
                        <option value="value">Default: Value…</option>
                        <option value="current_timestamp">Default: CURRENT_TIMESTAMP</option>
                    </select>
                    <input type="text" class="col-default" placeholder="default value" style="flex:1 1 200px; padding:6px 8px; border:1px solid var(--color-border-input); border-radius:8px; display:none;">
                    <label style="display:flex; align-items:center; gap:6px;margin-left:15px;">
                        <input type="checkbox" class="col-null"> NULL
                    </label>
                    <label style="display:flex; align-items:center; gap:6px;">
                        <input type="checkbox" class="col-ai"> AI
                    </label>
                </div>
                <div class="row-line" style="margin-top:6px;">
                    <label style="display:flex; align-items:center; gap:6px;margin-right:20px;">
                        <input type="checkbox" class="col-primary"> 🔑&nbsp;PRIMARY&nbsp;KEY
                    </label>
                    <label style="display:flex; align-items:center; gap:6px;margin-right:20px;">
                        <input type="checkbox" class="col-index"> 📇&nbsp;INDEX
                    </label>
                    <label style="display:flex; align-items:center; gap:6px;">
                        <input type="checkbox" class="col-unique"> ✨&nbsp;UNIQUE
                    </label>
                </div>
            </div>
        `);

        // Toggle default value input visibility
        row.find('.col-default-mode').on('change', function(){
            const mode = $(this).val();
            row.find('.col-default').toggle(mode === 'value');
        });

        // Force lowercase column names as user types
        row.find('.col-name').on('input', function(){
            this.value = this.value.toLowerCase();
        });

        // When PRIMARY KEY is checked, uncheck NULL (PRIMARY KEY implies NOT NULL)
        row.find('.col-primary').on('change', function(){
            if ($(this).is(':checked')) {
                row.find('.col-null').prop('checked', false);
            }
        });

        // When NULL is checked, uncheck PRIMARY KEY (they're mutually exclusive)
        row.find('.col-null').on('change', function(){
            if ($(this).is(':checked')) {
                row.find('.col-primary').prop('checked', false);
            }
        });

        row.find('.remove-col').on('click', function(){ row.remove(); ColumnBuilder.renumberBadges(); });

        $('#columnsBuilder .column-rows').append(row);
    },

    /**
     * Enable drag and drop for column rows
     */
    enableDragAndDrop: function() {
        const container = document.querySelector('#columnsBuilder .column-rows');
        let draggedEl = null;

        container.addEventListener('dragstart', function(e){
            const row = e.target.closest('.column-row');
            if (!row) return;
            draggedEl = row;
            row.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', 'drag');
        });

        container.addEventListener('dragend', function(e){
            if (draggedEl) draggedEl.classList.remove('dragging');
            draggedEl = null;
            ColumnBuilder.renumberBadges();
        });

        container.addEventListener('dragover', function(e){
            e.preventDefault();
            const afterElement = ColumnBuilder.getDragAfterElement(container, e.clientY);
            const dragging = container.querySelector('.dragging');
            if (!dragging) return;
            if (afterElement == null) {
                container.appendChild(dragging);
            } else {
                container.insertBefore(dragging, afterElement);
            }
        });

        // Make rows draggable
        new MutationObserver(function(){
            container.querySelectorAll('.column-row').forEach(function(row){
                row.setAttribute('draggable', 'true');
            });
        }).observe(container, { childList: true, subtree: true });

        // Initialize existing
        container.querySelectorAll('.column-row').forEach(function(row){ row.setAttribute('draggable','true'); });
    },

    /**
     * Get the element after which to insert dragged element
     */
    getDragAfterElement: function(container, y) {
        const draggableElements = [...container.querySelectorAll('.column-row:not(.dragging)')];

        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    },

    /**
     * Renumber column badges after drag and drop
     */
    renumberBadges: function(){
        $('#columnsBuilder .column-row .col-badge').each(function(i){
            const text = $(this).text().replace(/Column\s+\d+/, 'Column ' + (i+1));
            $(this).text(text);
        });
    },

    /**
     * Build columns DDL from builder rows
     */
    buildColumnsDDL: function() {
        const lines = [];
        const primaryKeys = [];
        const indexes = [];
        const uniqueKeys = [];
        
        $('#columnsBuilder .column-row').each(function(){
            const name = ($(this).find('.col-name').val() || '').trim().toLowerCase();
            const type = $(this).find('.col-type').val();
            const length = $(this).find('.col-length').val().trim();
            const allowNull = $(this).find('.col-null').is(':checked');
            const autoInc = $(this).find('.col-ai').is(':checked');
            const isPrimary = $(this).find('.col-primary').is(':checked');
            const isIndex = $(this).find('.col-index').is(':checked');
            const isUnique = $(this).find('.col-unique').is(':checked');
            const defaultMode = $(this).find('.col-default-mode').val();
            const defaultVal = $(this).find('.col-default').val();

            if (!name) return; // skip empty rows

            let ddl = name + ' ' + type + (length ? '(' + length + ')' : '');
            if (!allowNull) ddl += ' NOT NULL';

            // Default handling
            if (defaultMode === 'value' && defaultVal !== '') {
                // Quote string-like types
                const needsQuotes = /^(CHAR|VARCHAR|TEXT|TINYTEXT|MEDIUMTEXT|LONGTEXT)$/i.test(type);
                ddl += ' DEFAULT ' + (needsQuotes ? `'${defaultVal.replace(/'/g, "''")}'` : defaultVal);
            } else if (defaultMode === 'current_timestamp') {
                ddl += ' DEFAULT CURRENT_TIMESTAMP';
            }

            if (autoInc) ddl += ' AUTO_INCREMENT';

            lines.push(ddl);
            
            // Collect key constraints
            if (isPrimary) primaryKeys.push(name);
            if (isIndex) indexes.push(name);
            if (isUnique) uniqueKeys.push(name);
        });

        // Validate that at least one column was defined
        if (lines.length === 0) {
            Utils.showToast('Please define at least one column with a name', 'warning');
            return null;
        }

        // Add PRIMARY KEY constraint
        if (primaryKeys.length > 0) {
            lines.push('PRIMARY KEY (' + primaryKeys.join(', ') + ')');
        }

        // Add UNIQUE constraints
        uniqueKeys.forEach(function(colName) {
            lines.push('UNIQUE KEY `idx_unique_' + colName + '` (' + colName + ')');
        });

        // Add INDEX constraints
        indexes.forEach(function(colName) {
            lines.push('KEY `idx_' + colName + '` (' + colName + ')');
        });

        return lines.join(',\n');
    }
};

// Export for use in other modules
if (typeof window !== 'undefined') {
    window.ColumnBuilder = ColumnBuilder;
    window.addColumnRow = ColumnBuilder.addRow;
}


/**
 * UI Renderer Module
 * Handles all display and rendering functions
 */

const UIRenderer = {
    /**
     * Display databases list
     */
    displayDatabases: function() {
        const databaseList = $('#databaseList');
        databaseList.empty();

        let list = UIRenderer.getFilteredAndSortedDatabases();

        if (list.length === 0) {
            databaseList.append(`
                <div class="empty-state" style="padding: 40px 20px;">
                    <div class="empty-state-icon">🗄️</div>
                    <h3>No Databases Found</h3>
                    <p>Adjust your search or create a new database.</p>
                </div>
            `);
            return;
        }

        const maxSize = Math.max(1, ...list.map(db => (db.size || 0)));

        list.forEach((db) => {
            const isCurrent = db.name === window.State.currentDatabase;
            const sizeBytes = db.size || 0;
            const sizePercent = Math.max(4, Math.round((sizeBytes / maxSize) * 100));
            const displaySize = window.Utils.formatBytes(sizeBytes);
            const isLarge = sizeBytes > 100 * 1024 * 1024; // >100MB

            const databaseItem = $(`
                <div class="database-item ${isCurrent ? 'active' : ''}" data-database="${db.name}" tabindex="0">
                    <!-- Database Name Section (Left Part) -->
                    <div class="database-name-section">
                        <span class="expand-indicator" title="Click to expand/collapse" aria-label="Expand database ${db.name}"></span>
                        <span class="database-icon" aria-hidden="true">🗄️</span>
                        <div class="database-main-info">
                            <h4 class="database-name">${db.name}${isCurrent ? '<span class="badge-current" title="Currently selected">Current</span>' : ''}</h4>
                            <p class="database-tables">${db.tables || 0} tables</p>
                        </div>
                    </div>
                    
                    <!-- Size Indicator Section (Center Part) -->
                    <div class="database-size-section">
                        <div class="database-size-info">
                            <div class="database-size-bar" data-tooltip="Size: ${displaySize}" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="${sizePercent}" aria-label="Database size of ${db.name}">
                                <div class="database-size-fill ${isLarge ? 'large' : ''}" style="width: ${sizePercent}%;"></div>
                            </div>
                            <span class="database-size-text">${displaySize}</span>
                        </div>
                    </div>
                    
                    <!-- Buttons Section (Right Part) -->
                    <div class="database-actions-section">
                        <div class="actions-dropdown db-actions-dropdown" data-database="${db.name}">
                            <button type="button" class="dropdown-toggle">⚙️ Actions</button>
                            <div class="dropdown-menu" role="menu" aria-label="Actions for ${db.name}">
                                <ul>
                                    <li><button class="menu-item db-create-table-btn" data-database="${db.name}">➕ Create Table</button></li>
                                    <li><button class="menu-item db-export-btn" data-database="${db.name}">📤 Export</button></li>
                                    <li><button class="menu-item db-delete-btn" data-database="${db.name}">🗑️ Delete</button></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Database Tables Subsection (Hidden by default) -->
                <div class="database-tables-subsection" data-database="${db.name}">
                    <h4>Tables in ${db.name}</h4>
                    <div class="database-tables-grid">
                        <!-- Tables will be populated here when expanded -->
                    </div>
                </div>
            `);

            // Click on database item to select it
            databaseItem.on('click', function(e){
                if ($(e.target).is('button') || 
                    $(e.target).closest('button').length || 
                    $(e.target).closest('.actions-dropdown').length ||
                    $(e.target).hasClass('expand-indicator')) {
                    return;
                }
                window.UIInteractions.selectDatabase(db.name);
            });

            // Keyboard support: Enter/Space to select
            databaseItem.on('keydown', function(e){
                if (e.key === 'Enter' || e.key === ' ') { 
                    e.preventDefault(); 
                    window.UIInteractions.selectDatabase(db.name); 
                }
            });

            // Expand indicator click handler
            databaseItem.find('.expand-indicator').on('click', function(e){
                e.stopPropagation();
                window.UIInteractions.toggleDatabaseTables(db.name);
            });

            databaseList.append(databaseItem);
        });
    },

    /**
     * Display tables in subsection
     */
    displayTablesInSubsection: function(databaseName, tables) {
        const tablesGrid = $(`.database-tables-subsection[data-database="${databaseName}"] .database-tables-grid`);
        tablesGrid.empty();

        if (tables.length === 0) {
            tablesGrid.append(`
                <div style="text-align: center; color: var(--color-text-tertiary); padding: 20px; background: var(--color-bg-white); border: 1px solid var(--color-border-light); border-radius: 6px;">
                    No tables found in this database.
                </div>
            `);
        } else {
            const maxSize = Math.max(1, ...tables.map(t => (typeof t === 'object' ? (t.size || 0) : 0)));
            tables.forEach((table) => {
                const tableName = typeof table === 'string' ? table : table.name;
                const tableType = typeof table === 'object' ? table.type : 'BASE TABLE';
                const tableSize = typeof table === 'object' ? (table.size || 0) : 0;
                const isView = tableType === 'VIEW';
                const tableIcon = isView ? '👁️' : '📋';
                const sizePercent = Math.max(4, Math.round((tableSize / maxSize) * 100));
                const displaySize = window.Utils.formatBytes(tableSize || 0);
                
                const tableItem = $(`
                    <div class="database-table-item" data-table="${tableName}" data-database="${databaseName}" style="cursor: pointer;">
                        <span class="table-icon">${tableIcon}</span>
                        <span class="table-name">${tableName}</span>
                        ${isView ? '<span class="table-type">View</span>' : '<span class="table-type">Table</span>'}
                        
                        <div class="table-actions">
                            <button class="btn-success table-action-btn" onclick="event.stopPropagation(); viewTableStructure('${tableName}', '${databaseName}')" title="View table structure">View 🔍</button>
                            ${isView
                                ? `<button class="btn-secondary table-action-btn" disabled aria-disabled="true" title="Cannot rename a view">Ren. 🔄</button>`
                                : `<button class="btn-secondary table-action-btn table-rename-btn" data-table="${tableName}" data-database="${databaseName}" title="Rename table">Ren. 🔄</button>`}
                            ${isView
                                ? `<button class="btn-danger table-action-btn" disabled aria-disabled="true" title="Cannot delete a view">Del.  🗑️</button>`
                                : `<button class="btn-danger table-action-btn" onclick="event.stopPropagation(); deleteTable('${tableName}', '${databaseName}', true)" title="Delete table">Del. 🗑️</button>`}
                        </div>

                        <div class="table-size-section">
                            <div class="database-size-info">
                                <div class="database-size-bar" data-tooltip="Size: ${displaySize}" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="${sizePercent}" aria-label="Table size of ${tableName}">
                                    <div class="database-size-fill" style="width: ${sizePercent}%;"></div>
                                </div>
                                <span class="database-size-text">${displaySize}</span>
                            </div>
                        </div>

                    </div>
                `);
                
                // Add click handler to view the table
                tableItem.click(function (e) {
                    if ($(e.target).is('button') || $(e.target).closest('button').length) {
                        return;
                    }
                    e.stopPropagation();
                    window.TableOperations.viewData(tableName, databaseName);
                });
                
                tablesGrid.append(tableItem);
            });
        }
        
        // Show the subsection with animation
        $(`.database-tables-subsection[data-database="${databaseName}"]`).slideDown(200);
    },

    /**
     * Get filtered and sorted databases
     */
    getFilteredAndSortedDatabases: function() {
        let result = window.State.databases.slice();
        if (window.State.dbSearchQuery) {
            result = result.filter(db => (db.name || '').toLowerCase().includes(window.State.dbSearchQuery));
        }
        const byName = (a,b) => (a.name || '').localeCompare(b.name || '');
        const bySize = (a,b) => (b.size || 0) - (a.size || 0);
        const byTables = (a,b) => (b.tables || 0) - (a.tables || 0);
        switch (window.State.dbSortMode) {
            case 'name_desc': result.sort((a,b)=>byName(b,a)); break;
            case 'size_desc': result.sort(bySize); break;
            case 'size_asc': result.sort((a,b)=>-bySize(a,b)); break;
            case 'tables_desc': result.sort(byTables); break;
            case 'tables_asc': result.sort((a,b)=>-byTables(a,b)); break;
            default: result.sort(byName);
        }
        return result;
    },

    /**
     * Populate database select dropdown
     */
    populateDatabaseSelect: function() {
        const select = $('#databaseSelect');
        select.empty();
        select.append('<option value="">-- Select a database --</option>');

        window.State.databases.forEach((db) => {
            const selected = db.name === window.State.currentDatabase ? 'selected' : '';
            select.append(`<option value="${db.name}" ${selected}>${db.name}</option>`);
        });
    },

    /**
     * Update statistics
     */
    updateStats: function() {
        const statsGrid = $('#statsGrid');
        const totalDatabases = window.State.databases.length;
        const totalTables = window.State.databases.reduce((sum, db) => sum + (db.tables || 0), 0);
        const totalSize = window.State.databases.reduce((sum, db) => sum + (db.size || 0), 0);

        statsGrid.html(`
            <div class="stat-item">
                <div class="stat-value">${totalDatabases}</div>
                <div class="stat-label">Databases</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">${totalTables}</div>
                <div class="stat-label">Tables</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">${window.Utils.formatBytes(totalSize)}</div>
                <div class="stat-label">Total Size</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">${window.State.currentDatabase || 'None'}</div>
                <div class="stat-label">Current DB</div>
            </div>
        `);
    },

    /**
     * Update button states
     */
    updateButtonStates: function() {
        const hasDatabase = !!window.State.currentDatabase;
        $('#createTableMenuItem').prop('disabled', !hasDatabase);
        $('#exportDatabaseBtn').prop('disabled', !hasDatabase);
        $('#importDatabaseBtn').prop('disabled', false); // Can always import
    },

    /**
     * Update database badge in header
     */
    updateDatabaseBadge: function(databaseName, tableName = '') {
        const databaseBadge = document.querySelector('.control-group span span');
        if (databaseBadge) {
            let displayText = '🗄️ ' + databaseName;
            if (tableName) {
                displayText += ' -  ' + tableName;
            }
            databaseBadge.textContent = displayText;
        }
    },

    /**
     * Update table count in database item after table deletion
     */
    updateDatabaseTableCount: function(databaseName) {
        const databaseItem = $(`.database-item[data-database="${databaseName}"]`);
        const tablesCount = $(`.database-table-item[data-database="${databaseName}"]`).length;
        databaseItem.find('.database-tables').text(tablesCount + ' tables');
    }
};

// Export for use in other modules
if (typeof window !== 'undefined') {
    window.UIRenderer = UIRenderer;
    // Also expose functions globally for backward compatibility
    window.displayDatabases = UIRenderer.displayDatabases;
    window.displayTablesInSubsection = UIRenderer.displayTablesInSubsection;
    window.getFilteredAndSortedDatabases = UIRenderer.getFilteredAndSortedDatabases;
    window.populateDatabaseSelect = UIRenderer.populateDatabaseSelect;
    window.updateStats = UIRenderer.updateStats;
    window.updateButtonStates = UIRenderer.updateButtonStates;
    window.updateDatabaseBadge = UIRenderer.updateDatabaseBadge;
    window.updateDatabaseTableCount = UIRenderer.updateDatabaseTableCount;
}


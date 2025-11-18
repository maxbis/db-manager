/**
 * Database Manager - JavaScript Module
 * Handles all client-side interactions for the database manager
 */

// Global state
let currentDatabase = '';
let databases = [];
let dbSearchQuery = '';
let dbSortMode = 'name_asc';

// Initialize
$(document).ready(function () {
    // Get current database from session first
    $.ajax({
        url: '../api/?action=getCurrentDatabase',
        method: 'GET',
        dataType: 'json',
        success: function (response) {
            if (response.success && response.database !== undefined && response.database !== null && response.database !== '') {
                currentDatabase = response.database;
                console.log('Restored current database from session:', currentDatabase);
            } else {
                console.log('No database in session to restore');
            }
            // Then load databases (which will select the current one if set)
            loadDatabases();
        },
        error: function (err) {
            console.error('Error getting current database:', err);
            // If error, just load databases without pre-selection
            loadDatabases();
        }
    });

    // Event handlers
    $('#refreshBtn').click(function () {
        loadDatabases();
    });

    // Removed legacy refreshTablesBtn handler (bottom table list removed)

    $('#databaseSelect').change(function () {
        currentDatabase = $(this).val();
        
        // Close any expanded databases when selecting from dropdown
        closeAllExpandedDatabases();
        
        // Update visual state of database items
        $('.database-item').removeClass('active');
        if (currentDatabase) {
            $(`.database-item[data-database="${currentDatabase}"]`).addClass('active');
        }
        
        if (currentDatabase) {
            // Update session cache so header shows correct database
            $.ajax({
                url: '../api/',
                method: 'POST',
                data: {
                    action: 'setCurrentDatabase',
                    database: currentDatabase
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        // Update the database badge in header without reload
                        updateDatabaseBadge(currentDatabase);
                    }
                }
            });

            updateButtonStates();
            // Immediately refresh stats so "Current DB" shows the new selection
            updateStats();
        } else {
            updateButtonStates();
            updateStats();
        }
    });

    $('#createDatabaseBtn').click(function () {
        openModal('createDatabaseModal');
    });

    // Removed legacy createTableBtn handler (bottom table list removed)

    // Create Table from menu item (same action)
    $('#createTableMenuItem').click(function(){
        if (!currentDatabase) return;
        openModal('createTableModal');
    });

    // Removed handlers for deleted buttons (#deleteDatabaseBtn, #deleteTableBtn)

    $('#exportDatabaseBtn').click(function () {
        if (currentDatabase) {
            openExportModal(currentDatabase);
        }
    });

    $('#importDatabaseBtn').click(function () {
        openModal('importDatabaseModal');
    });

    $('#exportAllDatabasesBtn').click(function () {
        openModal('exportAllDatabasesModal');
    });

    // Actions dropdown open/close (for stats section)
    (function(){
        const $dropdown = $('#statsActions');
        const $menu = $dropdown.find('.dropdown-menu');
        $dropdown.find('.dropdown-toggle').on('click', function(e){
            e.stopPropagation();
            // Close all database action dropdowns
            $('.db-actions-dropdown .dropdown-menu').removeClass('show');
            // Toggle stats dropdown
            $menu.toggleClass('show');
        });
    })();

    // Database actions dropdowns - delegated event handler
    $(document).on('click', '.db-actions-dropdown .dropdown-toggle', function(e){
        e.stopPropagation();
        const $this = $(this);
        const $menu = $this.siblings('.dropdown-menu');
        const isOpen = $menu.hasClass('show');
        
        // Close all dropdowns (including stats)
        $('#statsActions .dropdown-menu').removeClass('show');
        $('.db-actions-dropdown .dropdown-menu').removeClass('show');
        
        // Toggle this one
        if (!isOpen) {
            $menu.addClass('show');
        }
    });

    // Close all dropdowns when clicking anywhere on document
    $(document).on('click', function(){
        $('#statsActions .dropdown-menu').removeClass('show');
        $('.db-actions-dropdown .dropdown-menu').removeClass('show');
    });

    // Database dropdown menu item handlers
    $(document).on('click', '.db-create-table-btn', function(e){
        e.stopPropagation();
        const dbName = $(this).data('database');
        // Select the database first
        selectDatabase(dbName);
        // Then open the create table modal
        setTimeout(function() {
            openModal('createTableModal');
        }, 100);
        // Close the dropdown
        $('.db-actions-dropdown .dropdown-menu').removeClass('show');
    });

    $(document).on('click', '.db-export-btn', function(e){
        e.stopPropagation();
        const dbName = $(this).data('database');
        openExportModal(dbName);
        // Close the dropdown
        $('.db-actions-dropdown .dropdown-menu').removeClass('show');
    });

    $(document).on('click', '.db-delete-btn', function(e){
        e.stopPropagation();
        const dbName = $(this).data('database');
        deleteDatabase(dbName);
        // Close the dropdown
        $('.db-actions-dropdown .dropdown-menu').removeClass('show');
    });

    // Table rename button (per table row)
    $(document).on('click', '.table-rename-btn', function(e){
        e.stopPropagation();
        const $btn = $(this);
        const tableName = $btn.data('table');
        const databaseName = $btn.data('database') || currentDatabase;
        openRenameTableModal(tableName, databaseName);
    });

    $('#confirmCreateDatabaseBtn').click(function () {
        createDatabase();
    });

    $('#confirmCreateTableBtn').click(function () {
        // Build columns DDL from builder rows
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
            showToast('Please define at least one column with a name', 'warning');
            return;
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

        // Set the textarea value with the built columns
        $('#newTableColumns').val(lines.join(',\n'));

        createTable();
    });

    $('#confirmRenameTableBtn').click(function () {
        renameTable();
    });

    $('#confirmImportBtn').click(function () {
        importDatabase();
    });

    $('#confirmExportBtn').click(function () {
        exportDatabase();
    });

    $('#confirmExportAllBtn').click(function () {
        exportAllDatabases();
    });

    // Search & sort handlers
    const debouncedFilter = debounce(function(){
        dbSearchQuery = ($('#dbSearchInput').val() || '').toLowerCase();
        displayDatabases();
    }, 250);
    $('#dbSearchInput').on('input', debouncedFilter);
    $('#dbSortSelect').on('change', function(){
        dbSortMode = $(this).val();
        displayDatabases();
    });

    // Initialize column builder with one default row
    addColumnRow();
    $('#addColumnRowBtn').on('click', function(){ addColumnRow(); });

    // Drag & drop reordering for column rows
    enableDragAndDrop();

    // Close modal on outside click
    $(document).click(function (e) {
        if ($(e.target).hasClass('modal')) {
            closeModal($(e.target).attr('id'));
        }
    });
});

// Debounce helper
function debounce(fn, delay = 250){
    let t; return function(...args){ clearTimeout(t); t = setTimeout(() => fn.apply(this, args), delay); };
}

function enableDragAndDrop() {
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
        renumberColumnBadges();
    });

    container.addEventListener('dragover', function(e){
        e.preventDefault();
        const afterElement = getDragAfterElement(container, e.clientY);
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
}

function getDragAfterElement(container, y) {
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
}

function renumberColumnBadges(){
    $('#columnsBuilder .column-row .col-badge').each(function(i){
        const text = $(this).text().replace(/Column\s+\d+/, 'Column ' + (i+1));
        $(this).text(text);
    });
}

// Load all databases
function loadDatabases() {
    $('#loading').addClass('active');

    $.ajax({
        url: '../api/?action=getDatabases',
        method: 'GET',
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                databases = response.databases;
                displayDatabases();
                populateDatabaseSelect();
                
                // If there's a current database set from session, restore its state
                if (currentDatabase) {
                    console.log('Restoring state for currentDatabase:', currentDatabase);
                    
                    // Update visual state of database items
                    $('.database-item').removeClass('active');
                    const $currentDbItem = $(`.database-item[data-database="${currentDatabase}"]`);
                    $currentDbItem.addClass('active');
                    console.log('Active class added to:', $currentDbItem.length, 'items');
                    
                    // Update database badges
                    let badgesUpdated = 0;
                    $('.database-name').each(function() {
                        const $this = $(this);
                        const $badge = $this.find('.badge-current');
                        const itemDbName = $this.closest('.database-item').data('database');
                        if (itemDbName === currentDatabase && !$badge.length) {
                            $this.append('<span class="badge-current" title="Currently selected">Current</span>');
                            badgesUpdated++;
                        } else if (itemDbName !== currentDatabase && $badge.length) {
                            $badge.remove();
                        }
                    });
                    console.log('Badges updated:', badgesUpdated);
                    
                    // Auto-expand the current database's tables section
                    const expandIndicator = $currentDbItem.find('.expand-indicator');
                    const tablesSubsection = $(`.database-tables-subsection[data-database="${currentDatabase}"]`);
                    
                    if (expandIndicator.length && tablesSubsection.length) {
                        expandIndicator.addClass('expanded');
                        tablesSubsection.addClass('expanded');
                        
                        // Function to scroll database into view
                        const scrollToDatabase = function() {
                            // Use setTimeout to ensure DOM is fully updated
                            setTimeout(function() {
                                const elementOffset = $currentDbItem.offset().top;
                                const windowHeight = $(window).height();
                                const elementHeight = $currentDbItem.outerHeight();
                                const scrollPosition = elementOffset - (windowHeight / 2) + (elementHeight / 2);
                                
                                // Smooth scroll to center the database item in viewport
                                $('html, body').animate({
                                    scrollTop: scrollPosition
                                }, 500);
                            }, 100);
                        };
                        
                        // Load tables if not already loaded
                        const tablesGrid = tablesSubsection.find('.database-tables-grid');
                        if (tablesGrid.children().length === 0) {
                            loadTablesForDatabase(currentDatabase, function(tables) {
                                displayTablesInSubsection(currentDatabase, tables);
                                scrollToDatabase();
                            });
                        } else {
                            // Tables already loaded, just show the subsection
                            tablesSubsection.show();
                            scrollToDatabase();
                        }
                    }
                    
                    updateButtonStates();
                } else {
                    console.log('No currentDatabase set, skipping state restoration');
                    // Ensure all databases are collapsed when no current database
                    closeAllExpandedDatabases();
                }
                
                // Update stats AFTER setting the visual state
                updateStats();
            }
            $('#loading').removeClass('active');
            $('#dashboardContent').show();
            $('#emptyState').hide();
        },
        error: function (xhr) {
            showToast('Error loading databases: ' + xhr.responseText, 'error');
            $('#loading').removeClass('active');
        }
    });
}

// Load tables for current database (kept for internal state; no bottom list UI)
function loadTables() {
    if (!currentDatabase) return;

    $.ajax({
        url: '../api/?action=getTables&database=' + encodeURIComponent(currentDatabase),
        method: 'GET',
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                tables = response.tables;
                // No longer updating #tableListSection or #currentDatabaseName
                // Bottom table list has been removed; tables are shown per-database subsection
            }
        },
        error: function (xhr) {
            showToast('Error loading tables: ' + xhr.responseText, 'error');
        }
    });
}

// Load tables for a specific database (for expand/collapse functionality)
function loadTablesForDatabase(databaseName, callback) {
    $.ajax({
        url: '../api/?action=getTables&database=' + encodeURIComponent(databaseName),
        method: 'GET',
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                if (typeof callback === 'function') {
                    callback(response.tables);
                }
            }
        },
        error: function (xhr) {
            showToast('Error loading tables for ' + databaseName + ': ' + xhr.responseText, 'error');
        }
    });
}

// Toggle database tables expand/collapse
function toggleDatabaseTables(databaseName) {
    const expandIndicator = $(`.expand-indicator[aria-label*="${databaseName}"]`);
    const tablesSubsection = $(`.database-tables-subsection[data-database="${databaseName}"]`);
    
    if (tablesSubsection.hasClass('expanded')) {
        // Collapse this database
        tablesSubsection.slideUp(200, function() {
            // Remove class after animation completes
            $(this).removeClass('expanded');
        });
        expandIndicator.removeClass('expanded');
    } else {
        // Close any other expanded databases first (but not this one)
        closeAllExpandedDatabases(databaseName);
        
        // Expand the selected database
        expandIndicator.addClass('expanded');
        tablesSubsection.addClass('expanded');
        
        // Select this database as the current database (without triggering change event)
        currentDatabase = databaseName;
        $('#databaseSelect').val(databaseName);
        
        // Update visual state manually
        $('.database-item').removeClass('active');
        $(`.database-item[data-database="${databaseName}"]`).addClass('active');
        
        // Update database badges manually
        $('.database-name').each(function() {
            const $this = $(this);
            const $badge = $this.find('.badge-current');
            const itemDbName = $this.closest('.database-item').data('database');
            if (itemDbName === databaseName && !$badge.length) {
                $this.append('<span class="badge-current" title="Currently selected">Current</span>');
            } else if (itemDbName !== databaseName && $badge.length) {
                $badge.remove();
            }
        });
        
        // Update session cache
        $.ajax({
            url: '../api/',
            method: 'POST',
            data: {
                action: 'setCurrentDatabase',
                database: databaseName
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    updateDatabaseBadge(databaseName);
                }
            }
        });
        
        // Load tables if not already loaded
        const tablesGrid = tablesSubsection.find('.database-tables-grid');
        if (tablesGrid.children().length === 0) {
            loadTablesForDatabase(databaseName, function(tables) {
                displayTablesInSubsection(databaseName, tables);
            });
        } else {
            tablesSubsection.slideDown(200);
        }
        
        // Update button states and stats
        updateButtonStates();
        updateStats();
    }
}

// Close all expanded databases (optionally exclude a specific database)
function closeAllExpandedDatabases(excludeDatabase = null) {
    $('.database-tables-subsection.expanded').each(function() {
        const databaseName = $(this).data('database');
        
        // Skip the excluded database
        if (excludeDatabase && databaseName === excludeDatabase) {
            return;
        }
        
        const expandIndicator = $(`.expand-indicator[aria-label*="${databaseName}"]`);
        
        $(this).slideUp(200, function() {
            // Remove class after animation completes
            $(this).removeClass('expanded');
        });
        expandIndicator.removeClass('expanded');
    });
}

// Display tables in the subsection
function displayTablesInSubsection(databaseName, tables) {
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
        tables.forEach(function (table) {
            const tableName = typeof table === 'string' ? table : table.name;
            const tableType = typeof table === 'object' ? table.type : 'BASE TABLE';
            const tableSize = typeof table === 'object' ? (table.size || 0) : 0;
            const isView = tableType === 'VIEW';
            const tableIcon = isView ? '👁️' : '📋';
            const sizePercent = Math.max(4, Math.round((tableSize / maxSize) * 100));
            const displaySize = formatBytes(tableSize || 0);
            
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
                            : `<button class="btn-danger table-action-btn" onclick=\"event.stopPropagation(); deleteTable('${tableName}', '${databaseName}', true)\" title=\"Delete table\">Del. 🗑️</button>`}
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
                console.log('Clicking on table:', tableName, databaseName);
                // Don't trigger if clicking on buttons
                if ($(e.target).is('button') || $(e.target).closest('button').length) {
                    return;
                }
                e.stopPropagation(); // Prevent event bubbling
                viewTableData(tableName, databaseName);
            });
            
            tablesGrid.append(tableItem);
        });
    }
    
    // Show the subsection with animation
    $(`.database-tables-subsection[data-database="${databaseName}"]`).slideDown(200);
}

// Display databases list (with search + sort + better progress)
function displayDatabases() {
    const databaseList = $('#databaseList');
    databaseList.empty();

    let list = getFilteredAndSortedDatabases();

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

    list.forEach(function (db) {
        const isCurrent = db.name === currentDatabase;
        const sizeBytes = db.size || 0;
        const sizePercent = Math.max(4, Math.round((sizeBytes / maxSize) * 100));
        const displaySize = formatBytes(sizeBytes);
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
            // Don't select if clicking on buttons, dropdown, or expand indicator
            if ($(e.target).is('button') || 
                $(e.target).closest('button').length || 
                $(e.target).closest('.actions-dropdown').length ||
                $(e.target).hasClass('expand-indicator')) {
                return;
            }
            selectDatabase(db.name);
        });

        // Keyboard support: Enter/Space to select
        databaseItem.on('keydown', function(e){
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); selectDatabase(db.name); }
        });

        // Expand indicator click handler
        databaseItem.find('.expand-indicator').on('click', function(e){
            e.stopPropagation(); // Prevent database selection
            toggleDatabaseTables(db.name);
        });

        databaseList.append(databaseItem);
    });
}

function getFilteredAndSortedDatabases(){
    let result = databases.slice();
    if (dbSearchQuery) {
        result = result.filter(db => (db.name || '').toLowerCase().includes(dbSearchQuery));
    }
    const byName = (a,b) => (a.name || '').localeCompare(b.name || '');
    const bySize = (a,b) => (b.size || 0) - (a.size || 0);
    const byTables = (a,b) => (b.tables || 0) - (a.tables || 0);
    switch (dbSortMode) {
        case 'name_desc': result.sort((a,b)=>byName(b,a)); break;
        case 'size_desc': result.sort(bySize); break;
        case 'size_asc': result.sort((a,b)=>-bySize(a,b)); break;
        case 'tables_desc': result.sort(byTables); break;
        case 'tables_asc': result.sort((a,b)=>-byTables(a,b)); break;
        default: result.sort(byName);
    }
    return result;
}

// displayTables removed with bottom table list; tables are shown per database subsection

// Add a column row to the builder
function addColumnRow() {
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
                <label style="display:flex; align-items:center; gap:6px;">
                    <input type="checkbox" class="col-null"> NULL
                </label>
                <label style="display:flex; align-items:center; gap:6px;">
                    <input type="checkbox" class="col-ai"> AI
                </label>
            </div>
            <div class="row-line" style="margin-top:6px;">
                <label style="display:flex; align-items:center; gap:6px;">
                    <input type="checkbox" class="col-primary"> 🔑 PRIMARY KEY
                </label>
                <label style="display:flex; align-items:center; gap:6px;">
                    <input type="checkbox" class="col-index"> 📇 INDEX
                </label>
                <label style="display:flex; align-items:center; gap:6px;">
                    <input type="checkbox" class="col-unique"> ✨ UNIQUE
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

    row.find('.remove-col').on('click', function(){ row.remove(); });

    $('#columnsBuilder .column-rows').append(row);
}

// Populate database select dropdown
function populateDatabaseSelect() {
    const select = $('#databaseSelect');
    select.empty();
    select.append('<option value="">-- Select a database --</option>');

    databases.forEach(function (db) {
        const selected = db.name === currentDatabase ? 'selected' : '';
        select.append(`<option value="${db.name}" ${selected}>${db.name}</option>`);
    });
}

// Update statistics
function updateStats() {
    const statsGrid = $('#statsGrid');
    const totalDatabases = databases.length;
    const totalTables = databases.reduce((sum, db) => sum + (db.tables || 0), 0);
    const totalSize = databases.reduce((sum, db) => sum + (db.size || 0), 0);

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
            <div class="stat-value">${formatBytes(totalSize)}</div>
            <div class="stat-label">Total Size</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">${currentDatabase || 'None'}</div>
            <div class="stat-label">Current DB</div>
        </div>
    `);
}

// Update button states based on current selection
function updateButtonStates() {
    const hasDatabase = !!currentDatabase;
    // Legacy bottom table list removed; keep only database-level controls

    $('#createTableMenuItem').prop('disabled', !hasDatabase);

    $('#exportDatabaseBtn').prop('disabled', !hasDatabase);

    $('#importDatabaseBtn').prop('disabled', false); // Can always import
}

// Select database
function selectDatabase(databaseName) {
    currentDatabase = databaseName;
    $('#databaseSelect').val(databaseName).trigger('change');
    
    // Update visual state of database items
    $('.database-item').removeClass('active');
    $(`.database-item[data-database="${databaseName}"]`).addClass('active');
    
    // Update database badges
    $('.database-name').each(function() {
        const $this = $(this);
        const $badge = $this.find('.badge-current');
        const itemDbName = $this.closest('.database-item').data('database');
        if (itemDbName === databaseName && !$badge.length) {
            $this.append('<span class="badge-current" title="Currently selected">Current</span>');
        } else if (itemDbName !== databaseName && $badge.length) {
            $badge.remove();
        }
    });
}

// Select table
function selectTable(tableName) {
    selectedTable = tableName;

    // Update visual selection
    $('.table-item').removeClass('selected');
    $(`.table-item[data-table="${tableName}"]`).addClass('selected');

    updateButtonStates();
}

// Update database badge in header
function updateDatabaseBadge(databaseName, tableName = '') {
    // Find the database badge in the header and update it
    const databaseBadge = document.querySelector('.control-group span span');
    if (databaseBadge) {
        let displayText = '🗄️ ' + databaseName;
        if (tableName) {
            displayText += ' -  ' + tableName;
        }
        databaseBadge.textContent = displayText;
    }
}

// View table (navigate to table structure page)
function viewTableStructure(tableName, databaseName = null) {
    const dbName = databaseName || currentDatabase;
    
    // Set both database and table in session before navigating
    $.ajax({
        url: '../api/',
        method: 'POST',
        data: {
            action: 'setCurrentDatabase',
            database: dbName
        },
        dataType: 'json',
        success: function() {
            // Set the current table in session
            $.ajax({
                url: '../api/',
                method: 'POST',
                data: {
                    action: 'setCurrentTable',
                    table: tableName
                },
                dataType: 'json',
                success: function() {
                    // Update the database badge to show database.table before navigating
                    updateDatabaseBadge(dbName, tableName);
                    window.location.href = '../table_structure/';
                },
                error: function() {
                    // If setting table fails, still navigate
                    updateDatabaseBadge(dbName, tableName);
                    window.location.href = '../table_structure/';
                }
            });
        },
        error: function() {
            // If setting database fails, still navigate (API will handle database selection)
            updateDatabaseBadge(dbName, tableName);
            window.location.href = '../table_structure/';
        }
    });
}

// View table (navigate to table data page)
function viewTableData(tableName, databaseName = null) {
    const dbName = databaseName || currentDatabase;
    
    // Set both database and table in session before navigating
    $.ajax({
        url: '../api/',
        method: 'POST',
        data: {
            action: 'setCurrentDatabase',
            database: dbName
        },
        dataType: 'json',
        success: function() {
            // Set the current table in session
            $.ajax({
                url: '../api/',
                method: 'POST',
                data: {
                    action: 'setCurrentTable',
                    table: tableName
                },
                dataType: 'json',
                success: function() {
                    // Update the database badge to show database.table before navigating
                    updateDatabaseBadge(dbName, tableName);
                    // Always include URL params as fallback even when session is set
                    window.location.href = `../data_manager/?table=${encodeURIComponent(tableName)}&database=${encodeURIComponent(dbName)}`;
                },
                error: function() {
                    // If setting table fails, still navigate with URL parameter as fallback
                    updateDatabaseBadge(dbName, tableName);
                    window.location.href = `../data_manager/?table=${encodeURIComponent(tableName)}&database=${encodeURIComponent(dbName)}`;
                }
            });
        },
        error: function() {
            // If setting database fails, still navigate with URL parameters as fallback
            updateDatabaseBadge(dbName, tableName);
            window.location.href = `../data_manager/?table=${encodeURIComponent(tableName)}&database=${encodeURIComponent(dbName)}`;
        }
    });
}

// Delete table (consolidated function)
function deleteTable(tableName, databaseName = null, fromSubsection = false) {
    const dbName = databaseName || currentDatabase;
    
    showConfirmDialog({
        title: 'Delete Table',
        message: `Are you sure you want to delete the table "${tableName}" from database "${dbName}"? This action cannot be undone!`,
        confirmText: 'Delete',
        confirmClass: 'btn-danger'
    }, function onConfirm() {
        $.ajax({
            url: '../api/',
            method: 'POST',
            data: {
                action: 'deleteTable',
                database: dbName,
                name: tableName
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    showToast('Table deleted successfully!', 'success');
                    
                    if (fromSubsection) {
                        // Remove the table from the subsection
                        $(`.database-table-item[data-table="${tableName}"]`).remove();
                        // Update the table count in the database item
                        updateDatabaseTableCount(dbName);
                    } else {
                        // Clear selection if the deleted table was selected
                        if (selectedTable === tableName) {
                            selectedTable = '';
                        }
                        loadTables();
                    }
                    
                    // Refresh the main database list stats
                    loadDatabases();
                } else {
                    showToast('Error: ' + response.error, 'error');
                }
            },
            error: function (xhr) {
                const response = JSON.parse(xhr.responseText);
                showToast('Error: ' + (response.error || 'Unknown error'), 'error');
            }
        });
    });
}

// Open rename table modal
function openRenameTableModal(tableName, databaseName = null) {
    const dbName = databaseName || currentDatabase;
    if (!dbName) {
        showToast('Please select a database first', 'warning');
        return;
    }

    $('#renameTableDatabase').val(dbName);
    $('#renameTableCurrentName').val(tableName);
    $('#renameTableNewName').val(tableName);

    // Reset button state in case of previous errors
    $('#confirmRenameTableBtn').prop('disabled', false).text('💾 Rename');

    openModal('renameTableModal');

    // Focus the input after modal opens
    setTimeout(function() {
        const input = document.getElementById('renameTableNewName');
        if (input) {
            input.focus();
            input.select();
        }
    }, 100);
}

// Rename table logic
function renameTable() {
    const databaseName = ($('#renameTableDatabase').val() || '').trim() || currentDatabase;
    const oldName = ($('#renameTableCurrentName').val() || '').trim();
    const newName = ($('#renameTableNewName').val() || '').trim();

    if (!databaseName || !oldName) {
        showToast('Missing table information. Please try again.', 'error');
        return;
    }

    if (!newName) {
        showToast('Please enter a new table name', 'warning');
        return;
    }

    if (!/^[a-zA-Z0-9_]+$/.test(newName)) {
        showToast('Table names can only contain letters, numbers, and underscores', 'warning');
        return;
    }

    if (newName === oldName) {
        showToast('Please enter a different table name', 'warning');
        return;
    }

    $('#confirmRenameTableBtn').prop('disabled', true).text('⏳ Renaming...');

    $.ajax({
        url: '../api/',
        method: 'POST',
        data: {
            action: 'renameTable',
            database: databaseName,
            oldName: oldName,
            newName: newName
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showToast('Table renamed successfully!', 'success');
                closeModal('renameTableModal');

                // Ensure the renamed table's database stays selected
                currentDatabase = databaseName;

                // Refresh database list to reflect new name
                loadDatabases();
            } else {
                showToast('Error: ' + response.error, 'error');
            }
        },
        error: function(xhr) {
            let errorMessage = 'Unknown error';
            try {
                const response = JSON.parse(xhr.responseText);
                errorMessage = response.error || 'Unknown error';
            } catch (e) {
                errorMessage = xhr.responseText || 'Unknown error';
            }
            showToast('Error: ' + errorMessage, 'error');
        },
        complete: function() {
            $('#confirmRenameTableBtn').prop('disabled', false).text('💾 Rename');
        }
    });
}

// Update table count in database item after table deletion
function updateDatabaseTableCount(databaseName) {
    const databaseItem = $(`.database-item[data-database="${databaseName}"]`);
    const tablesCount = $(`.database-table-item[data-database="${databaseName}"]`).length;
    databaseItem.find('.database-tables').text(tablesCount + ' tables');
}

// Export all databases
function exportAllDatabases() {
    const filename = $('#exportAllFilename').val().trim();
    const includeCreateDatabase = $('#exportAllIncludeCreateDatabase').is(':checked');
    const dataOnly = $('#exportAllDataOnly').is(':checked');

    if (!filename) {
        showToast('Please enter a filename', 'error');
        return;
    }

    // Show loading state
    $('#confirmExportAllBtn').prop('disabled', true).text('📦 Exporting...');

    // Close modal immediately
    closeModal('exportAllDatabasesModal');

    // Create a form to submit the request
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '../api/';
    // Remove target='_blank' to stay in same window

    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'exportAllDatabases';

    const filenameInput = document.createElement('input');
    filenameInput.type = 'hidden';
    filenameInput.name = 'filename';
    filenameInput.value = filename;

    const includeCreateInput = document.createElement('input');
    includeCreateInput.type = 'hidden';
    includeCreateInput.name = 'includeCreateDatabase';
    includeCreateInput.value = includeCreateDatabase ? 'true' : 'false';

    const dataOnlyInput = document.createElement('input');
    dataOnlyInput.type = 'hidden';
    dataOnlyInput.name = 'dataOnly';
    dataOnlyInput.value = dataOnly ? 'true' : 'false';

    form.appendChild(actionInput);
    form.appendChild(filenameInput);
    form.appendChild(includeCreateInput);
    form.appendChild(dataOnlyInput);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);

    // Reset button after a delay
    setTimeout(() => {
        $('#confirmExportAllBtn').prop('disabled', false).text('📦 Export All');
    }, 2000);
}

// Create database
function createDatabase() {
    const name = $('#newDatabaseName').val().trim();
    const charset = $('#newDatabaseCharset').val();
    const collation = $('#newDatabaseCollation').val();

    if (!name) {
        showToast('Please enter a database name', 'warning');
        return;
    }

    $.ajax({
        url: '../api/',
        method: 'POST',
        data: {
            action: 'createDatabase',
            name: name,
            charset: charset,
            collation: collation
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                showToast('Database created successfully!', 'success');
                closeModal('createDatabaseModal');
                loadDatabases();
            } else {
                showToast('Error: ' + response.error, 'error');
            }
        },
        error: function (xhr) {
            const response = JSON.parse(xhr.responseText);
            showToast('Error: ' + (response.error || 'Unknown error'), 'error');
        }
    });
}

// Create table
function createTable() {
    const name = $('#newTableName').val().trim();
    const columns = $('#newTableColumns').val().trim();
    const engine = $('#newTableEngine').val();

    if (!name || !columns) {
        showToast('Please enter table name and columns', 'warning');
        return;
    }

    $.ajax({
        url: '../api/',
        method: 'POST',
        data: {
            action: 'createTable',
            database: currentDatabase,
            name: name,
            columns: columns,
            engine: engine
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                showToast('Table created successfully!', 'success');
                closeModal('createTableModal');
                loadTables();
                loadDatabases(); // Refresh stats
            } else {
                showToast('Error: ' + response.error, 'error');
            }
        },
        error: function (xhr) {
            let errorMessage = 'Unknown error';
            try {
                const response = JSON.parse(xhr.responseText);
                errorMessage = response.error || 'Unknown error';
            } catch (e) {
                // If JSON parsing fails, use the raw response text
                errorMessage = xhr.responseText || 'Unknown error';
            }
            showToast('Error: ' + errorMessage, 'error');
        }
    });
}

// Delete database (with custom confirm modal)
function deleteDatabase(databaseName) {
    showConfirmDialog({
        title: 'Delete Database',
        message: `Are you sure you want to delete the database "${databaseName}"? This action cannot be undone!`,
        confirmText: 'Delete',
        confirmClass: 'btn-danger'
    }, function onConfirm() {
        $.ajax({
            url: '../api/',
            method: 'POST',
            data: {
                action: 'deleteDatabase',
                name: databaseName
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    showToast('Database deleted successfully!', 'success');
                    if (currentDatabase === databaseName) {
                        currentDatabase = '';
                        $('#databaseSelect').val('').trigger('change');
                    }
                    loadDatabases();
                } else {
                    showToast('Error: ' + response.error, 'error');
                }
            },
            error: function (xhr) {
                const response = JSON.parse(xhr.responseText);
                showToast('Error: ' + (response.error || 'Unknown error'), 'error');
            }
        });
    });
}


// Reusable confirm dialog helper
function showConfirmDialog(options, onConfirm) {
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
        closeModal('confirmActionModal');
    });
    $confirmBtn.on('click', function () {
        closeModal('confirmActionModal');
        if (typeof onConfirm === 'function') {
            onConfirm();
        }
    });

    // Open
    openModal('confirmActionModal');
}

// Open export modal
function openExportModal(databaseName) {
    $('#exportDatabaseName').val(databaseName);
    $('#exportFileName').val(`${databaseName}_export_${new Date().toISOString().split('T')[0]}`);
    $('#exportCreateDatabase').prop('checked', true);
    $('#exportDataOnly').prop('checked', false);
    openModal('exportDatabaseModal');
}

// Export database
function exportDatabase() {
    const databaseName = $('#exportDatabaseName').val();
    const fileName = $('#exportFileName').val().trim();
    const includeCreateDatabase = $('#exportCreateDatabase').is(':checked');
    const dataOnly = $('#exportDataOnly').is(':checked');

    if (!fileName) {
        showToast('Please enter a file name', 'warning');
        return;
    }

    showToast('Exporting database...', 'warning');

    $.ajax({
        url: '../api/',
        method: 'POST',
        data: {
            action: 'exportDatabase',
            name: databaseName,
            includeCreateDatabase: includeCreateDatabase,
            dataOnly: dataOnly
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                // Create download link
                const blob = new Blob([response.sql], { type: 'application/sql' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = fileName.endsWith('.sql') ? fileName : `${fileName}.sql`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);

                showToast('Database exported successfully!', 'success');
                closeModal('exportDatabaseModal');
            } else {
                showToast('Error: ' + response.error, 'error');
            }
        },
        error: function (xhr) {
            const response = JSON.parse(xhr.responseText);
            showToast('Error: ' + (response.error || 'Unknown error'), 'error');
        }
    });
}

// Import database
function importDatabase() {
    const fileInput = document.getElementById('importFile');
    const file = fileInput.files[0];
    const targetDatabase = $('#importTargetDatabase').val();
    const dropExisting = $('#importDropExisting').is(':checked');

    if (!file) {
        showToast('Please select a SQL file', 'warning');
        return;
    }

    if (!targetDatabase) {
        showToast('Please select a target database', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'importDatabase');
    formData.append('file', file);
    formData.append('database', targetDatabase);
    formData.append('dropExisting', dropExisting);

    showToast('Importing database...', 'warning');

    $.ajax({
        url: '../api/',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                showToast('Database imported successfully!', 'success');
                closeModal('importDatabaseModal');
                loadDatabases();
            } else {
                showToast('Error: ' + response.error, 'error');
            }
        },
        error: function (xhr) {
            const response = JSON.parse(xhr.responseText);
            showToast('Error: ' + (response.error || 'Unknown error'), 'error');
        }
    });
}

// Modal functions
function openModal(modalId) {
    $('#' + modalId).addClass('active');

    // Populate import target database dropdown
    if (modalId === 'importDatabaseModal') {
        const select = $('#importTargetDatabase');
        select.empty();
        select.append('<option value="">-- Select database --</option>');
        databases.forEach(function (db) {
            select.append(`<option value="${db.name}">${db.name}</option>`);
        });
    }
}

function closeModal(modalId) {
    $('#' + modalId).removeClass('active');

    // Clear form fields
    if (modalId === 'createDatabaseModal') {
        $('#newDatabaseName').val('');
    } else if (modalId === 'createTableModal') {
        $('#newTableName').val('');
        $('#newTableColumns').val('');
        // Clear and reset column builder
        $('#columnsBuilder .column-rows').empty();
        addColumnRow(); // Add one fresh empty row
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
}

// Show toast notification
function showToast(message, type = 'success') {
    const toast = $('#toast');
    toast.text(message);
    toast.removeClass('success error warning');
    toast.addClass(type);
    toast.addClass('active');

    setTimeout(function () {
        toast.removeClass('active');
    }, 4000);
}

// Format bytes to human readable
function formatBytes(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Smooth page transitions
$('.nav-link').click(function (e) {
    const href = $(this).attr('href');

    // Don't apply transition if it's the current page
    if ($(this).hasClass('active')) {
        e.preventDefault();
        return;
    }

    e.preventDefault();
    $('body').addClass('page-transitioning');

    // Navigate after fade out
    setTimeout(function () {
        window.location.href = href;
    }, 200);
});


/**
 * Database Sync - Client-side JavaScript
 * 
 * Handles sync operations, progress tracking, and cookie management
 */

// Cookie management
const COOKIE_PREFIX = 'db_sync_';
const COOKIE_EXPIRY_DAYS = 365;

/**
 * Set a cookie
 */
function setCookie(name, value, days = COOKIE_EXPIRY_DAYS) {
    const date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    const expires = "expires=" + date.toUTCString();
    document.cookie = COOKIE_PREFIX + name + "=" + encodeURIComponent(value) + ";" + expires + ";path=/";
}

/**
 * Get a cookie value
 */
function getCookie(name) {
    const cookieName = COOKIE_PREFIX + name + "=";
    const decodedCookie = decodeURIComponent(document.cookie);
    const cookieArray = decodedCookie.split(';');
    
    for (let i = 0; i < cookieArray.length; i++) {
        let cookie = cookieArray[i].trim();
        if (cookie.indexOf(cookieName) === 0) {
            return cookie.substring(cookieName.length, cookie.length);
        }
    }
    return "";
}

/**
 * Save form values to cookies
 */
function saveFormToCookies() {
    const form = document.getElementById('syncForm');
    const inputs = form.querySelectorAll('input, select');
    
    inputs.forEach(input => {
        if (input.type !== 'password') {  // Don't save passwords and API keys
            setCookie(input.name, input.value);
        }
    });
}

/**
 * Load form values from cookies
 */
function loadFormFromCookies() {
    const form = document.getElementById('syncForm');
    const inputs = form.querySelectorAll('input, select');
    
    inputs.forEach(input => {
        const savedValue = getCookie(input.name);
        if (savedValue && input.type !== 'password') {
            input.value = savedValue;
        }
    });
}

/**
 * Clear all saved form cookies
 */
function clearFormCookies() {
    const form = document.getElementById('syncForm');
    const inputs = form.querySelectorAll('input, select');
    
    inputs.forEach(input => {
        setCookie(input.name, '', -1); // Set expiry to past date to delete
    });
}

/**
 * Add log entry to the log container
 */
function addLog(message, type = 'info') {
    const logContainer = document.getElementById('logContainer');
    const time = new Date().toLocaleTimeString();
    
    const logEntry = document.createElement('div');
    logEntry.className = 'log-entry';
    
    const timeSpan = document.createElement('span');
    timeSpan.className = 'log-time';
    timeSpan.textContent = `[${time}]`;
    
    const messageSpan = document.createElement('span');
    messageSpan.className = `log-${type}`;
    messageSpan.textContent = message;
    
    logEntry.appendChild(timeSpan);
    logEntry.appendChild(messageSpan);
    logContainer.appendChild(logEntry);
    
    // Auto-scroll to bottom
    logContainer.scrollTop = logContainer.scrollHeight;
}

/**
 * Update progress bar
 */
function updateProgress(percent, text) {
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    
    progressFill.style.width = percent + '%';
    progressFill.textContent = Math.round(percent) + '%';
    progressText.textContent = text;
}

/**
 * Update statistics
 */
function updateStats(stats) {
    document.getElementById('statTables').textContent = stats.tables || 0;
    document.getElementById('statRows').textContent = (stats.rows || 0).toLocaleString();
    document.getElementById('statViews').textContent = stats.views || 0;
    document.getElementById('statTime').textContent = stats.time || '0s';
    
    const statsGrid = document.getElementById('statsGrid');
    statsGrid.style.display = 'grid';
}

/**
 * Make API request to remote server
 */
async function apiRequest(url, apiKey, action, params = {}) {
    const formData = new FormData();
    formData.append('action', action);
    
    // Add all parameters
    for (const [key, value] of Object.entries(params)) {
        formData.append(key, value);
    }
    
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'X-API-Key': apiKey
        },
        body: formData
    });
    
    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();
    
    if (!data.success) {
        throw new Error(data.message || 'API request failed');
    }
    
    return data.data;
}

/**
 * Execute SQL query on local database
 */
async function executeLocalSQL(sql, dbName = null) {
    const formData = new FormData();
    formData.append('action', 'execute_sql');
    formData.append('sql', sql);
    if (dbName) {
        formData.append('database', dbName);
    }
    
    const response = await fetch('sync_handler.php', {
        method: 'POST',
        body: formData
    });
    
    const data = await response.json();
    
    if (!data.success) {
        throw new Error(data.message || 'SQL execution failed');
    }
    
    return data.data;
}

/**
 * Test connection to remote server
 */
async function testConnection() {
    const form = document.getElementById('syncForm');
    const formData = new FormData(form);
    
    const btn = document.getElementById('testConnectionBtn');
    btn.disabled = true;
    btn.innerHTML = '<span>⏳</span><span>Testing...</span>';
    
    try {
        const params = {
            db_host: formData.get('remoteDbHost'),
            db_user: formData.get('remoteDbUser'),
            db_pass: formData.get('remoteDbPass'),
            db_name: formData.get('remoteDbName')
        };
        
        const data = await apiRequest(
            formData.get('remoteUrl'),
            formData.get('apiKey'),
            'get_tables',
            params
        );
        
        alert(`✅ Connection successful!\n\nFound ${data.tables.length} tables in database "${formData.get('remoteDbName')}"`);
        
    } catch (error) {
        alert(`❌ Connection failed!\n\n${error.message}`);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<span>🔌</span><span>Test Connection</span>';
    }
}

/**
 * Main sync function
 */
async function startSync() {
    const form = document.getElementById('syncForm');
    const formData = new FormData(form);
    
    // Save form to cookies
    saveFormToCookies();
    
    // Get form values
    const config = {
        remoteUrl: formData.get('remoteUrl'),
        apiKey: formData.get('apiKey'),
        remoteDbHost: formData.get('remoteDbHost'),
        remoteDbUser: formData.get('remoteDbUser'),
        remoteDbPass: formData.get('remoteDbPass'),
        remoteDbName: formData.get('remoteDbName'),
        localDbName: formData.get('localDbName'),
        chunkSize: parseInt(formData.get('chunkSize'))
    };
    
    // Show progress card
    const progressCard = document.getElementById('progressCard');
    progressCard.classList.add('active');
    
    // Clear previous logs
    document.getElementById('logContainer').innerHTML = '';
    
    // Disable form
    const syncBtn = document.getElementById('syncBtn');
    syncBtn.disabled = true;
    syncBtn.innerHTML = '<span>⏳</span><span>Syncing...</span>';
    
    // Track stats
    const stats = {
        tables: 0,
        rows: 0,
        views: 0,
        procedures: 0,
        functions: 0,
        triggers: 0,
        startTime: Date.now()
    };
    
    try {
        addLog('🚀 Starting database sync...', 'info');
        updateProgress(0, 'Initializing...');
        
        // Step 1: Create local database if it doesn't exist
        addLog(`📦 Creating/checking local database: ${config.localDbName}`, 'info');
        await executeLocalSQL(`CREATE DATABASE IF NOT EXISTS \`${config.localDbName}\``);
        updateProgress(5, 'Database created');
        
        // Step 2: Get list of tables from remote
        addLog('📋 Fetching table list from remote server...', 'info');
        const params = {
            db_host: config.remoteDbHost,
            db_user: config.remoteDbUser,
            db_pass: config.remoteDbPass,
            db_name: config.remoteDbName
        };
        
        const tablesData = await apiRequest(config.remoteUrl, config.apiKey, 'get_tables', params);
        const tables = tablesData.tables;
        stats.tables = tables.length;
        addLog(`✅ Found ${tables.length} tables`, 'success');
        updateProgress(10, `Found ${tables.length} tables`);
        
        // Step 3: Sync each table
        let tableProgress = 10;
        const tableProgressIncrement = 60 / tables.length; // 60% for tables
        
        for (let i = 0; i < tables.length; i++) {
            const table = tables[i];
            addLog(`📊 Syncing table ${i + 1}/${tables.length}: ${table}`, 'info');
            
            // Get table structure
            const structureData = await apiRequest(config.remoteUrl, config.apiKey, 'get_table_structure', {
                ...params,
                table: table
            });
            
            // Drop table if exists and recreate
            await executeLocalSQL(`DROP TABLE IF EXISTS \`${table}\``, config.localDbName);
            await executeLocalSQL(structureData.create_statement, config.localDbName);
            addLog(`  ✓ Created structure for ${table}`, 'success');
            
            // Get table data in chunks
            let offset = 0;
            let hasMore = true;
            let tableRows = 0;
            
            while (hasMore) {
                const dataResult = await apiRequest(config.remoteUrl, config.apiKey, 'get_table_data', {
                    ...params,
                    table: table,
                    offset: offset,
                    limit: config.chunkSize
                });
                
                if (dataResult.data.length > 0) {
                    // Insert data
                    const rows = dataResult.data;
                    
                    // Build INSERT statement
                    const columns = Object.keys(rows[0]);
                    const values = rows.map(row => {
                        const vals = columns.map(col => {
                            const val = row[col];
                            if (val === null) return 'NULL';
                            return "'" + String(val).replace(/'/g, "''") + "'";
                        });
                        return '(' + vals.join(', ') + ')';
                    });
                    
                    const insertSQL = `INSERT INTO \`${table}\` (\`${columns.join('`, `')}\`) VALUES ${values.join(', ')}`;
                    await executeLocalSQL(insertSQL, config.localDbName);
                    
                    tableRows += rows.length;
                    stats.rows += rows.length;
                    addLog(`  ✓ Inserted ${rows.length} rows (total: ${tableRows}/${dataResult.total_rows})`, 'success');
                }
                
                offset += config.chunkSize;
                hasMore = dataResult.has_more;
            }
            
            tableProgress += tableProgressIncrement;
            updateProgress(tableProgress, `Synced ${i + 1}/${tables.length} tables`);
            updateStats({
                ...stats,
                time: Math.round((Date.now() - stats.startTime) / 1000) + 's'
            });
        }
        
        // Step 4: Sync views
        addLog('👁️ Syncing views...', 'info');
        const viewsData = await apiRequest(config.remoteUrl, config.apiKey, 'get_views', params);
        const views = viewsData.views;
        stats.views = views.length;
        
        for (const view of views) {
            const viewStructure = await apiRequest(config.remoteUrl, config.apiKey, 'get_view_structure', {
                ...params,
                view: view
            });
            
            await executeLocalSQL(`DROP VIEW IF EXISTS \`${view}\``, config.localDbName);
            await executeLocalSQL(viewStructure.create_statement, config.localDbName);
            addLog(`  ✓ Created view: ${view}`, 'success');
        }
        updateProgress(75, `Synced ${views.length} views`);
        
        // Step 5: Sync stored procedures
        addLog('⚙️ Syncing stored procedures...', 'info');
        const proceduresData = await apiRequest(config.remoteUrl, config.apiKey, 'get_procedures', params);
        const procedures = proceduresData.procedures;
        stats.procedures = procedures.length;
        
        for (const procedure of procedures) {
            const procedureStructure = await apiRequest(config.remoteUrl, config.apiKey, 'get_procedure_structure', {
                ...params,
                procedure: procedure
            });
            
            await executeLocalSQL(`DROP PROCEDURE IF EXISTS \`${procedure}\``, config.localDbName);
            await executeLocalSQL(procedureStructure.create_statement, config.localDbName);
            addLog(`  ✓ Created procedure: ${procedure}`, 'success');
        }
        updateProgress(85, `Synced ${procedures.length} procedures`);
        
        // Step 6: Sync functions
        addLog('🔧 Syncing functions...', 'info');
        const functionsData = await apiRequest(config.remoteUrl, config.apiKey, 'get_functions', params);
        const functions = functionsData.functions;
        stats.functions = functions.length;
        
        for (const func of functions) {
            const functionStructure = await apiRequest(config.remoteUrl, config.apiKey, 'get_function_structure', {
                ...params,
                function: func
            });
            
            await executeLocalSQL(`DROP FUNCTION IF EXISTS \`${func}\``, config.localDbName);
            await executeLocalSQL(functionStructure.create_statement, config.localDbName);
            addLog(`  ✓ Created function: ${func}`, 'success');
        }
        updateProgress(95, `Synced ${functions.length} functions`);
        
        // Step 7: Sync triggers
        addLog('⚡ Syncing triggers...', 'info');
        const triggersData = await apiRequest(config.remoteUrl, config.apiKey, 'get_triggers', params);
        const triggers = triggersData.triggers;
        stats.triggers = triggers.length;
        
        for (const trigger of triggers) {
            const createTrigger = `CREATE TRIGGER \`${trigger.Trigger}\` ${trigger.Timing} ${trigger.Event} ON \`${trigger.Table}\` FOR EACH ROW ${trigger.Statement}`;
            await executeLocalSQL(`DROP TRIGGER IF EXISTS \`${trigger.Trigger}\``, config.localDbName);
            await executeLocalSQL(createTrigger, config.localDbName);
            addLog(`  ✓ Created trigger: ${trigger.Trigger}`, 'success');
        }
        
        // Complete!
        updateProgress(100, 'Sync completed successfully!');
        updateStats({
            ...stats,
            time: Math.round((Date.now() - stats.startTime) / 1000) + 's'
        });
        
        addLog('🎉 Database sync completed successfully!', 'success');
        addLog(`📊 Summary: ${stats.tables} tables, ${stats.rows.toLocaleString()} rows, ${stats.views} views, ${stats.procedures} procedures, ${stats.functions} functions, ${stats.triggers} triggers`, 'success');
        
        // Show success alert
        setTimeout(() => {
            alert(`✅ Sync completed successfully!\n\nTables: ${stats.tables}\nRows: ${stats.rows.toLocaleString()}\nViews: ${stats.views}\nTime: ${Math.round((Date.now() - stats.startTime) / 1000)}s`);
        }, 500);
        
    } catch (error) {
        addLog(`❌ ERROR: ${error.message}`, 'error');
        updateProgress(0, 'Sync failed');
        alert(`❌ Sync failed!\n\n${error.message}`);
    } finally {
        // Re-enable form
        syncBtn.disabled = false;
        syncBtn.innerHTML = '<span>🔄</span><span>Start Sync</span>';
    }
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Load saved form values
    loadFormFromCookies();
    
    // Form submit
    document.getElementById('syncForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (confirm('⚠️ WARNING: This will completely replace the local database.\n\nAre you sure you want to continue?')) {
            startSync();
        }
    });
    
    // Test connection button
    document.getElementById('testConnectionBtn').addEventListener('click', testConnection);
    
    // Clear form button
    document.getElementById('clearFormBtn').addEventListener('click', function() {
        if (confirm('Clear all saved form data?')) {
            document.getElementById('syncForm').reset();
            clearFormCookies();
            alert('✅ Form cleared and cookies deleted');
        }
    });
    
    // Auto-save form values on change
    const form = document.getElementById('syncForm');
    form.addEventListener('change', function() {
        saveFormToCookies();
    });
});


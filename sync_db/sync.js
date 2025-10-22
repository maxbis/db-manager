/**
 * Database Sync - Client-side JavaScript
 * 
 * Handles sync operations, progress tracking, and cookie management
 */

// Cookie management
const COOKIE_PREFIX = 'db_sync_';
const COOKIE_EXPIRY_DAYS = 90;  // 3 months for regular fields
const PASSWORD_EXPIRY_HOURS = 1;  // 1 hour for passwords

/**
 * Set a cookie
 * @param {string} name - Cookie name
 * @param {string} value - Cookie value
 * @param {number} days - Expiration in days (can be fractional for hours)
 */
function setCookie(name, value, days = COOKIE_EXPIRY_DAYS) {
    const date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    const expires = "expires=" + date.toUTCString();
    document.cookie = COOKIE_PREFIX + name + "=" + encodeURIComponent(value) + ";" + expires + ";path=/";
}

/**
 * Set a cookie with expiration in hours
 * @param {string} name - Cookie name
 * @param {string} value - Cookie value
 * @param {number} hours - Expiration in hours
 */
function setCookieHours(name, value, hours) {
    const days = hours / 24;
    setCookie(name, value, days);
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
 * Regular fields: 3 months, renewed on each use
 * Passwords: 1 hour only
 */
function saveFormToCookies() {
    const form = document.getElementById('syncForm');
    const inputs = form.querySelectorAll('input, select');
    
    inputs.forEach(input => {
        if (input.type === 'password') {
            // Save passwords (including API key and DB passwords) with 1 hour expiration
            if (input.value) {
                setCookieHours(input.name, input.value, PASSWORD_EXPIRY_HOURS);
            }
        } else {
            // Save other fields with 3 months expiration
            setCookie(input.name, input.value, COOKIE_EXPIRY_DAYS);
        }
    });
}

/**
 * Load form values from cookies
 * Also loads password fields (if not expired)
 */
function loadFormFromCookies() {
    const form = document.getElementById('syncForm');
    const inputs = form.querySelectorAll('input, select');
    
    inputs.forEach(input => {
        const savedValue = getCookie(input.name);
        if (savedValue) {
            input.value = savedValue;
        }
    });
}

/**
 * Renew all existing cookies by resaving them with updated expiration
 * Called on page load to extend cookie life
 */
function renewCookies() {
    const form = document.getElementById('syncForm');
    const inputs = form.querySelectorAll('input, select');
    
    inputs.forEach(input => {
        const savedValue = getCookie(input.name);
        if (savedValue) {
            // Renew with appropriate expiration time
            if (input.type === 'password') {
                setCookieHours(input.name, savedValue, PASSWORD_EXPIRY_HOURS);
            } else {
                setCookie(input.name, savedValue, COOKIE_EXPIRY_DAYS);
            }
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
    const progressPercentage = document.getElementById('progressPercentage');
    const progressText = document.getElementById('progressText');
    
    progressFill.style.width = percent + '%';
    progressPercentage.textContent = Math.round(percent) + '%';
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
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'X-API-Key': apiKey
            },
            body: formData
        });
        
        // Read raw text first to preserve server errors and non-JSON output
        const rawText = await response.text();
        let data;
        try {
            data = JSON.parse(rawText);
        } catch (jsonError) {
            // Try to extract useful message from HTML or text
            const textSnippet = rawText
                .replace(/<[^>]*>/g, '')
                .replace(/\s+/g, ' ')
                .trim()
                .slice(0, 400);
            throw new Error(`Invalid JSON response (HTTP ${response.status}): ${textSnippet || response.statusText}`);
        }
        
        // Check if request was successful
        if (!response.ok) {
            const errorMsg = data.message || `HTTP error ${response.status}: ${response.statusText}`;
            const timestamp = data.timestamp || new Date().toISOString();
            throw new Error(`[${timestamp}] ${errorMsg}`);
        }
        
        // Check API response success flag
        if (!data.success) {
            const errorMsg = data.message || 'API request failed';
            const timestamp = data.timestamp || new Date().toISOString();
            throw new Error(`[${timestamp}] ${errorMsg}`);
        }
        
        return data.data;
        
    } catch (error) {
        // If it's a network error or fetch error, provide more details
        if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
            throw new Error(`Network error: Cannot connect to ${url}. Please check the URL and network connection.`);
        }
        // Re-throw the error with preserved message
        throw error;
    }
}

/**
 * Execute SQL query on local database
 */
async function executeLocalSQL(sql, dbName = null, options = {}) {
    // Validate SQL before sending
    if (!sql || sql === 'null' || sql === 'undefined') {
        throw new Error('Invalid SQL statement: SQL cannot be null or undefined');
    }
    
    const formData = new FormData();
    formData.append('action', 'execute_sql');
    formData.append('sql', sql);
    if (dbName) {
        formData.append('database', dbName);
    }
    if (options.disableForeignKeys === true) {
        formData.append('disable_fk', '1');
    }
    if (options.increasePacket === true) {
        formData.append('increase_packet', '1');
    }
    
    const response = await fetch('sync_handler.php', {
        method: 'POST',
        body: formData
    });
    
    // Read raw text to guard against HTML notices breaking JSON
    const rawText = await response.text();
    let data;
    try {
        data = JSON.parse(rawText);
    } catch (jsonError) {
        const textSnippet = rawText
            .replace(/<[^>]*>/g, '')
            .replace(/\s+/g, ' ')
            .trim()
            .slice(0, 400);
        throw new Error(`Invalid JSON from local handler: ${textSnippet || response.statusText}`);
    }
    
    if (!data.success) {
        throw new Error(data.message || 'SQL execution failed');
    }
    
    return data.data;
}

/**
 * Show error in GUI
 */
function showError(title, message) {
    // Show in log
    addLog(`❌ ${title}`, 'error');
    addLog(`   ${message}`, 'error');
    
    // Show error alert box
    const errorAlert = document.getElementById('errorAlert');
    const errorTitle = document.getElementById('errorTitle');
    const errorMessage = document.getElementById('errorMessage');
    
    errorTitle.textContent = title;
    
    // Enhance IP address display in message
    let displayMessage = message;
    if (message.includes('Unauthorized: IP address')) {
        const ipMatch = message.match(/IP address '([^']+)'/);
        if (ipMatch) {
            const deniedIP = ipMatch[1];
            displayMessage = message.replace(
                /IP address '[^']+'/, 
                `IP address '${deniedIP}' (your IP)`
            );
        }
    }
    
    errorMessage.textContent = displayMessage;
    errorAlert.style.display = 'flex';
    
    // Scroll to error
    errorAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    
    // Show alert dialog too
    Dialog.alert({
        title: title,
        message: message,
        icon: '❌',
        confirmClass: 'btn-danger'
    });
    
    // Update progress card
    const progressCard = document.getElementById('progressCard');
    progressCard.classList.add('active');
    updateProgress(0, 'Failed');
}

/**
 * Hide error alert
 */
function hideError() {
    const errorAlert = document.getElementById('errorAlert');
    errorAlert.style.display = 'none';
}

/**
 * Load available databases from remote server
 */
async function loadDatabases() {
    const form = document.getElementById('syncForm');
    const formData = new FormData(form);
    
    const btn = document.getElementById('loadDatabasesBtn');
    const dbInput = document.getElementById('remoteDbName');
    const container = document.querySelector('.database-select-container');
    
    // Check if we have required fields
    const remoteUrl = formData.get('remoteUrl');
    const apiKey = formData.get('apiKey');
    const dbHost = formData.get('remoteDbHost');
    const dbUser = formData.get('remoteDbUser');
    const dbPass = formData.get('remoteDbPass');
    
    if (!remoteUrl || !apiKey || !dbHost || !dbUser || !dbPass) {
        Dialog.alert({
            title: 'Missing Information',
            message: 'Please fill in Remote Server URL, API Key, DB Host, DB User, and DB Password before loading databases.',
            icon: '⚠️',
            confirmClass: 'btn-warning'
        });
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<span>⏳</span><span>Loading...</span>';
    
    // Hide any previous error
    hideError();
    
    try {
        const params = {
            db_host: dbHost,
            db_user: dbUser,
            db_pass: dbPass,
            // Provide a placeholder database for backward compatibility with older remote APIs
            db_name: 'information_schema'
        };
        
        const data = await apiRequest(remoteUrl, apiKey, 'list_databases', params);
        
        // Show database list directly (no modal) so it's clickable
        showDatabaseList(data.databases, dbInput, container);
        
    } catch (error) {
        showError('Failed to Load Databases', error.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<span>📋</span><span>Load DBs</span>';
    }
}

/**
 * Show database selection dropdown
 */
function showDatabaseList(databases, input, container) {
    // Remove existing dropdown
    const existingList = container.querySelector('.database-list');
    if (existingList) {
        existingList.remove();
    }
    
    // Create dropdown
    const dropdown = document.createElement('div');
    dropdown.className = 'database-list';
    // Respect CSS absolute positioning; just make it visible
    dropdown.style.display = 'block';
    
    if (databases.length === 0) {
        dropdown.innerHTML = '<div class="database-loading">No databases found</div>';
    } else {
        databases.forEach(db => {
            const item = document.createElement('div');
            item.className = 'database-item';
            item.textContent = db;
            item.onclick = function() {
                input.value = db;
                dropdown.remove();
                // Trigger change event to update local DB name
                input.dispatchEvent(new Event('input', { bubbles: true }));
            };
            dropdown.appendChild(item);
        });
    }
    
    container.appendChild(dropdown);
    
    // Close dropdown when clicking outside
    const closeDropdown = (e) => {
        if (!container.contains(e.target)) {
            dropdown.remove();
            document.removeEventListener('click', closeDropdown);
        }
    };
    
    setTimeout(() => {
        document.addEventListener('click', closeDropdown);
    }, 100);
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
    
    // Hide any previous error
    hideError();
    
    // Show progress card and clear logs
    const progressCard = document.getElementById('progressCard');
    progressCard.classList.add('active');
    document.getElementById('logContainer').innerHTML = '';
    
    addLog('🔌 Testing connection to remote server...', 'info');
    
    try {
        const params = {
            db_host: formData.get('remoteDbHost'),
            db_user: formData.get('remoteDbUser'),
            db_pass: formData.get('remoteDbPass'),
            db_name: formData.get('remoteDbName')
        };
        
        addLog(`📡 Connecting to ${formData.get('remoteUrl')}...`, 'info');
        
        const data = await apiRequest(
            formData.get('remoteUrl'),
            formData.get('apiKey'),
            'get_tables',
            params
        );
        
        addLog(`✅ Connection successful!`, 'success');
        addLog(`   Found ${data.tables.length} tables in database "${formData.get('remoteDbName')}"`, 'success');
        
        Dialog.alert({
            title: 'Connection Successful',
            message: `Found ${data.tables.length} tables in database "${formData.get('remoteDbName')}"`,
            icon: '✅',
            confirmClass: 'btn-success'
        });
        
    } catch (error) {
        showError('Connection Failed', error.message);
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
    
    // Hide any previous error
    hideError();
    
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
    
    let foreignKeysDisabled = false;
    try {
        addLog('🚀 Starting database sync...', 'info');
        updateProgress(0, 'Initializing...');
        
        // Disable foreign key checks to allow dropping/creating tables in any order
        try {
            addLog('🔧 Disabling foreign key checks...', 'info');
            // Use per-request disable to ensure the session that executes mutations has FKs off
            await executeLocalSQL('SET FOREIGN_KEY_CHECKS=0', config.localDbName, { disableForeignKeys: true });
            foreignKeysDisabled = true;
        } catch (fkErr) {
            addLog('⚠️ Could not disable foreign key checks (continuing).', 'warning');
        }
        
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
            
            // Validate structure data
            if (!structureData || !structureData.create_statement) {
                throw new Error(`Failed to retrieve valid CREATE TABLE statement for table: ${table}`);
            }
            
            // Drop table if exists and recreate
            await executeLocalSQL(`DROP TABLE IF EXISTS \`${table}\``, config.localDbName, { disableForeignKeys: true });
            await executeLocalSQL(structureData.create_statement, config.localDbName, { disableForeignKeys: true });
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
                    // Build value tuples with safe escaping
                    const valueTuples = rows.map(row => {
                        const vals = columns.map(col => {
                            const val = row[col];
                            if (val === null) return 'NULL';
                            // Escape backslashes and single quotes
                            const s = String(val)
                                .replace(/\\/g, "\\\\")
                                .replace(/'/g, "''");
                            return "'" + s + "'";
                        });
                        return '(' + vals.join(', ') + ')';
                    });

                    // Dynamically batch by SQL size to avoid max_allowed_packet
                    const MAX_SQL_BYTES = 1024 * 1024; // ~1MB per statement
                    const targetDb = config.localDbName;
                    const prefix = `${targetDb ? `USE \`${targetDb}\`; ` : ''}INSERT INTO \`${table}\` (\`${columns.join('`, `')}\`) VALUES `;
                    let batchParts = [];
                    let currentLength = prefix.length;

                    async function flushBatchIfNeeded(nextPartLength) {
                        // Account for comma+space between tuples when concatenating
                        const sep = batchParts.length > 0 ? 2 : 0;
                        if (currentLength + sep + nextPartLength > MAX_SQL_BYTES && batchParts.length > 0) {
                            const sql = prefix + batchParts.join(', ');
                            await executeLocalSQL(sql, config.localDbName, { disableForeignKeys: true, increasePacket: true });
                            batchParts = [];
                            currentLength = prefix.length;
                        }
                    }

                    for (let iVal = 0; iVal < valueTuples.length; iVal++) {
                        const part = valueTuples[iVal];
                        await flushBatchIfNeeded(part.length);
                        // If a single tuple itself exceeds the limit, send alone
                        if (part.length + prefix.length > MAX_SQL_BYTES && batchParts.length === 0) {
                            await executeLocalSQL(prefix + part, config.localDbName, { disableForeignKeys: true, increasePacket: true });
                            continue;
                        }
                        if (batchParts.length > 0) currentLength += 2; // ', '
                        batchParts.push(part);
                        currentLength += part.length;
                    }

                    if (batchParts.length > 0) {
                        const sql = prefix + batchParts.join(', ');
                        await executeLocalSQL(sql, config.localDbName, { disableForeignKeys: true, increasePacket: true });
                    }
                    
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
            
            if (!viewStructure || !viewStructure.create_statement) {
                throw new Error(`Failed to retrieve valid CREATE VIEW statement for view: ${view}`);
            }
            
            await executeLocalSQL(`DROP VIEW IF EXISTS \`${view}\``, config.localDbName, { disableForeignKeys: true });
            await executeLocalSQL(viewStructure.create_statement, config.localDbName, { disableForeignKeys: true });
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
            
            if (!procedureStructure || !procedureStructure.create_statement) {
                throw new Error(`Failed to retrieve valid CREATE PROCEDURE statement for procedure: ${procedure}`);
            }
            
            await executeLocalSQL(`DROP PROCEDURE IF EXISTS \`${procedure}\``, config.localDbName, { disableForeignKeys: true });
            await executeLocalSQL(procedureStructure.create_statement, config.localDbName, { disableForeignKeys: true });
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
            
            if (!functionStructure || !functionStructure.create_statement) {
                throw new Error(`Failed to retrieve valid CREATE FUNCTION statement for function: ${func}`);
            }
            
            await executeLocalSQL(`DROP FUNCTION IF EXISTS \`${func}\``, config.localDbName, { disableForeignKeys: true });
            await executeLocalSQL(functionStructure.create_statement, config.localDbName, { disableForeignKeys: true });
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
            await executeLocalSQL(`DROP TRIGGER IF EXISTS \`${trigger.Trigger}\``, config.localDbName, { disableForeignKeys: true });
            await executeLocalSQL(createTrigger, config.localDbName, { disableForeignKeys: true });
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
            Dialog.alert({
                title: 'Sync Completed Successfully',
                message: `Tables: ${stats.tables}<br>Rows: ${stats.rows.toLocaleString()}<br>Views: ${stats.views}<br>Procedures: ${stats.procedures}<br>Functions: ${stats.functions}<br>Triggers: ${stats.triggers}<br>Time: ${Math.round((Date.now() - stats.startTime) / 1000)}s`,
                icon: '✅',
                confirmClass: 'btn-success'
            });
        }, 500);
        
    } catch (error) {
        // Show detailed error in GUI
        addLog(`❌ SYNC FAILED`, 'error');
        addLog(`   ${error.message}`, 'error');
        updateProgress(0, 'Sync failed - See error details above');
        
        // Show error alert with more details
        let errorDetails = error.message;
        
        // Add helpful troubleshooting hints based on error type
        if (errorDetails.includes('Unauthorized: IP address')) {
            // Extract IP from error message if present
            const ipMatch = errorDetails.match(/IP address '([^']+)'/);
            const deniedIP = ipMatch ? ipMatch[1] : 'your IP';
            errorDetails += `\n\n💡 Troubleshooting:\n- Add ${deniedIP} to ipAllowed.txt on the remote server\n- Or access from localhost on remote server\n- Check if you're behind a proxy (forwarded IP may differ)`;
        } else if (errorDetails.includes('Unauthorized: Invalid API key')) {
            errorDetails += '\n\n💡 Troubleshooting:\n- Check that API keys match on both servers\n- Verify the API key in config.php';
        } else if (errorDetails.includes('Network error')) {
            errorDetails += '\n\n💡 Troubleshooting:\n- Check the remote URL is correct\n- Verify the remote server is accessible\n- Check for firewall/CORS issues';
        } else if (errorDetails.includes('Connection failed')) {
            errorDetails += '\n\n💡 Troubleshooting:\n- Verify database credentials\n- Check that database exists\n- Ensure database user has proper permissions';
        }
        
        // Format error details with line breaks
        const formattedError = errorDetails.replace(/\n\n/g, '<br><br>').replace(/\n/g, '<br>');
        
        Dialog.alert({
            title: 'Sync Failed',
            message: formattedError,
            icon: '❌',
            confirmClass: 'btn-danger'
        });
    } finally {
        // Always attempt to re-enable foreign key checks
        if (foreignKeysDisabled) {
            try {
                addLog('🔧 Re-enabling foreign key checks...', 'info');
                await executeLocalSQL('SET FOREIGN_KEY_CHECKS=1', document.getElementById('localDbName').value || null, { disableForeignKeys: true });
            } catch (fkEnableErr) {
                addLog('⚠️ Failed to re-enable foreign key checks. Please run: SET FOREIGN_KEY_CHECKS=1;', 'warning');
            }
        }
        // Re-enable form
        syncBtn.disabled = false;
        syncBtn.innerHTML = '<span>🔄</span><span>Start Sync</span>';
    }
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Load saved form values
    loadFormFromCookies();
    
    // Renew cookie expiration dates (3 months for regular fields, 1 hour for passwords)
    renewCookies();
    
    // Form submit
    document.getElementById('syncForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        Dialog.confirm({
            title: 'Confirm Database Sync',
            message: 'This will completely replace the local database. Are you sure you want to continue?',
            icon: '⚠️',
            confirmText: 'Start Sync',
            cancelText: 'Cancel',
            confirmClass: 'btn-danger',
            onConfirm: function() {
                startSync();
            }
        });
    });
    
    // Test connection button
    document.getElementById('testConnectionBtn').addEventListener('click', testConnection);
    
    // Load databases button
    document.getElementById('loadDatabasesBtn').addEventListener('click', loadDatabases);
    
    // Clear form button
    document.getElementById('clearFormBtn').addEventListener('click', function() {
        Dialog.confirm({
            title: 'Clear Form Data',
            message: 'Clear all saved form data?',
            icon: '🗑️',
            confirmText: 'Clear',
            cancelText: 'Cancel',
            confirmClass: 'btn-danger',
            onConfirm: function() {
                document.getElementById('syncForm').reset();
                clearFormCookies();
                Dialog.alert({
                    title: 'Success',
                    message: 'Form cleared and cookies deleted',
                    icon: '✅',
                    confirmClass: 'btn-success'
                });
            }
        });
    });
    
    // Auto-save form values on change
    const form = document.getElementById('syncForm');
    form.addEventListener('change', function() {
        saveFormToCookies();
    });
    
    // Auto-sync local database name from remote database name
    const remoteDbNameInput = document.getElementById('remoteDbName');
    const localDbNameInput = document.getElementById('localDbName');
    const localDbLock = document.getElementById('localDbLock');
    const localDbHelp = document.getElementById('localDbHelp');
    let userEditedLocalDb = false; // Track if user manually edited the local DB name
    
    // Function to unlock local DB field
    function unlockLocalDbField() {
        localDbNameInput.removeAttribute('readonly');
        localDbNameInput.style.background = '';
        localDbNameInput.style.cursor = '';
        localDbLock.textContent = '🔓';
        localDbHelp.textContent = 'Editable - customize if needed';
    }
    
    // Function to lock local DB field
    function lockLocalDbField() {
        localDbNameInput.setAttribute('readonly', 'readonly');
        localDbNameInput.style.background = '#F0F4F8';
        localDbNameInput.style.cursor = 'not-allowed';
        localDbLock.textContent = '🔒';
        localDbHelp.textContent = 'Auto-synced from remote database name (editable after setting remote)';
    }
    
    // When remote DB name changes
    remoteDbNameInput.addEventListener('input', function() {
        const remoteDbValue = this.value.trim();
        
        if (remoteDbValue) {
            // Unlock the field if it was locked
            unlockLocalDbField();
            
            // Only auto-sync if user hasn't manually edited it
            if (!userEditedLocalDb) {
                localDbNameInput.value = remoteDbValue;
                localDbHelp.textContent = 'Auto-synced from remote (click to customize)';
            }
        } else {
            // Lock the field if remote DB is empty
            lockLocalDbField();
            if (!userEditedLocalDb) {
                localDbNameInput.value = '';
            }
        }
    });
    
    // Track when user manually edits local DB name
    localDbNameInput.addEventListener('input', function() {
        if (!this.hasAttribute('readonly')) {
            userEditedLocalDb = true;
            localDbHelp.textContent = 'Custom name (will not auto-sync)';
        }
    });
    
    // Reset user-edited flag when remote DB changes significantly
    remoteDbNameInput.addEventListener('blur', function() {
        const remoteDbValue = this.value.trim();
        const localDbValue = localDbNameInput.value.trim();
        
        // If they match, reset the flag (user accepted the auto-sync)
        if (remoteDbValue && remoteDbValue === localDbValue) {
            userEditedLocalDb = false;
            localDbHelp.textContent = 'Auto-synced from remote (click to customize)';
        }
    });
    
    // Check initial state after loading from cookies
    setTimeout(function() {
        const remoteDbValue = remoteDbNameInput.value.trim();
        const localDbValue = localDbNameInput.value.trim();
        
        if (remoteDbValue) {
            unlockLocalDbField();
            
            // If local is different from remote, user has customized it
            if (localDbValue && localDbValue !== remoteDbValue) {
                userEditedLocalDb = true;
                localDbHelp.textContent = 'Custom name (will not auto-sync)';
            } else if (!localDbValue) {
                // If local is empty but remote has value, auto-fill it
                localDbNameInput.value = remoteDbValue;
                localDbHelp.textContent = 'Auto-synced from remote (click to customize)';
            }
        } else if (!remoteDbValue && !localDbValue) {
            lockLocalDbField();
        }
    }, 100);
});


<?php
/**
 * Database Sync - Copy Remote Database to Local
 * IP Authorization Check
 */
require_once __DIR__ . '/../login/auth_check.php';

$pageConfig = [
    'id' => 'db_sync',
    'title' => 'Database Sync',
    'icon' => '🔄',
    'controls_html' => ''
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Sync - Remote to Local</title>
    <link rel="stylesheet" href="../styles/common.css">
    <style>
        /* Page-specific styles for db_sync.php */

        .sync-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        @media (max-width: 1024px) {
            .sync-container {
                grid-template-columns: 1fr;
            }
        }

        .sync-panel {
            background: var(--color-bg-white);
            border: 1px solid var(--color-border-light);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow-md);
        }

        .sync-panel h2 {
            font-size: 16px;
            font-weight: 600;
            color: var(--color-text-primary);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--color-text-secondary);
            margin-bottom: 6px;
        }

        .form-group input[type="text"],
        .form-group input[type="password"],
        .form-group input[type="number"],
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--color-border-input);
            border-radius: 8px;
            font-size: 13px;
            color: var(--color-text-primary);
            background: var(--color-bg-white);
            transition: all 0.2s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px var(--overlay-focus);
        }

        .form-group small {
            display: block;
            font-size: 11px;
            color: var(--color-text-muted);
            margin-top: 4px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .btn {
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-light) 100%);
            color: white;
            box-shadow: var(--shadow-sm);
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-secondary {
            background: var(--color-bg-lighter);
            color: var(--color-primary);
            border: 1px solid var(--color-border-light);
        }

        .btn-secondary:hover:not(:disabled) {
            background: var(--color-bg-hover);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--color-success) 0%, var(--color-success-light) 100%);
            color: white;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .progress-container {
            display: none;
            margin-top: 20px;
        }

        .progress-container.active {
            display: block;
        }

        .progress-bar {
            width: 100%;
            height: 30px;
            background: var(--color-bg-lighter);
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            border: 1px solid var(--color-border-light);
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--color-primary) 0%, var(--color-primary-light) 100%);
            width: 0%;
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: 600;
        }

        .progress-info {
            margin-top: 12px;
            padding: 12px;
            background: var(--color-bg-light);
            border-radius: 8px;
            font-size: 12px;
            color: var(--color-text-secondary);
        }

        .log-container {
            margin-top: 20px;
            max-height: 400px;
            overflow-y: auto;
            background: var(--color-bg-light);
            border: 1px solid var(--color-border-light);
            border-radius: 8px;
            padding: 12px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.6;
        }

        .log-entry {
            margin-bottom: 4px;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .log-entry.info {
            color: var(--color-primary);
        }

        .log-entry.success {
            color: var(--color-success);
            background: rgba(44, 68, 76, 0.1);
        }

        .log-entry.error {
            color: var(--color-danger);
            background: rgba(196, 71, 4, 0.1);
        }

        .log-entry.warning {
            color: var(--color-warning);
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.idle {
            background: var(--color-bg-lighter);
            color: var(--color-text-tertiary);
        }

        .status-badge.testing {
            background: var(--color-warning-pale);
            color: var(--color-warning);
        }

        .status-badge.syncing {
            background: var(--color-primary-pale);
            color: var(--color-primary);
        }

        .status-badge.success {
            background: rgba(44, 68, 76, 0.1);
            color: var(--color-success);
        }

        .status-badge.error {
            background: rgba(196, 71, 4, 0.1);
            color: var(--color-danger);
        }

        .checkbox-group {
            margin-top: 12px;
        }

        .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 8px;
            cursor: pointer;
        }

        .checkbox-group input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .table-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid var(--color-border-light);
            border-radius: 8px;
            padding: 12px;
            background: var(--color-bg-light);
            margin-top: 8px;
        }

        .table-list-item {
            padding: 8px;
            margin-bottom: 4px;
            background: var(--color-bg-white);
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
        }

        .spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid var(--color-border-light);
            border-top-color: var(--color-primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 13px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .alert-info {
            background: var(--color-primary-pale);
            color: var(--color-primary);
            border: 1px solid var(--color-primary-lightest);
        }

        .alert-warning {
            background: var(--color-warning-pale);
            color: var(--color-warning);
            border: 1px solid var(--color-warning-lightest);
        }

        .alert-success {
            background: rgba(44, 68, 76, 0.1);
            color: var(--color-success);
            border: 1px solid var(--color-success-lighter);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin-top: 16px;
        }

        .stat-card {
            background: var(--color-bg-light);
            padding: 12px;
            border-radius: 8px;
            border: 1px solid var(--color-border-lighter);
        }

        .stat-card .label {
            font-size: 11px;
            color: var(--color-text-tertiary);
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .stat-card .value {
            font-size: 20px;
            font-weight: 700;
            color: var(--color-primary);
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../templates/header.php'; ?>

    <div class="alert alert-info">
        <span>ℹ️</span>
        <div>
            <strong>Database Sync Tool - API Method</strong><br>
            This tool allows you to copy a remote database to your local environment via a secure API.
            <strong>First:</strong> Upload <code>sync_remote_api.php</code> to your production server and configure it.
            Then enter the API details below and click "Test Connection" to verify access.
        </div>
    </div>

    <div class="sync-container">
        <!-- Remote Database Configuration -->
        <div class="sync-panel">
            <h2>🌐 Remote Database (API)</h2>
            
            <div class="form-group">
                <label>API URL</label>
                <input type="text" id="remoteApiUrl" placeholder="https://yoursite.com/sync_remote_api.php" value="">
                <small>Full URL to sync_remote_api.php on your production server</small>
            </div>

            <div class="form-group">
                <label>API Secret Key</label>
                <input type="password" id="remoteApiKey" placeholder="Your secret API key">
                <small>Must match SECRET_KEY in sync_remote_api.php</small>
            </div>

            <div class="form-group">
                <label>Database Name</label>
                <input type="text" id="remoteDatabase" placeholder="production_database">
                <small>Name of the remote database to sync (optional, uses default if empty)</small>
            </div>

            <div class="form-group">
                <label>Status</label>
                <div>
                    <span class="status-badge idle" id="remoteStatus">Not Connected</span>
                </div>
            </div>

            <div class="alert alert-warning" style="margin-top: 16px; font-size: 12px;">
                <span>🔐</span>
                <div>
                    <strong>Security Checklist:</strong><br>
                    ✓ API_ENABLED = true in remote file<br>
                    ✓ SECRET_KEY configured<br>
                    ✓ Your IP in config/ipAllowed.txt on production<br>
                    ✓ Using HTTPS (recommended)
                </div>
            </div>

            <div class="btn-group">
                <button class="btn btn-secondary" id="btnTestConnection">
                    🔌 Test Connection
                </button>
                <button class="btn btn-secondary" id="btnLoadTables" disabled>
                    📋 Load Tables
                </button>
            </div>

            <div id="tableListContainer" style="display: none; margin-top: 16px;">
                <label style="font-size: 13px; font-weight: 600; color: var(--color-text-secondary); margin-bottom: 8px; display: block;">
                    Select Tables to Sync
                </label>
                <div style="margin-bottom: 8px;">
                    <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;" id="btnSelectAll">
                        ✓ Select All
                    </button>
                    <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;" id="btnDeselectAll">
                        ✗ Deselect All
                    </button>
                </div>
                <div class="table-list" id="tableList"></div>
            </div>
        </div>

        <!-- Local Database Configuration -->
        <div class="sync-panel">
            <h2>💻 Local Database</h2>
            
            <div class="form-group">
                <label>Host</label>
                <input type="text" id="localHost" value="localhost" readonly>
                <small>Local MySQL server (current configuration)</small>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Port</label>
                    <input type="number" id="localPort" value="3306" readonly>
                </div>
                <div class="form-group">
                    <label>Target Database</label>
                    <input type="text" id="localDatabase" placeholder="local_database_name">
                    <small>Local database to sync to</small>
                </div>
            </div>

            <div class="checkbox-group">
                <label>
                    <input type="checkbox" id="dropExisting" checked>
                    <span>Drop existing tables before sync</span>
                </label>
                <label>
                    <input type="checkbox" id="createDatabase" checked>
                    <span>Create database if it doesn't exist</span>
                </label>
                <label>
                    <input type="checkbox" id="syncData" checked>
                    <span>Sync table data (not just structure)</span>
                </label>
            </div>

            <div class="alert alert-warning" style="margin-top: 16px;">
                <span>⚠️</span>
                <div>
                    <strong>Warning:</strong> This will overwrite your local database. Make sure you have backups if needed.
                </div>
            </div>

            <div class="btn-group">
                <button class="btn btn-primary" id="btnStartSync" disabled>
                    🚀 Start Sync
                </button>
            </div>
        </div>
    </div>

    <!-- Progress Section -->
    <div class="sync-panel" id="progressContainer">
        <h2>📊 Sync Progress</h2>
        
        <div class="progress-container" id="progressBar">
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill">0%</div>
            </div>
            <div class="progress-info" id="progressInfo">
                Ready to sync...
            </div>
        </div>

        <div class="stats-grid" id="statsGrid" style="display: none;">
            <div class="stat-card">
                <div class="label">Tables Synced</div>
                <div class="value" id="statTables">0</div>
            </div>
            <div class="stat-card">
                <div class="label">Rows Copied</div>
                <div class="value" id="statRows">0</div>
            </div>
            <div class="stat-card">
                <div class="label">Duration</div>
                <div class="value" id="statDuration">0s</div>
            </div>
            <div class="stat-card">
                <div class="label">Status</div>
                <div class="value" id="statStatus" style="font-size: 14px;">⏸️ Idle</div>
            </div>
        </div>

        <div class="log-container" id="logContainer"></div>
    </div>

    <?php include __DIR__ . '/../templates/footer.php'; ?>

    <script>
        // State management
        let remoteTables = [];
        let selectedTables = [];
        let isConnected = false;
        let isSyncing = false;
        let syncStartTime = null;
        let durationInterval = null;

        // DOM elements
        const remoteStatus = document.getElementById('remoteStatus');
        const btnTestConnection = document.getElementById('btnTestConnection');
        const btnLoadTables = document.getElementById('btnLoadTables');
        const btnStartSync = document.getElementById('btnStartSync');
        const btnSelectAll = document.getElementById('btnSelectAll');
        const btnDeselectAll = document.getElementById('btnDeselectAll');
        const tableListContainer = document.getElementById('tableListContainer');
        const tableList = document.getElementById('tableList');
        const progressBar = document.getElementById('progressBar');
        const progressFill = document.getElementById('progressFill');
        const progressInfo = document.getElementById('progressInfo');
        const logContainer = document.getElementById('logContainer');
        const statsGrid = document.getElementById('statsGrid');

        // Utility functions
        function addLog(message, type = 'info') {
            const entry = document.createElement('div');
            entry.className = `log-entry ${type}`;
            const timestamp = new Date().toLocaleTimeString();
            entry.textContent = `[${timestamp}] ${message}`;
            logContainer.appendChild(entry);
            logContainer.scrollTop = logContainer.scrollHeight;
        }

        function updateStatus(element, text, className) {
            element.textContent = text;
            element.className = `status-badge ${className}`;
        }

        function setProgress(percent, info) {
            progressBar.classList.add('active');
            progressFill.style.width = percent + '%';
            progressFill.textContent = Math.round(percent) + '%';
            if (info) {
                progressInfo.textContent = info;
            }
        }

        function getRemoteConfig() {
            return {
                apiUrl: document.getElementById('remoteApiUrl').value.trim(),
                apiKey: document.getElementById('remoteApiKey').value.trim(),
                database: document.getElementById('remoteDatabase').value.trim()
            };
        }

        function getLocalConfig() {
            return {
                database: document.getElementById('localDatabase').value.trim(),
                dropExisting: document.getElementById('dropExisting').checked,
                createDatabase: document.getElementById('createDatabase').checked,
                syncData: document.getElementById('syncData').checked
            };
        }

        // Test remote connection
        btnTestConnection.addEventListener('click', async () => {
            const config = getRemoteConfig();
            
            if (!config.apiUrl || !config.apiKey) {
                addLog('Please enter API URL and API Key', 'error');
                return;
            }

            btnTestConnection.disabled = true;
            btnTestConnection.innerHTML = '<span class="spinner"></span> Testing...';
            updateStatus(remoteStatus, 'Testing...', 'testing');
            addLog('Testing connection to remote API...', 'info');

            try {
                const response = await fetch('sync_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'testConnection',
                        config: config
                    })
                });

                const result = await response.json();

                if (result.success) {
                    isConnected = true;
                    updateStatus(remoteStatus, 'Connected ✓', 'success');
                    addLog(`API connection successful!`, 'success');
                    btnLoadTables.disabled = false;
                } else {
                    isConnected = false;
                    updateStatus(remoteStatus, 'Connection Failed', 'error');
                    addLog(`Connection failed: ${result.error}`, 'error');
                }
            } catch (error) {
                isConnected = false;
                updateStatus(remoteStatus, 'Error', 'error');
                addLog(`Error: ${error.message}`, 'error');
            } finally {
                btnTestConnection.disabled = false;
                btnTestConnection.innerHTML = '🔌 Test Connection';
            }
        });

        // Load tables from remote database
        btnLoadTables.addEventListener('click', async () => {
            const config = getRemoteConfig();
            
            btnLoadTables.disabled = true;
            btnLoadTables.innerHTML = '<span class="spinner"></span> Loading...';
            addLog('Loading table list from remote database...', 'info');

            try {
                const response = await fetch('sync_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'getTables',
                        config: config
                    })
                });

                const result = await response.json();

                if (result.success) {
                    remoteTables = result.tables;
                    selectedTables = [...remoteTables]; // Select all by default
                    renderTableList();
                    tableListContainer.style.display = 'block';
                    addLog(`Loaded ${remoteTables.length} tables`, 'success');
                    btnStartSync.disabled = false;
                } else {
                    addLog(`Failed to load tables: ${result.error}`, 'error');
                }
            } catch (error) {
                addLog(`Error: ${error.message}`, 'error');
            } finally {
                btnLoadTables.disabled = false;
                btnLoadTables.innerHTML = '📋 Load Tables';
            }
        });

        // Render table list
        function renderTableList() {
            tableList.innerHTML = '';
            remoteTables.forEach(table => {
                const item = document.createElement('div');
                item.className = 'table-list-item';
                
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.value = table;
                checkbox.checked = selectedTables.includes(table);
                checkbox.addEventListener('change', (e) => {
                    if (e.target.checked) {
                        selectedTables.push(table);
                    } else {
                        selectedTables = selectedTables.filter(t => t !== table);
                    }
                });

                const label = document.createElement('span');
                label.textContent = table;

                item.appendChild(checkbox);
                item.appendChild(label);
                tableList.appendChild(item);
            });
        }

        // Select/Deselect all tables
        btnSelectAll.addEventListener('click', () => {
            selectedTables = [...remoteTables];
            renderTableList();
        });

        btnDeselectAll.addEventListener('click', () => {
            selectedTables = [];
            renderTableList();
        });

        // Start sync
        btnStartSync.addEventListener('click', async () => {
            const remoteConfig = getRemoteConfig();
            const localConfig = getLocalConfig();

            if (!localConfig.database) {
                addLog('Please specify a local database name', 'error');
                return;
            }

            if (selectedTables.length === 0) {
                addLog('Please select at least one table to sync', 'error');
                return;
            }

            if (!confirm(`This will sync ${selectedTables.length} table(s) to local database "${localConfig.database}".\n\n${localConfig.dropExisting ? 'WARNING: Existing tables will be dropped!\n\n' : ''}Continue?`)) {
                return;
            }

            // Start sync
            isSyncing = true;
            syncStartTime = Date.now();
            btnStartSync.disabled = true;
            btnTestConnection.disabled = true;
            btnLoadTables.disabled = true;
            statsGrid.style.display = 'grid';

            // Start duration timer
            durationInterval = setInterval(() => {
                const duration = Math.floor((Date.now() - syncStartTime) / 1000);
                document.getElementById('statDuration').textContent = duration + 's';
            }, 1000);

            addLog('='.repeat(60), 'info');
            addLog('Starting database sync via API...', 'info');
            addLog(`Remote API: ${remoteConfig.apiUrl}`, 'info');
            addLog(`Remote DB: ${remoteConfig.database || 'default'}`, 'info');
            addLog(`Local DB: ${localConfig.database}`, 'info');
            addLog(`Tables: ${selectedTables.length}`, 'info');
            addLog('='.repeat(60), 'info');

            document.getElementById('statStatus').textContent = '🔄 Syncing';

            try {
                const response = await fetch('sync_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'syncDatabase',
                        remoteConfig: remoteConfig,
                        localConfig: localConfig,
                        tables: selectedTables
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // Update progress for each step
                    if (result.logs) {
                        result.logs.forEach(log => {
                            addLog(log.message, log.type);
                        });
                    }

                    // Update stats
                    document.getElementById('statTables').textContent = result.stats.tablesSynced || 0;
                    document.getElementById('statRows').textContent = result.stats.rowsCopied || 0;
                    document.getElementById('statStatus').textContent = '✅ Complete';

                    setProgress(100, 'Sync completed successfully!');
                    addLog('='.repeat(60), 'success');
                    addLog('✅ Database sync completed successfully!', 'success');
                    addLog(`Total tables synced: ${result.stats.tablesSynced}`, 'success');
                    addLog(`Total rows copied: ${result.stats.rowsCopied}`, 'success');
                } else {
                    document.getElementById('statStatus').textContent = '❌ Failed';
                    addLog('='.repeat(60), 'error');
                    addLog(`❌ Sync failed: ${result.error}`, 'error');
                    setProgress(0, 'Sync failed');
                }
            } catch (error) {
                document.getElementById('statStatus').textContent = '❌ Error';
                addLog('='.repeat(60), 'error');
                addLog(`❌ Error: ${error.message}`, 'error');
                setProgress(0, 'Error occurred');
            } finally {
                isSyncing = false;
                btnStartSync.disabled = false;
                btnTestConnection.disabled = false;
                btnLoadTables.disabled = false;
                clearInterval(durationInterval);
            }
        });

        // Initial log
        addLog('Database sync tool ready (API method). Upload sync_remote_api.php to production first, then configure API settings and click "Test Connection".', 'info');
    </script>
</body>

</html>


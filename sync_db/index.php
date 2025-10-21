<?php
/**
 * Database Sync - Client Page
 * 
 * This page allows you to sync a database from a remote server to local.
 * It uses the same authentication system as other pages.
 */
require_once __DIR__ . '/../login/auth_check.php';
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/config.php';

// Page configuration for header template
$pageConfig = [
    'id' => 'sync_db',
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
    <title><?php echo $pageConfig['icon']; ?> <?php echo htmlspecialchars($pageConfig['title']); ?></title>
    <link rel="stylesheet" href="../styles/common.css">
    <style>
        /* Page-specific styles */
        .sync-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .sync-card {
            background: var(--color-bg-white);
            border: 2px solid var(--color-border-light);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-md);
        }

        .sync-card h2 {
            color: var(--color-primary);
            font-size: 20px;
            margin: 0 0 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-weight: 600;
            color: var(--color-text-primary);
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            padding: 10px 12px;
            border: 1px solid var(--color-border-light);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px var(--color-primary-pale);
        }

        .form-group small {
            color: var(--color-text-tertiary);
            font-size: 12px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #06457F 0%, #04324D 100%);
            color: white;
            border: 2px solid #06457F;
            box-shadow: 0 2px 8px rgba(6, 69, 127, 0.3);
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(6, 69, 127, 0.5);
            background: linear-gradient(135deg, #0856A0 0%, #06457F 100%);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            box-shadow: none;
        }

        .btn-secondary {
            background: #F8FAFC;
            color: #262B40;
            border: 2px solid #D1D9E0;
        }

        .btn-secondary:hover:not(:disabled) {
            background: #FFFFFF;
            border-color: #06457F;
            color: #06457F;
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
            background: linear-gradient(90deg, var(--color-success) 0%, var(--color-success-dark) 100%);
            width: 0%;
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 12px;
        }

        .progress-text {
            margin-top: 10px;
            font-size: 14px;
            color: var(--color-text-secondary);
        }

        .log-container {
            max-height: 400px;
            overflow-y: auto;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 13px;
            line-height: 1.6;
            margin-top: 15px;
        }

        .log-entry {
            margin-bottom: 5px;
            display: flex;
            gap: 10px;
        }

        .log-time {
            color: #858585;
        }

        .log-success {
            color: #4ec9b0;
        }

        .log-error {
            color: #f48771;
        }

        .log-info {
            color: #4fc1ff;
        }

        .log-warning {
            color: #dcdcaa;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .alert-info {
            background: var(--color-info-pale);
            color: var(--color-info-dark);
            border: 1px solid var(--color-info-light);
        }

        .alert-success {
            background: var(--color-success-pale);
            color: var(--color-success-dark);
            border: 1px solid var(--color-success-light);
        }

        .alert-error {
            background: var(--color-danger-pale);
            color: var(--color-danger-dark);
            border: 1px solid var(--color-danger-light);
        }

        .alert-warning {
            background: var(--color-warning-pale);
            color: var(--color-warning-dark);
            border: 1px solid var(--color-warning-light);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .stat-card {
            background: var(--color-bg-lighter);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid var(--color-border-lighter);
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--color-primary);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 12px;
            color: var(--color-text-tertiary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .button-group .btn {
            min-width: 140px;
            justify-content: center;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../templates/header.php'; ?>

    <div class="sync-container">
        <!-- Information Alert -->
        <div class="alert alert-info">
            <span style="font-size: 20px;">ℹ️</span>
            <div>
                <strong>Database Sync Tool</strong><br>
                This tool allows you to sync a complete database from a remote server to your local server.
                All tables, data, views, stored procedures, functions, and triggers will be copied.
            </div>
        </div>

        <!-- Error Alert (Hidden by default) -->
        <div class="alert alert-error" id="errorAlert" style="display: none;">
            <span style="font-size: 20px;">❌</span>
            <div style="flex: 1;">
                <strong id="errorTitle">Error</strong><br>
                <span id="errorMessage">An error occurred</span>
            </div>
            <button onclick="hideError()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--color-danger); padding: 0; margin-left: 10px;" title="Dismiss">✖</button>
        </div>

        <!-- Configuration Card -->
        <div class="sync-card">
            <h2>🔧 Sync Configuration</h2>
            
            <form id="syncForm">
                <div class="form-grid">
                    <!-- Remote Server Settings -->
                    <div class="form-group">
                        <label for="remoteUrl">Remote Server URL</label>
                        <input type="text" id="remoteUrl" name="remoteUrl" placeholder="https://example.com/sync_db/api.php" required>
                        <small>Full URL to the remote sync API endpoint</small>
                    </div>

                    <div class="form-group">
                        <label for="apiKey">API Key</label>
                        <input type="password" id="apiKey" name="apiKey" placeholder="Enter API key" required>
                        <small>The secure API key configured in config.php (saved in cookie)</small>
                    </div>

                    <div class="form-group">
                        <label for="remoteDbHost">Remote DB Host</label>
                        <input type="text" id="remoteDbHost" name="remoteDbHost" value="localhost" required>
                        <small>Usually "localhost" on remote server</small>
                    </div>

                    <div class="form-group">
                        <label for="remoteDbUser">Remote DB Username</label>
                        <input type="text" id="remoteDbUser" name="remoteDbUser" placeholder="database_user" required>
                        <small>Database username on remote server</small>
                    </div>

                    <div class="form-group">
                        <label for="remoteDbPass">Remote DB Password</label>
                        <input type="password" id="remoteDbPass" name="remoteDbPass" placeholder="database_password" required>
                        <small>Database password on remote server</small>
                    </div>

                    <div class="form-group">
                        <label for="remoteDbName">Remote Database Name</label>
                        <input type="text" id="remoteDbName" name="remoteDbName" placeholder="remote_database" required>
                        <small>Name of database on remote server</small>
                    </div>

                    <!-- Local Database Settings -->
                    <div class="form-group">
                        <label for="localDbName">
                            Local Database Name
                            <span id="localDbLock" style="color: var(--color-text-tertiary); font-size: 12px; margin-left: 5px;">🔒</span>
                        </label>
                        <input type="text" id="localDbName" name="localDbName" placeholder="Specify remote DB first" readonly style="background: #F0F4F8; cursor: not-allowed;" required>
                        <small id="localDbHelp">Auto-synced from remote database name (editable after setting remote)</small>
                    </div>

                    <div class="form-group">
                        <label for="chunkSize">Chunk Size (rows per batch)</label>
                        <input type="number" id="chunkSize" name="chunkSize" value="1000" min="100" max="10000" required>
                        <small>Number of rows to transfer per request</small>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary" id="syncBtn">
                        <span>🔄</span>
                        <span>Start Sync</span>
                    </button>
                    <button type="button" class="btn btn-secondary" id="testConnectionBtn">
                        <span>🔌</span>
                        <span>Test Connection</span>
                    </button>
                    <button type="button" class="btn btn-secondary" id="clearFormBtn">
                        <span>🗑️</span>
                        <span>Clear Form</span>
                    </button>
                    <a href="check_ip.php" class="btn btn-secondary" style="text-decoration: none;">
                        <span>🌐</span>
                        <span>Check My IP</span>
                    </a>
                </div>
            </form>
        </div>

        <!-- Progress Card -->
        <div class="sync-card progress-container" id="progressCard">
            <h2>📊 Sync Progress</h2>
            
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill">0%</div>
            </div>
            
            <div class="progress-text" id="progressText">Initializing...</div>

            <div class="stats-grid" id="statsGrid" style="display: none;">
                <div class="stat-card">
                    <div class="stat-value" id="statTables">0</div>
                    <div class="stat-label">Tables</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="statRows">0</div>
                    <div class="stat-label">Rows Synced</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="statViews">0</div>
                    <div class="stat-label">Views</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="statTime">0s</div>
                    <div class="stat-label">Elapsed Time</div>
                </div>
            </div>

            <div class="log-container" id="logContainer"></div>
        </div>
    </div>

    <?php include __DIR__ . '/../templates/footer.php'; ?>

    <script>
        <?php echo file_get_contents(__DIR__ . '/sync.js'); ?>
    </script>
</body>
</html>


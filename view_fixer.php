<?php
/**
 * View Definer Fixer Tool
 * Automatically fixes MySQL view definer issues
 */
require_once 'login/auth_check.php';
require_once 'db_config.php';

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        $conn = getDbConnection();
        $action = $_POST['action'];
        
        switch ($action) {
            case 'getViews':
                getViewsInfo($conn);
                break;
                
            case 'fixView':
                $database = $_POST['database'] ?? '';
                $viewName = $_POST['viewName'] ?? '';
                fixViewDefiner($conn, $database, $viewName);
                break;
                
            case 'fixAllViews':
                $database = $_POST['database'] ?? '';
                fixAllViewDefiners($conn, $database);
                break;
                
            default:
                throw new Exception("Invalid action");
        }
        
        closeDbConnection($conn);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

/**
 * Get all views with their definer information
 */
function getViewsInfo($conn) {
    // Get all databases
    $result = $conn->query("SHOW DATABASES");
    $systemDatabases = ['information_schema', 'performance_schema', 'mysql', 'sys'];
    $allViews = [];
    
    while ($row = $result->fetch_array()) {
        $dbName = $row[0];
        if (in_array($dbName, $systemDatabases)) {
            continue;
        }
        
        // Get views in this database
        $conn->query("USE `$dbName`");
        $viewsResult = $conn->query("
            SELECT 
                TABLE_NAME,
                DEFINER,
                SECURITY_TYPE,
                IS_UPDATABLE
            FROM information_schema.VIEWS
            WHERE TABLE_SCHEMA = '$dbName'
        ");
        
        if ($viewsResult && $viewsResult->num_rows > 0) {
            while ($view = $viewsResult->fetch_assoc()) {
                // Check if definer exists
                $definer = $view['DEFINER'];
                list($user, $host) = explode('@', $definer);
                $user = trim($user, "'`");
                $host = trim($host, "'`");
                
                $userCheck = $conn->query("
                    SELECT COUNT(*) as count 
                    FROM mysql.user 
                    WHERE User = '$user' AND Host = '$host'
                ");
                
                $definerExists = false;
                if ($userCheck) {
                    $userRow = $userCheck->fetch_assoc();
                    $definerExists = $userRow['count'] > 0;
                }
                
                $allViews[] = [
                    'database' => $dbName,
                    'name' => $view['TABLE_NAME'],
                    'definer' => $definer,
                    'definerExists' => $definerExists,
                    'securityType' => $view['SECURITY_TYPE'],
                    'isUpdatable' => $view['IS_UPDATABLE']
                ];
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'views' => $allViews,
        'currentUser' => $conn->query("SELECT CURRENT_USER()")->fetch_row()[0]
    ]);
}

/**
 * Fix a single view's definer
 */
function fixViewDefiner($conn, $database, $viewName) {
    $conn->query("USE `$database`");
    
    // Get current view definition
    $result = $conn->query("SHOW CREATE VIEW `$viewName`");
    if (!$result) {
        throw new Exception("Failed to get view definition: " . $conn->error);
    }
    
    $row = $result->fetch_assoc();
    $createStatement = $row['Create View'];
    
    // Extract the SELECT part (everything after AS)
    if (!preg_match('/\s+AS\s+(.+)$/is', $createStatement, $matches)) {
        throw new Exception("Could not parse view definition");
    }
    $selectStatement = $matches[1];
    
    // Get current user to use as new definer
    $currentUser = $conn->query("SELECT CURRENT_USER()")->fetch_row()[0];
    
    // Drop and recreate the view
    if (!$conn->query("DROP VIEW `$viewName`")) {
        throw new Exception("Failed to drop view: " . $conn->error);
    }
    
    $newCreateStatement = "CREATE DEFINER=$currentUser VIEW `$viewName` AS $selectStatement";
    
    if (!$conn->query($newCreateStatement)) {
        throw new Exception("Failed to recreate view: " . $conn->error);
    }
    
    echo json_encode([
        'success' => true,
        'message' => "View '$viewName' definer updated successfully to $currentUser"
    ]);
}

/**
 * Fix all views in a database
 */
function fixAllViewDefiners($conn, $database) {
    $conn->query("USE `$database`");
    
    // Get all views in database
    $result = $conn->query("
        SELECT TABLE_NAME 
        FROM information_schema.VIEWS 
        WHERE TABLE_SCHEMA = '$database'
    ");
    
    if (!$result) {
        throw new Exception("Failed to get views: " . $conn->error);
    }
    
    $fixed = 0;
    $failed = [];
    
    while ($row = $result->fetch_assoc()) {
        $viewName = $row['TABLE_NAME'];
        
        try {
            // Get current view definition
            $viewResult = $conn->query("SHOW CREATE VIEW `$viewName`");
            if (!$viewResult) {
                throw new Exception("Failed to get view definition");
            }
            
            $viewRow = $viewResult->fetch_assoc();
            $createStatement = $viewRow['Create View'];
            
            // Extract the SELECT part
            if (!preg_match('/\s+AS\s+(.+)$/is', $createStatement, $matches)) {
                throw new Exception("Could not parse view definition");
            }
            $selectStatement = $matches[1];
            
            // Get current user
            $currentUser = $conn->query("SELECT CURRENT_USER()")->fetch_row()[0];
            
            // Drop and recreate
            if (!$conn->query("DROP VIEW `$viewName`")) {
                throw new Exception("Failed to drop view");
            }
            
            $newCreateStatement = "CREATE DEFINER=$currentUser VIEW `$viewName` AS $selectStatement";
            
            if (!$conn->query($newCreateStatement)) {
                throw new Exception("Failed to recreate view");
            }
            
            $fixed++;
            
        } catch (Exception $e) {
            $failed[] = [
                'view' => $viewName,
                'error' => $e->getMessage()
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Fixed $fixed view(s)" . (count($failed) > 0 ? ", " . count($failed) . " failed" : ""),
        'fixed' => $fixed,
        'failed' => $failed
    ]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Definer Fixer - Database CRUD Manager</title>
    <style>
        :root {
            /* Sapphire Nightfall Whisper Color Palette */
            --color-sapphire-bright: #0474C4;
            --color-sapphire-muted: #5379AE;
            --color-sapphire-dark: #2C444C;
            --color-sapphire-light: #A8C4EC;
            --color-sapphire-rich: #06457F;
            --color-sapphire-navy: #262B40;
            
            --color-primary: var(--color-sapphire-rich);
            --color-primary-light: var(--color-sapphire-bright);
            --color-primary-lighter: var(--color-sapphire-muted);
            --color-primary-lightest: var(--color-sapphire-light);
            --color-primary-pale: #E8F2FF;
            
            --color-success: var(--color-sapphire-dark);
            --color-success-light: #4A6B73;
            
            --color-danger: #C44704;
            --color-danger-light: #E06B3A;
            --color-danger-lighter: #F08F70;
            
            --color-warning: #C4A004;
            --color-warning-light: #E0C63A;
            --color-warning-pale: #FFF9E6;
            
            --color-text-primary: var(--color-sapphire-navy);
            --color-text-secondary: var(--color-sapphire-dark);
            --color-text-tertiary: var(--color-sapphire-muted);
            --color-text-muted: #8A9BA8;
            
            --color-bg-body-start: #F8FAFC;
            --color-bg-body-end: #F0F4F8;
            --color-bg-white: #FFFFFF;
            --color-bg-light: #F5F8FC;
            --color-bg-lighter: #E8F2FF;
            --color-bg-hover: #F0F7FF;
            
            --color-border-primary: var(--color-sapphire-light);
            --color-border-light: #D1E0F0;
            --color-border-lighter: #E0E8F0;
            
            --shadow-sm: 0 2px 8px rgba(6, 69, 127, 0.08);
            --shadow-md: 0 4px 12px rgba(6, 69, 127, 0.12);
            --shadow-lg: 0 4px 20px rgba(6, 69, 127, 0.10);
            --shadow-xl: 0 10px 40px rgba(6, 69, 127, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--color-bg-body-start) 0%, var(--color-bg-body-end) 100%);
            color: var(--color-text-primary);
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: var(--color-bg-white);
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, var(--color-primary-lightest) 0%, var(--color-bg-white) 100%);
            padding: 25px 30px;
            border-bottom: 3px solid var(--color-primary-light);
        }

        .header h1 {
            color: var(--color-primary);
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .header p {
            color: var(--color-text-secondary);
            font-size: 14px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--color-primary);
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: var(--color-primary-light);
            transform: translateX(-2px);
        }

        .content {
            padding: 30px;
        }

        .info-box {
            background: var(--color-warning-pale);
            border: 2px solid var(--color-warning-light);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .info-box h3 {
            color: var(--color-warning);
            margin-bottom: 10px;
            font-size: 18px;
        }

        .info-box p {
            color: var(--color-text-secondary);
            line-height: 1.6;
            margin-bottom: 8px;
        }

        .info-box ul {
            margin-left: 20px;
            margin-top: 10px;
        }

        .info-box li {
            color: var(--color-text-secondary);
            margin-bottom: 5px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: var(--color-bg-lighter);
            border: 1px solid var(--color-border-light);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }

        .stat-card h3 {
            color: var(--color-primary);
            font-size: 32px;
            margin-bottom: 5px;
        }

        .stat-card p {
            color: var(--color-text-secondary);
            font-size: 14px;
        }

        .stat-card.warning h3 {
            color: var(--color-danger);
        }

        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: var(--color-bg-light);
            border-radius: 8px;
        }

        button {
            font-family: inherit;
            font-size: 14px;
            padding: 10px 20px;
            border: 2px solid var(--color-border-primary);
            border-radius: 6px;
            outline: none;
            transition: all 0.3s ease;
            cursor: pointer;
            font-weight: 600;
        }

        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--color-primary-light) 0%, var(--color-primary-lighter) 100%);
            color: var(--color-bg-white);
            border: 2px solid var(--color-primary);
        }

        .btn-primary:hover:not(:disabled) {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-light) 100%);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--color-success-light) 0%, var(--color-success) 100%);
            color: var(--color-bg-white);
            border: 2px solid var(--color-success);
        }

        .btn-success:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--color-warning-light) 0%, var(--color-warning) 100%);
            color: var(--color-bg-white);
            border: 2px solid var(--color-warning);
        }

        .btn-warning:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .table-wrapper {
            overflow-x: auto;
            border: 2px solid var(--color-border-light);
            border-radius: 8px;
            background: var(--color-bg-white);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        thead {
            background: linear-gradient(135deg, var(--color-primary-lightest) 0%, var(--color-primary-pale) 100%);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        th {
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            color: var(--color-sapphire-navy);
            border-bottom: 2px solid var(--color-primary-light);
            white-space: nowrap;
        }

        tbody tr {
            border-bottom: 1px solid var(--color-border-lighter);
            transition: all 0.2s ease;
        }

        tbody tr:hover {
            background: var(--color-bg-hover);
        }

        td {
            padding: 12px;
            color: var(--color-text-secondary);
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-ok {
            background: #d4edda;
            color: #155724;
        }

        .status-error {
            background: #f8d7da;
            color: #721c24;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: var(--color-text-muted);
        }

        .spinner {
            border: 4px solid var(--color-border-light);
            border-top: 4px solid var(--color-primary-light);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--color-sapphire-dark);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: var(--shadow-xl);
            z-index: 3000;
            display: none;
            max-width: 400px;
        }

        .toast.active {
            display: block;
            animation: slideInRight 0.3s ease;
        }

        .toast.success {
            background: var(--color-success);
        }

        .toast.error {
            background: var(--color-danger);
        }

        .toast.warning {
            background: var(--color-warning);
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--color-text-muted);
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        /* Navigation Menu Styles */
        .nav-menu {
            display: flex;
            gap: 0;
            margin-top: 20px;
            border-bottom: 2px solid var(--color-border-light);
            background: var(--color-bg-white);
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 15px 20px;
            text-decoration: none;
            color: var(--color-text-secondary);
            font-weight: 500;
            font-size: 14px;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-link:hover {
            color: var(--color-primary);
            background: var(--color-bg-hover);
            border-bottom-color: var(--color-primary-light);
        }

        .nav-link.active {
            color: var(--color-primary);
            background: var(--color-bg-lighter);
            border-bottom-color: var(--color-primary);
            font-weight: 600;
        }

        .nav-icon {
            font-size: 16px;
        }
    </style>
</head>
<body>
    <?php
    $pageConfig = [
        'id' => 'view_fixer',
        'title' => 'View Definer Fixer',
        'icon' => '🔧',
        'controls_html' => ''
    ];
    include 'templates/header.php';
    ?>

        <div style="margin-bottom: 20px;">
            <a href="database_manager.php" style="display: inline-flex; align-items: center; gap: 8px; color: var(--color-primary); text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                ← Back to Database Manager
            </a>
        </div>

        <div class="info-box">
            <h3>⚠️ What This Tool Does</h3>
            <p>This tool fixes MySQL view definer errors that occur when:</p>
            <ul>
                <li>Views were created by a MySQL user that no longer exists</li>
                <li>Database was imported/migrated from another server</li>
                <li>You see errors like: "The user specified as a definer does not exist"</li>
            </ul>
            <p><strong>How it works:</strong> The tool recreates views with your current MySQL user as the definer, preserving the original SELECT statement.</p>
        </div>

        <div id="statsGrid" class="stats-grid">
            <!-- Stats will be populated here -->
        </div>

        <div class="action-bar">
            <div>
                <strong>Current MySQL User:</strong> <span id="currentUser" style="color: var(--color-primary);">Loading...</span>
            </div>
            <div style="display: flex; gap: 10px;">
                <button class="btn-primary" onclick="loadViews()">🔄 Refresh</button>
                <button class="btn-warning" id="fixAllBtn" onclick="fixAllViews()" disabled>🔧 Fix All Problematic Views</button>
            </div>
        </div>

        <div id="loading" class="loading">
            <div class="spinner"></div>
            <p>Loading views...</p>
        </div>

        <div id="viewsTable" style="display: none;">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Database</th>
                            <th>View Name</th>
                            <th>Current Definer</th>
                            <th>Status</th>
                            <th>Security Type</th>
                            <th>Updatable</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="viewsBody">
                        <!-- Views will be populated here -->
                    </tbody>
                </table>
            </div>
        </div>

        <div id="emptyState" class="empty-state" style="display: none;">
            <div class="empty-state-icon">✅</div>
            <h3>No Views Found</h3>
            <p>No database views found in your databases, or all views are working correctly.</p>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let allViews = [];
        let currentUser = '';

        // Load views on page load
        $(document).ready(function() {
            loadViews();
        });

        // Load all views
        function loadViews() {
            $('#loading').show();
            $('#viewsTable').hide();
            $('#emptyState').hide();

            $.ajax({
                url: 'view_fixer.php',
                method: 'POST',
                data: { action: 'getViews' },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        allViews = response.views;
                        currentUser = response.currentUser;
                        $('#currentUser').text(currentUser);
                        displayViews();
                        updateStats();
                    }
                    $('#loading').hide();
                },
                error: function(xhr) {
                    showToast('Error loading views: ' + (xhr.responseJSON?.error || xhr.responseText), 'error');
                    $('#loading').hide();
                }
            });
        }

        // Display views in table
        function displayViews() {
            const tbody = $('#viewsBody');
            tbody.empty();

            if (allViews.length === 0) {
                $('#emptyState').show();
                $('#fixAllBtn').prop('disabled', true);
                return;
            }

            $('#viewsTable').show();

            allViews.forEach(function(view) {
                const statusBadge = view.definerExists 
                    ? '<span class="status-badge status-ok">✓ OK</span>'
                    : '<span class="status-badge status-error">✗ Definer Missing</span>';

                const actionBtn = !view.definerExists
                    ? `<button class="btn-success" style="padding: 6px 12px; font-size: 12px;" onclick="fixView('${view.database}', '${view.name}')">🔧 Fix</button>`
                    : '<span style="color: var(--color-text-muted);">No action needed</span>';

                const row = `
                    <tr>
                        <td><strong>${view.database}</strong></td>
                        <td>👁️ ${view.name}</td>
                        <td><code style="background: var(--color-bg-lighter); padding: 2px 6px; border-radius: 4px;">${view.definer}</code></td>
                        <td>${statusBadge}</td>
                        <td>${view.securityType}</td>
                        <td>${view.isUpdatable}</td>
                        <td>${actionBtn}</td>
                    </tr>
                `;
                tbody.append(row);
            });

            // Enable fix all button if there are problematic views
            const problematicViews = allViews.filter(v => !v.definerExists);
            $('#fixAllBtn').prop('disabled', problematicViews.length === 0);
        }

        // Update statistics
        function updateStats() {
            const totalViews = allViews.length;
            const problematicViews = allViews.filter(v => !v.definerExists).length;
            const okViews = totalViews - problematicViews;
            const databases = [...new Set(allViews.map(v => v.database))].length;

            const html = `
                <div class="stat-card">
                    <h3>${totalViews}</h3>
                    <p>Total Views</p>
                </div>
                <div class="stat-card">
                    <h3>${okViews}</h3>
                    <p>Working Views</p>
                </div>
                <div class="stat-card warning">
                    <h3>${problematicViews}</h3>
                    <p>Problematic Views</p>
                </div>
                <div class="stat-card">
                    <h3>${databases}</h3>
                    <p>Databases</p>
                </div>
            `;

            $('#statsGrid').html(html);
        }

        // Fix a single view
        function fixView(database, viewName) {
            if (!confirm(`Fix view "${viewName}" in database "${database}"?\n\nThis will recreate the view with your current user (${currentUser}) as the definer.`)) {
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.textContent = '🔄 Fixing...';

            $.ajax({
                url: 'view_fixer.php',
                method: 'POST',
                data: {
                    action: 'fixView',
                    database: database,
                    viewName: viewName
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast(response.message, 'success');
                        loadViews(); // Reload to show updated status
                    }
                },
                error: function(xhr) {
                    showToast('Error fixing view: ' + (xhr.responseJSON?.error || xhr.responseText), 'error');
                    btn.disabled = false;
                    btn.textContent = '🔧 Fix';
                }
            });
        }

        // Fix all problematic views
        function fixAllViews() {
            const problematicViews = allViews.filter(v => !v.definerExists);
            
            if (problematicViews.length === 0) {
                showToast('No problematic views to fix!', 'warning');
                return;
            }

            const databases = [...new Set(problematicViews.map(v => v.database))];
            
            if (!confirm(`Fix ${problematicViews.length} problematic view(s) across ${databases.length} database(s)?\n\nDatabases: ${databases.join(', ')}\n\nAll views will be recreated with your current user (${currentUser}) as the definer.`)) {
                return;
            }

            const btn = $('#fixAllBtn');
            btn.prop('disabled', true).text('🔄 Fixing All...');

            // Fix views database by database
            let fixed = 0;
            let failed = 0;

            const fixNextDatabase = (index) => {
                if (index >= databases.length) {
                    // All done
                    showToast(`Fixed ${fixed} view(s)` + (failed > 0 ? `, ${failed} failed` : ''), fixed > 0 ? 'success' : 'warning');
                    loadViews(); // Reload to show updated status
                    return;
                }

                const database = databases[index];
                
                $.ajax({
                    url: 'view_fixer.php',
                    method: 'POST',
                    data: {
                        action: 'fixAllViews',
                        database: database
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            fixed += response.fixed;
                            failed += response.failed.length;
                            
                            if (response.failed.length > 0) {
                                console.error('Failed views in ' + database + ':', response.failed);
                            }
                        }
                        fixNextDatabase(index + 1);
                    },
                    error: function(xhr) {
                        failed += problematicViews.filter(v => v.database === database).length;
                        console.error('Error fixing database ' + database + ':', xhr.responseJSON?.error);
                        fixNextDatabase(index + 1);
                    }
                });
            };

            fixNextDatabase(0);
        }

        // Show toast notification
        function showToast(message, type = 'success') {
            const toast = $('#toast');
            toast.text(message);
            toast.removeClass('success error warning');
            toast.addClass(type);
            toast.addClass('active');

            setTimeout(function() {
                toast.removeClass('active');
            }, 4000);
        }
    </script>

    <?php include 'templates/footer.php'; ?>
</body>
</html>


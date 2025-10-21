<?php
/**
 * View Definer Fixer Tool
 * Automatically fixes MySQL view definer issues
 */
require_once '../login/auth_check.php';
require_once '../db_config.php';

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
    <link rel="stylesheet" href="../styles/common.css">
    <link rel="stylesheet" href="view_fixer.css">
</head>
<body>
    <?php
    $pageConfig = [
        'id' => 'view_fixer',
        'title' => 'View Definer Fixer',
        'icon' => '🔧',
        'controls_html' => ''
    ];
    include '../templates/header.php';
    ?>

    <div style="margin-bottom: 20px;">
        <a href="../db_manager/" style="display: inline-flex; align-items: center; gap: 8px; color: var(--color-primary); text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
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

    <!-- Toast Notification -->
    <div class="toast" id="toast"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="view_fixer.js"></script>

    <?php include '../templates/footer.php'; ?>
</body>
</html>

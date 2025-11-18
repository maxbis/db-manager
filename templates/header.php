<?php
/**
 * Reusable Header Template
 * 
 * Usage:
 * $pageConfig = [
 *     'id' => 'index',                           // Page identifier for active menu highlighting
 *     'title' => 'Database CRUD Manager',        // Page title (without icon)
 *     'icon' => '📊',                            // Page icon/emoji
 *     'controls_html' => '<div>...</div>'        // Optional: Custom HTML for controls section
 * ];
 * include 'templates/header.php';
 */

// Ensure db_config is loaded for database functions
if (!function_exists('getCurrentDatabase')) {
    try {
        require_once __DIR__ . '/../db_config.php';
    } catch (Exception $e) {
        // If db_config.php fails to load, log error but don't break the page
        error_log('Failed to load db_config.php: ' . $e->getMessage());
    }
}

// Default values
$pageConfig = array_merge([
    'id' => '',
    'title' => 'Database Manager',
    'icon' => '📊',
    'controls_html' => ''
], $pageConfig ?? []);

// Ensure session is started before accessing $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determine relative path prefix for shared assets based on current script depth
$scriptDirectory = trim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($scriptDirectory === '.' || $scriptDirectory === DIRECTORY_SEPARATOR) {
    $scriptDirectory = '';
}
$directoryDepth = ($scriptDirectory === '' ? 0 : substr_count($scriptDirectory, '/') + 1);
$pathPrefix = str_repeat('../', $directoryDepth);

$sessionStatusUrl = $pathPrefix . 'login/session_status.php';
$loginUrl = $pathPrefix . 'login/login.php';
$sessionHandlerScript = $pathPrefix . 'assets/js/session_handler.js';

// Define menu items
$menuItems = [
    [
        'id' => 'database',
        'url' => '../db_manager',
        'icon' => '🗄️',
        'name' => 'Database Manager'
    ],
    [
        'id' => 'query',
        'url' => '../query_builder',
        'icon' => '⚡',
        'name' => 'SQL Query Builder'
    ],
    [
        'id' => 'data',
        'url' => '../data_manager',
        'icon' => '📊',
        'name' => 'Data Manager'
    ],
    [
        'id' => 'table',
        'url' => '../table_structure',
        'icon' => '🔍',
        'name' => 'Table Structure'
    ]
];

// Get selected table from session (with URL parameter as fallback for backward compatibility)
$selectedTable = $_SESSION['current_table'] ?? $_GET['table'] ?? '';

// Get current database if available
$currentDatabase = null;

// Priority order: URL parameter > session cache > getCurrentDatabase() > DB_NAME
$currentDatabase = $_GET['database'] ?? $_SESSION['auto_selected_database'] ?? null;

// If still no database, try getCurrentDatabase() function
if (!$currentDatabase && function_exists('getCurrentDatabase')) {
    try {
        $currentDatabase = getCurrentDatabase();
    } catch (Exception $e) {
        // Silently fail - database credentials might not be available yet
        // This is expected when user hasn't logged in or credentials aren't configured
        $currentDatabase = null;
    }
}

// Final fallback - no default database (DB_NAME constant may not be set)
// if (!$currentDatabase) {
//     $currentDatabase = DB_NAME;
// }

// Format database display: database.table if table is selected, otherwise just database
$databaseDisplay = $currentDatabase;
if (!empty($selectedTable) && !empty($currentDatabase)) {
    $databaseDisplay = $currentDatabase . ' -  ' . $selectedTable;
}
?>

<script>
window.APP_SESSION_CONFIG = Object.assign({}, window.APP_SESSION_CONFIG || {}, {
    statusUrl: '<?php echo htmlspecialchars($sessionStatusUrl, ENT_QUOTES); ?>',
    loginUrl: '<?php echo htmlspecialchars($loginUrl, ENT_QUOTES); ?>'
});
</script>
<script defer src="<?php echo htmlspecialchars($sessionHandlerScript, ENT_QUOTES); ?>"></script>

<div class="container">
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <h1 style="margin: 0;"><?php echo $pageConfig['icon']; ?> <?php echo htmlspecialchars($pageConfig['title']); ?></h1>
            
            <?php if (isset($_SESSION['username'])): ?>
            <div class="control-group">
                <span style="font-size: 12px; color: var(--color-text-tertiary);">
                    👤 <?php echo htmlspecialchars($_SESSION['username']); ?>
                </span>
                <a href="../login/logout.php" style="padding: 8px 12px; font-size: 12px; text-decoration: none; background: linear-gradient(135deg, var(--color-danger-lighter) 0%, var(--color-danger-lightest) 100%); color: var(--color-danger); border: 1px solid var(--color-danger-light); border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.3s ease;">
                    🚪 Logout
                </a>
            </div>
            <?php else: ?>
            <div class="control-group">
                <a href="../login/login.php" style="padding: 8px 12px; font-size: 12px; text-decoration: none; background: linear-gradient(135deg, var(--color-primary-lighter) 0%, var(--color-primary-lightest) 100%); color: var(--color-primary); border: 1px solid var(--color-primary-light); border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.3s ease;">
                    🔐 Login
                </a>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="controls">
            <?php echo $pageConfig['controls_html']; ?>
            
            <?php if (!empty($currentDatabase)): ?>
            <div class="control-group" style="margin-left: auto;">
                <span style="font-size: 18px; color: var(--color-text-tertiary); display: flex; align-items: center; gap: 6px;">
                    <span style="color: var(--color-success); padding: 6px 10px; border-radius: 6px; font-weight: 600; margin-right: 0px;">
                        🗄️ <?php echo htmlspecialchars($databaseDisplay); ?>
                    </span>
                </span>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Navigation Menu -->
        <nav class="nav-menu">
            <?php foreach ($menuItems as $item): ?>
                <?php 
                $activeClass = ($pageConfig['id'] === $item['id']) ? 'active' : '';
                $url = $item['url'];
                // No need to add table parameters - using session-based storage
                ?>
                <a href="<?php echo htmlspecialchars($url); ?>" class="<?php echo $activeClass; ?> nav-link">
                    <span class="nav-icon"><?php echo $item['icon']; ?></span>
                    <span><?php echo htmlspecialchars($item['name']); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <div class="content">


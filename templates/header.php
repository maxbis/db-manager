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
    require_once __DIR__ . '/../db_config.php';
}

// Default values
$pageConfig = array_merge([
    'id' => '',
    'title' => 'Database Manager',
    'icon' => '📊',
    'controls_html' => ''
], $pageConfig ?? []);

// Define menu items
$menuItems = [
    [
        'id' => 'database_manager',
        'url' => '../db_manager',
        'icon' => '🗄️',
        'name' => 'Database Manager'
    ],
    [
        'id' => 'index',
        'url' => '../data_manager',
        'icon' => '📊',
        'name' => 'Data Manager'
    ],
    [
        'id' => 'table_structure',
        'url' => '../table_structure',
        'icon' => '🔍',
        'name' => 'Table Structure'
    ],
    [
        'id' => 'query',
        'url' => '../query_builder',
        'icon' => '⚡',
        'name' => 'SQL Query Builder'
    ]
];

// Get selected table from URL if available
$selectedTable = isset($_GET['table']) ? $_GET['table'] : '';

// Get current database if available
$currentDatabase = null;

// Priority order: URL parameter > session cache > getCurrentDatabase() > DB_NAME
$currentDatabase = $_GET['database'] ?? $_SESSION['auto_selected_database'] ?? null;

// If still no database, try getCurrentDatabase() function
if (!$currentDatabase && function_exists('getCurrentDatabase')) {
    $currentDatabase = getCurrentDatabase();
}

// Final fallback to DB_NAME
if (!$currentDatabase) {
    $currentDatabase = DB_NAME;
}

// Format database display: database.table if table is selected, otherwise just database
$databaseDisplay = $currentDatabase;
if (!empty($selectedTable) && !empty($currentDatabase)) {
    $databaseDisplay = $currentDatabase . ' -  ' . $selectedTable;
}
?>

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
                // Add table parameter if available and not database_manager page
                if ($selectedTable && $item['id'] !== 'database_manager') {
                    $url .= '?table=' . urlencode($selectedTable);
                }
                ?>
                <a href="<?php echo htmlspecialchars($url); ?>" class="<?php echo $activeClass; ?> nav-link">
                    <span class="nav-icon"><?php echo $item['icon']; ?></span>
                    <span><?php echo htmlspecialchars($item['name']); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <div class="content">


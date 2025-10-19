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
        'url' => 'database_manager.php',
        'icon' => '🗄️',
        'name' => 'Database Manager'
    ],
    [
        'id' => 'index',
        'url' => 'index.php',
        'icon' => '📊',
        'name' => 'Data Manager'
    ],
    [
        'id' => 'table_structure',
        'url' => 'table_structure.php',
        'icon' => '🔍',
        'name' => 'Table Structure'
    ],
    [
        'id' => 'query',
        'url' => 'query.php',
        'icon' => '⚡',
        'name' => 'SQL Query Builder'
    ]
];

// Get selected table from URL if available
$selectedTable = isset($_GET['table']) ? $_GET['table'] : '';
?>

<div class="container">
    <div class="header">
        <h1><?php echo $pageConfig['icon']; ?> <?php echo htmlspecialchars($pageConfig['title']); ?></h1>
        
        <div class="controls">
            <?php echo $pageConfig['controls_html']; ?>
            
            <?php if (isset($_SESSION['username'])): ?>
            <div class="control-group" style="margin-left: auto;">
                <span style="font-size: 12px; color: var(--color-text-tertiary);">
                    👤 <?php echo htmlspecialchars($_SESSION['username']); ?>
                </span>
                <a href="login/logout.php" style="padding: 8px 12px; font-size: 12px; text-decoration: none; background: linear-gradient(135deg, var(--color-danger-lighter) 0%, var(--color-danger-lightest) 100%); color: var(--color-danger); border: 1px solid var(--color-danger-light); border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.3s ease;">
                    🚪 Logout
                </a>
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


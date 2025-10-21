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
    <link rel="stylesheet" href="sync.css">
</head>
<body>
    <?php include __DIR__ . '/../templates/header.php'; ?>

    <div class="sync-container">
        <?php include __DIR__ . '/partials/alerts.php'; ?>

        <?php include __DIR__ . '/partials/config_form.php'; ?>

        <?php include __DIR__ . '/partials/progress_card.php'; ?>
    </div>

    <?php include __DIR__ . '/../templates/footer.php'; ?>

    <script src="sync.js"></script>
</body>
</html>


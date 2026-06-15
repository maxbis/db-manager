<?php
/**
 * Database Sync - Client Page
 * 
 * This page allows you to sync a database from a remote server to local.
 * It uses the same authentication system as other pages.
 */
require_once __DIR__ . '/../login/auth_check.php';
require_once __DIR__ . '/../db_connection.php';

$syncConfigPath = __DIR__ . '/config.php';
$hasSyncConfig = is_file($syncConfigPath);

if ($hasSyncConfig) {
    require_once $syncConfigPath;
}

// Derive a human-readable label for the local (target) server
$targetServerHost = gethostname();
if (!$targetServerHost) {
    $targetServerHost = $_SERVER['SERVER_NAME'] ?? 'this server';
}

$targetDbHost = null;
try {
    $credentials = getDbCredentials();
    $targetDbHost = $credentials['host'] ?? null;
} catch (Exception $e) {
    $targetDbHost = null;
}

$targetServerLabel = $targetServerHost;
if ($targetDbHost) {
    $targetServerLabel .= ' (DB host: ' . $targetDbHost . ')';
}

// Canonical local sync API URL and hostnames (for self-sync protection in the client)
$localApiScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$localHttpHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$syncBasePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/sync_db'), '/\\');
$localApiUrl = $localApiScheme . '://' . $localHttpHost . $syncBasePath . '/api.php';

$localHosts = [];
$hostFromHeader = strtolower(parse_url($localApiScheme . '://' . $localHttpHost, PHP_URL_HOST)
    ?: preg_replace('/:\d+$/', '', $localHttpHost));
if ($hostFromHeader !== '') {
    $localHosts[] = $hostFromHeader;
}
if (!empty($_SERVER['SERVER_NAME'])) {
    $localHosts[] = strtolower($_SERVER['SERVER_NAME']);
}
if ($targetServerHost) {
    $localHosts[] = strtolower($targetServerHost);
}
$localHosts = array_values(array_unique(array_filter($localHosts)));

$remoteUrlPresets = [];
$settingsFile = __DIR__ . '/../settings/settings.json';
$defaultSyncSettings = [
    'database_sync' => [
        'remote server presets' => [],
    ],
];
$syncSettings = $defaultSyncSettings;
if (is_file($settingsFile)) {
    $jsonContent = file_get_contents($settingsFile);
    $decoded = json_decode($jsonContent, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $syncSettings = array_replace_recursive($defaultSyncSettings, $decoded);
    }
}

$presets = $syncSettings['database_sync']['remote server presets'] ?? [];
if (empty($presets)) {
    $legacyUrl = trim($syncSettings['database_sync']['remote server URL'] ?? '');
    if ($legacyUrl !== '') {
        $host = parse_url($legacyUrl, PHP_URL_HOST);
        $presets = [
            [
                'label' => $host ?: $legacyUrl,
                'url' => $legacyUrl,
            ],
        ];
    }
}

foreach ($presets as $preset) {
    if (!is_array($preset)) {
        continue;
    }
    $url = trim($preset['url'] ?? '');
    if ($url === '') {
        continue;
    }
    $label = trim($preset['label'] ?? '');
    if ($label === '') {
        $label = parse_url($url, PHP_URL_HOST) ?: $url;
    }
    $remoteUrlPresets[] = [
        'label' => $label,
        'url' => $url,
    ];
}

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
    <link rel="stylesheet" href="../dialog/dialog.css">
</head>
<body>
    <?php include __DIR__ . '/../templates/header.php'; ?>

    <script>
        window.SYNC_TARGET_SERVER_LABEL = <?php echo json_encode($targetServerLabel); ?>;
        window.SYNC_TARGET_HOSTNAME = <?php echo json_encode($targetServerHost); ?>;
        window.SYNC_TARGET_DBHOST = <?php echo json_encode($targetDbHost); ?>;
        window.SYNC_LOCAL_API_URL = <?php echo json_encode($localApiUrl, JSON_UNESCAPED_SLASHES); ?>;
        window.SYNC_LOCAL_HOSTS = <?php echo json_encode($localHosts, JSON_UNESCAPED_SLASHES); ?>;
        window.SYNC_REMOTE_URL_PRESETS = <?php echo json_encode($remoteUrlPresets, JSON_UNESCAPED_SLASHES); ?>;
    </script>

    <div class="sync-container">
        <?php include __DIR__ . '/partials/alerts.php'; ?>

        <?php if (!$hasSyncConfig): ?>
        <div class="alert alert-warning">
            <span style="font-size: 20px;">⚠️</span>
            <div>
                <strong>Sync API not configured on this server</strong><br>
                The file <code>sync_db/config.php</code> is missing. If this server should act as a sync source, copy
                <code>sync_db/config.template.php</code> to <code>sync_db/config.php</code> and set the correct
                <code>SYNC_API_KEY</code>. You can still use this page as a client to sync from another server.
            </div>
        </div>
        <?php endif; ?>

        <?php include __DIR__ . '/partials/config_form.php'; ?>

        <?php include __DIR__ . '/partials/progress_card.php'; ?>
    </div>

    <?php include __DIR__ . '/../templates/footer.php'; ?>
    
    <?php include __DIR__ . '/../dialog/dialog.php'; ?>

    <script src="../dialog/dialog.js"></script>
    <script src="sync.js"></script>
</body>
</html>

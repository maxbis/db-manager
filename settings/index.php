<?php
/**
 * Settings Page
 * Allows authenticated users to edit application settings.
 */

require_once '../login/auth_check.php';

// Check authentication
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: ../login/login.php');
    exit;
}

$settingsFile = __DIR__ . '/settings.json';
$message = '';
$messageType = '';

// Default settings structure
$defaultSettings = [
    'database_sync' => [
        'remote server URL' => '',
        'API Key' => '',
        'remote DB host' => 'localhost'
    ],
    'Database Manager' => [
        'initial scroll' => true
    ],
    'Crud Manager' => [
        'records per page' => 20
    ]
];

// Load current settings
$currentSettings = $defaultSettings;
if (file_exists($settingsFile)) {
    $jsonContent = file_get_contents($settingsFile);
    $decoded = json_decode($jsonContent, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        // Merge with defaults to ensure all keys exist
        $currentSettings = array_replace_recursive($defaultSettings, $decoded);
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token if you have one, skipping for now as per context
    
    // Update settings from POST data
    $newSettings = $currentSettings;
    
    // Database Sync Settings
    if (isset($_POST['remote_server_url'])) {
        $newSettings['database_sync']['remote server URL'] = trim($_POST['remote_server_url']);
    }
    if (isset($_POST['api_key'])) {
        $newSettings['database_sync']['API Key'] = trim($_POST['api_key']);
    }
    if (isset($_POST['remote_db_host'])) {
        $newSettings['database_sync']['remote DB host'] = trim($_POST['remote_db_host']);
    }
    
    // Database Manager Settings
    // Checkbox: if set, true; else false
    $newSettings['Database Manager']['initial scroll'] = isset($_POST['initial_scroll']);
    
    // Crud Manager Settings
    if (isset($_POST['records_per_page'])) {
        $rpp = (int)$_POST['records_per_page'];
        if ($rpp < 20) $rpp = 20;
        if ($rpp > 1000) $rpp = 1000;
        $newSettings['Crud Manager']['records per page'] = $rpp;
    }
    
    // Save to file
    if (file_put_contents($settingsFile, json_encode($newSettings, JSON_PRETTY_PRINT))) {
        $message = 'Settings saved successfully.';
        $messageType = 'success';
        $currentSettings = $newSettings;
    } else {
        $message = 'Error saving settings. Please check file permissions.';
        $messageType = 'error';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Database Manager</title>
    <link rel="stylesheet" href="../styles/common.css">
    <link rel="stylesheet" href="settings.css">
</head>
<body>
    <?php
    $pageConfig = [
        'id' => 'settings',
        'title' => 'Settings',
        'icon' => '⚙️',
        'controls_html' => '' // No extra controls needed in header
    ];
    include '../templates/header.php';
    ?>

    <div class="content">
        <div class="settings-container">
            
            <?php if ($message): ?>
                <div class="toast active <?php echo $messageType; ?>" style="position: static; margin-bottom: 20px; max-width: 100%;">
                    <div class="toast-content">
                        <div class="toast-message"><?php echo htmlspecialchars($message); ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php">
                
                <!-- Database Sync Section -->
                <div class="settings-section">
                    <div class="settings-section-header">
                        <h2>Database Sync</h2>
                    </div>
                    <div class="settings-section-body">
                        <div class="form-group">
                            <label for="remote_server_url">Remote Server URL</label>
                            <input type="url" id="remote_server_url" name="remote_server_url" 
                                   value="<?php echo htmlspecialchars($currentSettings['database_sync']['remote server URL'] ?? ''); ?>"
                                   placeholder="https://example.com/api">
                        </div>
                        
                        <div class="form-group">
                            <label for="api_key">API Key</label>
                            <input type="text" id="api_key" name="api_key" 
                                   value="<?php echo htmlspecialchars($currentSettings['database_sync']['API Key'] ?? ''); ?>"
                                   placeholder="Enter your API key">
                        </div>
                        
                        <div class="form-group">
                            <label for="remote_db_host">Remote DB Host</label>
                            <input type="text" id="remote_db_host" name="remote_db_host" 
                                   value="<?php echo htmlspecialchars($currentSettings['database_sync']['remote DB host'] ?? 'localhost'); ?>"
                                   placeholder="localhost">
                        </div>
                    </div>
                </div>

                <!-- Database Manager Section -->
                <div class="settings-section">
                    <div class="settings-section-header">
                        <h2>Database Manager</h2>
                    </div>
                    <div class="settings-section-body">
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="initial_scroll" value="1" 
                                       <?php echo ($currentSettings['Database Manager']['initial scroll'] ?? true) ? 'checked' : ''; ?>>
                                Initial Scroll
                            </label>
                            <div class="field-info">Automatically scroll to the active database on load.</div>
                        </div>
                    </div>
                </div>

                <!-- Crud Manager Section -->
                <div class="settings-section">
                    <div class="settings-section-header">
                        <h2>Crud Manager</h2>
                    </div>
                    <div class="settings-section-body">
                        <div class="form-group">
                            <label for="records_per_page">Records Per Page</label>
                            <input type="number" id="records_per_page" name="records_per_page" 
                                   min="20" max="1000" step="10"
                                   value="<?php echo htmlspecialchars($currentSettings['Crud Manager']['records per page'] ?? 20); ?>">
                            <div class="field-info">Number of records to display per page (20 - 1000).</div>
                        </div>
                    </div>
                </div>

                <div class="settings-form-actions">
                    <button type="submit" class="btn-primary">💾 Save Settings</button>
                </div>

            </form>
        </div>
    </div>

    <?php include '../templates/footer.php'; ?>
</body>
</html>

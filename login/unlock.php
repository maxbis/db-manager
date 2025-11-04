<?php
/**
 * Account Unlock Utility
 * Removes lockout from a user account
 * 
 * Security: Only accessible from localhost for security
 */

// Security check - only allow from localhost
$allowedIPs = ['127.0.0.1', '::1'];
$clientIP = $_SERVER['REMOTE_ADDR'] ?? '';

if (!in_array($clientIP, $allowedIPs)) {
    http_response_code(403);
    die('Access denied. This utility can only be accessed from localhost.');
}

// Start session for CSRF protection
session_start();

// Include necessary files
$credentialsFile = __DIR__ . '/credentials.txt';

// CSRF token generation
if (!isset($_SESSION['unlock_csrf_token'])) {
    $_SESSION['unlock_csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$error = '';
$accountInfo = null;

// Process unlock request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['unlock_csrf_token']) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        
        if (empty($username)) {
            $error = 'Please enter a username';
        } elseif (!file_exists($credentialsFile)) {
            $error = 'No credentials found. Please run setup.php first.';
        } else {
            // Read credentials
            $credentials = file_get_contents($credentialsFile);
            $parts = explode('|', trim($credentials));
            
            if (count($parts) >= 6) {
                list($storedUsername, $storedPasswordHash, $created, $lastLogin, $failedAttempts, $lockedUntil) = $parts;
                
                if ($username === $storedUsername) {
                    // Account found - check if it's actually locked
                    if (!empty($lockedUntil) && strtotime($lockedUntil) > time()) {
                        // Account is locked - unlock it
                        $updatedCredentials = sprintf(
                            "%s|%s|%s|%s|0|%s\n",
                            $storedUsername,
                            $storedPasswordHash,
                            $created,
                            $lastLogin,
                            '' // Clear lockedUntil
                        );
                        
                        if (file_put_contents($credentialsFile, $updatedCredentials) !== false) {
                            $message = "Account '$username' has been successfully unlocked!";
                            $accountInfo = [
                                'username' => $storedUsername,
                                'created' => $created,
                                'lastLogin' => $lastLogin,
                                'failedAttempts' => 0,
                                'wasLocked' => true,
                                'lockExpired' => $lockedUntil
                            ];
                        } else {
                            $error = 'Failed to update credentials file. Check file permissions.';
                        }
                    } else {
                        // Account is not locked
                        $accountInfo = [
                            'username' => $storedUsername,
                            'created' => $created,
                            'lastLogin' => $lastLogin,
                            'failedAttempts' => (int)$failedAttempts,
                            'wasLocked' => false,
                            'lockExpired' => $lockedUntil
                        ];
                        $message = "Account '$username' is not currently locked.";
                    }
                } else {
                    $error = 'Username not found.';
                }
            } else {
                $error = 'Invalid credentials file format.';
            }
        }
    }
    
    // Regenerate CSRF token after each attempt
    $_SESSION['unlock_csrf_token'] = bin2hex(random_bytes(32));
}

// Get current account status
if (!$accountInfo && file_exists($credentialsFile)) {
    $credentials = file_get_contents($credentialsFile);
    $parts = explode('|', trim($credentials));
    
    if (count($parts) >= 6) {
        list($storedUsername, $storedPasswordHash, $created, $lastLogin, $failedAttempts, $lockedUntil) = $parts;
        $accountInfo = [
            'username' => $storedUsername,
            'created' => $created,
            'lastLogin' => $lastLogin,
            'failedAttempts' => (int)$failedAttempts,
            'wasLocked' => !empty($lockedUntil) && strtotime($lockedUntil) > time(),
            'lockExpired' => $lockedUntil
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Unlock Utility</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }
        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            box-sizing: border-box;
        }
        input[type="text"]:focus {
            outline: none;
            border-color: #007bff;
        }
        .btn {
            background: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 6px;
            border-left: 4px solid;
        }
        .alert-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .alert-danger {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .alert-info {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }
        .account-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .account-info h3 {
            margin-top: 0;
            color: #333;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #666;
        }
        .info-value {
            color: #333;
        }
        .locked {
            color: #dc3545;
            font-weight: bold;
        }
        .unlocked {
            color: #28a745;
            font-weight: bold;
        }
        .back-link {
            text-align: center;
            margin-top: 30px;
        }
        .back-link a {
            color: #007bff;
            text-decoration: none;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔓 Account Unlock Utility</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($accountInfo): ?>
            <div class="account-info">
                <h3>📊 Account Status</h3>
                <div class="info-row">
                    <span class="info-label">Username:</span>
                    <span class="info-value"><?php echo htmlspecialchars($accountInfo['username']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Created:</span>
                    <span class="info-value"><?php echo htmlspecialchars($accountInfo['created']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Last Login:</span>
                    <span class="info-value"><?php echo htmlspecialchars($accountInfo['lastLogin'] ?: 'Never'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Failed Attempts:</span>
                    <span class="info-value"><?php echo $accountInfo['failedAttempts']; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value <?php echo $accountInfo['wasLocked'] ? 'locked' : 'unlocked'; ?>">
                        <?php echo $accountInfo['wasLocked'] ? '🔒 LOCKED' : '🔓 UNLOCKED'; ?>
                    </span>
                </div>
                <?php if ($accountInfo['wasLocked'] && $accountInfo['lockExpired']): ?>
                <div class="info-row">
                    <span class="info-label">Lock Expires:</span>
                    <span class="info-value"><?php echo htmlspecialchars($accountInfo['lockExpired']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['unlock_csrf_token']); ?>">
            
            <div class="form-group">
                <label for="username">Username to Unlock:</label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                       placeholder="Enter username"
                       required>
            </div>
            
            <button type="submit" class="btn">
                <?php echo $accountInfo && $accountInfo['wasLocked'] ? '🔓 Unlock Account' : '🔍 Check Account Status'; ?>
            </button>
        </form>
        
        <div class="back-link">
            <a href="index.php">← Back to Login</a> | 
            <a href="setup.php">Setup New Account</a>
        </div>
    </div>
</body>
</html>





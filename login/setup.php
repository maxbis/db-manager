<?php
/**
 * Setup Page - Create Login Credentials
 * 
 * SECURITY: This page can ONLY be accessed from localhost
 * Use this to create or reset your admin credentials
 */

// Check if running from localhost
$clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
$isLocalhost = in_array($clientIP, ['127.0.0.1', '::1', 'localhost']);

if (!$isLocalhost) {
    http_response_code(403);
    die('⛔ Access Denied: This setup page can only be accessed from localhost (127.0.0.1)');
}

$success = false;
$error = '';
$credentialsFile = __DIR__ . '/credentials.txt';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = trim($_POST['db_pass'] ?? '');
    $dbHost = trim($_POST['db_host'] ?? '');
    
    // Validation
    if (empty($username) || empty($password)) {
        $error = 'Username and password are required';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Username can only contain letters, numbers, and underscores';
    } elseif (empty($dbUser)) {
        $error = 'Database username is required';
    } else {
        // Create credentials
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $created = date('Y-m-d H:i:s');
        
        // Format: username|hashed_password|created_date|last_login|failed_attempts|locked_until|db_user|db_pass|db_host
        $credentialLine = sprintf(
            "%s|%s|%s|%s|%d|%s|%s|%s|%s\n",
            $username,
            $hashedPassword,
            $created,
            '', // last_login (empty initially)
            0,  // failed_attempts
            '', // locked_until (empty initially)
            $dbUser, // database username (optional)
            $dbPass, // database password (optional)
            $dbHost  // database host (optional, defaults to localhost)
        );
        
        // Read existing credentials to append (support multiple users)
        $existingContent = '';
        if (file_exists($credentialsFile)) {
            clearstatcache(true, $credentialsFile);
            $existingContent = file_get_contents($credentialsFile);
            $existingLines = explode("\n", trim($existingContent));
            
            // Check if username already exists
            foreach ($existingLines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                $parts = explode('|', $line);
                if (count($parts) > 0 && $parts[0] === $username) {
                    $error = 'Username already exists. Please choose a different username.';
                    break;
                }
            }
        }
        
        if (empty($error)) {
            // Append new user (or create file if it doesn't exist)
            $newContent = $existingContent . $credentialLine;
            $bytesWritten = file_put_contents($credentialsFile, $newContent, LOCK_EX);
            
            if ($bytesWritten !== false) {
                chmod($credentialsFile, 0600); // Read/write for owner only
                
                // Clear stat cache again after writing
                clearstatcache(true, $credentialsFile);
                
                // Verify the write was successful
                $verifyContent = file_get_contents($credentialsFile);
                
                if (strpos($verifyContent, $username) === false) {
                    $error = 'File was written but content verification failed. Check file permissions and ensure file is writable.';
                    $success = false;
                } else {
                    $success = true;
                }
            } else {
                $error = 'Failed to write credentials file. Check file permissions.';
            }
        }
    }
}

// Check if credentials already exist
$credentialsExist = file_exists($credentialsFile) && filesize($credentialsFile) > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - Create Credentials</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #F8FAFC 0%, #F0F4F8 100%);
            color: #262B40;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .setup-container {
            max-width: 500px;
            width: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(6, 69, 127, 0.10);
            overflow: hidden;
        }
        
        .setup-header {
            background: linear-gradient(135deg, #A8C4EC 0%, #5379AE 100%);
            padding: 30px;
            text-align: center;
        }
        
        .setup-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        .setup-header h1 {
            color: white;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .setup-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
        }
        
        .setup-body {
            padding: 30px;
        }
        
        .warning-box {
            background: #FFF9E6;
            border-left: 4px solid #C4A004;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .warning-box h3 {
            color: #C4A004;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .warning-box p {
            color: #2C444C;
            font-size: 13px;
            line-height: 1.5;
        }
        
        .success-box {
            background: #E8F5E9;
            border-left: 4px solid #2C444C;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .success-box h3 {
            color: #2C444C;
            font-size: 16px;
            margin-bottom: 8px;
        }
        
        .success-box p {
            color: #2C444C;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 8px;
        }
        
        .success-box p:last-of-type {
            margin-top: 16px;
            margin-bottom: 16px;
        }
        
        .success-box .credentials-info {
            background: white;
            padding: 12px;
            border-radius: 4px;
            margin-top: 12px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        
        .error-box {
            background: #FFEBEE;
            border-left: 4px solid #C44704;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #C44704;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #262B40;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group .required {
            color: #C44704;
            margin-left: 4px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            font-family: inherit;
            font-size: 14px;
            border: 2px solid #A8C4EC;
            border-radius: 6px;
            outline: none;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            border-color: #0474C4;
            box-shadow: 0 0 0 3px rgba(168, 196, 236, 0.15);
        }
        
        .form-group .help-text {
            font-size: 12px;
            color: #8A9BA8;
            margin-top: 5px;
        }
        
        .password-requirements {
            background: #F5F8FC;
            padding: 12px;
            border-radius: 6px;
            margin-top: 8px;
            font-size: 12px;
            color: #2C444C;
        }
        
        .password-requirements ul {
            margin: 8px 0 0 20px;
        }
        
        .password-requirements li {
            margin-bottom: 4px;
        }
        
        .btn {
            width: 100%;
            padding: 12px 20px;
            font-family: inherit;
            font-size: 15px;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #0474C4 0%, #5379AE 100%);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #06457F 0%, #0474C4 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(6, 69, 127, 0.3);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .btn-secondary {
            background: #E8ECF1;
            color: #262B40;
            margin-top: 10px;
        }
        
        .btn-secondary:hover {
            background: #D1D8E0;
        }
        
        .info-box {
            background: #E8F2FF;
            border-left: 4px solid #0474C4;
            padding: 15px;
            margin-top: 20px;
            border-radius: 4px;
            font-size: 13px;
            color: #2C444C;
        }
        
        .localhost-badge {
            display: inline-block;
            background: #2C444C;
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <div class="setup-icon">🔐</div>
            <h1>Database Manager Setup</h1>
            <p>Create Your Login Credentials</p>
        </div>
        
        <div class="setup-body">
            <div class="localhost-badge">✓ LOCALHOST ACCESS ONLY</div>
            
            <?php if ($success): ?>
                <div class="success-box">
                    <h3>✅ Credentials Created Successfully!</h3>
                    <p>Your login credentials have been saved securely.</p>
                    <div class="credentials-info">
                        <strong>Username:</strong> <?php echo htmlspecialchars($username); ?><br>
                        <strong>File:</strong> credentials.txt (hashed)
                    </div>
                    <p style="margin-top: 12px;">You can now login to the application:</p>
                    <a href="login.php" class="btn btn-primary">Go to Login Page →</a>
                </div>
            <?php else: ?>
                <?php if ($credentialsExist): ?>
                    <div class="info-box" style="background: #E8F2FF; border-left: 4px solid #0474C4;">
                        <h3 style="color: #0474C4; margin-bottom: 8px;">ℹ️ Multiple Users Supported</h3>
                        <p style="color: #2C444C;">A credentials file already exists. You can add additional users - each user can have their own database credentials.</p>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="error-box">
                        <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">
                            Username <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            required 
                            minlength="3"
                            pattern="[a-zA-Z0-9_]+"
                            value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                            placeholder="Enter your username"
                        >
                        <div class="help-text">Letters, numbers, and underscores only (min. 3 characters)</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">
                            Password <span class="required">*</span>
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required 
                            minlength="8"
                            placeholder="Enter your password"
                        >
                        <div class="password-requirements">
                            <strong>Password Requirements:</strong>
                            <ul>
                                <li>Minimum 8 characters</li>
                                <li>Recommended: Mix of letters, numbers, and symbols</li>
                                <li>Avoid common words or patterns</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">
                            Confirm Password <span class="required">*</span>
                        </label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            required 
                            minlength="8"
                            placeholder="Confirm your password"
                        >
                    </div>
                    
                    <div style="margin: 30px 0; border-top: 2px solid #E0E8F0; padding-top: 20px;">
                        <h3 style="font-size: 16px; color: #262B40; margin-bottom: 15px;">🗄️ Database Credentials <span class="required">*</span></h3>
                        <p style="font-size: 13px; color: #8A9BA8; margin-bottom: 20px;">
                            Database credentials are required. These will be used for all database connections when this user is logged in.
                        </p>
                        
                        <div class="form-group">
                            <label for="db_user">
                                Database Username <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="db_user" 
                                name="db_user" 
                                required
                                value="<?php echo htmlspecialchars($_POST['db_user'] ?? ''); ?>"
                                placeholder="Enter database username"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="db_pass">Database Password</label>
                            <input 
                                type="password" 
                                id="db_pass" 
                                name="db_pass" 
                                value="<?php echo htmlspecialchars($_POST['db_pass'] ?? ''); ?>"
                                placeholder="Enter database password (can be empty)"
                            >
                            <div class="help-text">Leave empty if database user has no password</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="db_host">Database Host</label>
                            <input 
                                type="text" 
                                id="db_host" 
                                name="db_host" 
                                value="<?php echo htmlspecialchars($_POST['db_host'] ?? 'localhost'); ?>"
                                placeholder="localhost"
                            >
                            <div class="help-text">Default: localhost</div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <?php echo $credentialsExist ? '➕ Add User' : '✨ Create User'; ?>
                    </button>
                </form>
                
                <div class="info-box">
                    <strong>ℹ️ Security Note:</strong><br>
                    • Your password will be securely hashed (bcrypt)<br>
                    • The credentials file is protected (600 permissions)<br>
                    • This page is only accessible from localhost<br>
                    • Keep your credentials safe and secure!
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>


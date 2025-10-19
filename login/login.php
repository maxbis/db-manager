<?php
/**
 * Login Page
 * 
 * Handles user authentication with:
 * - CSRF protection
 * - Rate limiting
 * - Account lockout
 * - Secure session management
 * - Remember me functionality
 */

require_once 'remember_tokens.php';

session_start();

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$credentialsFile = __DIR__ . '/credentials.txt';

// Check if user is already logged in
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header('Location: ../index.php');
    exit;
}

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password';
        } elseif (!file_exists($credentialsFile)) {
            $error = 'No credentials found. Please run setup.php first.';
        } else {
            // Read credentials
            $credentials = file_get_contents($credentialsFile);
            $parts = explode('|', trim($credentials));
            
            if (count($parts) >= 6) {
                list($storedUsername, $storedPasswordHash, $created, $lastLogin, $failedAttempts, $lockedUntil) = $parts;
                
                // Check if account is locked
                if (!empty($lockedUntil) && strtotime($lockedUntil) > time()) {
                    $remainingMinutes = ceil((strtotime($lockedUntil) - time()) / 60);
                    $error = "Account is temporarily locked. Try again in $remainingMinutes minute(s).";
                } elseif ($username === $storedUsername) {
                    // Username matches, verify password
                    if (password_verify($password, $storedPasswordHash)) {
                        // Success! Create secure session
                        session_regenerate_id(true); // Prevent session fixation
                        
                        $_SESSION['authenticated'] = true;
                        $_SESSION['username'] = $username;
                        $_SESSION['login_time'] = time();
                        $_SESSION['last_activity'] = time();
                        $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
                        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
                        
                        // Handle "Remember Me" functionality
                        if (isset($_POST['remember_me']) && $_POST['remember_me'] === '1') {
                            // Generate and store remember-me token
                            $rememberToken = generateRememberToken();
                            $expiryDays = 90; // 3 months
                            
                            if (storeRememberToken($username, $rememberToken, $expiryDays)) {
                                // Set cookie with the token
                                $cookieExpiry = time() + ($expiryDays * 24 * 60 * 60);
                                setcookie(
                                    'remember_token',
                                    $rememberToken,
                                    [
                                        'expires' => $cookieExpiry,
                                        'path' => '/',
                                        'secure' => isset($_SERVER['HTTPS']),
                                        'httponly' => true,
                                        'samesite' => 'Strict'
                                    ]
                                );
                            }
                        }
                        
                        // Reset failed attempts and update last login
                        $updatedCredentials = sprintf(
                            "%s|%s|%s|%s|%d|%s\n",
                            $storedUsername,
                            $storedPasswordHash,
                            $created,
                            date('Y-m-d H:i:s'), // last_login
                            0, // reset failed_attempts
                            '' // clear locked_until
                        );
                        file_put_contents($credentialsFile, $updatedCredentials);
                        
                        // Redirect to main page
                        header('Location: ../index.php');
                        exit;
                    } else {
                        // Password incorrect - increment failed attempts
                        $failedAttempts = (int)$failedAttempts + 1;
                        $lockedUntil = '';
                        
                        // Lock account after 5 failed attempts
                        if ($failedAttempts >= 5) {
                            $lockedUntil = date('Y-m-d H:i:s', time() + 1800); // 30 minutes
                            $error = 'Too many failed attempts. Account locked for 30 minutes.';
                        } else {
                            $remainingAttempts = 5 - $failedAttempts;
                            $error = "Invalid username or password. $remainingAttempts attempt(s) remaining.";
                        }
                        
                        // Update credentials file
                        $updatedCredentials = sprintf(
                            "%s|%s|%s|%s|%d|%s\n",
                            $storedUsername,
                            $storedPasswordHash,
                            $created,
                            $lastLogin,
                            $failedAttempts,
                            $lockedUntil
                        );
                        file_put_contents($credentialsFile, $updatedCredentials);
                    }
                } else {
                    // Username doesn't match - don't reveal this for security
                    $error = 'Invalid username or password';
                }
            } else {
                $error = 'Invalid credentials file format';
            }
        }
    }
    
    // Regenerate CSRF token after each attempt
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Database Manager</title>
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
        
        .login-container {
            max-width: 420px;
            width: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(6, 69, 127, 0.10);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #0474C4 0%, #5379AE 100%);
            padding: 40px 30px;
            text-align: center;
        }
        
        .login-icon {
            font-size: 64px;
            margin-bottom: 15px;
        }
        
        .login-header h1 {
            color: white;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .login-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
        }
        
        .login-body {
            padding: 30px;
        }
        
        .error-box {
            background: #FFEBEE;
            border-left: 4px solid #C44704;
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #C44704;
            font-size: 14px;
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
        
        .btn-login {
            width: 100%;
            padding: 14px 20px;
            font-family: inherit;
            font-size: 15px;
            font-weight: 600;
            background: linear-gradient(135deg, #0474C4 0%, #5379AE 100%);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            background: linear-gradient(135deg, #06457F 0%, #0474C4 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(6, 69, 127, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .login-footer {
            padding: 20px 30px;
            background: #F5F8FC;
            border-top: 1px solid #E0E8F0;
            text-align: center;
            font-size: 13px;
            color: #8A9BA8;
        }
        
        .login-footer a {
            color: #0474C4;
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .security-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #E8F2FF;
            color: #0474C4;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-icon">📊</div>
            <h1>Database Manager</h1>
            <p>Please login to continue</p>
        </div>
        
        <div class="login-body">
            <div class="security-badge">
                🔒 Secure Login
            </div>
            
            <?php if ($error): ?>
                <div class="error-box">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required 
                        autocomplete="username"
                        placeholder="Enter your username"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        autocomplete="current-password"
                        placeholder="Enter your password"
                    >
                </div>
                
                <div class="form-group" style="margin-bottom: 25px;">
                    <label style="display: flex; align-items: center; cursor: pointer; font-weight: normal;">
                        <input 
                            type="checkbox" 
                            name="remember_me" 
                            value="1"
                            style="width: auto; margin-right: 8px; cursor: pointer;"
                        >
                        <span>Remember me for 90 days</span>
                    </label>
                    <div style="font-size: 11px; color: #8A9BA8; margin-top: 4px; margin-left: 24px;">
                        Stay logged in on this device
                    </div>
                </div>
                
                <button type="submit" class="btn-login">
                    🔓 Login
                </button>
            </form>
        </div>
        
        <div class="login-footer">
            No credentials yet? 
            <a href="setup.php">Run setup from localhost</a>
        </div>
    </div>
</body>
</html>


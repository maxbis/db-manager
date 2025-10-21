<?php
/**
 * IP Authorization Check
 * 
 * Checks if the current IP address is authorized to access the application
 * - Allows all access from localhost
 * - Checks whitelist from ipAllowed.txt for other IPs
 * - Validates remember-me tokens for auto-loginn 
 */

require_once __DIR__ . '/remember_tokens.php';

/**
 * Get the client's real IP address
 * Handles various proxy configurations
 */
function getClientIP() {
    $ip = '';
    
    // Check for various headers that might contain the real IP
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // X-Forwarded-For can contain multiple IPs, get the first one
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED'];
    } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
        $ip = $_SERVER['HTTP_FORWARDED'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    }
    
    return $ip;
}

/**
 * Check if IP is localhost
 */
function isLocalhost($ip) {
    $localhostPatterns = [
        '127.0.0.1',
        '::1',
        'localhost',
        '::ffff:127.0.0.1'
    ];
    
    return in_array($ip, $localhostPatterns);
}

/**
 * Check if IP matches a CIDR pattern
 */
function ipMatchesCIDR($ip, $cidr) {
    // Handle simple IP without CIDR
    if (strpos($cidr, '/') === false) {
        return $ip === $cidr;
    }
    
    list($subnet, $mask) = explode('/', $cidr);
    
    // Convert IP addresses to long integers
    $ipLong = ip2long($ip);
    $subnetLong = ip2long($subnet);
    
    if ($ipLong === false || $subnetLong === false) {
        return false;
    }
    
    // Calculate the network mask
    $maskLong = -1 << (32 - (int)$mask);
    
    // Check if IP is in the subnet
    return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
}

/**
 * Check if IP is whitelisted
 */
function isIPWhitelisted($ip, $whitelistFile = null) {
    // Default to ipAllowed.txt in same directory as this file
    if ($whitelistFile === null) {
        $whitelistFile = __DIR__ . '/ipAllowed.txt';
    }
    
    // Check if whitelist file exists
    if (!file_exists($whitelistFile)) {
        // If no whitelist file, deny access
        return false;
    }
    
    // Read whitelist file
    $whitelist = file($whitelistFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    if ($whitelist === false) {
        return false;
    }
    
    // Check each entry in whitelist
    foreach ($whitelist as $entry) {
        $entry = trim($entry);
        
        // Skip empty lines and comments
        if (empty($entry) || $entry[0] === '#') {
            continue;
        }
        
        // Check if IP matches (supports CIDR notation)
        if (ipMatchesCIDR($ip, $entry)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Main authorization check
 * Now includes session-based authentication
 */
function checkAuthorization() {
    $clientIP = getClientIP();
    
    // Step 1: Check IP authorization
    $ipAuthorized = false;
    
    // Allow localhost without checking whitelist
    if (isLocalhost($clientIP)) {
        $ipAuthorized = true;
    }
    // Check whitelist for other IPs
    elseif (isIPWhitelisted($clientIP)) {
        $ipAuthorized = true;
    }
    
    // If IP is not authorized, deny immediately
    if (!$ipAuthorized) {
        return false;
    }
    
    // Step 2: Check session authentication (if credentials exist)
    $credentialsFile = __DIR__ . '/credentials.txt';
    if (file_exists($credentialsFile) && filesize($credentialsFile) > 0) {
        // Credentials exist - require login
        // Don't check session for login page itself
        $currentScript = basename($_SERVER['SCRIPT_NAME']);
        if (!in_array($currentScript, ['login.php', 'logout.php', 'setup.php'])) {
            // Start session if not already started
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Check if authenticated
            if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
                // Not authenticated - try remember-me token
                if (isset($_COOKIE['remember_token'])) {
                    $userData = validateRememberToken($_COOKIE['remember_token']);
                    
                    if ($userData !== false) {
                        // Valid remember-me token - auto-login
                        session_regenerate_id(true);
                        
                        $_SESSION['authenticated'] = true;
                        $_SESSION['username'] = $userData['username'];
                        $_SESSION['login_time'] = time();
                        $_SESSION['last_activity'] = time();
                        $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
                        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
                        $_SESSION['auto_login'] = true; // Flag to indicate auto-login via remember-me
                        
                        // Allow access to continue
                        return true;
                    } else {
                        // Invalid or expired token - clear the cookie
                        setcookie('remember_token', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
                    }
                }
                
                // Not authenticated and no valid remember-me token - redirect to login
                // Calculate relative path to login from current directory
                $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
                $depth = substr_count(trim($scriptDir, '/'), '/');
                $prefix = str_repeat('../', $depth);
                header('Location: ' . $prefix . 'login/login.php');
                exit;
            }
        }
    }
    
    return true;
}

/**
 * Display access denied message
 */
function displayAccessDenied($ip) {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Access Denied - Database Manager</title>
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
            
            .access-denied-container {
                max-width: 600px;
                background: white;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(6, 69, 127, 0.10);
                overflow: hidden;
                text-align: center;
            }
            
            .access-denied-header {
                background: linear-gradient(135deg, #FFB3A6 0%, #F08F70 100%);
                padding: 40px 30px;
            }
            
            .access-denied-icon {
                font-size: 72px;
                margin-bottom: 15px;
            }
            
            .access-denied-header h1 {
                color: #C44704;
                font-size: 28px;
                font-weight: 600;
                margin-bottom: 10px;
            }
            
            .access-denied-body {
                padding: 40px 30px;
            }
            
            .access-denied-body p {
                color: #2C444C;
                font-size: 16px;
                line-height: 1.6;
                margin-bottom: 20px;
            }
            
            .ip-display {
                background: #F5F8FC;
                border: 2px solid #A8C4EC;
                border-radius: 8px;
                padding: 15px 20px;
                font-family: 'Courier New', monospace;
                font-size: 18px;
                color: #06457F;
                font-weight: 600;
                margin: 20px 0;
            }
            
            .access-denied-info {
                background: #FFF9E6;
                border-left: 4px solid #C4A004;
                padding: 15px 20px;
                margin-top: 25px;
                text-align: left;
            }
            
            .access-denied-info h3 {
                color: #C4A004;
                font-size: 16px;
                margin-bottom: 10px;
            }
            
            .access-denied-info p {
                color: #2C444C;
                font-size: 14px;
                margin-bottom: 8px;
            }
            
            .access-denied-info ul {
                margin-left: 20px;
                color: #2C444C;
                font-size: 14px;
            }
            
            .access-denied-info li {
                margin-bottom: 5px;
            }
        </style>
    </head>
    <body>
        <div class="access-denied-container">
            <div class="access-denied-header">
                <div class="access-denied-icon">🚫</div>
                <h1>Access Denied</h1>
            </div>
            <div class="access-denied-body">
                <p>Your IP address is not authorized to access this application.</p>
                
                <div class="ip-display">
                    no access for ip number: <?php echo htmlspecialchars($ip); ?>
                </div>
                
                <div class="access-denied-info">
                    <h3>📋 To gain access:</h3>
                    <ul>
                        <li>Contact the system administrator</li>
                        <li>Request your IP address to be whitelisted</li>
                        <li>Access from localhost (127.0.0.1) does not require whitelisting</li>
                    </ul>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Perform the authorization check
if (!checkAuthorization()) {
    displayAccessDenied(getClientIP());
}
?>


<?php
/**
 * Session Validation and Security Check
 * 
 * Include this file at the top of protected pages to:
 * - Verify user is logged in
 * - Check session timeout
 * - Prevent session hijacking
 * - Auto-logout inactive users
 */

// Start session with secure settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS'])); // Secure if HTTPS
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session timeout (30 minutes of inactivity)
$sessionTimeout = 1800; // 30 minutes in seconds

// Check if user is authenticated
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    // Not authenticated - redirect to login
    header('Location: login/login.php');
    exit;
}

// Check session timeout
if (isset($_SESSION['last_activity'])) {
    $inactiveTime = time() - $_SESSION['last_activity'];
    
    if ($inactiveTime > $sessionTimeout) {
        // Session expired - destroy and redirect
        session_unset();
        session_destroy();
        header('Location: login/login.php?timeout=1');
        exit;
    }
}

// Check for session hijacking attempts
$currentIP = $_SERVER['REMOTE_ADDR'] ?? '';
$currentUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

if (isset($_SESSION['user_ip']) && $_SESSION['user_ip'] !== $currentIP) {
    // IP changed - possible session hijacking
    session_unset();
    session_destroy();
    header('Location: login/login.php?security=1');
    exit;
}

if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $currentUserAgent) {
    // User agent changed - possible session hijacking
    session_unset();
    session_destroy();
    header('Location: login/login.php?security=1');
    exit;
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Regenerate session ID periodically (every 30 minutes)
if (!isset($_SESSION['regenerated_at'])) {
    $_SESSION['regenerated_at'] = time();
} elseif (time() - $_SESSION['regenerated_at'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['regenerated_at'] = time();
}

// Get session info for display
$sessionInfo = [
    'username' => $_SESSION['username'] ?? 'Unknown',
    'login_time' => $_SESSION['login_time'] ?? time(),
    'last_activity' => $_SESSION['last_activity'] ?? time(),
    'remaining_time' => $sessionTimeout - (time() - ($_SESSION['last_activity'] ?? time()))
];

// Function to check if session is still valid (for AJAX calls)
function isSessionValid() {
    global $sessionTimeout;
    
    if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        return false;
    }
    
    if (isset($_SESSION['last_activity'])) {
        $inactiveTime = time() - $_SESSION['last_activity'];
        if ($inactiveTime > $sessionTimeout) {
            return false;
        }
    }
    
    return true;
}

// Function to get remaining session time in minutes
function getSessionRemainingMinutes() {
    global $sessionTimeout;
    
    if (!isset($_SESSION['last_activity'])) {
        return floor($sessionTimeout / 60);
    }
    
    $remainingSeconds = $sessionTimeout - (time() - $_SESSION['last_activity']);
    return max(0, floor($remainingSeconds / 60));
}
?>


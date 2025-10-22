<?php
/**
 * Logout Handler
 * 
 * Securely destroys the session and redirects to login page
 */

require_once 'remember_tokens.php';

// Ensure session storage path is valid (fallback for missing/invalid XAMPP tmp path)
$currentSavePath = ini_get('session.save_path');
if (!$currentSavePath || !is_dir($currentSavePath)) {
    $fallbackPath = __DIR__ . '/../tmp/sessions';
    if (!is_dir($fallbackPath)) {
        @mkdir($fallbackPath, 0777, true);
    }
    if (is_dir($fallbackPath) && is_writable($fallbackPath)) {
        ini_set('session.save_path', $fallbackPath);
    }
}

// Secure session cookie settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Revoke remember-me token if present
if (isset($_COOKIE['remember_token'])) {
    revokeRememberToken($_COOKIE['remember_token']);
    // Clear the cookie
    setcookie('remember_token', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
}

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php?logout=1');
exit;
?>


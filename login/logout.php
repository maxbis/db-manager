<?php
/**
 * Logout Handler
 * 
 * Securely destroys the session and redirects to login page
 */

require_once 'remember_tokens.php';

session_start();

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


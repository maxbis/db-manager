<?php
/**
 * Session status endpoint
 *
 * Returns JSON describing whether the current session is active.
 * Optionally attempts to refresh the session using a remember-me token.
 */

require_once __DIR__ . '/remember_tokens.php';

header('Content-Type: application/json; charset=utf-8');

// Secure session cookie settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);

// Ensure session storage path fallback exists
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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$sessionTimeout = 1800; // 30 minutes
$action = $_GET['action'] ?? $_POST['action'] ?? 'check';

function buildResponse(string $status, array $extra = []): void
{
    $payload = array_merge([
        'status' => $status,
        'timestamp' => time()
    ], $extra);

    echo json_encode($payload);
    exit;
}

function isSessionActive(int $sessionTimeout): bool
{
    if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        return false;
    }

    if (!isset($_SESSION['last_activity'])) {
        return false;
    }

    $inactiveTime = time() - $_SESSION['last_activity'];
    if ($inactiveTime > $sessionTimeout) {
        return false;
    }

    return true;
}

function refreshSessionActivity(): void
{
    $_SESSION['last_activity'] = time();
    if (!isset($_SESSION['regenerated_at'])) {
        $_SESSION['regenerated_at'] = time();
    } elseif (time() - $_SESSION['regenerated_at'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['regenerated_at'] = time();
    }
}

function attemptRememberMeLogin(): bool
{
    if (!isset($_COOKIE['remember_token'])) {
        return false;
    }

    $userData = validateRememberToken($_COOKIE['remember_token']);
    if ($userData === false) {
        // Clear invalid token
        setcookie('remember_token', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
        return false;
    }

    session_regenerate_id(true);

    $_SESSION['authenticated'] = true;
    $_SESSION['username'] = $userData['username'];
    $_SESSION['login_time'] = $_SESSION['login_time'] ?? time();
    $_SESSION['last_activity'] = time();
    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $_SESSION['auto_login'] = true;

    return true;
}

if (isSessionActive($sessionTimeout)) {
    refreshSessionActivity();
    $remainingSeconds = $sessionTimeout - (time() - $_SESSION['last_activity']);
    buildResponse('active', [
        'remaining_seconds' => max(0, $remainingSeconds)
    ]);
}

if ($action === 'refresh' && attemptRememberMeLogin()) {
    refreshSessionActivity();
    buildResponse('reauthenticated');
}

session_unset();
session_destroy();

buildResponse('expired', [
    'login_url' => 'login.php?timeout=1'
]);


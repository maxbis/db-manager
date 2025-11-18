<?php
/**
 * Database Sync Handler (Local)
 * 
 * Handles local database operations for the sync process
 * This file runs on the LOCAL server and executes SQL commands
 */

// Require authentication
require_once __DIR__ . '/../login/auth_check.php';
require_once __DIR__ . '/../db_config.php';

header('Content-Type: application/json');

// Avoid leaking HTML warnings/notices which would break JSON
@ini_set('display_errors', '0');
@ini_set('html_errors', '0');
if (ob_get_level() === 0) {
    ob_start();
}

/**
 * Send JSON response
 */
function sendResponse($success, $data = null, $message = '') {
    // Capture stray output and include it in message
    $extraOutput = '';
    if (ob_get_level() > 0) {
        $extraOutput = ob_get_contents();
        ob_clean();
    }
    if (!empty($extraOutput)) {
        $extraOutput = trim(strip_tags($extraOutput));
        if ($extraOutput !== '') {
            $message = trim($message);
            $message = $message !== '' ? ($message . ' | ' . $extraOutput) : $extraOutput;
        }
    }

    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

// Get action
$action = $_POST['action'] ?? '';

if ($action !== 'execute_sql') {
    sendResponse(false, null, 'Invalid action');
}

// Get SQL and database
$sql = $_POST['sql'] ?? '';
$database = $_POST['database'] ?? null;
$disableFk = isset($_POST['disable_fk']) && $_POST['disable_fk'] === '1';
$increasePacket = isset($_POST['increase_packet']) && $_POST['increase_packet'] === '1';

if (empty($sql)) {
    sendResponse(false, null, 'SQL query required');
}

try {
    // Get database connection
    // First connect without database to allow database creation
    $credentials = getDbCredentials();
    $conn = new mysqli($credentials['host'], $credentials['user'], $credentials['pass']);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset('utf8mb4');
    
    // If database is specified and not creating a database, select it and enforce with USE
    if ($database && stripos($sql, 'CREATE DATABASE') === false) {
        if (!$conn->select_db($database)) {
            throw new Exception("Failed to select database: " . $conn->error);
        }
        // Extra safety: explicit USE to ensure default DB is set for this session
        $dbEsc = $conn->real_escape_string($database);
        if (!$conn->query("USE `{$dbEsc}`")) {
            throw new Exception("Failed to set active database: " . $conn->error);
        }
    }
    
    // Optionally increase packet/buffer sizes for this request
    if ($increasePacket) {
        // Note: Both max_allowed_packet and net_buffer_length may require SUPER privileges
        // to set globally, and some MySQL configurations don't allow session-level changes
        // We'll rely on the existing server configuration and conservative batching
        // in the client to handle large data transfers
    }

    // Optionally disable foreign key checks for this request (session scope)
    if ($disableFk) {
        if (!$conn->query('SET FOREIGN_KEY_CHECKS=0')) {
            throw new Exception("Failed to disable foreign key checks: " . $conn->error);
        }
    }
    
    // Execute SQL
    $result = $conn->multi_query($sql);
    
    if ($result === false) {
        throw new Exception("SQL execution failed: " . $conn->error);
    }
    
    // Clear any result sets
    while ($conn->more_results()) {
        $conn->next_result();
        if ($res = $conn->store_result()) {
            $res->free();
        }
    }
    
    // Get affected rows
    $affectedRows = $conn->affected_rows;
    
    // Re-enable FK checks if they were disabled for this request
    if ($disableFk) {
        // Try to re-enable, but don't fail the whole request if this errors
        @$conn->query('SET FOREIGN_KEY_CHECKS=1');
    }
    
    $conn->close();
    
    sendResponse(true, [
        'affected_rows' => $affectedRows
    ], 'SQL executed successfully');
    
} catch (Exception $e) {
    sendResponse(false, null, $e->getMessage());
}


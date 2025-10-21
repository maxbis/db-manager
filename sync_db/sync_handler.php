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

/**
 * Send JSON response
 */
function sendResponse($success, $data = null, $message = '') {
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

if (empty($sql)) {
    sendResponse(false, null, 'SQL query required');
}

try {
    // Get database connection
    // First connect without database to allow database creation
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset('utf8mb4');
    
    // If database is specified and not creating a database, select it
    if ($database && stripos($sql, 'CREATE DATABASE') === false) {
        if (!$conn->select_db($database)) {
            throw new Exception("Failed to select database: " . $conn->error);
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
    
    $conn->close();
    
    sendResponse(true, [
        'affected_rows' => $affectedRows
    ], 'SQL executed successfully');
    
} catch (Exception $e) {
    sendResponse(false, null, $e->getMessage());
}


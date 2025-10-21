<?php
/**
 * Database Sync API Endpoint (Remote Server)
 * 
 * This file should be deployed on the REMOTE server.
 * It handles requests from the local client to fetch database structure and data.
 * 
 * Security:
 * - IP Whitelist check (uses ipAllowed.txt)
 * - API Key authentication
 * - Returns data in JSON format
 */

// Prevent direct access without proper authentication
header('Content-Type: application/json');

// Allow cross-origin requests (adjust as needed for your setup)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Load configuration
require_once __DIR__ . '/config.php';

// Set execution limits for large databases
set_time_limit(SYNC_MAX_EXECUTION_TIME);
ini_set('memory_limit', SYNC_MEMORY_LIMIT);

/**
 * Get the client's real IP address
 */
function getClientIP() {
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
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
    $localhostPatterns = ['127.0.0.1', '::1', 'localhost', '::ffff:127.0.0.1'];
    return in_array($ip, $localhostPatterns);
}

/**
 * Check if IP is in whitelist
 */
function isIPAllowed($ip) {
    // Always allow localhost
    if (isLocalhost($ip)) {
        return true;
    }
    
    // Check whitelist file
    $whitelistFile = __DIR__ . '/../login/ipAllowed.txt';
    if (!file_exists($whitelistFile)) {
        return false;
    }
    
    $allowedIPs = file($whitelistFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($allowedIPs as $allowedIP) {
        $allowedIP = trim($allowedIP);
        if (empty($allowedIP) || strpos($allowedIP, '#') === 0) {
            continue;
        }
        if ($allowedIP === $ip) {
            return true;
        }
    }
    
    return false;
}

/**
 * Send JSON response
 */
function sendResponse($success, $data = null, $message = '', $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

/**
 * Log sync operations
 */
function logSync($message) {
    if (!SYNC_ENABLE_LOGGING) {
        return;
    }
    $timestamp = date('Y-m-d H:i:s');
    $ip = getClientIP();
    $logMessage = "[$timestamp] [IP: $ip] $message\n";
    file_put_contents(SYNC_LOG_FILE, $logMessage, FILE_APPEND);
}

// Check IP whitelist
$clientIP = getClientIP();
if (!isIPAllowed($clientIP)) {
    logSync("UNAUTHORIZED: IP not in whitelist");
    sendResponse(false, null, 'Unauthorized: IP address not allowed', 403);
}

// Check API key
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_POST['api_key'] ?? $_GET['api_key'] ?? '';
if (empty($apiKey) || $apiKey !== SYNC_API_KEY) {
    logSync("UNAUTHORIZED: Invalid API key");
    sendResponse(false, null, 'Unauthorized: Invalid API key', 401);
}

// Get action
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Get database credentials from request
$dbHost = $_POST['db_host'] ?? 'localhost';
$dbUser = $_POST['db_user'] ?? '';
$dbPass = $_POST['db_pass'] ?? '';
$dbName = $_POST['db_name'] ?? '';

if (empty($dbUser) || empty($dbName)) {
    sendResponse(false, null, 'Database credentials required', 400);
}

// Connect to database
try {
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
} catch (Exception $e) {
    logSync("DATABASE ERROR: " . $e->getMessage());
    sendResponse(false, null, 'Database connection error: ' . $e->getMessage(), 500);
}

// Handle different actions
switch ($action) {
    case 'list_databases':
        // List all available databases
        $result = $conn->query("SHOW DATABASES");
        $databases = [];
        while ($row = $result->fetch_assoc()) {
            $databases[] = $row['Database'];
        }
        logSync("Listed databases");
        sendResponse(true, ['databases' => $databases], 'Databases retrieved successfully');
        break;
        
    case 'get_tables':
        // Get list of tables in database
        $result = $conn->query("SHOW TABLES");
        $tables = [];
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
        logSync("Retrieved tables from database: $dbName");
        sendResponse(true, ['tables' => $tables], 'Tables retrieved successfully');
        break;
        
    case 'get_table_structure':
        // Get CREATE TABLE statement for a specific table
        $table = $_POST['table'] ?? '';
        if (empty($table)) {
            sendResponse(false, null, 'Table name required', 400);
        }
        
        $result = $conn->query("SHOW CREATE TABLE `" . $conn->real_escape_string($table) . "`");
        if (!$result) {
            sendResponse(false, null, 'Failed to get table structure: ' . $conn->error, 500);
        }
        
        $row = $result->fetch_assoc();
        $createStatement = $row['Create Table'];
        
        logSync("Retrieved structure for table: $table");
        sendResponse(true, ['create_statement' => $createStatement], 'Table structure retrieved successfully');
        break;
        
    case 'get_table_data':
        // Get data from a specific table (with pagination)
        $table = $_POST['table'] ?? '';
        $offset = intval($_POST['offset'] ?? 0);
        $limit = intval($_POST['limit'] ?? SYNC_CHUNK_SIZE);
        
        if (empty($table)) {
            sendResponse(false, null, 'Table name required', 400);
        }
        
        // Get total count
        $countResult = $conn->query("SELECT COUNT(*) as total FROM `" . $conn->real_escape_string($table) . "`");
        $totalRows = $countResult->fetch_assoc()['total'];
        
        // Get data chunk
        $result = $conn->query("SELECT * FROM `" . $conn->real_escape_string($table) . "` LIMIT $offset, $limit");
        if (!$result) {
            sendResponse(false, null, 'Failed to get table data: ' . $conn->error, 500);
        }
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        logSync("Retrieved data for table: $table (offset: $offset, limit: $limit)");
        sendResponse(true, [
            'table' => $table,
            'data' => $data,
            'total_rows' => $totalRows,
            'offset' => $offset,
            'limit' => $limit,
            'has_more' => ($offset + $limit) < $totalRows
        ], 'Table data retrieved successfully');
        break;
        
    case 'get_views':
        // Get all views in the database
        $result = $conn->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
        $views = [];
        while ($row = $result->fetch_array()) {
            $views[] = $row[0];
        }
        logSync("Retrieved views from database: $dbName");
        sendResponse(true, ['views' => $views], 'Views retrieved successfully');
        break;
        
    case 'get_view_structure':
        // Get CREATE VIEW statement for a specific view
        $view = $_POST['view'] ?? '';
        if (empty($view)) {
            sendResponse(false, null, 'View name required', 400);
        }
        
        $result = $conn->query("SHOW CREATE VIEW `" . $conn->real_escape_string($view) . "`");
        if (!$result) {
            sendResponse(false, null, 'Failed to get view structure: ' . $conn->error, 500);
        }
        
        $row = $result->fetch_assoc();
        $createStatement = $row['Create View'];
        
        logSync("Retrieved structure for view: $view");
        sendResponse(true, ['create_statement' => $createStatement], 'View structure retrieved successfully');
        break;
        
    case 'get_triggers':
        // Get all triggers in the database
        $result = $conn->query("SHOW TRIGGERS");
        $triggers = [];
        while ($row = $result->fetch_assoc()) {
            $triggers[] = $row;
        }
        logSync("Retrieved triggers from database: $dbName");
        sendResponse(true, ['triggers' => $triggers], 'Triggers retrieved successfully');
        break;
        
    case 'get_procedures':
        // Get all stored procedures in the database
        $result = $conn->query("SHOW PROCEDURE STATUS WHERE Db = '" . $conn->real_escape_string($dbName) . "'");
        $procedures = [];
        while ($row = $result->fetch_assoc()) {
            $procedures[] = $row['Name'];
        }
        logSync("Retrieved procedures from database: $dbName");
        sendResponse(true, ['procedures' => $procedures], 'Procedures retrieved successfully');
        break;
        
    case 'get_procedure_structure':
        // Get CREATE PROCEDURE statement
        $procedure = $_POST['procedure'] ?? '';
        if (empty($procedure)) {
            sendResponse(false, null, 'Procedure name required', 400);
        }
        
        $result = $conn->query("SHOW CREATE PROCEDURE `" . $conn->real_escape_string($procedure) . "`");
        if (!$result) {
            sendResponse(false, null, 'Failed to get procedure structure: ' . $conn->error, 500);
        }
        
        $row = $result->fetch_assoc();
        $createStatement = $row['Create Procedure'];
        
        logSync("Retrieved structure for procedure: $procedure");
        sendResponse(true, ['create_statement' => $createStatement], 'Procedure structure retrieved successfully');
        break;
        
    case 'get_functions':
        // Get all functions in the database
        $result = $conn->query("SHOW FUNCTION STATUS WHERE Db = '" . $conn->real_escape_string($dbName) . "'");
        $functions = [];
        while ($row = $result->fetch_assoc()) {
            $functions[] = $row['Name'];
        }
        logSync("Retrieved functions from database: $dbName");
        sendResponse(true, ['functions' => $functions], 'Functions retrieved successfully');
        break;
        
    case 'get_function_structure':
        // Get CREATE FUNCTION statement
        $function = $_POST['function'] ?? '';
        if (empty($function)) {
            sendResponse(false, null, 'Function name required', 400);
        }
        
        $result = $conn->query("SHOW CREATE FUNCTION `" . $conn->real_escape_string($function) . "`");
        if (!$result) {
            sendResponse(false, null, 'Failed to get function structure: ' . $conn->error, 500);
        }
        
        $row = $result->fetch_assoc();
        $createStatement = $row['Create Function'];
        
        logSync("Retrieved structure for function: $function");
        sendResponse(true, ['create_statement' => $createStatement], 'Function structure retrieved successfully');
        break;
        
    default:
        sendResponse(false, null, 'Invalid action', 400);
}

$conn->close();


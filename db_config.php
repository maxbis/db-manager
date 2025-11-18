<?php
/**
 * Database Configuration File
 * 
 * Configure your database connection settings here
 */

// Database connection parameters
const DB_HOST = 'localhost';
const DB_USER = 'root';
const DB_PASS = '';

/**
 * DB_NAME Configuration:
 * 
 * Option 1: Specify a database name
 *   define('DB_NAME', 'your_database_name');
 * 
 * Option 2: Leave empty to auto-select first available database
 *   define('DB_NAME', '');
 *   - System will automatically use the first non-system database
 *   - Perfect for portable installations!
 * 
 * The system will automatically fall back to the first available database
 * if DB_NAME is empty, making this portable across different environments.
 */
const DB_NAME = ''; // Leave empty for auto-detection
const DB_CHARSET = 'utf8mb4';

/**
 * Get database connection
 * 
 * @param string|null $database Optional: Specific database to connect to
 * @return mysqli Database connection object
 * @throws Exception if connection fails
 */
function getDbConnection(?string $database = null): mysqli
{
    // Use provided database, or fall back to DB_NAME constant, or connect without database
    $dbToUse = $database ?? (DB_NAME ?: null);
    
    // Check if user has custom database credentials in session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Use session-based credentials if available, otherwise use defaults
    $dbUser = $_SESSION['db_user'] ?? DB_USER;
    $dbPass = $_SESSION['db_pass'] ?? DB_PASS;
    $dbHost = $_SESSION['db_host'] ?? DB_HOST;
    
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbToUse);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset(DB_CHARSET);
    
    return $conn;
}

/**
 * Get first available non-system database
 * 
 * @param mysqli $conn Database connection object
 * @return string|null First available database name or null if none found
 */
function getFirstAvailableDatabase(mysqli $conn) {
    $result = $conn->query("SHOW DATABASES");
    if (!$result) {
        return null;
    }
    
    $systemDatabases = ['information_schema', 'performance_schema', 'mysql', 'sys'];
    
    while ($row = $result->fetch_array()) {
        $dbName = $row[0];
        if (!in_array($dbName, $systemDatabases)) {
            return $dbName;
        }
    }
    
    return null;
}

/**
 * Get current database name
 * 
 * @return string|null Current database name or null if none selected
 */
function getCurrentDatabase() {
    try {
        $conn = getDbConnection();
        $result = $conn->query("SELECT DATABASE()");
        if ($result) {
            $row = $result->fetch_array();
            $dbName = $row[0];
            closeDbConnection($conn);
            return $dbName;
        }
    } catch (Exception $e) {
        // Silently fail - no database selected
    }
    
    // Try session cache
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['auto_selected_database'] ?? null;
}

/**
 * Set current database in session cache
 * 
 * @param string $database Database name to cache
 */
function setCurrentDatabase(string $database) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['auto_selected_database'] = $database;
}

/**
 * Select a database on an existing connection
 * 
 * @param mysqli $conn Database connection object
 * @param string $database Database name to select
 * @throws Exception if selection fails
 */
function selectDatabase(mysqli $conn, string $database) {
    if (!$conn->select_db($database)) {
        throw new Exception("Failed to select database '$database': " . $conn->error);
    }
}

/**
 * Close database connection
 * 
 * @param mysqli $conn Database connection object
 */
function closeDbConnection(mysqli $conn) {
    $conn->close();
}


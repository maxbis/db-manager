<?php
/**
 * Database Connection File
 * 
 * Provides database connection and management functions.
 * Database credentials are retrieved from session (loaded from credentials.txt).
 */

// Database connection parameters
// const DB_HOST = 'localhost';
// const DB_USER = 'root';
// const DB_PASS = '';

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
 * Get database credentials from session
 * 
 * @return array Array with 'host', 'user', 'pass' keys
 * @throws Exception if credentials not found in session
 */
function getDbCredentials(): array {
    // Always use credentials from session (loaded from credentials.txt)
    // Ensure session is started
    if (session_status() === PHP_SESSION_NONE) {
        // Try to start session - if it fails, we'll handle it below
        if (!@session_start()) {
            throw new Exception("Failed to start session. Please check session configuration.");
        }
    }
    
    // Check if session is active and has credentials
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new Exception("Session not active. Please log in again.");
    }
    
    // Require database credentials from session - no fallback to defaults
    if (empty($_SESSION['db_user'])) {
        // Provide more helpful error message
        $username = $_SESSION['username'] ?? 'unknown';
        throw new Exception("Database credentials not configured for user '$username'. Please set database credentials in login/setup.php.");
    }
    
    return [
        'host' => $_SESSION['db_host'] ?? 'localhost',
        'user' => $_SESSION['db_user'],
        'pass' => $_SESSION['db_pass'] ?? ''
    ];
}

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
    
    // Get credentials from session
    $credentials = getDbCredentials();
    
    $conn = new mysqli($credentials['host'], $credentials['user'], $credentials['pass'], $dbToUse);
    
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
    // Try session cache first (doesn't require DB connection)
    // Don't start session if not already started - let the calling code handle that
    if (session_status() === PHP_SESSION_ACTIVE) {
        if (isset($_SESSION['auto_selected_database']) && !empty($_SESSION['auto_selected_database'])) {
            return $_SESSION['auto_selected_database'];
        }
        
        // Only try to connect if we have credentials
        try {
            // Check if we have database credentials before trying to connect
            if (!empty($_SESSION['db_user'])) {
                $conn = getDbConnection();
                $result = $conn->query("SELECT DATABASE()");
                if ($result) {
                    $row = $result->fetch_array();
                    $dbName = $row[0];
                    closeDbConnection($conn);
                    return $dbName;
                }
            }
        } catch (Exception $e) {
            // Silently fail - no database selected or credentials not available
            // This is expected when user hasn't logged in yet or credentials aren't configured
            // Don't log this as it's expected behavior
        }
    }
    
    return null;
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


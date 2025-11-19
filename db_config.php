<?php
/**
 * Database Configuration File
 * 
 * IMPORTANT: Database credentials are now stored per-user in login/credentials.txt
 * Each user can have their own database username, password, and host.
 * 
 * The constants below (DB_NAME, DB_CHARSET) are still used for configuration,
 * but DB_HOST, DB_USER, and DB_PASS are no longer used - they are loaded from
 * the user's session after login.
 */

/**
 * DB_NAME Configuration:
 * 
 * Option 1: Specify a database name
 *   const DB_NAME = 'your_database_name';
 * 
 * Option 2: Leave empty to auto-select first available database
 *   const DB_NAME = '';
 *   - System will automatically use the first non-system database
 *   - Perfect for portable installations!
 */
const DB_NAME = ''; // Leave empty for auto-detection

/**
 * Database character set
 */
const DB_CHARSET = 'utf8mb4';

/**
 * Get database credentials from user session
 * 
 * Credentials are loaded from login/credentials.txt when the user logs in
 * and stored in the session. Each user can have their own database credentials.
 * 
 * @return array Array with 'host', 'user', 'pass' keys
 * @throws Exception if credentials not found in session or session not active
 */
function getDbCredentials(): array {
    // Ensure session is started
    if (session_status() === PHP_SESSION_NONE) {
        if (!@session_start()) {
            throw new Exception("Failed to start session. Please check session configuration.");
        }
    }
    
    // Verify session is active
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new Exception("Session not active. Please log in again.");
    }
    
    // Require database credentials from session (no fallback to defaults)
    if (empty($_SESSION['db_user'])) {
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
 * Get database connection using user's credentials from session
 * 
 * Uses credentials stored in session (loaded from login/credentials.txt).
 * Each logged-in user will use their own database credentials.
 * 
 * @param string|null $database Optional: Specific database to connect to
 * @return mysqli Database connection object
 * @throws Exception if connection fails or credentials not available
 */
function getDbConnection(?string $database = null): mysqli
{
    // Determine which database to use
    $dbToUse = $database ?? (DB_NAME ?: null);
    
    // Get user's database credentials from session
    $credentials = getDbCredentials();
    
    // Create connection using user's credentials
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
 * Scans available databases and returns the first one that is not a system database.
 * Used for auto-selection when DB_NAME is empty.
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
 * Tries to get the database name from session cache first (fast, no DB connection needed).
 * If not in cache, attempts to query the database connection.
 * 
 * @return string|null Current database name or null if none selected or credentials unavailable
 */
function getCurrentDatabase() {
    // Check session cache first (doesn't require DB connection)
    if (session_status() === PHP_SESSION_ACTIVE) {
        // Return cached database if available
        if (isset($_SESSION['auto_selected_database']) && !empty($_SESSION['auto_selected_database'])) {
            return $_SESSION['auto_selected_database'];
        }
        
        // Try to query database if credentials are available
        try {
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
            // Silently fail - expected when credentials not configured or user not logged in
        }
    }
    
    return null;
}

/**
 * Set current database in session cache
 * 
 * Stores the selected database name in the session for quick retrieval
 * without requiring a database query.
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
 * Changes the default database for the given connection.
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
 * Properly closes the MySQL connection.
 * 
 * @param mysqli $conn Database connection object
 */
function closeDbConnection(mysqli $conn) {
    $conn->close();
}


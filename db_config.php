<?php
/**
 * Database Configuration File
 * 
 * Configure your database connection settings here
 */

// Database connection parameters
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'canvas-c25');
define('DB_CHARSET', 'utf8mb4');

/**
 * Get database connection
 * 
 * @return mysqli Database connection object
 * @throws Exception if connection fails
 */
function getDbConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset(DB_CHARSET);
    
    return $conn;
}

/**
 * Close database connection
 * 
 * @param mysqli $conn Database connection object
 */
function closeDbConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}
?>


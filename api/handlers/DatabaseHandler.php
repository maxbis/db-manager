<?php
/**
 * Database Handler
 * 
 * Handles database management operations
 */

class DatabaseHandler {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get all databases
     */
    public function getDatabases() {
        $result = $this->conn->query("SHOW DATABASES");
        $databases = [];
        
        while ($row = $result->fetch_assoc()) {
            $dbName = $row['Database'];
            
            // Skip system databases
            if (in_array($dbName, ['information_schema', 'performance_schema', 'mysql', 'sys'])) {
                continue;
            }
            
            // Get table count and size for each database
            $this->conn->query("USE `$dbName`");
            $tableResult = $this->conn->query("SHOW TABLE STATUS");
            $tableCount = $tableResult->num_rows;
            $totalSize = 0;
            
            while ($table = $tableResult->fetch_assoc()) {
                $totalSize += ($table['Data_length'] ?? 0) + ($table['Index_length'] ?? 0);
            }
            
            $databases[] = [
                'name' => $dbName,
                'tables' => $tableCount,
                'size' => $totalSize
            ];
        }
        
        echo json_encode([
            'success' => true,
            'databases' => $databases
        ]);
    }
    
    /**
     * Create a new database
     */
    public function createDatabase($name, $charset, $collation) {
        if (empty($name)) {
            throw new Exception("Database name is required");
        }
        
        // Validate database name (alphanumeric and underscores only)
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            throw new Exception("Database name can only contain letters, numbers, and underscores");
        }
        
        $sql = "CREATE DATABASE `$name` CHARACTER SET $charset COLLATE $collation";
        
        if ($this->conn->query($sql)) {
            echo json_encode([
                'success' => true,
                'message' => "Database '$name' created successfully"
            ]);
        } else {
            throw new Exception("Failed to create database: " . $this->conn->error);
        }
    }
    
    /**
     * Delete a database
     */
    public function deleteDatabase($name) {
        if (empty($name)) {
            throw new Exception("Database name is required");
        }
        
        // Prevent deletion of system databases
        if (in_array($name, ['information_schema', 'performance_schema', 'mysql', 'sys'])) {
            throw new Exception("Cannot delete system database");
        }
        
        $sql = "DROP DATABASE `$name`";
        
        if ($this->conn->query($sql)) {
            // Clear session variable if the deleted database was the currently selected one
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (isset($_SESSION['auto_selected_database']) && $_SESSION['auto_selected_database'] === $name) {
                unset($_SESSION['auto_selected_database']);
            }
            
            echo json_encode([
                'success' => true,
                'message' => "Database '$name' deleted successfully"
            ]);
        } else {
            throw new Exception("Failed to delete database: " . $this->conn->error);
        }
    }
    
    /**
     * Set the current database in session
     */
    public function setCurrentDatabase($database) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['auto_selected_database'] = $database;
        
        echo json_encode([
            'success' => true,
            'message' => 'Current database updated'
        ]);
    }
    
    /**
     * Get the current database from session
     */
    public function getCurrentDatabase() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $currentDb = $_SESSION['auto_selected_database'] ?? '';
        
        echo json_encode([
            'success' => true,
            'database' => $currentDb
        ]);
    }
    
    /**
     * Set the current table in session
     */
    public function setCurrentTable($table) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['current_table'] = $table;
        
        echo json_encode([
            'success' => true,
            'message' => 'Current table updated'
        ]);
    }
    
    /**
     * Get the current table from session
     */
    public function getCurrentTable() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $currentTable = $_SESSION['current_table'] ?? '';
        
        echo json_encode([
            'success' => true,
            'table' => $currentTable
        ]);
    }
}
?>


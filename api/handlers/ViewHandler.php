<?php
/**
 * View Handler
 * 
 * Handles database view operations
 */

class ViewHandler {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get view source SQL
     */
    public function getViewSource($tableName) {
        $tableName = $this->conn->real_escape_string($tableName);
        
        // Check if it's actually a view
        $typeResult = $this->conn->query("SHOW FULL TABLES LIKE '$tableName'");
        if (!$typeResult) {
            throw new Exception("Failed to check table type: " . $this->conn->error);
        }
        
        $typeRow = $typeResult->fetch_array();
        if (!$typeRow || $typeRow[1] !== 'VIEW') {
            throw new Exception("'$tableName' is not a view");
        }
        
        // Get the view definition
        $result = $this->conn->query("SHOW CREATE VIEW `$tableName`");
        if (!$result) {
            throw new Exception("Failed to get view definition: " . $this->conn->error);
        }
        
        $row = $result->fetch_assoc();
        if (!$row) {
            throw new Exception("View definition not found");
        }
        
        echo json_encode([
            'success' => true,
            'viewName' => $row['View'],
            'createStatement' => $row['Create View']
        ]);
    }
}
?>


<?php
/**
 * Import Handler
 * 
 * Handles database import operations
 */

class ImportHandler {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Import database from SQL file
     */
    public function importDatabase() {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("No file uploaded or upload error");
        }
        
        $database = $_POST['database'] ?? '';
        $dropExisting = $_POST['dropExisting'] ?? false;
        
        if (empty($database)) {
            throw new Exception("Target database is required");
        }
        
        // Read SQL file
        $sql = file_get_contents($_FILES['file']['tmp_name']);
        if ($sql === false) {
            throw new Exception("Failed to read SQL file");
        }
        
        // Switch to target database
        $this->conn->query("USE `$database`");
        
        // Drop existing tables if requested
        if ($dropExisting) {
            $result = $this->conn->query("SHOW TABLES");
            while ($row = $result->fetch_array()) {
                $this->conn->query("DROP TABLE IF EXISTS `" . $row[0] . "`");
            }
        }
        
        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        $executed = 0;
        $errors = [];
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                if ($this->conn->query($statement)) {
                    $executed++;
                } else {
                    $errors[] = "Error executing: " . substr($statement, 0, 100) . "... - " . $this->conn->error;
                }
            }
        }
        
        if (empty($errors)) {
            echo json_encode([
                'success' => true,
                'message' => "Database imported successfully. $executed statements executed."
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => "Import completed with errors. $executed statements executed. Errors: " . implode('; ', $errors)
            ]);
        }
    }
}
?>


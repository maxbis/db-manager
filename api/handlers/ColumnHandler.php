<?php
/**
 * Column Handler
 * 
 * Handles column-related operations on tables
 */

require_once __DIR__ . '/../utils/ColumnBuilder.php';

class ColumnHandler {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Add a new column to table
     */
    public function addColumn($tableName, $data) {
        $tableName = $this->conn->real_escape_string($tableName);
        
        // Build column definition
        $columnDef = ColumnBuilder::buildDefinition($data);
        
        // Determine position
        $position = $data['position'] ?? 'end';
        $positionClause = '';
        
        switch ($position) {
            case 'first':
                $positionClause = ' FIRST';
                break;
            case 'end':
                // No position clause needed - defaults to end
                break;
            default:
                if (strpos($position, 'after_') === 0) {
                    $afterColumn = $this->conn->real_escape_string(substr($position, 6));
                    $positionClause = " AFTER `$afterColumn`";
                }
                break;
        }
        
        $query = "ALTER TABLE `$tableName` ADD COLUMN `{$data['name']}` $columnDef$positionClause";
        
        if ($this->conn->query($query)) {
            echo json_encode([
                'success' => true,
                'message' => 'Column added successfully'
            ]);
        } else {
            throw new Exception("Add column failed: " . $this->conn->error);
        }
    }
    
    /**
     * Update an existing column
     */
    public function updateColumn($tableName, $oldName, $data) {
        $tableName = $this->conn->real_escape_string($tableName);
        $oldName = $this->conn->real_escape_string($oldName);
        
        // Build column definition
        $columnDef = ColumnBuilder::buildDefinition($data);
        
        $query = "ALTER TABLE `$tableName` MODIFY COLUMN `$oldName` $columnDef";
        
        if ($this->conn->query($query)) {
            // If name changed, rename the column
            if ($data['name'] !== $oldName) {
                $newName = $this->conn->real_escape_string($data['name']);
                $renameQuery = "ALTER TABLE `$tableName` RENAME COLUMN `$oldName` TO `$newName`";
                if (!$this->conn->query($renameQuery)) {
                    throw new Exception("Rename column failed: " . $this->conn->error);
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Column updated successfully'
            ]);
        } else {
            throw new Exception("Update column failed: " . $this->conn->error);
        }
    }
    
    /**
     * Delete a column
     */
    public function deleteColumn($tableName, $columnName) {
        $tableName = $this->conn->real_escape_string($tableName);
        $columnName = $this->conn->real_escape_string($columnName);
        
        $query = "ALTER TABLE `$tableName` DROP COLUMN `$columnName`";
        
        if ($this->conn->query($query)) {
            echo json_encode([
                'success' => true,
                'message' => 'Column deleted successfully'
            ]);
        } else {
            throw new Exception("Delete column failed: " . $this->conn->error);
        }
    }
}
?>


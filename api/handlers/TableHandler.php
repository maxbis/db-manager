<?php
/**
 * Table Handler
 * 
 * Handles table-related operations
 */

class TableHandler {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get all tables from database (including views)
     */
    public function getTables() {
        $result = $this->conn->query("SHOW FULL TABLES");
        $tables = [];

        if (!$result) {
            throw new Exception("Failed to list tables: " . $this->conn->error);
        }

        while ($row = $result->fetch_array()) {
            $tables[] = [
                'name' => $row[0],
                'type' => $row[1] // 'BASE TABLE' or 'VIEW'
            ];
        }

        // Fetch table sizes using SHOW TABLE STATUS (Data_length + Index_length)
        $sizes = [];
        $statusResult = $this->conn->query("SHOW TABLE STATUS");
        if ($statusResult) {
            while ($t = $statusResult->fetch_assoc()) {
                $dataLength = isset($t['Data_length']) ? (int)$t['Data_length'] : 0;
                $indexLength = isset($t['Index_length']) ? (int)$t['Index_length'] : 0;
                $sizes[$t['Name']] = $dataLength + $indexLength;
            }
        }

        // Attach size to each table (views will typically be 0)
        foreach ($tables as &$table) {
            $name = $table['name'];
            $table['size'] = $sizes[$name] ?? 0;
        }
        unset($table);

        echo json_encode([
            'success' => true,
            'tables' => $tables
        ]);
    }
    
    /**
     * Get table structure information (columns, types, primary key)
     */
    public function getTableInfo($tableName) {
        // Sanitize table name
        $tableName = $this->conn->real_escape_string($tableName);
        
        // Get current database name
        $dbResult = $this->conn->query("SELECT DATABASE()");
        $dbRow = $dbResult->fetch_array();
        $databaseName = $dbRow[0];
        $databaseNameEscaped = $this->conn->real_escape_string($databaseName);
        
        // Check if it's a view or table
        $typeResult = $this->conn->query("SHOW FULL TABLES LIKE '$tableName'");
        if (!$typeResult) {
            throw new Exception("Failed to check table type: " . $this->conn->error);
        }
        $typeRow = $typeResult->fetch_array();
        $isView = ($typeRow && $typeRow[1] === 'VIEW');
        
        // Get column information
        $result = $this->conn->query("SHOW COLUMNS FROM `$tableName`");
        if (!$result) {
            throw new Exception("Failed to get columns from '$tableName': " . $this->conn->error);
        }
        
        // Get foreign key information from information_schema
        $fkQuery = "
            SELECT 
                kcu.COLUMN_NAME,
                kcu.CONSTRAINT_NAME,
                kcu.REFERENCED_TABLE_NAME,
                kcu.REFERENCED_COLUMN_NAME,
                rc.UPDATE_RULE,
                rc.DELETE_RULE
            FROM information_schema.KEY_COLUMN_USAGE kcu
            LEFT JOIN information_schema.REFERENTIAL_CONSTRAINTS rc 
                ON kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME 
                AND kcu.TABLE_SCHEMA = rc.CONSTRAINT_SCHEMA
            WHERE kcu.TABLE_SCHEMA = '$databaseNameEscaped'
              AND kcu.TABLE_NAME = '$tableName'
              AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
        ";
        $fkResult = $this->conn->query($fkQuery);
        $foreignKeys = [];
        if ($fkResult) {
            while ($fkRow = $fkResult->fetch_assoc()) {
                $columnName = $fkRow['COLUMN_NAME'];
                if (!isset($foreignKeys[$columnName])) {
                    $foreignKeys[$columnName] = [];
                }
                $foreignKeys[$columnName][] = [
                    'constraint_name' => $fkRow['CONSTRAINT_NAME'],
                    'referenced_table' => $fkRow['REFERENCED_TABLE_NAME'],
                    'referenced_column' => $fkRow['REFERENCED_COLUMN_NAME'],
                    'update_rule' => $fkRow['UPDATE_RULE'],
                    'delete_rule' => $fkRow['DELETE_RULE']
                ];
            }
        }
        
        $columns = [];
        $primaryKey = null;
        
        while ($row = $result->fetch_assoc()) {
            $columnInfo = [
                'name' => $row['Field'],
                'type' => $row['Type'],
                'null' => $row['Null'] === 'YES',
                'key' => $row['Key'],
                'default' => $row['Default'],
                'extra' => $row['Extra']
            ];
            
            // Parse type to get base type and length
            preg_match('/^(\w+)(\(([^)]+)\))?/', $row['Type'], $matches);
            $columnInfo['baseType'] = strtolower($matches[1]);
            $columnInfo['length'] = $matches[3] ?? null;
            
            // Extract enum/set values
            if (in_array($columnInfo['baseType'], ['enum', 'set'])) {
                preg_match_all("/'([^']+)'/", $row['Type'], $enumMatches);
                $columnInfo['enumValues'] = $enumMatches[1];
            }
            
            // Add foreign key information if exists
            if (isset($foreignKeys[$row['Field']])) {
                // Use the first foreign key if multiple exist
                $fk = $foreignKeys[$row['Field']][0];
                $columnInfo['foreignKey'] = [
                    'referenced_table' => $fk['referenced_table'],
                    'referenced_column' => $fk['referenced_column'],
                    'update_rule' => $fk['update_rule'],
                    'delete_rule' => $fk['delete_rule'],
                    'constraint_name' => $fk['constraint_name']
                ];
            }
            
            $columns[] = $columnInfo;
            
            if ($row['Key'] === 'PRI') {
                $primaryKey = $row['Field'];
            }
        }
        
        echo json_encode([
            'success' => true,
            'columns' => $columns,
            'primaryKey' => $isView ? null : $primaryKey, // Views don't have primary keys
            'isView' => $isView,
            'tableType' => $isView ? 'VIEW' : 'BASE TABLE'
        ]);
    }

    /**
     * Get maximum used length for a VARCHAR column
     */
    public function getColumnMaxLength($tableName, $columnName) {
        if (empty($tableName) || empty($columnName)) {
            throw new Exception("Table name and column name are required");
        }

        $tableNameEscaped = $this->conn->real_escape_string($tableName);
        $columnNameEscaped = $this->conn->real_escape_string($columnName);

        // Verify column exists and is VARCHAR
        $columnResult = $this->conn->query("SHOW COLUMNS FROM `$tableNameEscaped` LIKE '$columnNameEscaped'");
        if (!$columnResult || $columnResult->num_rows === 0) {
            throw new Exception("Column '$columnName' not found in table '$tableName'");
        }
        $columnInfo = $columnResult->fetch_assoc();
        $typeLower = strtolower(trim($columnInfo['Type']));
        if (strpos($typeLower, 'varchar') !== 0) {
            throw new Exception("Column '$columnName' is not a VARCHAR column (type: " . $columnInfo['Type'] . ")");
        }

        $query = "SELECT MAX(CHAR_LENGTH(`$columnNameEscaped`)) AS maxLength FROM `$tableNameEscaped`";
        $result = $this->conn->query($query);
        if (!$result) {
            throw new Exception("Failed to compute max length: " . $this->conn->error);
        }
        $row = $result->fetch_assoc();
        $maxLength = isset($row['maxLength']) ? (int)$row['maxLength'] : 0;

        echo json_encode([
            'success' => true,
            'table' => $tableName,
            'column' => $columnName,
            'maxLength' => $maxLength
        ]);
    }
    
    /**
     * Create a new table
     */
    public function createTable($database, $name, $columns, $engine) {
        if (empty($database) || empty($name) || empty($columns)) {
            throw new Exception("Database name, table name, and columns are required");
        }
        
        // Validate table name
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            throw new Exception("Table name can only contain letters, numbers, and underscores");
        }
        
        // Switch to the specified database
        $this->conn->query("USE `$database`");
        
        // Check if table already exists
        $checkResult = $this->conn->query("SHOW TABLES LIKE '$name'");
        if ($checkResult && $checkResult->num_rows > 0) {
            throw new Exception("Table '$name' already exists in database '$database'");
        }
        
        // Parse columns (separated by comma or newline)
        // Support both comma-separated (new format) and newline-separated (legacy)
        $separator = strpos($columns, ',') !== false ? ',' : "\n";
        $columnLines = array_filter(array_map('trim', explode($separator, $columns)));
        $columnDefinitions = [];
        
        foreach ($columnLines as $line) {
            if (!empty($line)) {
                $columnDefinitions[] = $line;
            }
        }
        
        if (empty($columnDefinitions)) {
            throw new Exception("At least one column definition is required");
        }
        
        $sql = "CREATE TABLE `$name` (" . implode(', ', $columnDefinitions) . ") ENGINE=$engine";
        
        if ($this->conn->query($sql)) {
            echo json_encode([
                'success' => true,
                'message' => "Table '$name' created successfully in database '$database'"
            ]);
        } else {
            throw new Exception("Failed to create table: " . $this->conn->error);
        }
    }
    
    /**
     * Delete a table
     */
    public function deleteTable($database, $name) {
        if (empty($database) || empty($name)) {
            throw new Exception("Database name and table name are required");
        }
        
        // Switch to the specified database
        $this->conn->query("USE `$database`");
        
        $sql = "DROP TABLE `$name`";
        
        if ($this->conn->query($sql)) {
            echo json_encode([
                'success' => true,
                'message' => "Table '$name' deleted successfully from database '$database'"
            ]);
        } else {
            throw new Exception("Failed to delete table: " . $this->conn->error);
        }
    }

    /**
     * Rename a table within a database
     */
    public function renameTable($database, $oldName, $newName) {
        if (empty($database) || empty($oldName) || empty($newName)) {
            throw new Exception("Database name, current table name, and new table name are required");
        }

        // Validate table names
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $oldName) || !preg_match('/^[a-zA-Z0-9_]+$/', $newName)) {
            throw new Exception("Table names can only contain letters, numbers, and underscores");
        }

        if ($oldName === $newName) {
            throw new Exception("The new table name must be different from the current name");
        }

        // Switch to the specified database
        $this->conn->query("USE `$database`");

        // Ensure the source table exists
        $checkOld = $this->conn->query("SHOW FULL TABLES LIKE '$oldName'");
        if (!$checkOld || $checkOld->num_rows === 0) {
            throw new Exception("Table '$oldName' does not exist in database '$database'");
        }

        // Ensure the destination table name is not already taken
        $checkNew = $this->conn->query("SHOW FULL TABLES LIKE '$newName'");
        if ($checkNew && $checkNew->num_rows > 0) {
            throw new Exception("A table or view named '$newName' already exists in database '$database'");
        }

        $sql = "RENAME TABLE `$oldName` TO `$newName`";

        if ($this->conn->query($sql)) {
            echo json_encode([
                'success' => true,
                'message' => "Table '$oldName' was renamed to '$newName' in database '$database'"
            ]);
        } else {
            throw new Exception("Failed to rename table: " . $this->conn->error);
        }
    }
    
    /**
     * Get list of tables for foreign key reference selection
     */
    public function getTablesForForeignKey() {
        $result = $this->conn->query("SHOW TABLES");
        $tables = [];

        if (!$result) {
            throw new Exception("Failed to list tables: " . $this->conn->error);
        }

        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }

        echo json_encode([
            'success' => true,
            'tables' => $tables
        ]);
    }
    
    /**
     * Get columns from a table (for foreign key reference selection)
     */
    public function getTableColumns($tableName) {
        $tableName = $this->conn->real_escape_string($tableName);
        
        $result = $this->conn->query("SHOW COLUMNS FROM `$tableName`");
        if (!$result) {
            throw new Exception("Failed to get columns from '$tableName': " . $this->conn->error);
        }
        
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = [
                'name' => $row['Field'],
                'type' => $row['Type'],
                'key' => $row['Key']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'columns' => $columns
        ]);
    }
}
?>


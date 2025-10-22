<?php
/**
 * Record Handler
 * 
 * Handles CRUD operations on database records
 */

class RecordHandler {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get records from table with filtering and sorting
     */
    public function getRecords($tableName, $offset, $limit, $sortColumn, $sortOrder, $filters) {
        $tableName = $this->conn->real_escape_string($tableName);
        
        // Build WHERE clause for filters
        $whereConditions = [];
        $params = [];
        $types = '';
        
        foreach ($filters as $column => $value) {
            if ($value !== '') {
                $column = $this->conn->real_escape_string($column);
                $whereConditions[] = "`$column` LIKE ?";
                $params[] = "%$value%";
                $types .= 's';
            }
        }
        
        $whereClause = count($whereConditions) > 0 ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Build ORDER BY clause
        $orderClause = '';
        if ($sortColumn) {
            $sortColumn = $this->conn->real_escape_string($sortColumn);
            $sortOrder = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';
            $orderClause = "ORDER BY `$sortColumn` $sortOrder";
        }
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM `$tableName` $whereClause";
        try {
            if (count($params) > 0) {
                $stmt = $this->conn->prepare($countQuery);
                if (!$stmt) {
                    throw new Exception("Prepare failed for count query: " . $this->conn->error);
                }
                $stmt->bind_param($types, ...$params);
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed for count query: " . $stmt->error);
                }
                $countResult = $stmt->get_result();
                $total = $countResult->fetch_assoc()['total'];
            } else {
                $countResult = $this->conn->query($countQuery);
                if (!$countResult) {
                    throw new Exception("Count query failed: " . $this->conn->error);
                }
                $total = $countResult->fetch_assoc()['total'];
            }
        } catch (Exception $e) {
            throw new Exception("Error getting record count from '$tableName': " . $e->getMessage());
        }
        
        // Get records
        $query = "SELECT * FROM `$tableName` $whereClause $orderClause LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        try {
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            if (count($params) > 0) {
                $stmt->bind_param($types, ...$params);
            }
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $result = $stmt->get_result();
            
            $records = [];
            while ($row = $result->fetch_assoc()) {
                $records[] = $row;
            }
        } catch (Exception $e) {
            throw new Exception("Error fetching records from '$tableName': " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'records' => $records,
            'total' => $total,
            'offset' => $offset,
            'limit' => $limit
        ]);
    }
    
    /**
     * Get a single record by primary key
     */
    public function getRecord($tableName, $primaryKey, $primaryValue) {
        $tableName = $this->conn->real_escape_string($tableName);
        $primaryKey = $this->conn->real_escape_string($primaryKey);
        
        $stmt = $this->conn->prepare("SELECT * FROM `$tableName` WHERE `$primaryKey` = ? LIMIT 1");
        $stmt->bind_param('s', $primaryValue);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $record = $result->fetch_assoc();
        
        if (!$record) {
            throw new Exception("Record not found");
        }
        
        echo json_encode([
            'success' => true,
            'record' => $record
        ]);
    }
    
    /**
     * Insert a new record
     */
    public function insertRecord($tableName, $data) {
        $tableName = $this->conn->real_escape_string($tableName);
        
        $columns = [];
        $values = [];
        $params = [];
        $types = '';
        
        foreach ($data as $column => $value) {
            $column = $this->conn->real_escape_string($column);
            $columns[] = "`$column`";
            $values[] = '?';
            $params[] = $value === '' ? null : $value;
            $types .= 's';
        }
        
        $columnsStr = implode(', ', $columns);
        $valuesStr = implode(', ', $values);
        
        $query = "INSERT INTO `$tableName` ($columnsStr) VALUES ($valuesStr)";
        $stmt = $this->conn->prepare($query);
        
        if (count($params) > 0) {
            $stmt->bind_param($types, ...$params);
        }
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Record inserted successfully',
                'insertId' => $this->conn->insert_id
            ]);
        } else {
            throw new Exception("Insert failed: " . $stmt->error);
        }
    }
    
    /**
     * Update an existing record
     */
    public function updateRecord($tableName, $primaryKey, $primaryValue, $data) {
        $tableName = $this->conn->real_escape_string($tableName);
        $primaryKey = $this->conn->real_escape_string($primaryKey);
        
        $setParts = [];
        $params = [];
        $types = '';
        
        foreach ($data as $column => $value) {
            $column = $this->conn->real_escape_string($column);
            $setParts[] = "`$column` = ?";
            $params[] = $value === '' ? null : $value;
            $types .= 's';
        }
        
        $params[] = $primaryValue;
        $types .= 's';
        
        $setClause = implode(', ', $setParts);
        $query = "UPDATE `$tableName` SET $setClause WHERE `$primaryKey` = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Record updated successfully',
                'affectedRows' => $stmt->affected_rows
            ]);
        } else {
            throw new Exception("Update failed: " . $stmt->error);
        }
    }
    
    /**
     * Delete a record
     */
    public function deleteRecord($tableName, $primaryKey, $primaryValue) {
        $tableName = $this->conn->real_escape_string($tableName);
        $primaryKey = $this->conn->real_escape_string($primaryKey);
        
        $stmt = $this->conn->prepare("DELETE FROM `$tableName` WHERE `$primaryKey` = ?");
        $stmt->bind_param('s', $primaryValue);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Record deleted successfully',
                'affectedRows' => $stmt->affected_rows
            ]);
        } else {
            throw new Exception("Delete failed: " . $stmt->error);
        }
    }
}
?>


<?php
/**
 * Query Handler
 * 
 * Handles SQL query execution
 */

class QueryHandler {
    private $conn;
    
    // Maximum rows to return for SELECT queries
    const MAX_QUERY_RESULTS = 100;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Execute a SQL query
     * Supports SELECT, INSERT, UPDATE, DELETE, and other SQL commands
     * Results limited to first MAX_QUERY_RESULTS rows for SELECT queries
     */
    public function executeQuery($query) {
        $query = trim($query);
        
        if (empty($query)) {
            throw new Exception("Query cannot be empty");
        }
        
        // Determine query type
        $queryType = strtoupper(substr(ltrim($query), 0, 6));
        
        // Security check: prevent multiple queries (basic protection)
        if (strpos($query, ';') !== false && substr(rtrim($query), -1) !== ';') {
            throw new Exception("Multiple queries are not allowed");
        }
        
        // Remove trailing semicolon if present
        $query = rtrim($query, '; ');
        
        try {
            if (strpos($queryType, 'SELECT') === 0) {
                // SELECT query - limit to MAX_QUERY_RESULTS rows
                $limitedQuery = $query;
                
                // Check if query already has a LIMIT clause
                if (stripos($query, 'LIMIT') === false) {
                    $limitedQuery .= ' LIMIT ' . self::MAX_QUERY_RESULTS;
                } else {
                    // Extract existing limit and ensure it's not more than MAX_QUERY_RESULTS
                    if (preg_match('/LIMIT\s+(\d+)/i', $query, $matches)) {
                        $existingLimit = intval($matches[1]);
                        if ($existingLimit > self::MAX_QUERY_RESULTS) {
                            $limitedQuery = preg_replace('/LIMIT\s+\d+/i', 'LIMIT ' . self::MAX_QUERY_RESULTS, $query);
                        }
                    }
                }
                
                $result = $this->conn->query($limitedQuery);
                
                if ($result === false) {
                    throw new Exception($this->conn->error);
                }
                
                $data = [];
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                
                echo json_encode([
                    'success' => true,
                    'type' => 'select',
                    'data' => $data,
                    'rowCount' => count($data),
                    'totalRows' => count($data),
                    'message' => count($data) . ' rows returned'
                ]);
                
            } else {
                // Non-SELECT query (INSERT, UPDATE, DELETE, etc.)
                $result = $this->conn->query($query);
                
                if ($result === false) {
                    throw new Exception($this->conn->error);
                }
                
                $affectedRows = $this->conn->affected_rows;
                $message = '';
                
                if (strpos($queryType, 'INSERT') === 0) {
                    $message = "Record inserted successfully. Insert ID: " . $this->conn->insert_id;
                } else if (strpos($queryType, 'UPDATE') === 0) {
                    $message = "Query executed successfully. $affectedRows row(s) affected";
                } else if (strpos($queryType, 'DELETE') === 0) {
                    $message = "Query executed successfully. $affectedRows row(s) deleted";
                } else {
                    $message = "Query executed successfully";
                }
                
                echo json_encode([
                    'success' => true,
                    'type' => 'non-select',
                    'affectedRows' => $affectedRows,
                    'insertId' => $this->conn->insert_id,
                    'message' => $message
                ]);
            }
            
        } catch (Exception $e) {
            throw new Exception("Query execution failed: " . $e->getMessage());
        }
    }
}
?>


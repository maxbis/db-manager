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
    const MAX_EXPORT_RESULTS = 5000;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Execute a SQL query
     * Supports SELECT, INSERT, UPDATE, DELETE, and other SQL commands
     * Results limited to first MAX_QUERY_RESULTS rows for SELECT queries
     */
    public function executeQuery($query) {
        $query = $this->normalizeQuery($query);
        $queryType = strtoupper(substr(ltrim($query), 0, 6));
        
        try {
            if (strpos($queryType, 'SELECT') === 0) {
                $limitedQuery = $this->enforceRowLimit($query, self::MAX_QUERY_RESULTS);
                
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

    public function exportQuery($query) {
        $query = $this->normalizeQuery($query);
        
        if (stripos($query, 'SELECT') !== 0) {
            throw new Exception("Only SELECT queries can be exported");
        }
        
        $requestedMaxRows = isset($_POST['maxRows']) ? intval($_POST['maxRows']) : self::MAX_EXPORT_RESULTS;
        if ($requestedMaxRows <= 0) {
            $requestedMaxRows = self::MAX_EXPORT_RESULTS;
        }
        $maxRows = min($requestedMaxRows, self::MAX_EXPORT_RESULTS);
        
        $exportQuery = $this->enforceRowLimit($query, $maxRows);
        
        $result = $this->conn->query($exportQuery);
        
        if ($result === false) {
            throw new Exception($this->conn->error);
        }
        
        $filename = $_POST['filename'] ?? ('query-results-' . date('Y-m-d_H-i-s'));
        if (stripos($filename, '.csv') === false) {
            $filename .= '.csv';
        }
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        if ($output === false) {
            $result->free();
            throw new Exception('Failed to prepare export stream');
        }
        
        fwrite($output, "\xEF\xBB\xBF");
        
        $fields = $result->fetch_fields();
        if ($fields) {
            $headers = [];
            foreach ($fields as $field) {
                $headers[] = $field->name;
            }
            fputcsv($output, $headers);
        }
        
        while ($row = $result->fetch_assoc()) {
            $values = [];
            foreach ($row as $value) {
                $values[] = $this->formatCsvValue($value);
            }
            fputcsv($output, $values);
        }
        
        fclose($output);
        $result->free();
    }

    private function normalizeQuery(string $query): string {
        $query = trim($query);

        if ($query === '') {
            throw new Exception('Query cannot be empty');
        }

        if (strpos($query, ';') !== false && substr(rtrim($query), -1) !== ';') {
            throw new Exception('Multiple queries are not allowed');
        }

        return rtrim($query, '; ');
    }

    private function enforceRowLimit(string $query, int $maxRows): string {
        if ($maxRows <= 0) {
            return $query;
        }

        if (stripos($query, 'LIMIT') === false) {
            return $query . ' LIMIT ' . $maxRows;
        }

        $patternLimitComma = '/LIMIT\s+(\d+)\s*,\s*(\d+)/i';
        if (preg_match($patternLimitComma, $query, $matches)) {
            $offset = (int) $matches[1];
            $limit = (int) $matches[2];
            if ($limit > $maxRows) {
                return preg_replace($patternLimitComma, 'LIMIT ' . $offset . ', ' . $maxRows, $query, 1);
            }
            return $query;
        }

        $patternLimitOffset = '/LIMIT\s+(\d+)\s+OFFSET\s+(\d+)/i';
        if (preg_match($patternLimitOffset, $query, $matches)) {
            $limit = (int) $matches[1];
            $offset = (int) $matches[2];
            if ($limit > $maxRows) {
                return preg_replace($patternLimitOffset, 'LIMIT ' . $maxRows . ' OFFSET ' . $offset, $query, 1);
            }
            return $query;
        }

        $patternSimpleLimit = '/LIMIT\s+(\d+)/i';
        if (preg_match($patternSimpleLimit, $query, $matches)) {
            $limit = (int) $matches[1];
            if ($limit > $maxRows) {
                return preg_replace($patternSimpleLimit, 'LIMIT ' . $maxRows, $query, 1);
            }
            return $query;
        }

        return $query;
    }

    private function formatCsvValue($value) {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_resource($value)) {
            return '[resource]';
        }

        if (is_object($value) || is_array($value)) {
            $encoded = json_encode($value);
            return $encoded !== false ? $encoded : '[unserializable]';
        }

        return (string) $value;
    }
}
?>


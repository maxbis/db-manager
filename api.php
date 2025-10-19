<?php
/**
 * API Backend for CRUD Operations
 * 
 * Handles all AJAX requests for database operations
 */

header('Content-Type: application/json');
require_once 'db_config.php';

// Get the action from request
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $conn = getDbConnection();
    
    switch ($action) {
        case 'getTables':
            getTables($conn);
            break;
            
        case 'getTableInfo':
            $tableName = $_GET['table'] ?? '';
            getTableInfo($conn, $tableName);
            break;
            
        case 'getRecords':
            $tableName = $_GET['table'] ?? '';
            $offset = intval($_GET['offset'] ?? 0);
            $limit = intval($_GET['limit'] ?? 20);
            $sortColumn = $_GET['sortColumn'] ?? '';
            $sortOrder = $_GET['sortOrder'] ?? 'ASC';
            $filters = json_decode($_GET['filters'] ?? '{}', true) ?: [];
            getRecords($conn, $tableName, $offset, $limit, $sortColumn, $sortOrder, $filters);
            break;
            
        case 'getRecord':
            $tableName = $_POST['table'] ?? '';
            $primaryKey = $_POST['primaryKey'] ?? '';
            $primaryValue = $_POST['primaryValue'] ?? '';
            getRecord($conn, $tableName, $primaryKey, $primaryValue);
            break;
            
        case 'insertRecord':
            $tableName = $_POST['table'] ?? '';
            $data = json_decode($_POST['data'] ?? '{}', true) ?: [];
            insertRecord($conn, $tableName, $data);
            break;
            
        case 'updateRecord':
            $tableName = $_POST['table'] ?? '';
            $primaryKey = $_POST['primaryKey'] ?? '';
            $primaryValue = $_POST['primaryValue'] ?? '';
            $data = json_decode($_POST['data'] ?? '{}', true) ?: [];
            updateRecord($conn, $tableName, $primaryKey, $primaryValue, $data);
            break;
            
        case 'deleteRecord':
            $tableName = $_POST['table'] ?? '';
            $primaryKey = $_POST['primaryKey'] ?? '';
            $primaryValue = $_POST['primaryValue'] ?? '';
            deleteRecord($conn, $tableName, $primaryKey, $primaryValue);
            break;
            
        case 'addColumn':
            $tableName = $_POST['table'] ?? '';
            $data = json_decode($_POST['data'] ?? '{}', true) ?: [];
            addColumn($conn, $tableName, $data);
            break;
            
        case 'updateColumn':
            $tableName = $_POST['table'] ?? '';
            $oldName = $_POST['oldName'] ?? '';
            $data = json_decode($_POST['data'] ?? '{}', true) ?: [];
            updateColumn($conn, $tableName, $oldName, $data);
            break;
            
        case 'deleteColumn':
            $tableName = $_POST['table'] ?? '';
            $columnName = $_POST['columnName'] ?? '';
            deleteColumn($conn, $tableName, $columnName);
            break;
            
        case 'executeQuery':
            $query = $_POST['query'] ?? '';
            executeQuery($conn, $query);
            break;
            
        /* SAVED QUERIES NOW USE BROWSER LOCALSTORAGE - Database endpoints commented out
        case 'saveQuery':
            $queryName = $_POST['queryName'] ?? '';
            $querySql = $_POST['querySql'] ?? '';
            $tableName = $_POST['tableName'] ?? null;
            $description = $_POST['description'] ?? null;
            saveQuery($conn, $queryName, $querySql, $tableName, $description);
            break;
            
        case 'getSavedQueries':
            $tableName = $_GET['table'] ?? null;
            getSavedQueries($conn, $tableName);
            break;
            
        case 'loadSavedQuery':
            $queryId = $_POST['queryId'] ?? 0;
            loadSavedQuery($conn, $queryId);
            break;
            
        case 'deleteSavedQuery':
            $queryId = $_POST['queryId'] ?? 0;
            deleteSavedQuery($conn, $queryId);
            break;
        */
            
        default:
            throw new Exception("Invalid action: $action");
    }
    
    closeDbConnection($conn);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Get all tables from database
 */
function getTables($conn) {
    $result = $conn->query("SHOW TABLES");
    $tables = [];
    
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    
    echo json_encode([
        'success' => true,
        'tables' => $tables
    ]);
}

/**
 * Get table structure information (columns, types, primary key)
 */
function getTableInfo($conn, $tableName) {
    // Sanitize table name
    $tableName = $conn->real_escape_string($tableName);
    
    // Get column information
    $result = $conn->query("SHOW COLUMNS FROM `$tableName`");
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
        
        $columns[] = $columnInfo;
        
        if ($row['Key'] === 'PRI') {
            $primaryKey = $row['Field'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'columns' => $columns,
        'primaryKey' => $primaryKey
    ]);
}

/**
 * Get records from table with filtering and sorting
 */
function getRecords($conn, $tableName, $offset, $limit, $sortColumn, $sortOrder, $filters) {
    $tableName = $conn->real_escape_string($tableName);
    
    // Build WHERE clause for filters
    $whereConditions = [];
    $params = [];
    $types = '';
    
    foreach ($filters as $column => $value) {
        if ($value !== '') {
            $column = $conn->real_escape_string($column);
            $whereConditions[] = "`$column` LIKE ?";
            $params[] = "%$value%";
            $types .= 's';
        }
    }
    
    $whereClause = count($whereConditions) > 0 ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Build ORDER BY clause
    $orderClause = '';
    if ($sortColumn) {
        $sortColumn = $conn->real_escape_string($sortColumn);
        $sortOrder = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';
        $orderClause = "ORDER BY `$sortColumn` $sortOrder";
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM `$tableName` $whereClause";
    if (count($params) > 0) {
        $stmt = $conn->prepare($countQuery);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $countResult = $stmt->get_result();
        $total = $countResult->fetch_assoc()['total'];
    } else {
        $countResult = $conn->query($countQuery);
        $total = $countResult->fetch_assoc()['total'];
    }
    
    // Get records
    $query = "SELECT * FROM `$tableName` $whereClause $orderClause LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($query);
    if (count($params) > 0) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
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
function getRecord($conn, $tableName, $primaryKey, $primaryValue) {
    $tableName = $conn->real_escape_string($tableName);
    $primaryKey = $conn->real_escape_string($primaryKey);
    
    $stmt = $conn->prepare("SELECT * FROM `$tableName` WHERE `$primaryKey` = ? LIMIT 1");
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
function insertRecord($conn, $tableName, $data) {
    $tableName = $conn->real_escape_string($tableName);
    
    $columns = [];
    $values = [];
    $params = [];
    $types = '';
    
    foreach ($data as $column => $value) {
        $column = $conn->real_escape_string($column);
        $columns[] = "`$column`";
        $values[] = '?';
        $params[] = $value === '' ? null : $value;
        $types .= 's';
    }
    
    $columnsStr = implode(', ', $columns);
    $valuesStr = implode(', ', $values);
    
    $query = "INSERT INTO `$tableName` ($columnsStr) VALUES ($valuesStr)";
    $stmt = $conn->prepare($query);
    
    if (count($params) > 0) {
        $stmt->bind_param($types, ...$params);
    }
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Record inserted successfully',
            'insertId' => $conn->insert_id
        ]);
    } else {
        throw new Exception("Insert failed: " . $stmt->error);
    }
}

/**
 * Update an existing record
 */
function updateRecord($conn, $tableName, $primaryKey, $primaryValue, $data) {
    $tableName = $conn->real_escape_string($tableName);
    $primaryKey = $conn->real_escape_string($primaryKey);
    
    $setParts = [];
    $params = [];
    $types = '';
    
    foreach ($data as $column => $value) {
        $column = $conn->real_escape_string($column);
        $setParts[] = "`$column` = ?";
        $params[] = $value === '' ? null : $value;
        $types .= 's';
    }
    
    $params[] = $primaryValue;
    $types .= 's';
    
    $setClause = implode(', ', $setParts);
    $query = "UPDATE `$tableName` SET $setClause WHERE `$primaryKey` = ?";
    
    $stmt = $conn->prepare($query);
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
function deleteRecord($conn, $tableName, $primaryKey, $primaryValue) {
    $tableName = $conn->real_escape_string($tableName);
    $primaryKey = $conn->real_escape_string($primaryKey);
    
    $stmt = $conn->prepare("DELETE FROM `$tableName` WHERE `$primaryKey` = ?");
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

/**
 * Add a new column to table
 */
function addColumn($conn, $tableName, $data) {
    $tableName = $conn->real_escape_string($tableName);
    
    // Build column definition
    $columnDef = buildColumnDefinition($data);
    
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
                $afterColumn = $conn->real_escape_string(substr($position, 6));
                $positionClause = " AFTER `$afterColumn`";
            }
            break;
    }
    
    $query = "ALTER TABLE `$tableName` ADD COLUMN `{$data['name']}` $columnDef$positionClause";
    
    if ($conn->query($query)) {
        echo json_encode([
            'success' => true,
            'message' => 'Column added successfully'
        ]);
    } else {
        throw new Exception("Add column failed: " . $conn->error);
    }
}

/**
 * Update an existing column
 */
function updateColumn($conn, $tableName, $oldName, $data) {
    $tableName = $conn->real_escape_string($tableName);
    $oldName = $conn->real_escape_string($oldName);
    
    // Build column definition
    $columnDef = buildColumnDefinition($data);
    
    $query = "ALTER TABLE `$tableName` MODIFY COLUMN `$oldName` $columnDef";
    
    if ($conn->query($query)) {
        // If name changed, rename the column
        if ($data['name'] !== $oldName) {
            $newName = $conn->real_escape_string($data['name']);
            $renameQuery = "ALTER TABLE `$tableName` RENAME COLUMN `$oldName` TO `$newName`";
            if (!$conn->query($renameQuery)) {
                throw new Exception("Rename column failed: " . $conn->error);
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Column updated successfully'
        ]);
    } else {
        throw new Exception("Update column failed: " . $conn->error);
    }
}

/**
 * Delete a column
 */
function deleteColumn($conn, $tableName, $columnName) {
    $tableName = $conn->real_escape_string($tableName);
    $columnName = $conn->real_escape_string($columnName);
    
    $query = "ALTER TABLE `$tableName` DROP COLUMN `$columnName`";
    
    if ($conn->query($query)) {
        echo json_encode([
            'success' => true,
            'message' => 'Column deleted successfully'
        ]);
    } else {
        throw new Exception("Delete column failed: " . $conn->error);
    }
}

/**
 * Build column definition from form data
 */
function buildColumnDefinition($data) {
    $definition = $data['type'];
    
    // Add NOT NULL if not allowing null
    if (!$data['null']) {
        $definition .= ' NOT NULL';
    }
    
    // Add DEFAULT value
    if ($data['default'] !== null && $data['default'] !== '') {
        $definition .= ' DEFAULT ' . $data['default'];
    }
    
    // Add AUTO_INCREMENT
    if ($data['auto_increment']) {
        $definition .= ' AUTO_INCREMENT';
    }
    
    // Add UNIQUE
    if ($data['unique']) {
        $definition .= ' UNIQUE';
    }
    
    // Add PRIMARY KEY
    if ($data['primary']) {
        $definition .= ' PRIMARY KEY';
    }
    
    // Add extra attributes
    if (!empty($data['extra'])) {
        $definition .= ' ' . $data['extra'];
    }
    
    return $definition;
}

/**
 * Execute a SQL query
 * Supports SELECT, INSERT, UPDATE, DELETE, and other SQL commands
 * Results limited to first 100 rows for SELECT queries
 */
function executeQuery($conn, $query) {
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
            // SELECT query - limit to 100 rows
            $limitedQuery = $query;
            
            // Check if query already has a LIMIT clause
            if (stripos($query, 'LIMIT') === false) {
                $limitedQuery .= ' LIMIT 100';
            } else {
                // Extract existing limit and ensure it's not more than 100
                if (preg_match('/LIMIT\s+(\d+)/i', $query, $matches)) {
                    $existingLimit = intval($matches[1]);
                    if ($existingLimit > 100) {
                        $limitedQuery = preg_replace('/LIMIT\s+\d+/i', 'LIMIT 100', $query);
                    }
                }
            }
            
            $result = $conn->query($limitedQuery);
            
            if ($result === false) {
                throw new Exception($conn->error);
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
            $result = $conn->query($query);
            
            if ($result === false) {
                throw new Exception($conn->error);
            }
            
            $affectedRows = $conn->affected_rows;
            $message = '';
            
            if (strpos($queryType, 'INSERT') === 0) {
                $message = "Record inserted successfully. Insert ID: " . $conn->insert_id;
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
                'insertId' => $conn->insert_id,
                'message' => $message
            ]);
        }
        
    } catch (Exception $e) {
        throw new Exception("Query execution failed: " . $e->getMessage());
    }
}

/* SAVED QUERIES FUNCTIONS - Now using LocalStorage, kept for reference
/**
 * Save a SQL query for later use
 */
function saveQuery($conn, $queryName, $querySql, $tableName, $description) {
    if (empty($queryName)) {
        throw new Exception("Query name is required");
    }
    
    if (empty($querySql)) {
        throw new Exception("Query SQL is required");
    }
    
    $stmt = $conn->prepare("INSERT INTO `saved_queries` 
        (query_name, query_sql, table_name, description) 
        VALUES (?, ?, ?, ?)");
    
    $stmt->bind_param('ssss', $queryName, $querySql, $tableName, $description);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Query saved successfully',
            'queryId' => $conn->insert_id
        ]);
    } else {
        throw new Exception("Failed to save query: " . $stmt->error);
    }
}

/**
 * Get all saved queries, optionally filtered by table
 */
function getSavedQueries($conn, $tableName = null) {
    if ($tableName) {
        $stmt = $conn->prepare("SELECT * FROM `saved_queries` 
            WHERE table_name = ? OR table_name IS NULL 
            ORDER BY last_used_at DESC, created_at DESC");
        $stmt->bind_param('s', $tableName);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query("SELECT * FROM `saved_queries` 
            ORDER BY last_used_at DESC, created_at DESC");
    }
    
    $queries = [];
    while ($row = $result->fetch_assoc()) {
        $queries[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'queries' => $queries
    ]);
}

/**
 * Load a saved query and update its usage stats
 */
function loadSavedQuery($conn, $queryId) {
    $queryId = intval($queryId);
    
    if ($queryId <= 0) {
        throw new Exception("Invalid query ID");
    }
    
    // Get the query
    $stmt = $conn->prepare("SELECT * FROM `saved_queries` WHERE id = ?");
    $stmt->bind_param('i', $queryId);
    $stmt->execute();
    $result = $stmt->get_result();
    $query = $result->fetch_assoc();
    
    if (!$query) {
        throw new Exception("Query not found");
    }
    
    // Update usage stats
    $conn->query("UPDATE `saved_queries` 
        SET last_used_at = CURRENT_TIMESTAMP, 
            use_count = use_count + 1 
        WHERE id = $queryId");
    
    echo json_encode([
        'success' => true,
        'query' => $query
    ]);
}

/**
 * Delete a saved query
 */
function deleteSavedQuery($conn, $queryId) {
    $queryId = intval($queryId);
    
    if ($queryId <= 0) {
        throw new Exception("Invalid query ID");
    }
    
    $stmt = $conn->prepare("DELETE FROM `saved_queries` WHERE id = ?");
    $stmt->bind_param('i', $queryId);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Query deleted successfully'
        ]);
    } else {
        throw new Exception("Failed to delete query: " . $stmt->error);
    }
}
// END OF SAVED QUERIES FUNCTIONS COMMENT BLOCK */
?>


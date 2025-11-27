<?php
/**
 * API Backend for CRUD Operations
 * 
 * Handles all AJAX requests for database operations
 * 
 * This file serves as the routing layer. All business logic has been
 * extracted into handler classes for better maintainability.
 */

// IP Authorization Check
require_once '../login/auth_check.php';

header('Content-Type: application/json');
require_once '../db_connection.php';

// Get the action from request
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $conn = getDbConnection();

    // Determine which database to use
    $database = $_GET['database'] ?? $_POST['database'] ?? DB_NAME;

    // For operations that require a database (not database management operations)
    $needsDatabase = !in_array($action, ['getDatabases', 'createDatabase', 'deleteDatabase', 'getCurrentDatabase', 'setCurrentDatabase']);

    if ($needsDatabase) {
        // If no database specified, try to auto-select first available
        if (empty($database)) {
            // Start session to cache the selected database
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Check if we have a cached database selection
            if (!empty($_SESSION['auto_selected_database'])) {
                $database = $_SESSION['auto_selected_database'];
            } else {
                // Auto-select first available database
                $database = getFirstAvailableDatabase($conn);
                if (empty($database)) {
                    throw new Exception("No databases available. Please create a database first or set DB_NAME in db_connection.php.");
                }
                // Cache the selection
                $_SESSION['auto_selected_database'] = $database;
            }
        }

        selectDatabase($conn, $database);
    }

    // Route requests to appropriate handlers (lazy loading)
    switch ($action) {
        // Table Operations
        case 'getTables':
            require_once __DIR__ . '/handlers/TableHandler.php';
            $handler = new TableHandler($conn);
            $handler->getTables();
            break;

        case 'getTableInfo':
            require_once __DIR__ . '/handlers/TableHandler.php';
            $handler = new TableHandler($conn);
            $tableName = $_GET['table'] ?? '';
            $handler->getTableInfo($tableName);
            break;

        case 'getColumnMaxLength':
            require_once __DIR__ . '/handlers/TableHandler.php';
            $handler = new TableHandler($conn);
            $tableName = $_GET['table'] ?? '';
            $columnName = $_GET['column'] ?? '';
            $handler->getColumnMaxLength($tableName, $columnName);
            break;

        case 'getTablesForForeignKey':
            require_once __DIR__ . '/handlers/TableHandler.php';
            $handler = new TableHandler($conn);
            $handler->getTablesForForeignKey();
            break;

        case 'getTableColumns':
            require_once __DIR__ . '/handlers/TableHandler.php';
            $handler = new TableHandler($conn);
            $tableName = $_GET['table'] ?? '';
            $handler->getTableColumns($tableName);
            break;

        case 'createTable':
            require_once __DIR__ . '/handlers/TableHandler.php';
            $handler = new TableHandler($conn);
            $database = $_POST['database'] ?? '';
            $name = $_POST['name'] ?? '';
            $columns = $_POST['columns'] ?? '';
            $engine = $_POST['engine'] ?? 'InnoDB';
            $handler->createTable($database, $name, $columns, $engine);
            break;

        case 'deleteTable':
            require_once __DIR__ . '/handlers/TableHandler.php';
            $handler = new TableHandler($conn);
            $database = $_POST['database'] ?? '';
            $name = $_POST['name'] ?? '';
            $handler->deleteTable($database, $name);
            break;

        case 'renameTable':
            require_once __DIR__ . '/handlers/TableHandler.php';
            $handler = new TableHandler($conn);
            $database = $_POST['database'] ?? '';
            $oldName = $_POST['oldName'] ?? '';
            $newName = $_POST['newName'] ?? '';
            $handler->renameTable($database, $oldName, $newName);
            break;

        case 'addForeignKey':
            require_once __DIR__ . '/handlers/TableHandler.php';
            $handler = new TableHandler($conn);
            $database = $_POST['database'] ?? '';
            $table = $_POST['table'] ?? '';
            $constraintName = $_POST['constraintName'] ?? '';
            $column = $_POST['column'] ?? '';
            $refTable = $_POST['refTable'] ?? '';
            $refColumn = $_POST['refColumn'] ?? '';
            $onDelete = $_POST['onDelete'] ?? 'RESTRICT';
            $onUpdate = $_POST['onUpdate'] ?? 'RESTRICT';
            $handler->addForeignKey($database, $table, $constraintName, $column, $refTable, $refColumn, $onDelete, $onUpdate);
            break;

        case 'dropForeignKey':
            require_once __DIR__ . '/handlers/TableHandler.php';
            $handler = new TableHandler($conn);
            $database = $_POST['database'] ?? '';
            $table = $_POST['table'] ?? '';
            $constraintName = $_POST['constraintName'] ?? '';
            $handler->dropForeignKey($database, $table, $constraintName);
            break;

        // Record Operations
        case 'getRecords':
            require_once __DIR__ . '/handlers/RecordHandler.php';
            $handler = new RecordHandler($conn);
            $tableName = $_GET['table'] ?? '';
            $offset = intval($_GET['offset'] ?? 0);
            $limit = intval($_GET['limit'] ?? 20);
            $sortColumn = $_GET['sortColumn'] ?? '';
            $sortOrder = $_GET['sortOrder'] ?? 'ASC';
            $filters = json_decode($_GET['filters'] ?? '{}', true) ?: [];
            $handler->getRecords($tableName, $offset, $limit, $sortColumn, $sortOrder, $filters);
            break;

        case 'getRecord':
            require_once __DIR__ . '/handlers/RecordHandler.php';
            $handler = new RecordHandler($conn);
            $tableName = $_POST['table'] ?? '';
            $primaryKey = $_POST['primaryKey'] ?? '';
            $primaryValue = $_POST['primaryValue'] ?? '';
            $handler->getRecord($tableName, $primaryKey, $primaryValue);
            break;

        case 'insertRecord':
            require_once __DIR__ . '/handlers/RecordHandler.php';
            $handler = new RecordHandler($conn);
            $tableName = $_POST['table'] ?? '';
            $data = json_decode($_POST['data'] ?? '{}', true) ?: [];
            $handler->insertRecord($tableName, $data);
            break;

        case 'updateRecord':
            require_once __DIR__ . '/handlers/RecordHandler.php';
            $handler = new RecordHandler($conn);
            $tableName = $_POST['table'] ?? '';
            $primaryKey = $_POST['primaryKey'] ?? '';
            $primaryValue = $_POST['primaryValue'] ?? '';
            $data = json_decode($_POST['data'] ?? '{}', true) ?: [];
            $handler->updateRecord($tableName, $primaryKey, $primaryValue, $data);
            break;

        case 'deleteRecord':
            require_once __DIR__ . '/handlers/RecordHandler.php';
            $handler = new RecordHandler($conn);
            $tableName = $_POST['table'] ?? '';
            $primaryKey = $_POST['primaryKey'] ?? '';
            $primaryValue = $_POST['primaryValue'] ?? '';
            $handler->deleteRecord($tableName, $primaryKey, $primaryValue);
            break;

        // Column Operations
        case 'addColumn':
            require_once __DIR__ . '/handlers/ColumnHandler.php';
            $handler = new ColumnHandler($conn);
            $tableName = $_POST['table'] ?? '';
            $data = json_decode($_POST['data'] ?? '{}', true) ?: [];
            $handler->addColumn($tableName, $data);
            break;

        case 'updateColumn':
            require_once __DIR__ . '/handlers/ColumnHandler.php';
            $handler = new ColumnHandler($conn);
            $tableName = $_POST['table'] ?? '';
            $oldName = $_POST['oldName'] ?? '';
            $data = json_decode($_POST['data'] ?? '{}', true) ?: [];
            $handler->updateColumn($tableName, $oldName, $data);
            break;

        case 'deleteColumn':
            require_once __DIR__ . '/handlers/ColumnHandler.php';
            $handler = new ColumnHandler($conn);
            $tableName = $_POST['table'] ?? '';
            $columnName = $_POST['columnName'] ?? '';
            $handler->deleteColumn($tableName, $columnName);
            break;

        // Query Execution
        case 'executeQuery':
            require_once __DIR__ . '/handlers/QueryHandler.php';
            $handler = new QueryHandler($conn);
            $query = $_POST['query'] ?? '';
            $handler->executeQuery($query);
            break;

        case 'exportQuery':
            require_once __DIR__ . '/handlers/QueryHandler.php';
            $handler = new QueryHandler($conn);
            $query = $_POST['query'] ?? '';
            $handler->exportQuery($query);
            break;

        // Database Management Operations
        case 'getDatabases':
            require_once __DIR__ . '/handlers/DatabaseHandler.php';
            $handler = new DatabaseHandler($conn);
            $handler->getDatabases();
            break;

        case 'createDatabase':
            require_once __DIR__ . '/handlers/DatabaseHandler.php';
            $handler = new DatabaseHandler($conn);
            $name = $_POST['name'] ?? '';
            $charset = $_POST['charset'] ?? 'utf8mb4';
            $collation = $_POST['collation'] ?? 'utf8mb4_unicode_ci';
            $handler->createDatabase($name, $charset, $collation);
            break;

        case 'deleteDatabase':
            require_once __DIR__ . '/handlers/DatabaseHandler.php';
            $handler = new DatabaseHandler($conn);
            $name = $_POST['name'] ?? '';
            $handler->deleteDatabase($name);
            break;

        case 'setCurrentDatabase':
            require_once __DIR__ . '/handlers/DatabaseHandler.php';
            $handler = new DatabaseHandler($conn);
            $database = $_POST['database'] ?? '';
            $handler->setCurrentDatabase($database);
            break;

        case 'getCurrentDatabase':
            require_once __DIR__ . '/handlers/DatabaseHandler.php';
            $handler = new DatabaseHandler($conn);
            $handler->getCurrentDatabase();
            break;

        case 'setCurrentTable':
            require_once __DIR__ . '/handlers/DatabaseHandler.php';
            $handler = new DatabaseHandler($conn);
            $table = $_POST['table'] ?? '';
            $handler->setCurrentTable($table);
            break;

        case 'getCurrentTable':
            require_once __DIR__ . '/handlers/DatabaseHandler.php';
            $handler = new DatabaseHandler($conn);
            $handler->getCurrentTable();
            break;

        // Export Operations
        case 'exportAllDatabases':
            require_once __DIR__ . '/handlers/ExportHandler.php';
            $handler = new ExportHandler($conn);
            // Try mysqldump first for maximum speed, fallback to PHP export
            if ($handler->tryMysqldumpExport()) {
                // mysqldump succeeded, exit
                exit;
            } else {
                // Fallback to PHP export
                $handler->exportAllDatabases();
            }
            break;

        case 'exportDatabase':
            require_once __DIR__ . '/handlers/ExportHandler.php';
            $handler = new ExportHandler($conn);
            $name = $_POST['name'] ?? '';
            $handler->exportDatabase($name);
            break;

        // Import Operations
        case 'importDatabase':
            require_once __DIR__ . '/handlers/ImportHandler.php';
            $handler = new ImportHandler($conn);
            $handler->importDatabase();
            break;

        // View Operations
        case 'getViewSource':
            require_once __DIR__ . '/handlers/ViewHandler.php';
            $handler = new ViewHandler($conn);
            $tableName = $_GET['table'] ?? '';
            $handler->getViewSource($tableName);
            break;

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
?>
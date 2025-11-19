<?php
/**
 * Export Handler
 * 
 * Handles database export operations
 */

class ExportHandler {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Export database to SQL
     */
    public function exportDatabase($name) {
        if (empty($name)) {
            throw new Exception("Database name is required");
        }
        
        $includeCreateDatabase = $_POST['includeCreateDatabase'] ?? true;
        $dataOnly = $_POST['dataOnly'] ?? false;
        
        $sql = "-- Database Export: $name\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        // Include CREATE DATABASE statement if requested
        if ($includeCreateDatabase) {
            $sql .= "-- Create database\n";
            $sql .= "CREATE DATABASE IF NOT EXISTS `$name`;\n";
            $sql .= "USE `$name`;\n\n";
        }
        
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        
        // Switch to the database
        $this->conn->query("USE `$name`");
        
        // Get all tables
        $result = $this->conn->query("SHOW TABLES");
        $tables = [];
        
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
        
        // Export each table
        foreach ($tables as $table) {
            if (!$dataOnly) {
                // Get table structure
                $createResult = $this->conn->query("SHOW CREATE TABLE `$table`");
                $createRow = $createResult->fetch_assoc();
                $sql .= "-- Table structure for table `$table`\n";
                $sql .= "DROP TABLE IF EXISTS `$table`;\n";
                $sql .= $createRow['Create Table'] . ";\n\n";
            }
            
            // Get table data
            $dataResult = $this->conn->query("SELECT * FROM `$table`");
            if ($dataResult->num_rows > 0) {
                $sql .= "-- Data for table `$table`\n";
                
                while ($row = $dataResult->fetch_assoc()) {
                    $columns = array_keys($row);
                    $values = array_map(function($value) {
                        return $value === null ? 'NULL' : "'" . $this->conn->real_escape_string($value) . "'";
                    }, array_values($row));
                    
                    $sql .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
                }
                $sql .= "\n";
            }
        }
        
        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        
        echo json_encode([
            'success' => true,
            'sql' => $sql
        ]);
    }
    
    /**
     * Export all databases to SQL (optimized for speed)
     */
    public function exportAllDatabases() {
        $includeCreateDatabase = $_POST['includeCreateDatabase'] ?? true;
        $dataOnly = $_POST['dataOnly'] ?? false;
        $customFilename = $_POST['filename'] ?? 'all_databases_export';
        
        // Optimize PHP settings for speed
        ini_set('memory_limit', '1G');
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        
        // Set headers for file download
        $filename = $customFilename . '_' . date('Y-m-d_H-i-s') . '.sql';
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        // Disable output buffering for faster streaming
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Output header
        echo "-- Complete Database Export\n";
        echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        echo "-- Exported all user databases\n\n";
        echo "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        
        // Flush output immediately
        if (ob_get_level()) {
            ob_flush();
            flush();
        }
        
        // Get all databases
        $result = $this->conn->query("SHOW DATABASES");
        $systemDatabases = ['information_schema', 'performance_schema', 'mysql', 'sys'];
        $databaseCount = 0;
        
        while ($row = $result->fetch_array()) {
            $dbName = $row[0];
            if (in_array($dbName, $systemDatabases)) {
                continue;
            }
            
            $databaseCount++;
            
            // Output database separator
            echo "-- =============================================\n";
            echo "-- Database: $dbName\n";
            echo "-- =============================================\n\n";
            
            // Include CREATE DATABASE statement if requested
            if ($includeCreateDatabase) {
                echo "-- Create database\n";
                echo "CREATE DATABASE IF NOT EXISTS `$dbName`;\n";
                echo "USE `$dbName`;\n\n";
            }
            
            // Switch to the database
            $this->conn->query("USE `$dbName`");
            
            // Get all tables in this database
            $tableResult = $this->conn->query("SHOW TABLES");
            $tables = [];
            
            while ($tableRow = $tableResult->fetch_array()) {
                $tables[] = $tableRow[0];
            }
            
            if (empty($tables)) {
                echo "-- No tables found in database `$dbName`\n\n";
                continue;
            }
            
            // Export each table
            foreach ($tables as $table) {
                if (!$dataOnly) {
                    // Get table structure
                    $createResult = $this->conn->query("SHOW CREATE TABLE `$table`");
                    $createRow = $createResult->fetch_assoc();
                    echo "-- Table structure for table `$table`\n";
                    echo "DROP TABLE IF EXISTS `$table`;\n";
                    echo $createRow['Create Table'] . ";\n\n";
                }
                
                // Export table data using optimized bulk insert
                $dataResult = $this->conn->query("SELECT COUNT(*) as total_rows FROM `$table`");
                $totalRows = $dataResult->fetch_assoc()['total_rows'];
                
                if ($totalRows > 0) {
                    echo "-- Data for table `$table` ($totalRows rows)\n";
                    
                    // Use larger chunk size for better performance
                    $chunkSize = 5000; // Increased from 1000 to 5000
                    $offset = 0;
                    $bulkInsertBuffer = [];
                    $bulkInsertSize = 100; // Insert 100 rows at once
                    
                    while ($offset < $totalRows) {
                        $chunkResult = $this->conn->query("SELECT * FROM `$table` LIMIT $chunkSize OFFSET $offset");
                        
                        while ($row = $chunkResult->fetch_assoc()) {
                            $columns = array_keys($row);
                            $values = array_map(function($value) {
                                return $value === null ? 'NULL' : "'" . $this->conn->real_escape_string($value) . "'";
                            }, array_values($row));
                            
                            $bulkInsertBuffer[] = "(" . implode(', ', $values) . ")";
                            
                            // When buffer is full, output bulk insert
                            if (count($bulkInsertBuffer) >= $bulkInsertSize) {
                                echo "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES " . implode(', ', $bulkInsertBuffer) . ";\n";
                                $bulkInsertBuffer = [];
                            }
                        }
                        
                        // Output remaining rows in buffer
                        if (!empty($bulkInsertBuffer)) {
                            echo "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES " . implode(', ', $bulkInsertBuffer) . ";\n";
                            $bulkInsertBuffer = [];
                        }
                        
                        $offset += $chunkSize;
                        
                        // Flush output less frequently for better performance
                        if ($offset % ($chunkSize * 2) === 0) {
                            if (ob_get_level()) {
                                ob_flush();
                                flush();
                            }
                        }
                    }
                    echo "\n";
                } else {
                    echo "-- No data in table `$table`\n\n";
                }
            }
            
            echo "\n";
            
            // Flush output after each database
            if (ob_get_level()) {
                ob_flush();
                flush();
            }
        }
        
        echo "SET FOREIGN_KEY_CHECKS = 1;\n";
        
        // Final flush
        if (ob_get_level()) {
            ob_flush();
            flush();
        }
        
        exit;
    }
    
    /**
     * Try to use mysqldump for fastest export (if available)
     */
    public function tryMysqldumpExport() {
        // Check if mysqldump is available
        $mysqldumpPath = '';
        $possiblePaths = [
            'mysqldump',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/opt/homebrew/bin/mysqldump',
            '/Applications/XAMPP/bin/mysqldump',
            '/Applications/MAMP/bin/mysqldump'
        ];
        
        foreach ($possiblePaths as $path) {
            if (is_executable($path) || (function_exists('exec') && exec("which $path 2>/dev/null"))) {
                $mysqldumpPath = $path;
                break;
            }
        }
        
        if (empty($mysqldumpPath)) {
            return false; // mysqldump not available
        }
        
        // Get connection details from session
        require_once __DIR__ . '/../../db_connection.php';
        $credentials = getDbCredentials();
        $host = $credentials['host'];
        $user = $credentials['user'];
        $pass = $credentials['pass'];
        $port = 3306; // Default MySQL port
        
        // Parse host for port
        if (strpos($host, ':') !== false) {
            list($host, $port) = explode(':', $host, 2);
        }
        
        // Get all databases
        $result = $this->conn->query("SHOW DATABASES");
        $systemDatabases = ['information_schema', 'performance_schema', 'mysql', 'sys'];
        $databases = [];
        
        while ($row = $result->fetch_array()) {
            $dbName = $row[0];
            if (!in_array($dbName, $systemDatabases)) {
                $databases[] = $dbName;
            }
        }
        
        if (empty($databases)) {
            return false; // No databases to export
        }
        
        // Set headers for file download
        $customFilename = $_POST['filename'] ?? 'all_databases_export';
        $filename = $customFilename . '_' . date('Y-m-d_H-i-s') . '.sql';
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        // Build mysqldump command
        $command = escapeshellarg($mysqldumpPath);
        $command .= ' --host=' . escapeshellarg($host);
        $command .= ' --port=' . escapeshellarg($port);
        $command .= ' --user=' . escapeshellarg($user);
        if (!empty($pass)) {
            $command .= ' --password=' . escapeshellarg($pass);
        }
        $command .= ' --single-transaction';
        $command .= ' --routines';
        $command .= ' --triggers';
        $command .= ' --events';
        $command .= ' --add-drop-database';
        $command .= ' --databases ' . implode(' ', array_map('escapeshellarg', $databases));
        
        // Execute mysqldump and stream output
        $handle = popen($command . ' 2>/dev/null', 'r');
        
        if ($handle === false) {
            return false; // Failed to execute mysqldump
        }
        
        // Stream output directly to browser
        while (!feof($handle)) {
            echo fread($handle, 8192); // Read in 8KB chunks
            if (ob_get_level()) {
                ob_flush();
                flush();
            }
        }
        
        pclose($handle);
        return true;
    }
}
?>


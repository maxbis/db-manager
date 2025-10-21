<?php
/**
 * Database Sync API (Local)
 * 
 * Handles remote to local database synchronization via remote API
 * This file stays on your local development server
 */

// Prevent any output before JSON
ob_start();

// Suppress warnings in production (comment out for debugging)
error_reporting(E_ERROR | E_PARSE);

// Set JSON header first
header('Content-Type: application/json');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * API-friendly authentication check
 * Reuses the same logic as login/auth_check.php but returns JSON instead of redirecting
 */

// Load auth functions (but don't execute the automatic check at the bottom)
require_once __DIR__ . '/../login/remember_tokens.php';

// Check IP authorization (same as auth_check.php)
function getClientIP() {
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED'];
    } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
        $ip = $_SERVER['HTTP_FORWARDED'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    }
    return $ip;
}

function isLocalhost($ip) {
    $localhostPatterns = ['127.0.0.1', '::1', 'localhost', '::ffff:127.0.0.1'];
    return in_array($ip, $localhostPatterns);
}

function ipMatchesCIDR($ip, $cidr) {
    if (strpos($cidr, '/') === false) {
        return $ip === $cidr;
    }
    list($subnet, $mask) = explode('/', $cidr);
    $ipLong = ip2long($ip);
    $subnetLong = ip2long($subnet);
    if ($ipLong === false || $subnetLong === false) {
        return false;
    }
    $maskLong = -1 << (32 - (int)$mask);
    return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
}

function isIPWhitelisted($ip, $whitelistFile = null) {
    if ($whitelistFile === null) {
        $whitelistFile = __DIR__ . '/../login/ipAllowed.txt';
    }
    if (!file_exists($whitelistFile)) {
        return false;
    }
    $whitelist = file($whitelistFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($whitelist === false) {
        return false;
    }
    foreach ($whitelist as $entry) {
        $entry = trim($entry);
        if (empty($entry) || $entry[0] === '#') {
            continue;
        }
        if (ipMatchesCIDR($ip, $entry)) {
            return true;
        }
    }
    return false;
}

// Perform the same checks as auth_check.php but return JSON on failure
$clientIP = getClientIP();
$ipAuthorized = false;

if (isLocalhost($clientIP)) {
    $ipAuthorized = true;
} elseif (isIPWhitelisted($clientIP)) {
    $ipAuthorized = true;
}

if (!$ipAuthorized) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Access denied: IP not authorized'
    ]);
    exit;
}

// Check session authentication (same logic as auth_check.php)
$credentialsFile = __DIR__ . '/../login/credentials.txt';
if (file_exists($credentialsFile) && filesize($credentialsFile) > 0) {
    // Credentials exist - require login
    if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        // Try remember-me token (same as auth_check.php)
        if (isset($_COOKIE['remember_token'])) {
            $userData = validateRememberToken($_COOKIE['remember_token']);
            if ($userData !== false) {
                // Auto-login via remember token
                session_regenerate_id(true);
                $_SESSION['authenticated'] = true;
                $_SESSION['username'] = $userData['username'];
                $_SESSION['login_time'] = time();
                $_SESSION['last_activity'] = time();
                $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
                $_SESSION['auto_login'] = true;
            } else {
                // Invalid token - clear cookie
                setcookie('remember_token', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
                
                // Return JSON error instead of redirecting
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'error' => 'Authentication required. Please log in first.'
                ]);
                exit;
            }
        } else {
            // Not authenticated - return JSON error instead of redirecting
            ob_clean();
            echo json_encode([
                'success' => false,
                'error' => 'Authentication required. Please log in first.'
            ]);
            exit;
        }
    }
}

// Authentication passed - continue with API logic

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'testConnection':
            $config = $input['config'];
            $result = testRemoteConnection($config);
            ob_clean();
            echo json_encode($result);
            break;

        case 'getTables':
            $config = $input['config'];
            $result = getRemoteTables($config);
            ob_clean();
            echo json_encode($result);
            break;

        case 'syncDatabase':
            $remoteConfig = $input['remoteConfig'];
            $localConfig = $input['localConfig'];
            $tables = $input['tables'];
            $result = syncDatabase($remoteConfig, $localConfig, $tables);
            ob_clean();
            echo json_encode($result);
            break;

        default:
            ob_clean();
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action'
            ]);
    }
} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// ═══════════════════════════════════════════════════════════════════════════
// REMOTE API COMMUNICATION FUNCTIONS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Generate time-based token
 */
function generateToken($secretKey, $validitySeconds = 300) {
    $currentTimeSlot = floor(time() / $validitySeconds);
    return hash('sha256', $secretKey . $currentTimeSlot);
}

/**
 * Make authenticated request to remote API
 */
function callRemoteAPI($url, $apiKey, $action, $data = []) {
    $token = generateToken($apiKey);
    
    $payload = array_merge(['action' => $action], $data);
    $jsonPayload = json_encode($payload);
    
    $ch = curl_init($url);
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $jsonPayload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-API-Key: ' . $apiKey,
            'X-Token: ' . $token
        ],
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => true, // Set to false if using self-signed cert (not recommended)
        CURLOPT_SSL_VERIFYHOST => 2
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($response === false) {
        throw new Exception("cURL error: " . $error);
    }
    
    $result = json_decode($response, true);
    
    if ($result === null) {
        throw new Exception("Invalid JSON response from remote API. Response: " . substr($response, 0, 200));
    }
    
    if (!isset($result['success'])) {
        throw new Exception("Malformed API response");
    }
    
    return $result;
}

/**
 * Test connection to remote API
 */
function testRemoteConnection($config) {
    try {
        $url = $config['apiUrl'] ?? '';
        $apiKey = $config['apiKey'] ?? '';
        
        if (empty($url)) {
            return [
                'success' => false,
                'error' => 'Remote API URL is required'
            ];
        }
        
        if (empty($apiKey)) {
            return [
                'success' => false,
                'error' => 'API Key is required'
            ];
        }
        
        // Test with ping action
        $result = callRemoteAPI($url, $apiKey, 'ping');
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Connection successful'
            ];
        } else {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Unknown error'
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Get list of tables from remote database
 */
function getRemoteTables($config) {
    try {
        $url = $config['apiUrl'] ?? '';
        $apiKey = $config['apiKey'] ?? '';
        $database = $config['database'] ?? '';
        
        if (empty($url) || empty($apiKey)) {
            return [
                'success' => false,
                'error' => 'API URL and Key are required'
            ];
        }
        
        $result = callRemoteAPI($url, $apiKey, 'getTables', ['database' => $database]);
        
        return $result;
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Sync database from remote to local via API
 */
function syncDatabase($remoteConfig, $localConfig, $tables) {
    $logs = [];
    $stats = [
        'tablesSynced' => 0,
        'rowsCopied' => 0
    ];

    try {
        $url = $remoteConfig['apiUrl'] ?? '';
        $apiKey = $remoteConfig['apiKey'] ?? '';
        $remoteDatabase = $remoteConfig['database'] ?? '';
        
        if (empty($url) || empty($apiKey)) {
            throw new Exception("Remote API URL and Key are required");
        }
        
        // Connect to local database
        $logs[] = ['message' => 'Connecting to local database...', 'type' => 'info'];
        require_once __DIR__ . '/../db_config.php';
        
        $localConn = new mysqli(DB_HOST, DB_USER, DB_PASS, null, $localConfig['port'] ?? 3306);

        if ($localConn->connect_error) {
            throw new Exception("Local connection failed: " . $localConn->connect_error);
        }

        $localConn->set_charset('utf8mb4');

        // Create local database if needed
        if ($localConfig['createDatabase']) {
            $dbName = $localConn->real_escape_string($localConfig['database']);
            $logs[] = ['message' => "Creating local database '{$dbName}' if not exists...", 'type' => 'info'];
            $localConn->query("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }

        // Select local database
        if (!$localConn->select_db($localConfig['database'])) {
            throw new Exception("Failed to select local database: " . $localConn->error);
        }

        $logs[] = ['message' => '✓ Connected to local database', 'type' => 'success'];

        // Disable foreign key checks temporarily
        $localConn->query("SET FOREIGN_KEY_CHECKS = 0");
        $logs[] = ['message' => 'Disabled foreign key checks', 'type' => 'info'];

        // Sync each table
        foreach ($tables as $table) {
            $logs[] = ['message' => "Processing table: {$table}", 'type' => 'info'];

            try {
                // Get table structure from remote
                $structureResult = callRemoteAPI($url, $apiKey, 'getTableStructure', [
                    'database' => $remoteDatabase,
                    'table' => $table
                ]);
                
                if (!$structureResult['success']) {
                    throw new Exception("Failed to get structure: " . $structureResult['error']);
                }
                
                $createStatement = $structureResult['structure'];

                // Drop existing table if requested
                if ($localConfig['dropExisting']) {
                    $localConn->query("DROP TABLE IF EXISTS `{$table}`");
                    $logs[] = ['message' => "  ↳ Dropped existing table", 'type' => 'warning'];
                }

                // Create table structure
                if (!$localConn->query($createStatement)) {
                    // If table exists and we're not dropping, that's okay
                    if ($localConn->errno != 1050) {
                        throw new Exception("Failed to create table: " . $localConn->error);
                    }
                }
                $logs[] = ['message' => "  ↳ Created table structure", 'type' => 'success'];

                // Sync data if requested
                if ($localConfig['syncData']) {
                    // Clear existing data
                    $localConn->query("TRUNCATE TABLE `{$table}`");

                    // Fetch data in batches
                    $offset = 0;
                    $batchSize = 1000;
                    $totalCopied = 0;
                    $hasMore = true;

                    while ($hasMore) {
                        $dataResult = callRemoteAPI($url, $apiKey, 'getTableData', [
                            'database' => $remoteDatabase,
                            'table' => $table,
                            'offset' => $offset,
                            'limit' => $batchSize
                        ]);
                        
                        if (!$dataResult['success']) {
                            throw new Exception("Failed to fetch data: " . $dataResult['error']);
                        }
                        
                        $rows = $dataResult['rows'];
                        $hasMore = $dataResult['hasMore'];

                        if (count($rows) > 0) {
                            // Get column names from first row
                            $columnNames = array_keys($rows[0]);
                            $columnNamesEscaped = array_map(function($col) {
                                return "`{$col}`";
                            }, $columnNames);

                            // Insert rows
                            foreach ($rows as $row) {
                                $values = array_map(function($value) use ($localConn) {
                                    if ($value === null) {
                                        return 'NULL';
                                    }
                                    return "'" . $localConn->real_escape_string($value) . "'";
                                }, array_values($row));

                                $insertSQL = "INSERT INTO `{$table}` (" . implode(', ', $columnNamesEscaped) . ") VALUES (" . implode(', ', $values) . ")";
                                
                                if (!$localConn->query($insertSQL)) {
                                    $logs[] = ['message' => "  ⚠ Warning inserting row: " . $localConn->error, 'type' => 'warning'];
                                } else {
                                    $totalCopied++;
                                    $stats['rowsCopied']++;
                                }
                            }

                            $offset += count($rows);
                        } else {
                            $hasMore = false;
                        }
                    }

                    if ($totalCopied > 0) {
                        $logs[] = ['message' => "  ↳ Copied {$totalCopied} rows", 'type' => 'success'];
                    } else {
                        $logs[] = ['message' => "  ↳ Table is empty (0 rows)", 'type' => 'info'];
                    }
                }

                $stats['tablesSynced']++;
                $logs[] = ['message' => "✓ Completed table: {$table}", 'type' => 'success'];

            } catch (Exception $e) {
                $logs[] = ['message' => "✗ Error with table {$table}: " . $e->getMessage(), 'type' => 'error'];
            }
        }

        // Re-enable foreign key checks
        $localConn->query("SET FOREIGN_KEY_CHECKS = 1");
        $logs[] = ['message' => 'Re-enabled foreign key checks', 'type' => 'info'];

        // Close connection
        $localConn->close();

        return [
            'success' => true,
            'logs' => $logs,
            'stats' => $stats
        ];

    } catch (Exception $e) {
        $logs[] = ['message' => 'Fatal error: ' . $e->getMessage(), 'type' => 'error'];
        
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'logs' => $logs,
            'stats' => $stats
        ];
    }
}
?>

<?php
/**
 * Database Sync Configuration Template
 * 
 * INSTRUCTIONS:
 * 1. Copy this file to config.php
 * 2. Generate a secure API key (or use the one from your local server)
 * 3. Copy the SAME API key to both local and remote servers
 * 4. Adjust other settings as needed
 */

// API Authentication Key - MUST BE THE SAME ON BOTH SERVERS!
// Generate a secure random key:
// Option 1: Use the auto-generated key from config.php
// Option 2: Generate new: php -r "echo bin2hex(random_bytes(32));"
define('SYNC_API_KEY', 'REPLACE-WITH-YOUR-SECURE-API-KEY');

// Maximum execution time for sync operations (in seconds)
// Set to 0 for unlimited (recommended for large databases)
// Note: You may also need to adjust php.ini settings
define('SYNC_MAX_EXECUTION_TIME', 0);

// Maximum memory limit for sync operations
// Set to -1 for unlimited (recommended for large databases)
// Examples: '256M', '512M', '1G', '-1'
define('SYNC_MEMORY_LIMIT', '-1');

// Chunk size for data transfer (number of rows per request)
// Smaller values = more requests but less memory usage
// Larger values = fewer requests but more memory usage
// Recommended: 1000-5000 for most databases
define('SYNC_CHUNK_SIZE', 1000);

// Whether to log sync operations
// Logs are written to SYNC_LOG_FILE
define('SYNC_ENABLE_LOGGING', true);

// Log file path (relative to sync_db directory)
// Make sure this directory is writable
define('SYNC_LOG_FILE', __DIR__ . '/sync_log.txt');

/**
 * IMPORTANT NOTES:
 * 
 * 1. API KEY SECURITY:
 *    - The API key MUST be exactly the same on both local and remote servers
 *    - Keep this key secret and secure
 *    - Do not commit this file to version control
 *    - Change the key regularly for better security
 * 
 * 2. PERFORMANCE TUNING:
 *    - For small databases (< 1GB): Keep default settings
 *    - For medium databases (1-10GB): Consider chunk size 2000-3000
 *    - For large databases (> 10GB): Consider chunk size 5000+ and ensure adequate memory
 * 
 * 3. SERVER REQUIREMENTS:
 *    - PHP 7.0 or higher
 *    - MySQL 5.6 or higher (or MariaDB equivalent)
 *    - Sufficient memory for handling data chunks
 *    - Network connectivity between local and remote servers
 * 
 * 4. PERMISSIONS:
 *    - Remote server: Database user needs SELECT, SHOW VIEW privileges
 *    - Local server: Database user needs ALL privileges or CREATE, INSERT, DROP
 */


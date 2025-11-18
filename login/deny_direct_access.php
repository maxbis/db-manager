<?php
/**
 * Deny Direct Access Protection
 * 
 * This file should be included at the top of any PHP file that reads sensitive files.
 * It provides an additional layer of protection by checking if the file is being
 * accessed directly via HTTP (which should be denied).
 * 
 * Usage: require_once __DIR__ . '/deny_direct_access.php';
 * 
 * Note: This is a fallback protection. Primary protection is via .htaccess
 */

// If this file is accessed directly via HTTP, deny access
if (basename($_SERVER['PHP_SELF']) === 'deny_direct_access.php') {
    http_response_code(403);
    die('Access Denied: Direct access to this file is not allowed.');
}

// Function to check if a file request is direct HTTP access
function isDirectHttpAccess($filePath) {
    // Check if the requested file matches the current script
    $requestedFile = basename($_SERVER['REQUEST_URI']);
    $targetFile = basename($filePath);
    
    // If someone is trying to access .txt files directly
    if (preg_match('/\.(txt|log|bak|backup|old|tmp)$/i', $requestedFile)) {
        return true;
    }
    
    return false;
}

// Additional check: If accessing sensitive files directly, deny
if (isset($_SERVER['REQUEST_URI'])) {
    $sensitivePatterns = [
        '/credentials\.txt$/i',
        '/remember_tokens\.txt$/i',
        '/ipAllowed\.txt$/i',
        '/\.env$/i',
        '/config\.php$/i'
    ];
    
    foreach ($sensitivePatterns as $pattern) {
        if (preg_match($pattern, $_SERVER['REQUEST_URI'])) {
            http_response_code(403);
            header('Content-Type: text/plain');
            die('Access Denied: Direct access to sensitive files is not allowed.');
        }
    }
}


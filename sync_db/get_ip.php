<?php
/**
 * Simple IP Detection API
 * 
 * This endpoint returns the client's IP address as seen by the server.
 * No authentication needed - it only returns the requesting IP.
 * 
 * Deploy this file to your REMOTE server to check what IP it sees.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Load shared IP functions
require_once __DIR__ . '/../login/ip_functions.php';

$clientIP = getClientIP();

// Return JSON response
echo json_encode([
    'success' => true,
    'ip' => $clientIP,
    'timestamp' => date('Y-m-d H:i:s'),
    'server' => $_SERVER['SERVER_NAME'] ?? 'unknown'
]);


<?php
/**
 * IP Address Utility Functions
 * 
 * Shared library for IP address detection and validation.
 * Used by authentication, sync APIs, and other components.
 * 
 * Usage:
 *   require_once __DIR__ . '/ip_functions.php';
 *   $ip = getClientIP();
 *   if (isIPWhitelisted($ip)) { ... }
 */

/**
 * Get the client's real IP address
 * Handles various proxy configurations
 * 
 * @return string The client's IP address
 */
function getClientIP() {
    $ip = '';
    
    // Check for various headers that might contain the real IP
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // X-Forwarded-For can contain multiple IPs, get the first one
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

/**
 * Check if IP is localhost
 * 
 * @param string $ip The IP address to check
 * @return bool True if IP is localhost
 */
function isLocalhost($ip) {
    $localhostPatterns = [
        '127.0.0.1',
        '::1',
        'localhost',
        '::ffff:127.0.0.1'
    ];
    
    return in_array($ip, $localhostPatterns);
}

/**
 * Check if IP matches a CIDR pattern
 * 
 * @param string $ip The IP address to check
 * @param string $cidr The CIDR pattern (e.g., "192.168.1.0/24" or "192.168.1.100")
 * @return bool True if IP matches the CIDR pattern
 */
function ipMatchesCIDR($ip, $cidr) {
    // Handle simple IP without CIDR
    if (strpos($cidr, '/') === false) {
        return $ip === $cidr;
    }
    
    list($subnet, $mask) = explode('/', $cidr);
    
    // Convert IP addresses to long integers
    $ipLong = ip2long($ip);
    $subnetLong = ip2long($subnet);
    
    if ($ipLong === false || $subnetLong === false) {
        return false;
    }
    
    // Create mask
    $maskLong = -1 << (32 - (int)$mask);
    
    // Compare network addresses
    return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
}

/**
 * Check if IP is in whitelist
 * 
 * @param string $ip The IP address to check
 * @param string|null $whitelistFile Optional custom whitelist file path
 * @return bool True if IP is whitelisted
 */
function isIPWhitelisted($ip, $whitelistFile = null) {
    // Always allow localhost
    if (isLocalhost($ip)) {
        return true;
    }
    
    // Use default whitelist file if not specified
    if ($whitelistFile === null) {
        $whitelistFile = __DIR__ . '/ipAllowed.txt';
    }
    
    // Check if whitelist file exists
    if (!file_exists($whitelistFile)) {
        return false;
    }
    
    // Read whitelist
    $allowedIPs = file($whitelistFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($allowedIPs as $allowedIP) {
        $allowedIP = trim($allowedIP);
        
        // Skip empty lines and comments
        if (empty($allowedIP) || strpos($allowedIP, '#') === 0) {
            continue;
        }
        
        // Check exact match or CIDR match
        if ($allowedIP === $ip || ipMatchesCIDR($ip, $allowedIP)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Validate if a string is a valid IP address
 * 
 * @param string $ip The string to validate
 * @return bool True if valid IP address
 */
function isValidIP($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP) !== false;
}

/**
 * Get IP address type (IPv4 or IPv6)
 * 
 * @param string $ip The IP address
 * @return string 'ipv4', 'ipv6', or 'invalid'
 */
function getIPType($ip) {
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return 'ipv4';
    } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        return 'ipv6';
    } else {
        return 'invalid';
    }
}


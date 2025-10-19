<?php
/**
 * Remember Me Token Management System
 * 
 * Provides secure "remember me" functionality using random tokens
 * Tokens are stored separately from credentials for security
 */

/**
 * Generate a secure random token
 */
function generateRememberToken() {
    return bin2hex(random_bytes(32)); // 64 character hex string
}

/**
 * Get device fingerprint for additional security
 */
function getDeviceFingerprint() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
    
    // Create a hash of device characteristics
    return hash('sha256', $userAgent . $acceptLanguage . $acceptEncoding);
}

/**
 * Store a remember-me token
 * 
 * @param string $username The username to associate with the token
 * @param string $token The token to store
 * @param int $expiryDays Number of days until token expires (default 90)
 * @return bool Success status
 */
function storeRememberToken($username, $token, $expiryDays = 90) {
    $tokensFile = __DIR__ . '/remember_tokens.txt';
    
    // Token format: token|username|created_date|expiry_date|device_fingerprint|last_used
    $created = time();
    $expiry = $created + ($expiryDays * 24 * 60 * 60);
    $deviceFingerprint = getDeviceFingerprint();
    
    $tokenLine = sprintf(
        "%s|%s|%d|%d|%s|%d\n",
        hash('sha256', $token), // Store hashed version
        $username,
        $created,
        $expiry,
        $deviceFingerprint,
        $created // last_used same as created initially
    );
    
    // Append to tokens file
    $result = file_put_contents($tokensFile, $tokenLine, FILE_APPEND | LOCK_EX);
    
    if ($result !== false) {
        // Set file permissions
        @chmod($tokensFile, 0600);
        return true;
    }
    
    return false;
}

/**
 * Validate a remember-me token
 * 
 * @param string $token The token to validate
 * @return array|false Returns user data if valid, false otherwise
 */
function validateRememberToken($token) {
    $tokensFile = __DIR__ . '/remember_tokens.txt';
    
    if (!file_exists($tokensFile)) {
        return false;
    }
    
    $tokenHash = hash('sha256', $token);
    $deviceFingerprint = getDeviceFingerprint();
    $currentTime = time();
    
    // Read all tokens
    $tokens = file($tokensFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($tokens === false) {
        return false;
    }
    
    $validTokens = [];
    $tokenFound = false;
    $userData = false;
    
    foreach ($tokens as $line) {
        $parts = explode('|', $line);
        
        if (count($parts) !== 6) {
            continue; // Skip malformed lines
        }
        
        list($storedHash, $username, $created, $expiry, $storedFingerprint, $lastUsed) = $parts;
        
        // Remove expired tokens
        if ($currentTime > $expiry) {
            continue; // Don't add to validTokens (effectively deletes it)
        }
        
        // Check if this is the token we're looking for
        if ($storedHash === $tokenHash) {
            // Validate device fingerprint
            if ($storedFingerprint === $deviceFingerprint) {
                $tokenFound = true;
                $userData = [
                    'username' => $username,
                    'created' => $created,
                    'expiry' => $expiry
                ];
                
                // Update last_used timestamp
                $line = sprintf(
                    "%s|%s|%d|%d|%s|%d",
                    $storedHash,
                    $username,
                    $created,
                    $expiry,
                    $storedFingerprint,
                    $currentTime
                );
            }
        }
        
        $validTokens[] = $line;
    }
    
    // Write back valid tokens (removing expired ones)
    if (!empty($validTokens)) {
        file_put_contents($tokensFile, implode("\n", $validTokens) . "\n", LOCK_EX);
    } else {
        // All tokens expired, remove file
        @unlink($tokensFile);
    }
    
    return $userData;
}

/**
 * Revoke a specific token
 * 
 * @param string $token The token to revoke
 * @return bool Success status
 */
function revokeRememberToken($token) {
    $tokensFile = __DIR__ . '/remember_tokens.txt';
    
    if (!file_exists($tokensFile)) {
        return false;
    }
    
    $tokenHash = hash('sha256', $token);
    
    // Read all tokens
    $tokens = file($tokensFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($tokens === false) {
        return false;
    }
    
    $validTokens = [];
    $tokenFound = false;
    
    foreach ($tokens as $line) {
        $parts = explode('|', $line);
        
        if (count($parts) !== 6) {
            $validTokens[] = $line; // Keep malformed lines
            continue;
        }
        
        list($storedHash, $username, $created, $expiry, $storedFingerprint, $lastUsed) = $parts;
        
        // Skip the token we want to revoke
        if ($storedHash === $tokenHash) {
            $tokenFound = true;
            continue;
        }
        
        $validTokens[] = $line;
    }
    
    // Write back tokens without the revoked one
    if (!empty($validTokens)) {
        file_put_contents($tokensFile, implode("\n", $validTokens) . "\n", LOCK_EX);
    } else {
        // No tokens left, remove file
        @unlink($tokensFile);
    }
    
    return $tokenFound;
}

/**
 * Revoke all tokens for a specific user
 * 
 * @param string $username The username whose tokens to revoke
 * @return int Number of tokens revoked
 */
function revokeAllUserTokens($username) {
    $tokensFile = __DIR__ . '/remember_tokens.txt';
    
    if (!file_exists($tokensFile)) {
        return 0;
    }
    
    // Read all tokens
    $tokens = file($tokensFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($tokens === false) {
        return 0;
    }
    
    $validTokens = [];
    $revokedCount = 0;
    
    foreach ($tokens as $line) {
        $parts = explode('|', $line);
        
        if (count($parts) !== 6) {
            $validTokens[] = $line; // Keep malformed lines
            continue;
        }
        
        list($storedHash, $tokenUsername, $created, $expiry, $storedFingerprint, $lastUsed) = $parts;
        
        // Skip tokens for this user
        if ($tokenUsername === $username) {
            $revokedCount++;
            continue;
        }
        
        $validTokens[] = $line;
    }
    
    // Write back tokens without the user's tokens
    if (!empty($validTokens)) {
        file_put_contents($tokensFile, implode("\n", $validTokens) . "\n", LOCK_EX);
    } else {
        // No tokens left, remove file
        @unlink($tokensFile);
    }
    
    return $revokedCount;
}

/**
 * Get all active tokens for a user
 * 
 * @param string $username The username to get tokens for
 * @return array Array of token information
 */
function getUserTokens($username) {
    $tokensFile = __DIR__ . '/remember_tokens.txt';
    
    if (!file_exists($tokensFile)) {
        return [];
    }
    
    $tokens = file($tokensFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($tokens === false) {
        return [];
    }
    
    $userTokens = [];
    $currentTime = time();
    
    foreach ($tokens as $line) {
        $parts = explode('|', $line);
        
        if (count($parts) !== 6) {
            continue;
        }
        
        list($storedHash, $tokenUsername, $created, $expiry, $storedFingerprint, $lastUsed) = $parts;
        
        // Only include non-expired tokens for this user
        if ($tokenUsername === $username && $currentTime <= $expiry) {
            $userTokens[] = [
                'token_hash' => $storedHash,
                'created' => (int)$created,
                'expiry' => (int)$expiry,
                'last_used' => (int)$lastUsed,
                'device_fingerprint' => $storedFingerprint
            ];
        }
    }
    
    return $userTokens;
}

/**
 * Clean up expired tokens
 * 
 * @return int Number of tokens removed
 */
function cleanupExpiredTokens() {
    $tokensFile = __DIR__ . '/remember_tokens.txt';
    
    if (!file_exists($tokensFile)) {
        return 0;
    }
    
    $tokens = file($tokensFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($tokens === false) {
        return 0;
    }
    
    $validTokens = [];
    $removedCount = 0;
    $currentTime = time();
    
    foreach ($tokens as $line) {
        $parts = explode('|', $line);
        
        if (count($parts) !== 6) {
            $validTokens[] = $line;
            continue;
        }
        
        list($storedHash, $username, $created, $expiry, $storedFingerprint, $lastUsed) = $parts;
        
        // Remove expired tokens
        if ($currentTime > $expiry) {
            $removedCount++;
            continue;
        }
        
        $validTokens[] = $line;
    }
    
    // Write back valid tokens
    if (!empty($validTokens)) {
        file_put_contents($tokensFile, implode("\n", $validTokens) . "\n", LOCK_EX);
    } else {
        @unlink($tokensFile);
    }
    
    return $removedCount;
}
?>


# IP Functions Library

## Overview

This is a **shared library** for IP address detection and validation used throughout the application. It eliminates code duplication and provides a single source of truth for IP-related functionality.

## Purpose

Before this refactoring, IP checking code was duplicated in 4 locations:
1. ❌ `login/auth_check.php`
2. ❌ `sync_db/api.php`
3. ❌ `sync_db/get_ip.php`
4. ❌ `database_syncing/sync_api.php`

Now, all IP functionality is centralized in:
- ✅ `login/ip_functions.php` (single source of truth)

## Functions

### `getClientIP()`
Gets the client's real IP address, handling various proxy configurations.

```php
$ip = getClientIP();
// Returns: "81.204.237.36"
```

**Headers checked (in order):**
1. `HTTP_CLIENT_IP`
2. `HTTP_X_FORWARDED_FOR` (first IP if multiple)
3. `HTTP_X_FORWARDED`
4. `HTTP_FORWARDED_FOR`
5. `HTTP_FORWARDED`
6. `REMOTE_ADDR` (fallback)

### `isLocalhost($ip)`
Checks if an IP address is localhost.

```php
isLocalhost('127.0.0.1');  // true
isLocalhost('::1');         // true
isLocalhost('192.168.1.1'); // false
```

**Recognized localhost patterns:**
- `127.0.0.1` (IPv4)
- `::1` (IPv6)
- `localhost` (hostname)
- `::ffff:127.0.0.1` (IPv4-mapped IPv6)

### `ipMatchesCIDR($ip, $cidr)`
Checks if an IP matches a CIDR pattern.

```php
ipMatchesCIDR('192.168.1.100', '192.168.1.0/24');  // true
ipMatchesCIDR('192.168.2.100', '192.168.1.0/24');  // false
ipMatchesCIDR('192.168.1.100', '192.168.1.100');   // true (exact match)
```

**Supports:**
- Exact IP matching: `192.168.1.100`
- CIDR notation: `192.168.1.0/24`
- IPv4 only (IPv6 support could be added if needed)

### `isIPWhitelisted($ip, $whitelistFile = null)`
Checks if an IP is in the whitelist file.

```php
// Uses default ipAllowed.txt
isIPWhitelisted('81.204.237.36');  // true/false

// Use custom whitelist file
isIPWhitelisted('81.204.237.36', '/path/to/custom_whitelist.txt');
```

**Features:**
- Always allows localhost
- Reads from `login/ipAllowed.txt` by default
- Supports comments (lines starting with `#`)
- Supports CIDR notation
- Ignores empty lines

### `isValidIP($ip)`
Validates if a string is a valid IP address.

```php
isValidIP('192.168.1.1');    // true
isValidIP('2001:db8::1');    // true (IPv6)
isValidIP('not-an-ip');      // false
```

### `getIPType($ip)`
Returns the IP address type.

```php
getIPType('192.168.1.1');    // 'ipv4'
getIPType('2001:db8::1');    // 'ipv6'
getIPType('invalid');        // 'invalid'
```

## Usage

### In Your Code

```php
// Include the library
require_once __DIR__ . '/../login/ip_functions.php';

// Get client IP
$clientIP = getClientIP();

// Check if whitelisted
if (isIPWhitelisted($clientIP)) {
    // Allow access
} else {
    // Deny access
}
```

### Example: Custom Whitelist Location

```php
require_once 'login/ip_functions.php';

$customWhitelist = '/path/to/api_whitelist.txt';
$clientIP = getClientIP();

if (isIPWhitelisted($clientIP, $customWhitelist)) {
    // Allow API access
}
```

## Files Using This Library

| File | Purpose |
|------|---------|
| `login/auth_check.php` | Main authentication/authorization |
| `sync_db/api.php` | Database sync API endpoint |
| `sync_db/get_ip.php` | IP detection API |
| (Future files) | Any new code needing IP checks |

## Whitelist File Format

`login/ipAllowed.txt`:
```
# This is a comment
127.0.0.1

# Office network
192.168.1.0/24

# Specific remote server
81.204.237.36

# Another server with CIDR
203.0.113.0/25
```

## Benefits of Centralization

1. ✅ **Single Source of Truth** - One implementation to maintain
2. ✅ **Consistency** - Same behavior across all components
3. ✅ **Easy Updates** - Change once, applies everywhere
4. ✅ **Testing** - Test IP logic in one place
5. ✅ **Documentation** - One file to document
6. ✅ **No Duplication** - DRY principle

## Backward Compatibility

All existing code continues to work without changes because:
- Function names remain the same
- Function signatures remain the same
- Default behavior is identical
- No breaking changes

## Future Enhancements

Potential additions to this library:
- [ ] IPv6 CIDR matching
- [ ] IP range matching (e.g., `192.168.1.100-192.168.1.200`)
- [ ] Geographic IP lookup
- [ ] Rate limiting by IP
- [ ] IP blacklist support
- [ ] Caching for performance

## Testing

To test the library:

```php
require_once 'login/ip_functions.php';

// Test IP detection
echo "Your IP: " . getClientIP() . "\n";

// Test localhost detection
var_dump(isLocalhost('127.0.0.1'));  // bool(true)

// Test CIDR matching
var_dump(ipMatchesCIDR('192.168.1.100', '192.168.1.0/24'));  // bool(true)

// Test whitelist
var_dump(isIPWhitelisted(getClientIP()));  // bool(true/false)
```

## Migration Notes

If you have custom code using IP functions:

**Before (duplicated):**
```php
function getClientIP() {
    // ... duplicate code ...
}
```

**After (shared library):**
```php
require_once 'login/ip_functions.php';
// Use getClientIP() from shared library
```

## Support

For issues or questions about IP functionality:
1. Check this README
2. Review `login/ipAllowed.txt` format
3. Test with `sync_db/check_ip.php` tool
4. Check logs for IP-related errors

## Version History

- **v1.0** (2024-10-21) - Initial extraction and centralization
  - Extracted from auth_check.php, api.php, get_ip.php
  - Added documentation
  - Added helper functions (isValidIP, getIPType)


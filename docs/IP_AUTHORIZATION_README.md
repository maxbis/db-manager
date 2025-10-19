# IP Authorization System

## Overview

The Database Manager now includes IP-based access control to restrict access to authorized IP addresses only.

## How It Works

1. **Localhost Access**: All requests from localhost (127.0.0.1, ::1) are automatically allowed without checking the whitelist.

2. **Whitelist Check**: For non-localhost requests, the IP address is checked against the `ipAllowed.txt` file.

3. **Access Denied**: If the IP is not whitelisted, users see an access denied page with their IP address displayed.

## Configuration

### ipAllowed.txt Format

The `ipAllowed.txt` file contains a list of allowed IP addresses, one per line. The file supports:

- **Single IP addresses**: `192.168.1.100`
- **CIDR notation**: `192.168.1.0/24` (allows entire subnet)
- **Comments**: Lines starting with `#` are ignored
- **Empty lines**: Ignored

### Example ipAllowed.txt

```
# Office network
192.168.1.100/24

# Specific developer IP
81.204.237.36/24

# VPN server
10.0.0.50
```

## Adding IP Addresses

To allow access from a new IP address:

1. Open `ipAllowed.txt` in a text editor
2. Add the IP address on a new line
3. Save the file
4. Access is granted immediately (no restart required)

## CIDR Notation Examples

- `/32` - Single IP address (e.g., `192.168.1.1/32`)
- `/24` - 256 IP addresses (e.g., `192.168.1.0/24` allows 192.168.1.0-255)
- `/16` - 65,536 IP addresses (e.g., `192.168.0.0/16` allows 192.168.0.0-255.255)
- `/8` - 16,777,216 IP addresses (e.g., `10.0.0.0/8` allows 10.0.0.0-255.255.255)

## Protected Pages

All main entry points are protected:
- ✅ `index.php` - Main data manager
- ✅ `api.php` - API backend
- ✅ `query.php` - SQL query builder
- ✅ `table_structure.php` - Table structure viewer
- ✅ `database_manager.php` - Database manager
- ✅ `setup_saved_queries.php` - Setup script

## Security Features

1. **Automatic Localhost Detection**: Localhost access is always allowed for development
2. **Proxy Support**: Correctly detects client IP behind proxies (X-Forwarded-For headers)
3. **CIDR Support**: Flexible subnet whitelisting
4. **No Bypass**: All pages check authorization before any processing

## Access Denied Page

When access is denied, users see a professional error page showing:
- 🚫 Access denied icon
- Their IP address
- Instructions to contact the administrator
- Note that localhost access doesn't require whitelisting

## Testing

### Test Localhost Access
1. Visit any page from `http://localhost/` or `http://127.0.0.1/`
2. Access should be granted automatically

### Test Whitelist
1. Add your current IP to `ipAllowed.txt`
2. Access the application from a remote location
3. Access should be granted

### Test Denial
1. Remove your IP from `ipAllowed.txt`
2. Try accessing from a non-localhost IP
3. Should see "no access for ip number: X.X.X.X"

## Finding Your IP Address

To find the IP address that needs to be whitelisted:

1. Try accessing the application
2. The access denied page will show your IP address
3. Add that IP address to `ipAllowed.txt`

Alternatively:
- Visit https://whatismyipaddress.com/
- Or run: `curl ifconfig.me` from command line

## Troubleshooting

### "No access" even on localhost
- Make sure you're accessing via `localhost` or `127.0.0.1`, not your machine's external IP
- Check if `auth_check.php` exists in the application directory

### Access denied with whitelisted IP
- Verify the IP in `ipAllowed.txt` matches exactly
- Check for typos in the IP address
- Ensure the IP address doesn't have leading/trailing spaces
- If using CIDR, verify the subnet mask is correct
- Check if you're behind a proxy (IP might be different than expected)

### ipAllowed.txt not found
- Create the file in the same directory as other PHP files
- Add at least one IP address or CIDR range
- Ensure the file has read permissions for the web server

## Disabling IP Check (Not Recommended)

If you need to temporarily disable IP checking for development:

1. Edit `auth_check.php`
2. Comment out the final check:
```php
// Perform the authorization check
// if (!checkAuthorization()) {
//     displayAccessDenied(getClientIP());
// }
```

**⚠️ Warning**: Never deploy to production with IP checking disabled!

## Best Practices

1. **Use CIDR for offices**: Instead of individual IPs, use CIDR notation for office networks
2. **Document IPs**: Use comments in `ipAllowed.txt` to note what each IP/range is for
3. **Regular Review**: Periodically review and remove outdated IP addresses
4. **Backup**: Keep a backup of `ipAllowed.txt` in a secure location
5. **VPN Access**: Consider using a VPN for consistent IP addresses
6. **Monitor Access**: Check server logs for denied access attempts

## Technical Details

### IP Detection Priority

The system checks for the client IP in this order:
1. `HTTP_CLIENT_IP`
2. `HTTP_X_FORWARDED_FOR` (first IP if multiple)
3. `HTTP_X_FORWARDED`
4. `HTTP_FORWARDED_FOR`
5. `HTTP_FORWARDED`
6. `REMOTE_ADDR` (default)

### Localhost Patterns

These patterns are recognized as localhost:
- `127.0.0.1` (IPv4 localhost)
- `::1` (IPv6 localhost)
- `localhost` (hostname)
- `::ffff:127.0.0.1` (IPv6-mapped IPv4 localhost)

## Support

For issues or questions about IP authorization:
1. Check the access denied page for your current IP
2. Verify `ipAllowed.txt` configuration
3. Check PHP error logs for issues
4. Contact your system administrator


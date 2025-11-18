# Protecting Credentials from Direct Browser Access

## Overview

This document explains how sensitive files (especially `credentials.txt`) are protected from direct HTTP access while still allowing PHP scripts to read them.

## Problem

By default, files in a web directory can be accessed directly via HTTP:
```
❌ http://localhost/db-manager/login/credentials.txt
```

This exposes sensitive information including:
- Database usernames and passwords (plain text)
- User login credentials (hashed, but still sensitive)
- IP whitelist information

## Solution: Multi-Layer Protection

The system implements multiple layers of protection:

### Layer 1: .htaccess Files (Primary Protection)

**Files Created:**
- `login/.htaccess` - Protects files in login directory
- `.htaccess` (root) - Additional application-wide protection

**What it does:**
- Blocks direct HTTP access to `.txt` files
- Specifically protects `credentials.txt`, `remember_tokens.txt`, `ipAllowed.txt`
- **Does NOT block PHP file operations** - `file_get_contents()` still works

**How it works:**
```apache
# Apache 2.2 syntax
<FilesMatch "\.txt$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Apache 2.4+ syntax
<IfModule mod_authz_core.c>
    <FilesMatch "\.txt$">
        Require all denied
    </FilesMatch>
</IfModule>
```

### Layer 2: File Permissions

Set restrictive permissions on sensitive files:
```bash
chmod 600 login/credentials.txt
chmod 600 login/remember_tokens.txt
chmod 600 login/ipAllowed.txt
```

This ensures only the file owner (web server user) can read/write.

### Layer 3: PHP Protection (Fallback)

If `.htaccess` doesn't work (Nginx, disabled mod_rewrite, etc.):

**Option A: Move files outside web root**
```
/var/www/
  ├── db-manager/          (web root - accessible via HTTP)
  └── secure/              (outside web root - NOT accessible via HTTP)
      └── credentials.txt
```

**Option B: Use PHP protection file**
The file `login/deny_direct_access.php` provides additional checks.

### Layer 4: Web Server Configuration

For Nginx, add to server block:
```nginx
location ~ \.(txt|log|bak)$ {
    deny all;
    return 403;
}
```

## Testing Protection

### Test 1: Direct HTTP Access (Should Fail)

**Using browser:**
```
http://localhost/db-manager/login/credentials.txt
```
Expected: **403 Forbidden** or **404 Not Found**

**Using curl:**
```bash
curl http://localhost/db-manager/login/credentials.txt
```
Expected: **403 Forbidden**

**Using wget:**
```bash
wget http://localhost/db-manager/login/credentials.txt
```
Expected: **403 Forbidden**

### Test 2: PHP Access (Should Work)

The login system should work normally:
- `login/login.php` - Can read credentials.txt
- `login/setup.php` - Can read/write credentials.txt
- `login/auth_check.php` - Can read credentials.txt

This is because PHP uses `file_get_contents()`, not HTTP requests.

### Test 3: File Permissions

```bash
ls -l login/credentials.txt
```
Expected output:
```
-rw------- 1 www-data www-data 1234 Jan 15 10:00 credentials.txt
```
The `-rw-------` means:
- Owner: read/write (6)
- Group: no access (0)
- Others: no access (0)
- Total: 600 permissions

## Verification Checklist

- [ ] `.htaccess` files exist in `login/` and root directory
- [ ] Direct HTTP access to `credentials.txt` returns 403
- [ ] Login system works normally (PHP can read file)
- [ ] File permissions are set to 600
- [ ] File is in `.gitignore` (not committed to version control)

## Troubleshooting

### Problem: Still can access credentials.txt via browser

**Possible causes:**
1. Apache `.htaccess` not enabled
2. Using Nginx (needs different configuration)
3. File permissions too open

**Solutions:**
1. Check Apache `AllowOverride` setting:
   ```apache
   AllowOverride All
   ```
2. For Nginx, use server block configuration (see Layer 4 above)
3. Set file permissions: `chmod 600 login/credentials.txt`

### Problem: Login system can't read credentials.txt

**Possible causes:**
1. File permissions too restrictive
2. Wrong file path
3. Web server user doesn't have access

**Solutions:**
1. Check file owner: `ls -l login/credentials.txt`
2. Set correct owner: `chown www-data:www-data login/credentials.txt`
3. Verify file path in PHP code

### Problem: .htaccess not working

**Check:**
1. Apache `mod_rewrite` enabled?
2. `AllowOverride` set to `All`?
3. `.htaccess` file syntax correct?

**Test:**
```bash
# Check if mod_rewrite is loaded
apache2ctl -M | grep rewrite

# Check AllowOverride in httpd.conf or apache2.conf
grep -i AllowOverride /etc/apache2/apache2.conf
```

## Best Practices

1. **Always use .htaccess** - Primary protection layer
2. **Set file permissions to 600** - Restrictive permissions
3. **Keep files out of version control** - Use `.gitignore`
4. **Regular security audits** - Test protection periodically
5. **Monitor access logs** - Watch for 403 errors (attack attempts)
6. **Use HTTPS in production** - Encrypt data in transit
7. **Consider moving outside web root** - Most secure option

## Additional Security Measures

### 1. Move Files Outside Web Root (Most Secure)

```
/var/www/
  ├── db-manager/              (web root)
  │   └── login/
  │       └── login.php
  └── /var/secure/db-manager/   (outside web root)
      └── credentials.txt
```

Update PHP code:
```php
$credentialsFile = '/var/secure/db-manager/credentials.txt';
```

### 2. Encrypt Database Passwords

Consider encrypting database passwords before storing:
```php
// Store encrypted
$encrypted = openssl_encrypt($dbPassword, 'AES-256-CBC', $key);

// Decrypt when needed
$decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key);
```

### 3. Use Environment Variables

For production, consider using environment variables:
```php
$dbUser = getenv('DB_USER');
$dbPass = getenv('DB_PASS');
```

## Summary

✅ **Protection Implemented:**
- `.htaccess` files block direct HTTP access
- File permissions set to 600
- Files excluded from version control
- PHP can still read files programmatically

✅ **Result:**
- Direct browser access: **BLOCKED** ❌
- PHP file operations: **ALLOWED** ✅
- Login system: **WORKS** ✅

---

**Last Updated**: 2025-01-15
**Status**: ✅ Protection Active


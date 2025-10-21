# Database Sync Tool - API Method Guide

## Overview

The Database Sync tool allows developers to copy a remote (production) database to their local environment for testing and development purposes using a **secure API-based method**.

This method works even when your production server doesn't allow direct MySQL connections (common with shared hosting and phpMyAdmin-only access).

## Files Created

### Local Files (Your Dev Machine)
- **`database_syncing/db_sync.php`** - Main user interface page
- **`database_syncing/sync_api.php`** - Local API that communicates with remote
- **`database_syncing/sync_config.php`** - Configuration file for API settings
- **`database_syncing/sync_remote_api.php`** - Remote API (upload to production)
- **`database_syncing/README.md`** - Folder documentation
- **`docs/DB_SYNC_GUIDE.md`** - This documentation

### Production Files (Upload to Production Server)
- **`database_syncing/sync_remote_api.php`** - Upload this to production root
- **`config/ipAllowed.txt`** - IP whitelist for security

## How It Works

```
┌─────────────┐                  ┌──────────────────┐
│   Your PC   │                  │  Production      │
│             │   HTTPS/API      │  Server          │
│  db_sync.   ├─────────────────>│  sync_remote_    │
│  php        │  (Authenticated) │  api.php         │
│             │<─────────────────│                  │
│             │   JSON Response  │  ├─> MySQL       │
└─────────────┘                  └──────────────────┘

1. You configure API URL + Secret Key
2. Local page calls remote API with authentication
3. Remote API validates IP, Key, Token
4. Remote API exports database via MySQL
5. Data sent back as JSON
6. Local page imports to local MySQL
```

---

## 🚀 Quick Setup Guide

### Step 1: Configure Production Server

#### 1.1 Upload Remote API File

Upload `database_syncing/sync_remote_api.php` from your local machine to your production server root (e.g., in your website root or a subdirectory).

**Example:**
```
https://yoursite.com/sync_remote_api.php
```

#### 1.2 Configure Remote API

Edit `sync_remote_api.php` on production and set:

```php
// SECURITY LAYER 1: Enable API
define('API_ENABLED', true); // ← Set to TRUE only when syncing!

// SECURITY LAYER 2: Secret Key
define('SECRET_KEY', 'your-super-secret-random-key-here-minimum-32-chars');

// SECURITY LAYER 3: Database Credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'your_production_db_user');
define('DB_PASS', 'your_production_db_password');
define('DB_NAME', 'your_production_db_name');
```

**Generate a secure key:**
```bash
# On Linux/Mac:
openssl rand -base64 32

# Or use: https://www.random.org/strings/
```

#### 1.3 Setup IP Whitelist

Create `config/ipAllowed.txt` on production server and add your local development IP:

```
# config/ipAllowed.txt
123.45.67.89        # Your office IP
98.76.54.32         # Your home IP
10.0.0.0/24         # Your company network (CIDR notation)
```

**How to find your IP:**
- Visit: https://whatismyipaddress.com/
- Or run: `curl ifconfig.me`

---

### Step 2: Configure Local Development

#### 2.1 Update sync_config.php (Optional)

Edit `database_syncing/sync_config.php` on your local machine:

```php
return [
    'remote_api_url' => 'https://yoursite.com/sync_remote_api.php',
    'api_secret_key' => 'your-super-secret-random-key-here-minimum-32-chars',
    // ... other settings
];
```

> **Note:** You can also enter these in the UI instead of config file

---

### Step 3: Use the Sync Tool

#### 3.1 Access the Page

Navigate to:
```
http://localhost/db-manager/database_syncing/db_sync.php
```

#### 3.2 Enter API Details

**Remote Database (API):**
- **API URL:** `https://yoursite.com/sync_remote_api.php`
- **API Secret Key:** Your secret key (matches production)
- **Database Name:** Production database name (optional)

**Local Database:**
- **Target Database:** Name for local database
- **Options:**
  - ✅ Drop existing tables (recommended)
  - ✅ Create database if it doesn't exist
  - ✅ Sync data (uncheck for structure-only)

#### 3.3 Test Connection

Click **"Test Connection"** to verify:
- API is accessible
- Secret key is correct
- Your IP is whitelisted
- API is enabled

#### 3.4 Load Tables

Click **"Load Tables"** to see available tables from production.

Select which tables to sync (or select all).

#### 3.5 Start Sync

Click **"Start Sync"** and confirm.

Watch the progress bar and logs for real-time updates!

---

## 🔒 Security Features

### Multi-Layer Security

The API implements **6 layers of security**:

#### Layer 1: Manual Kill Switch ⭐⭐⭐⭐⭐
```php
define('API_ENABLED', false); // API disabled by default
```
- Set to `true` only when syncing
- Set back to `false` after sync
- Or delete the file completely

#### Layer 2: Secret API Key ⭐⭐⭐⭐⭐
```php
define('SECRET_KEY', 'random-32-char-string');
```
- Long random string (minimum 32 characters)
- Must match between production and local
- Changed regularly for best security

#### Layer 3: IP Whitelist ⭐⭐⭐⭐⭐
```
# config/ipAllowed.txt
123.45.67.89
```
- Only whitelisted IPs can access
- Supports CIDR notation (e.g., 10.0.0.0/24)
- Rejects all other requests immediately

#### Layer 4: Time-Based Tokens ⭐⭐⭐⭐
```php
// Token changes every 5 minutes
$token = hash('sha256', SECRET_KEY . floor(time() / 300));
```
- Prevents replay attacks
- Auto-expires after 5 minutes
- Generated client-side, validated server-side

#### Layer 5: Rate Limiting ⭐⭐⭐
```php
define('MAX_REQUESTS_PER_MINUTE', 10);
```
- Limits requests per IP per minute
- Prevents brute force attacks
- Prevents DDoS

#### Layer 6: HTTPS (Optional but Recommended) ⭐⭐⭐⭐⭐
```php
// Uncomment in sync_remote_api.php to enforce:
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    jsonError('HTTPS required');
}
```
- Encrypts all data in transit
- Protects API key and database contents

---

## 📊 Security Comparison

| Security Layer | Status | Can Disable? | Recommendation |
|----------------|--------|--------------|----------------|
| Kill Switch | ✅ Active | Yes | Always enable only when syncing |
| API Key | ✅ Active | No | Use 32+ char random string |
| IP Whitelist | ✅ Active | No | Add only trusted IPs |
| Time Tokens | ✅ Active | No | Auto-managed |
| Rate Limiting | ✅ Active | Yes | Keep enabled |
| HTTPS | ⚠️ Optional | Yes | **Highly recommended** |
| Audit Logging | ✅ Active | Yes | Review regularly |

---

## 🛡️ Best Security Practices

### DO:
✅ Enable API only when syncing  
✅ Use HTTPS for production  
✅ Use strong random API keys (32+ chars)  
✅ Whitelist only your dev machine IP  
✅ Review audit logs regularly  
✅ Delete API file after sync (or keep disabled)  
✅ Change API key regularly  

### DON'T:
❌ Leave API_ENABLED = true permanently  
❌ Use weak or predictable API keys  
❌ Share API keys in public repos  
❌ Whitelist 0.0.0.0/0 (entire internet)  
❌ Ignore security warnings  
❌ Use HTTP for sensitive data  

---

## 📝 Audit Logging

Every API access attempt is logged:

**Location:** `sync_audit.log` on production server

**Example log entries:**
```
[2025-10-21 11:30:15] IP: 123.45.67.89 | Event: ACCESS_GRANTED | All security checks passed
[2025-10-21 11:30:20] IP: 123.45.67.89 | Event: ACCESS_GRANTED | All security checks passed
[2025-10-21 11:45:33] IP: 98.76.54.32 | Event: IP_BLOCKED | Unauthorized IP: 98.76.54.32
[2025-10-21 12:05:12] IP: 123.45.67.89 | Event: INVALID_KEY | Invalid API key provided
```

**Review regularly to detect:**
- Unauthorized access attempts
- Failed authentication
- Suspicious patterns

---

## 🔧 Configuration Options

### Remote API Configuration

Edit `sync_remote_api.php`:

```php
// Enable/Disable API
define('API_ENABLED', false);

// Secret key (32+ characters)
define('SECRET_KEY', 'your-secret-key');

// IP whitelist file
define('IP_WHITELIST_FILE', __DIR__ . '/config/ipAllowed.txt');

// Token validity (seconds)
define('TOKEN_VALIDITY_SECONDS', 300);

// Rate limit (requests per minute)
define('MAX_REQUESTS_PER_MINUTE', 10);

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'db_user');
define('DB_PASS', 'db_password');
define('DB_NAME', 'db_name');
```

### Local Configuration

Edit `sync_config.php`:

```php
return [
    'remote_api_url' => 'https://yoursite.com/sync_remote_api.php',
    'api_secret_key' => 'matches-production-key',
    'token_validity' => 300,
    'timeout' => 60,
    'batch_size' => 1000
];
```

---

## 🐛 Troubleshooting

### "Access denied: IP not authorized"

**Problem:** Your IP isn't in the whitelist

**Solution:**
1. Check your current IP: https://whatismyipaddress.com/
2. Add it to `config/ipAllowed.txt` on production
3. Make sure file exists and is readable

### "Invalid API key"

**Problem:** API key mismatch

**Solution:**
1. Verify SECRET_KEY in `sync_remote_api.php`
2. Verify you're using the same key in UI
3. Keys are case-sensitive - copy/paste exactly

### "Invalid or expired token"

**Problem:** Time-based token issue

**Solution:**
1. Check server time on production (clock drift)
2. Wait 5 minutes and try again
3. Ensure TOKEN_VALIDITY_SECONDS matches on both sides

### "API is currently disabled"

**Problem:** Kill switch is active

**Solution:**
1. Edit `sync_remote_api.php` on production
2. Set `API_ENABLED` to `true`
3. Remember to set back to `false` after sync

### "Connection timed out"

**Problem:** Can't reach production server

**Solution:**
1. Verify API URL is correct
2. Check production server is online
3. Verify firewall allows HTTPS/HTTP
4. Try accessing URL directly in browser

### "cURL error"

**Problem:** PHP cURL not available or SSL issue

**Solution:**
1. Enable cURL in PHP: `extension=curl`
2. For SSL errors, verify HTTPS certificate is valid
3. For self-signed certs, see code comment about CURLOPT_SSL_VERIFYPEER

### "Rate limit exceeded"

**Problem:** Too many requests

**Solution:**
1. Wait 60 seconds
2. Increase MAX_REQUESTS_PER_MINUTE in config
3. Check for infinite loops in code

---

## 📈 Performance Tips

### For Large Databases:

1. **Selective Sync**
   - Only sync tables you need
   - Use "structure only" for large reference tables

2. **Batch Size**
   - Default: 1000 rows per request
   - Reduce if timing out: `'batch_size' => 500`
   - Increase for faster sync: `'batch_size' => 5000`

3. **Timeout Settings**
   - Increase PHP `max_execution_time` if needed
   - Increase cURL timeout in sync_api.php

4. **Network Speed**
   - Faster internet = faster sync
   - Use compression if available

### Estimated Sync Times:

| Database Size | Tables | Rows | Time (Approx) |
|--------------|--------|------|---------------|
| Small | 10 | 1,000 | < 1 minute |
| Medium | 50 | 50,000 | 2-5 minutes |
| Large | 100 | 500,000 | 10-30 minutes |
| Very Large | 200+ | 1,000,000+ | 30+ minutes |

---

## 🎯 After Sync Checklist

After completing a sync:

- [ ] Set `API_ENABLED = false` in sync_remote_api.php
- [ ] Or delete sync_remote_api.php from production
- [ ] Review audit log for suspicious activity
- [ ] Test local database works correctly
- [ ] Clear sensitive data if needed (passwords, emails, etc.)

---

## 🔐 Production Deployment Checklist

Before deploying to production:

**Required:**
- [ ] API_ENABLED set to false (enable only when needed)
- [ ] SECRET_KEY changed from default
- [ ] Database credentials configured
- [ ] config/ipAllowed.txt created with your IP
- [ ] File uploaded to production server

**Recommended:**
- [ ] HTTPS enforced (uncomment HTTPS check)
- [ ] Audit logging enabled
- [ ] Rate limiting configured
- [ ] Regular audit log reviews scheduled

**Optional:**
- [ ] Additional authentication layers
- [ ] Webhook notifications on access
- [ ] Two-factor authentication

---

## 🆚 Method Comparison

| Feature | Direct MySQL | API Method | SSH Tunnel |
|---------|-------------|------------|------------|
| **Security** | ⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Works with phpMyAdmin-only** | ❌ | ✅ | ✅ |
| **Firewall-friendly** | ❌ | ✅ | ✅ |
| **Speed** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Setup Complexity** | Easy | Medium | Hard |
| **No Server Changes** | ✅ | ❌ (Upload 1 file) | ❌ (Need SSH) |
| **Production Ready** | ❌ | ✅ | ✅ |

---

## 📚 Additional Resources

- **Security Best Practices:** `docs/SECURITY_RECOMMENDATIONS.md`
- **Authentication Guide:** `docs/AUTHENTICATION_GUIDE.md`
- **IP Whitelisting:** `docs/IP_AUTHORIZATION_README.md`

---

## 🆘 Support

If you encounter issues:

1. **Check the logs** in browser console and sync_audit.log
2. **Verify all security layers** are configured correctly
3. **Test API directly** by accessing URL in browser
4. **Review this guide** for troubleshooting steps
5. **Check PHP error logs** on both local and production

---

**Created:** October 2025  
**Version:** 2.0 (API Method)  
**Status:** Production Ready  
**Security Level:** ⭐⭐⭐⭐⭐ (5/5 with all layers enabled)

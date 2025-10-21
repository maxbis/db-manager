# Database Sync - Quick Reference

## 📁 Files Created

### For Local Development (Your PC)
```
database_syncing/
  ├─ db_sync.php               # Main UI - Access via browser
  ├─ sync_api.php              # Local API - Calls remote API
  ├─ sync_config.php           # Configuration (optional)
  ├─ sync_remote_api.php       # Remote API (upload this to production)
  └─ README.md                 # Folder documentation

docs/
  ├─ DB_SYNC_GUIDE.md          # Full documentation
  └─ DB_SYNC_README.md         # This file

SYNC_SETUP_INSTRUCTIONS.txt    # Production setup guide
```

### For Production Server (Upload These)
```
sync_remote_api.php            # Upload from database_syncing/ folder
config/ipAllowed.txt          # IP whitelist for security
```

---

## 🚀 Quick Start (3 Steps)

### 1️⃣ Upload to Production

Upload `sync_remote_api.php` to your production server:
```
https://yoursite.com/sync_remote_api.php
```

### 2️⃣ Configure Production

Edit `sync_remote_api.php` on production:

```php
define('API_ENABLED', true);                    // Enable API
define('SECRET_KEY', 'random-32-char-key');     // Set secret key
define('DB_USER', 'prod_db_user');              // Your DB user
define('DB_PASS', 'prod_db_password');          // Your DB password
define('DB_NAME', 'prod_database');             // Your DB name
```

Create `config/ipAllowed.txt` with your IP:
```
123.45.67.89
```

### 3️⃣ Use Sync Tool

1. Open: `http://localhost/db-manager/database_syncing/db_sync.php`
2. Enter API URL and Secret Key
3. Click "Test Connection"
4. Click "Load Tables"
5. Select tables to sync
6. Click "Start Sync"

---

## 🔒 Security Layers

| Layer | What It Does | Status |
|-------|-------------|--------|
| 🔴 Kill Switch | API disabled by default | ✅ Active |
| 🔑 API Key | Secret key authentication | ✅ Active |
| 🌐 IP Whitelist | Only allowed IPs can access | ✅ Active |
| ⏰ Time Tokens | Auto-expiring tokens (5 min) | ✅ Active |
| 🚦 Rate Limiting | Max 10 requests/minute | ✅ Active |
| 🔐 HTTPS | Encrypted connection | ⚠️ Optional |

---

## ⚠️ Important Security Notes

**Before Sync:**
- Set `API_ENABLED = true` in production file
- Add your IP to whitelist
- Use HTTPS if possible

**After Sync:**
- Set `API_ENABLED = false` 
- Or delete `sync_remote_api.php` completely
- Review `sync_audit.log` for suspicious activity

---

## 🐛 Common Issues

| Error | Fix |
|-------|-----|
| "API is currently disabled" | Set `API_ENABLED = true` |
| "IP not authorized" | Add your IP to `config/ipAllowed.txt` |
| "Invalid API key" | Check `SECRET_KEY` matches |
| "Connection failed" | Verify URL is correct |

---

## 📖 Full Documentation

For complete guide: [DB_SYNC_GUIDE.md](DB_SYNC_GUIDE.md)

For setup instructions: [SYNC_SETUP_INSTRUCTIONS.txt](../SYNC_SETUP_INSTRUCTIONS.txt)

---

## ✅ Works With

- ✅ Shared hosting (no direct MySQL access)
- ✅ phpMyAdmin-only environments
- ✅ Firewall-restricted servers
- ✅ Any hosting with PHP + MySQL
- ✅ Cloud databases
- ✅ VPS/Dedicated servers

---

## 🎯 Why API Method?

Most production servers block direct MySQL connections for security.

**Before (Direct MySQL):**
```
Your PC ─────X────→ Production MySQL (Port 3306 blocked)
```

**After (API Method):**
```
Your PC ────HTTP────→ Production API ────→ Production MySQL ✓
                      (Port 80/443 open)
```

---

## 💡 Pro Tips

1. **Keep API disabled** when not syncing
2. **Use strong secret keys** (32+ characters)
3. **Sync only needed tables** (faster)
4. **Use HTTPS** for production (encrypts data)
5. **Review audit logs** regularly
6. **Update IP whitelist** if your IP changes

---

**Version:** 2.0 (API Method)  
**Security:** ⭐⭐⭐⭐⭐ (5/5)  
**Status:** Production Ready


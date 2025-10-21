# Database Syncing - Remote to Local

This directory contains all files related to the Database Sync feature, which allows you to copy a remote (production) database to your local development environment using a secure API method.

## 📁 Files in This Directory

### Local Files (Use on your dev machine)

| File | Purpose | Access |
|------|---------|--------|
| **`db_sync.php`** | Main user interface | `http://localhost/db-manager/database_syncing/db_sync.php` |
| **`sync_api.php`** | Local API backend | Called by db_sync.php (AJAX) |
| **`sync_config.php`** | Configuration (optional) | Included by sync_api.php |
| **`README.md`** | This file | Documentation |

### Production Files (Upload to production server)

| File | Upload To | Configure |
|------|-----------|-----------|
| **`sync_remote_api.php`** | Production root (e.g., `yoursite.com/sync_remote_api.php`) | ✅ Required |

---

## 🚀 Quick Start

### For Developers (Local Setup)

Access the sync tool at:
```
http://localhost/db-manager/database_syncing/db_sync.php
```

Or create a shortcut link from the main app.

### For Production Setup

1. Upload `sync_remote_api.php` to your production server
2. Configure the file (API key, DB credentials)
3. Create `config/ipAllowed.txt` with your local IP
4. Use the tool to sync databases

---

## 📚 Documentation

Full documentation is in the main `docs/` folder:

- **[Complete Guide](../docs/DB_SYNC_GUIDE.md)** - Full setup and usage guide
- **[Quick Reference](../docs/DB_SYNC_README.md)** - Quick reference card  
- **[Setup Instructions](../SYNC_SETUP_INSTRUCTIONS.txt)** - Production checklist
- **[Summary](../REFACTORED_API_METHOD_SUMMARY.md)** - Overview and features

---

## 🔐 Security

This feature uses **6 layers of security**:

1. ✅ Kill Switch (API disabled by default)
2. ✅ Secret API Key (32+ character authentication)
3. ✅ IP Whitelist (uses `config/ipAllowed.txt`)
4. ✅ Time-Based Tokens (auto-expire every 5 minutes)
5. ✅ Rate Limiting (max 10 requests/minute)
6. ✅ Audit Logging (tracks all access attempts)

**Always disable the API or delete the production file after syncing!**

---

## 🎯 Why API Method?

Most production servers block direct MySQL connections for security. This API method:

- ✅ Works through HTTP/HTTPS (ports 80/443)
- ✅ Works with phpMyAdmin-only hosting
- ✅ Firewall-friendly
- ✅ No need to expose MySQL port 3306
- ✅ Multi-layer security
- ✅ Works with any hosting (shared, VPS, cloud)

---

## 📖 Need Help?

See the full documentation in `docs/DB_SYNC_GUIDE.md` for:
- Complete setup instructions
- Security best practices
- Troubleshooting guide
- Performance tips
- FAQs

---

## ⚡ Quick Commands

**Access the tool:**
```
http://localhost/db-manager/database_syncing/db_sync.php
```

**Test if production API is working:**
```
https://yoursite.com/sync_remote_api.php?action=ping
```

**Check your local IP:**
```
https://whatismyipaddress.com/
```

**Generate secure API key:**
```bash
openssl rand -base64 32
```

---

**Version:** 2.0  
**Method:** Secure API-based synchronization  
**Security Level:** ⭐⭐⭐⭐⭐ (5/5)


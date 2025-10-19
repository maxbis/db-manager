# 🚀 Quick Start - Authentication System

## ⚡ 3-Step Setup

### 1️⃣ Create Credentials (Localhost Only)
```
http://localhost/db-manager/setup.php
```
- Enter username (min 3 chars)
- Enter password (min 8 chars)
- Click "Create Credentials"

### 2️⃣ Login
```
http://your-server/db-manager/
```
- Automatically redirects to login
- Enter your credentials
- Start working!

### 3️⃣ Logout
Click **"🚪 Logout"** button in top-right corner

---

## 🔒 Security Features

✅ **IP Whitelist** - Only authorized IPs  
✅ **Login Required** - Username/password  
✅ **Password Hashing** - Bcrypt encryption  
✅ **CSRF Protection** - Token validation  
✅ **Session Timeout** - 30 min inactivity  
✅ **Account Lockout** - 5 failed = 30 min lock  
✅ **Hijacking Prevention** - IP/UA validation  

---

## 📋 Files Created

| File | Purpose |
|------|---------|
| `credentials.txt` | Stores hashed password (auto-created) |
| `setup.php` | Create credentials (localhost only) |
| `login.php` | Login form |
| `logout.php` | Logout handler |
| `session_check.php` | Session validation |
| `auth_check.php` | Combined IP + Login check (updated) |

---

## 🎯 Two-Layer Security

```
Request → IP Check → Login Check → Access Granted ✅
            ↓             ↓
       Denied if:    Denied if:
       - Not in      - Not logged in
         ipAllowed   - Session expired
       - Not         - Session hijacked
         localhost
```

---

## 🐛 Common Issues

### "Access Denied" on setup.php
**Solution**: Use http://localhost/ or http://127.0.0.1/

### Login Loop
**Solution**: 
1. Check credentials.txt exists
2. Verify your IP is in ipAllowed.txt
3. Clear browser cookies

### Account Locked
**Solution**: Wait 30 minutes OR recreate via setup.php

### Session Expires Fast
**Solution**: Edit `$sessionTimeout` in session_check.php

---

## ⚙️ Configuration

### Change Session Timeout
```php
// session_check.php
$sessionTimeout = 1800; // 30 min (default)
$sessionTimeout = 3600; // 1 hour
```

### Change Lockout Settings
```php
// login.php
if ($failedAttempts >= 5) {  // Number of attempts
    $lockedUntil = date('Y-m-d H:i:s', time() + 1800); // Lock duration
}
```

---

## 📝 Forgot Password?

1. Access from localhost: `http://localhost/db-manager/setup.php`
2. Create new credentials (overwrites old)
3. Login with new password

---

## 🎨 Add Logout to Other Pages

Copy this code from `index.php` to your other pages:

```php
<?php if (isset($_SESSION['username'])): ?>
<div class="control-group" style="margin-left: auto;">
    <span style="font-size: 12px; color: var(--color-text-tertiary);">
        👤 <?php echo htmlspecialchars($_SESSION['username']); ?>
    </span>
    <a href="logout.php" style="...">🚪 Logout</a>
</div>
<?php endif; ?>
```

---

## 📖 Full Documentation

See **AUTHENTICATION_GUIDE.md** for complete details:
- Security features explained
- Troubleshooting guide
- Best practices
- Customization options
- Upgrade paths

---

## ✅ Checklist

- [ ] Run setup.php from localhost
- [ ] Create username & password
- [ ] Test login
- [ ] Test logout
- [ ] Test session timeout
- [ ] Add credentials.txt to .gitignore
- [ ] Backup credentials.txt
- [ ] Test remote access

---

**Everything working? You're all set!** 🎉

Need help? Check **AUTHENTICATION_GUIDE.md**


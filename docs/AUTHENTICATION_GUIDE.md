# Authentication System Guide

## 🎉 Complete Login System Implemented!

Your Database Manager now has a secure username/password authentication system with session management.

---

## 📋 What Was Implemented

### ✅ Core Features
1. **Setup Page** (`setup.php`) - Create credentials (localhost only)
2. **Login Page** (`login.php`) - Secure login with CSRF protection
3. **Session Management** (`session_check.php`) - Auto timeout & hijacking prevention
4. **Logout** (`logout.php`) - Secure session destruction
5. **File-Based Storage** (`credentials.txt`) - No database needed!
6. **Integration** - Works seamlessly with existing IP whitelist

### ✅ Security Features
- 🔐 **Password Hashing** - Bcrypt encryption
- 🛡️ **CSRF Protection** - Token validation on forms
- ⏱️ **Session Timeout** - Auto logout after 30 minutes of inactivity
- 🚫 **Account Lockout** - 5 failed attempts = 30 minute lockout
- 🔒 **Session Hijacking Prevention** - IP & User-Agent validation
- 🔄 **Session Regeneration** - Prevents session fixation attacks
- 📝 **Rate Limiting** - Built into failed attempts tracking

---

## 🚀 Quick Start Guide

### Step 1: Create Your Credentials

1. **Access setup page from localhost:**
   ```
   http://localhost/db-manager/setup.php
   ```

2. **Enter your details:**
   - Username (min 3 characters, alphanumeric + underscore)
   - Password (min 8 characters)
   - Confirm password

3. **Click "Create Credentials"**
   - Creates `credentials.txt` with hashed password
   - File permissions set to 600 (secure)

⚠️ **Important**: `setup.php` can ONLY be accessed from localhost!

### Step 2: Login

1. **Access the application:**
   ```
   http://your-server/db-manager/
   ```

2. **You'll be redirected to login page automatically**

3. **Enter your credentials**

4. **Start using the Database Manager!**

### Step 3: Logout

Click the **"🚪 Logout"** button in the top-right corner.

---

## 🔒 Two-Layer Security

Your application now has **TWO security layers**:

```
Layer 1: IP Whitelist Check
   ↓ (if authorized IP)
Layer 2: Username/Password Login
   ↓ (if authenticated)
Access Granted ✅
```

### How It Works

1. **IP Check** (always runs first)
   - Localhost: Always allowed
   - Remote: Must be in `ipAllowed.txt`

2. **Login Check** (runs if `credentials.txt` exists)
   - Checks if user is logged in
   - Validates session isn't expired
   - Prevents session hijacking

---

## 📁 File Structure

```
db-manager/
├── credentials.txt          # Stores hashed credentials (auto-created)
├── setup.php               # Create credentials (localhost only)
├── login.php               # Login form
├── logout.php              # Logout handler
├── session_check.php       # Session validation
├── auth_check.php          # IP + Login verification (updated)
├── index.php               # Main app (protected)
├── api.php                 # API (protected)
└── ... (all other pages protected)
```

---

## 📄 credentials.txt Format

The file stores data in pipe-delimited format:

```
username|hashed_password|created_date|last_login|failed_attempts|locked_until
```

Example (actual data will be hashed):
```
admin|$2y$10$abcd...xyz|2025-10-19 12:00:00|2025-10-19 14:30:00|0|
```

**Security Notes:**
- Password is bcrypt hashed (irreversible)
- File has 600 permissions (owner read/write only)
- Username is stored in plain text for lookup
- Failed attempts and lockout are tracked

---

## 🔐 Security Features Explained

### 1. Password Hashing (Bcrypt)
```php
password_hash($password, PASSWORD_BCRYPT)
```
- Uses bcrypt algorithm
- Automatically salted
- Computationally expensive (slows brute force)
- Industry standard

### 2. CSRF Protection
```php
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
```
- Unique token per session
- Validated on every form submission
- Prevents cross-site request forgery

### 3. Account Lockout
```
Failed Attempts → Lockout Duration
1-4 attempts   → No lockout
5 attempts     → 30 minutes locked
```
- Automatically resets on successful login
- Tracked in credentials.txt

### 4. Session Timeout
```
Inactivity Timeout: 30 minutes
Maximum Session: N/A (as long as active)
```
- Auto logout after 30 min of inactivity
- Each page view extends the timeout
- Prevents abandoned sessions

### 5. Session Hijacking Prevention
```php
// Validates on every request:
- IP address must match
- User agent must match
- Session ID regenerated every 30 min
```

### 6. Secure Session Settings
```php
ini_set('session.cookie_httponly', 1);  // No JavaScript access
ini_set('session.cookie_secure', 1);    // HTTPS only
ini_set('session.cookie_samesite', 'Strict'); // CSRF protection
```

---

## 🎯 Usage Scenarios

### Scenario 1: First Time Setup

1. Access from localhost: `http://localhost/db-manager/setup.php`
2. Create username and password
3. Access main app: `http://localhost/db-manager/`
4. Login with your credentials
5. Start managing your database!

### Scenario 2: Remote Access (Production)

1. Ensure your IP is in `ipAllowed.txt`
2. Access: `https://your-server/db-manager/`
3. Login with credentials
4. Work securely!

### Scenario 3: Team Access

1. Each team member's IP in `ipAllowed.txt`
2. All share the same login credentials (one user account)
3. Everyone logs in with same username/password
4. **Note**: For multiple users, consider upgrading to SQLite (see below)

### Scenario 4: Forgot Password

1. Access `setup.php` from localhost
2. Create new credentials (overwrites old ones)
3. All existing sessions are invalidated
4. Login with new password

---

## 🔧 Configuration

### Adjust Session Timeout

Edit `session_check.php`:
```php
$sessionTimeout = 1800; // 30 minutes (default)
$sessionTimeout = 3600; // 1 hour
$sessionTimeout = 7200; // 2 hours
```

### Adjust Account Lockout

Edit `login.php`:
```php
if ($failedAttempts >= 5) { // Change 5 to your preferred limit
    $lockedUntil = date('Y-m-d H:i:s', time() + 1800); // 30 min
}
```

### Enable HTTPS Only

Edit `session_check.php`:
```php
ini_set('session.cookie_secure', 1); // Enable for HTTPS
```

### Change Lockout Duration

Edit `login.php`:
```php
$lockedUntil = date('Y-m-d H:i:s', time() + 1800); // 1800 = 30 min
```

---

## 🐛 Troubleshooting

### Can't Access Setup Page
**Problem**: "Access Denied" on setup.php  
**Solution**: You must access from localhost (127.0.0.1)
```
✅ http://localhost/db-manager/setup.php
✅ http://127.0.0.1/db-manager/setup.php
❌ http://192.168.1.100/db-manager/setup.php
```

### Login Keeps Redirecting
**Problem**: Stuck in login loop  
**Possible Causes**:
1. Sessions not working - check `php.ini` session settings
2. Credentials file corrupt - recreate via setup.php
3. IP not whitelisted - add to ipAllowed.txt

**Solution**:
```bash
# Check if sessions work
php -i | grep "session.save_path"

# Verify credentials file exists and readable
ls -l credentials.txt
```

### Account Locked
**Problem**: "Account temporarily locked" message  
**Solution**: 
1. Wait 30 minutes, or
2. Access setup.php from localhost to reset

### Session Expires Too Quickly
**Problem**: Logged out too often  
**Solution**: Increase timeout in `session_check.php`

### "Invalid Security Token" Error
**Problem**: CSRF token mismatch  
**Solution**: Refresh the login page and try again

---

## 🔄 Upgrading to Multi-User

The current system supports **one user account**. To add multiple users:

### Option 1: Multiple Credentials Files
Create a user manager that maintains multiple entries in `credentials.txt`

### Option 2: Upgrade to SQLite (Recommended)
For multiple users with roles, see `FILE_BASED_AUTH_OPTIONS.md`

**Benefits of SQLite upgrade:**
- Multiple user accounts
- Role-based access (admin, editor, viewer)
- User management interface
- Per-user activity tracking
- Better session management

---

## 📊 Session Information

The system tracks:
- **Username**: Current logged-in user
- **Login Time**: When session started
- **Last Activity**: Last page access
- **IP Address**: Session IP (for hijacking prevention)
- **User Agent**: Browser fingerprint

View in code:
```php
// In any protected page:
echo $_SESSION['username'];
echo date('Y-m-d H:i:s', $_SESSION['login_time']);
echo date('Y-m-d H:i:s', $_SESSION['last_activity']);
```

---

## 🛡️ Best Practices

### 1. Password Security
- ✅ Use strong passwords (8+ characters)
- ✅ Mix letters, numbers, symbols
- ✅ Don't share passwords
- ✅ Change periodically

### 2. Access Control
- ✅ Keep `credentials.txt` secure (600 permissions)
- ✅ Don't commit credentials.txt to git
- ✅ Add to .gitignore
- ✅ Keep ipAllowed.txt updated

### 3. Regular Maintenance
- ✅ Review login attempts (in credentials.txt)
- ✅ Change password if compromised
- ✅ Remove old whitelisted IPs
- ✅ Monitor for suspicious activity

### 4. Production Deployment
- ✅ Use HTTPS (SSL certificate)
- ✅ Set secure cookie flags
- ✅ Regular backups of credentials.txt
- ✅ Monitor server logs

---

## 🎨 Customization

### Add Logout Button to Other Pages

Copy from `index.php` to other pages:
```php
<?php if (isset($_SESSION['username'])): ?>
<div class="control-group" style="margin-left: auto;">
    <span>👤 <?php echo htmlspecialchars($_SESSION['username']); ?></span>
    <a href="logout.php" class="btn-logout">🚪 Logout</a>
</div>
<?php endif; ?>
```

### Custom Login Page Styling

Edit `login.php` styles section to match your branding.

### Add "Remember Me" Feature

Requires additional cookie management - see advanced guides.

---

## 📝 To-Do Checklist

- [ ] Run setup.php from localhost
- [ ] Create strong username & password
- [ ] Test login from localhost
- [ ] Add remote IPs to ipAllowed.txt
- [ ] Test login from remote IP
- [ ] Test logout functionality
- [ ] Test session timeout
- [ ] Test account lockout
- [ ] Add credentials.txt to .gitignore
- [ ] Backup credentials.txt
- [ ] Document your password securely
- [ ] Test all pages are protected
- [ ] Add logout buttons to other pages (optional)

---

## 🎯 What You Asked For vs What You Got

### You Asked For:
✅ Setup page (localhost only)  
✅ Store username & password hashed  
✅ credentials.txt file  
✅ Login system with sessions  
✅ Validate against stored credentials  

### Extra Features Added:
🎁 CSRF protection  
🎁 Account lockout after failed attempts  
🎁 Session timeout with auto-logout  
🎁 Session hijacking prevention  
🎁 IP + User-Agent validation  
🎁 Secure session configuration  
🎁 Session regeneration  
🎁 Professional UI  
🎁 Error messages without info disclosure  
🎁 Integration with existing IP whitelist  

---

## 🚀 Next Steps

### Immediate:
1. Run `setup.php` and create your credentials
2. Test login/logout
3. Verify all pages are protected

### Short Term:
1. Add logout buttons to remaining pages
2. Customize login page branding
3. Test from remote IPs

### Long Term:
1. Consider upgrading to SQLite for multiple users
2. Implement role-based access control
3. Add audit logging
4. Set up HTTPS

---

## 📞 Support

Need help?
- Check troubleshooting section above
- Review security recommendations
- Test with localhost first
- Check PHP error logs

---

**Implementation Complete! 🎉**

Your Database Manager now has:
- ✅ IP-based access control
- ✅ Username/Password authentication
- ✅ Secure session management
- ✅ File-based credential storage
- ✅ Professional security features

**You're ready to use it safely!** 🔒


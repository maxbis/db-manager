# Login System and Authorization Guide

## Table of Contents

1. [Overview](#overview)
2. [Two-Layer Security System](#two-layer-security-system)
3. [Login System](#login-system)
4. [User Authorization](#user-authorization)
5. [Database Authorization](#database-authorization)
6. [Setup and Configuration](#setup-and-configuration)
7. [Security Features](#security-features)
8. [Troubleshooting](#troubleshooting)

---

## Overview

The Database Manager uses a comprehensive two-layer security system that combines IP-based access control with username/password authentication. Each user can have their own database credentials, allowing fine-grained control over database access.

### Key Components

- **IP Whitelist**: First layer of defense - restricts access by IP address
- **Login System**: Second layer - requires username/password authentication
- **Session Management**: Secure session handling with timeout and hijacking prevention
- **Database Credentials**: Per-user database connection credentials stored securely
- **Remember Me**: Optional persistent login tokens

---

## Two-Layer Security System

The application uses a two-layer security approach:

```
┌─────────────────────────────────────┐
│   Layer 1: IP Authorization         │
│   - Check if IP is whitelisted      │
│   - Localhost always allowed        │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│   Layer 2: Login Authentication     │
│   - Username/Password required      │
│   - Session management              │
│   - Database credentials loaded     │
└──────────────┬──────────────────────┘
               │
               ▼
        Access Granted ✅
```

### Layer 1: IP Authorization

**Purpose**: Restrict access based on IP address before any authentication occurs.

**How it works**:
1. Client IP address is detected (with proxy support)
2. If IP is localhost (127.0.0.1, ::1), access is automatically granted
3. For other IPs, the system checks `login/ipAllowed.txt`
4. If IP is not whitelisted, access is denied immediately

**Configuration File**: `login/ipAllowed.txt`

**Example**:
```
# Office network
192.168.1.0/24

# Specific developer IP
81.204.237.36

# VPN server
10.0.0.50/32
```

**CIDR Notation Support**:
- `/32` - Single IP address
- `/24` - 256 IP addresses (entire subnet)
- `/16` - 65,536 IP addresses
- `/8` - 16,777,216 IP addresses

### Layer 2: Login Authentication

**Purpose**: Verify user identity and load database credentials.

**How it works**:
1. If `login/credentials.txt` exists, login is required
2. User must provide username and password
3. Credentials are verified against stored hashed passwords
4. On success, session is created with user data and database credentials
5. Database credentials are loaded from the credentials file into the session

**Configuration File**: `login/credentials.txt`

---

## Login System

### Components

#### 1. Setup Page (`login/setup.php`)

**Purpose**: Create or add user credentials (localhost only).

**Access**: Only accessible from localhost (127.0.0.1)

**Features**:
- Create username and password
- Set database credentials (username, password, host)
- Support for multiple users
- Password hashing with bcrypt
- Secure file permissions (600)

**Usage**:
1. Access from localhost: `http://localhost/db-manager/login/setup.php`
2. Fill in the form:
   - Username (min 3 chars, alphanumeric + underscore)
   - Password (min 8 chars)
   - Database username (required)
   - Database password (optional)
   - Database host (defaults to localhost)
3. Click "Create User" or "Add User"

**File Format**: `login/credentials.txt`
```
username|hashed_password|created_date|last_login|failed_attempts|locked_until|db_user|db_pass|db_host
```

**Example**:
```
admin|$2y$10$abcd...xyz|2025-01-15 10:00:00|2025-01-15 14:30:00|0||dbadmin|dbpass123|localhost
user1|$2y$10$efgh...uvw|2025-01-15 11:00:00|2025-01-15 12:00:00|0||dbuser1|pass456|localhost
```

#### 2. Login Page (`login/login.php`)

**Purpose**: Authenticate users and create sessions.

**Features**:
- CSRF protection
- Account lockout after 5 failed attempts (30 minutes)
- Rate limiting
- Remember me functionality (90 days)
- Secure session creation
- Database credentials loading

**Security Features**:
- Password verification using `password_verify()`
- Session regeneration on login (prevents session fixation)
- Failed attempt tracking
- Account lockout mechanism

**Session Data Stored**:
```php
$_SESSION['authenticated'] = true;
$_SESSION['username'] = 'username';
$_SESSION['login_time'] = timestamp;
$_SESSION['last_activity'] = timestamp;
$_SESSION['user_ip'] = 'IP address';
$_SESSION['user_agent'] = 'User agent';
$_SESSION['db_user'] = 'database username';
$_SESSION['db_pass'] = 'database password';
$_SESSION['db_host'] = 'database host';
```

#### 3. Session Management (`session_check.php`)

**Purpose**: Validate sessions on protected pages.

**Features**:
- Session timeout (30 minutes of inactivity)
- Session hijacking prevention (IP and User-Agent validation)
- Automatic session regeneration (every 30 minutes)
- Secure cookie settings

**Usage**: Include at the top of protected pages:
```php
require_once 'session_check.php';
```

**Session Timeout**: 30 minutes of inactivity (configurable)

**Security Checks**:
- IP address must match login IP
- User-Agent must match login User-Agent
- Session must not be expired

#### 4. Logout (`login/logout.php`)

**Purpose**: Securely destroy sessions and tokens.

**Features**:
- Revokes remember-me tokens
- Clears session data
- Destroys session cookie
- Redirects to login page

#### 5. Remember Me Tokens (`login/remember_tokens.php`)

**Purpose**: Enable persistent login across browser sessions.

**Features**:
- Secure token generation (64-character hex string)
- Device fingerprinting
- Token expiration (90 days default)
- Automatic cleanup of expired tokens

**Token Storage**: `login/remember_tokens.txt`

**Format**:
```
hashed_token|username|created_timestamp|expiry_timestamp|device_fingerprint|last_used_timestamp
```

---

## User Authorization

### How Users Are Authorized

The authorization process follows these steps:

1. **IP Check** (`login/auth_check.php`)
   - Gets client IP address
   - Checks if localhost → allow
   - Checks if IP in whitelist → allow
   - If not authorized → show access denied page

2. **Login Check** (if credentials file exists)
   - Checks if user has active session
   - If no session, checks for remember-me token
   - If valid token, auto-login
   - If no valid token, redirect to login page

3. **Session Validation** (`session_check.php`)
   - Verifies session is authenticated
   - Checks session timeout
   - Validates IP and User-Agent
   - Updates last activity time

### Authorization Flow Diagram

```
User Request
    │
    ▼
┌─────────────────────────┐
│  IP Authorization       │
│  (auth_check.php)       │
└───────────┬─────────────┘
            │
            ├─ Localhost? ──YES──► Allow
            │
            ├─ Whitelisted? ──YES──► Continue
            │
            └─ NO ──► Access Denied ❌
                      │
                      ▼
            ┌─────────────────────────┐
            │  Login Check            │
            │  (if credentials exist) │
            └───────────┬─────────────┘
                        │
                        ├─ Has Session? ──YES──► Validate Session
                        │
                        ├─ Has Token? ──YES──► Auto-Login
                        │
                        └─ NO ──► Redirect to Login
                                   │
                                   ▼
                        ┌─────────────────────────┐
                        │  Session Validation     │
                        │  (session_check.php)   │
                        └───────────┬─────────────┘
                                    │
                                    ├─ Valid? ──YES──► Access Granted ✅
                                    │
                                    └─ NO ──► Redirect to Login
```

### Adding New Users

**Method 1: Using Setup Page** (Recommended)
1. Access `login/setup.php` from localhost
2. Fill in user details and database credentials
3. Click "Add User"

**Method 2: Manual Entry**
1. Edit `login/credentials.txt`
2. Add a new line with format:
   ```
   username|hashed_password|created|last_login|failed_attempts|locked_until|db_user|db_pass|db_host
   ```
3. Generate password hash:
   ```php
   echo password_hash('your_password', PASSWORD_BCRYPT);
   ```

### Removing Users

1. Edit `login/credentials.txt`
2. Remove the line for the user
3. Optionally revoke their remember-me tokens:
   ```php
   require_once 'login/remember_tokens.php';
   revokeAllUserTokens('username');
   ```

### Resetting Passwords

1. Access `login/setup.php` from localhost
2. Create a new user with the same username (overwrites existing)
3. Or manually edit `login/credentials.txt` and update the password hash

---

## Database Authorization

### How Database Credentials Work

Each user in the system can have their own database credentials. When a user logs in, their database credentials are loaded into the session and used for all database connections.

#### Where Database Credentials Are Stored

**1. Permanent Storage: `login/credentials.txt`**

The database username and password are stored permanently in the credentials file alongside the login credentials.

- **File Location**: `login/credentials.txt`
- **Format**: Pipe-delimited, one user per line
  ```
  username|hashed_login_password|created|last_login|failed_attempts|locked_until|db_user|db_pass|db_host
  ```
- **Fields 7-9 contain database credentials**:
  - Field 7: `db_user` - Database username
  - Field 8: `db_pass` - Database password (stored in **plain text**)
  - Field 9: `db_host` - Database host (defaults to `localhost`)

**Example**:
```
admin|$2y$10$abcd...xyz|2025-01-15 10:00:00|2025-01-15 14:30:00|0||dbadmin|dbpass123|localhost
```

**⚠️ Important Security Note**: 
- The database password is stored in **plain text** in `credentials.txt`
- The file has restricted permissions (600) to protect it
- Keep this file secure and never commit it to version control
- **Direct browser access is blocked** via `.htaccess` protection (see below)

**2. Session Storage (Temporary)**

After successful login, database credentials are loaded into PHP session variables:

```php
$_SESSION['db_user']  // Database username
$_SESSION['db_pass']  // Database password
$_SESSION['db_host']  // Database host (default: localhost)
```

- These session variables are used for all database connections during the active session
- They are cleared when the user logs out
- They are loaded from `credentials.txt` on login

**How It Works**:
1. User logs in with username/password
2. System reads `login/credentials.txt` to find matching user
3. Extracts database credentials (fields 7-9) from the file
4. Stores them in session variables: `$_SESSION['db_user']`, `$_SESSION['db_pass']`, `$_SESSION['db_host']`
5. All database operations use these session variables for connection

### Setting Up Database Users in MySQL

To authorize users on a MySQL database, you need to create database users and grant them appropriate permissions.

#### 1. Connect to MySQL as Root

```bash
mysql -u root -p
```

#### 2. Create a Database User

```sql
CREATE USER 'dbadmin'@'localhost' IDENTIFIED BY 'secure_password';
```

**For remote access**:
```sql
CREATE USER 'dbadmin'@'%' IDENTIFIED BY 'secure_password';
```

**For specific IP**:
```sql
CREATE USER 'dbadmin'@'192.168.1.100' IDENTIFIED BY 'secure_password';
```

#### 3. Grant Permissions

**Full access to all databases**:
```sql
GRANT ALL PRIVILEGES ON *.* TO 'dbadmin'@'localhost' WITH GRANT OPTION;
```

**Full access to specific database**:
```sql
GRANT ALL PRIVILEGES ON database_name.* TO 'dbadmin'@'localhost';
```

**Read-only access to specific database**:
```sql
GRANT SELECT ON database_name.* TO 'dbadmin'@'localhost';
```

**Specific permissions**:
```sql
GRANT SELECT, INSERT, UPDATE, DELETE ON database_name.* TO 'dbadmin'@'localhost';
```

**Common Permission Sets**:
- `SELECT` - Read data
- `INSERT` - Add new records
- `UPDATE` - Modify existing records
- `DELETE` - Remove records
- `CREATE` - Create tables
- `DROP` - Delete tables
- `ALTER` - Modify table structure
- `INDEX` - Create indexes
- `ALL PRIVILEGES` - Full access

#### 4. Apply Changes

```sql
FLUSH PRIVILEGES;
```

#### 5. Verify User Permissions

```sql
SHOW GRANTS FOR 'dbadmin'@'localhost';
```

### Example: Setting Up Multiple Database Users

**Scenario**: You want different users with different access levels.

```sql
-- Admin user with full access
CREATE USER 'dbadmin'@'localhost' IDENTIFIED BY 'admin_pass123';
GRANT ALL PRIVILEGES ON *.* TO 'dbadmin'@'localhost';

-- Editor user with read/write access to specific database
CREATE USER 'dbeditor'@'localhost' IDENTIFIED BY 'editor_pass123';
GRANT SELECT, INSERT, UPDATE, DELETE ON myapp_db.* TO 'dbeditor'@'localhost';

-- Viewer user with read-only access
CREATE USER 'dbviewer'@'localhost' IDENTIFIED BY 'viewer_pass123';
GRANT SELECT ON myapp_db.* TO 'dbviewer'@'localhost';

-- Apply changes
FLUSH PRIVILEGES;
```

Then in `login/setup.php`, create users with corresponding database credentials:
- User "admin" → db_user: `dbadmin`, db_pass: `admin_pass123`
- User "editor" → db_user: `dbeditor`, db_pass: `editor_pass123`
- User "viewer" → db_user: `dbviewer`, db_pass: `viewer_pass123`

### Revoking Database Access

**Remove specific permissions**:
```sql
REVOKE DELETE ON database_name.* FROM 'dbadmin'@'localhost';
FLUSH PRIVILEGES;
```

**Remove all permissions**:
```sql
REVOKE ALL PRIVILEGES ON *.* FROM 'dbadmin'@'localhost';
FLUSH PRIVILEGES;
```

**Delete user**:
```sql
DROP USER 'dbadmin'@'localhost';
```

### Best Practices for Database Authorization

1. **Principle of Least Privilege**
   - Grant only necessary permissions
   - Use read-only users when possible
   - Separate admin and regular user accounts

2. **Strong Passwords**
   - Use complex passwords for database users
   - Change passwords regularly
   - Don't reuse passwords

3. **Host Restrictions**
   - Use `localhost` when possible
   - Specify IP addresses instead of `%` when needed
   - Avoid wildcard hosts (`%`) in production

4. **Regular Audits**
   - Review user permissions periodically
   - Remove unused accounts
   - Monitor database access logs

5. **Separate Accounts**
   - Use different database users for different applications
   - Don't share database credentials between users
   - Create role-based accounts (admin, editor, viewer)

### Common Database Authorization Scenarios

#### Scenario 1: Single User, Full Access
```sql
CREATE USER 'appuser'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON mydatabase.* TO 'appuser'@'localhost';
FLUSH PRIVILEGES;
```

#### Scenario 2: Multiple Users, Different Access Levels
```sql
-- Admin
CREATE USER 'admin'@'localhost' IDENTIFIED BY 'admin_pass';
GRANT ALL PRIVILEGES ON *.* TO 'admin'@'localhost';

-- Editor
CREATE USER 'editor'@'localhost' IDENTIFIED BY 'editor_pass';
GRANT SELECT, INSERT, UPDATE, DELETE ON mydb.* TO 'editor'@'localhost';

-- Viewer
CREATE USER 'viewer'@'localhost' IDENTIFIED BY 'viewer_pass';
GRANT SELECT ON mydb.* TO 'viewer'@'localhost';

FLUSH PRIVILEGES;
```

#### Scenario 3: User with Access to Multiple Databases
```sql
CREATE USER 'multiuser'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON db1.* TO 'multiuser'@'localhost';
GRANT SELECT ON db2.* TO 'multiuser'@'localhost';
GRANT SELECT, INSERT, UPDATE ON db3.* TO 'multiuser'@'localhost';
FLUSH PRIVILEGES;
```

#### Scenario 4: Remote Access with IP Restriction
```sql
CREATE USER 'remoteuser'@'192.168.1.100' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON mydatabase.* TO 'remoteuser'@'192.168.1.100';
FLUSH PRIVILEGES;
```

---

## Setup and Configuration

### Initial Setup

1. **Configure IP Whitelist**
   - Create `login/ipAllowed.txt`
   - Add allowed IP addresses or CIDR ranges
   - Localhost is automatically allowed

2. **Create First User**
   - Access `login/setup.php` from localhost
   - Create username and password
   - Set database credentials
   - Click "Create User"

3. **Set Up Database Users**
   - Connect to MySQL as root
   - Create database users with appropriate permissions
   - Use the same credentials in the setup form

4. **Test Login**
   - Access the application
   - Login with created credentials
   - Verify database access works

### Configuration Files

#### `login/ipAllowed.txt`
```
# Office network
192.168.1.0/24

# Developer IP
81.204.237.36

# Comments start with #
```

#### `login/credentials.txt`
```
username|hashed_password|created|last_login|failed_attempts|locked_until|db_user|db_pass|db_host
```

**File Permissions**: Should be 600 (owner read/write only)
```bash
chmod 600 login/credentials.txt
chmod 600 login/ipAllowed.txt
```

### Protecting Credentials from Direct Browser Access

**⚠️ Critical Security**: The credentials file must be protected from direct HTTP access. The system includes multiple layers of protection:

#### 1. .htaccess Protection (Primary - Apache)

Two `.htaccess` files have been created to block direct access:

**`login/.htaccess`** - Protects files in the login directory:
- Blocks all `.txt` files from direct HTTP access
- Specifically protects `credentials.txt`, `remember_tokens.txt`, and `ipAllowed.txt`
- PHP scripts can still read these files using `file_get_contents()` - only direct HTTP access is blocked

**`.htaccess`** (root) - Additional protection:
- Blocks sensitive file types (`.txt`, `.log`, `.bak`, etc.) across the entire application
- Provides an extra layer of security

**How it works**:
- When someone tries to access `http://localhost/db-manager/login/credentials.txt` directly, Apache will return a 403 Forbidden error
- PHP scripts can still read the file normally using `file_get_contents()` or `file()`
- This only blocks direct HTTP requests, not programmatic access

**Testing the protection**:
```bash
# This should return 403 Forbidden
curl http://localhost/db-manager/login/credentials.txt

# This should work (PHP can still read the file)
# Access via login.php or setup.php which use file_get_contents()
```

#### 2. File Permissions (Secondary)

Set restrictive file permissions:
```bash
chmod 600 login/credentials.txt
chmod 600 login/remember_tokens.txt
chmod 600 login/ipAllowed.txt
```

This ensures only the file owner (web server user) can read/write the file.

#### 3. PHP Protection (Fallback - for Nginx or if .htaccess doesn't work)

If you're using Nginx or `.htaccess` is not working, you can add PHP-based protection:

**Option A**: Move sensitive files outside the web root:
```
/var/www/
  ├── db-manager/          (web root)
  │   └── login/
  │       └── login.php
  └── secure/              (outside web root)
      └── credentials.txt
```

Then update file paths in PHP:
```php
$credentialsFile = '/var/www/secure/credentials.txt';
```

**Option B**: Use the provided PHP protection file:
```php
// Include at the top of files that read credentials
require_once __DIR__ . '/deny_direct_access.php';
```

#### 4. Web Server Configuration (Alternative)

**For Nginx**, add to your server block:
```nginx
location ~ \.(txt|log|bak|backup|old|tmp)$ {
    deny all;
    return 403;
}

location ~ (credentials|config|\.env) {
    deny all;
    return 403;
}
```

**For Apache** (if .htaccess is disabled), add to `httpd.conf` or virtual host:
```apache
<Directory "/path/to/db-manager/login">
    <FilesMatch "\.txt$">
        Require all denied
    </FilesMatch>
</Directory>
```

#### Verification

To verify protection is working:

1. **Test direct access** (should fail):
   ```
   http://localhost/db-manager/login/credentials.txt
   ```
   Expected: 403 Forbidden or 404 Not Found

2. **Test PHP access** (should work):
   - Login via `login/login.php` - should work normally
   - Setup via `login/setup.php` - should work normally

3. **Check file permissions**:
   ```bash
   ls -l login/credentials.txt
   # Should show: -rw------- (600 permissions)
   ```

### Session Configuration

**Default Settings** (in `session_check.php`):
- Timeout: 30 minutes of inactivity
- Regeneration: Every 30 minutes
- Secure cookies: Enabled (if HTTPS)

**To Change Timeout**:
```php
// In session_check.php
$sessionTimeout = 3600; // 1 hour in seconds
```

**To Change Lockout Duration**:
```php
// In login/login.php
$lockedUntil = date('Y-m-d H:i:s', time() + 3600); // 1 hour
```

---

## Security Features

### Password Security
- ✅ Bcrypt hashing (one-way encryption)
- ✅ Automatic salting
- ✅ Minimum 8 characters required
- ✅ Password verification without storing plain text

### Session Security
- ✅ HTTP-only cookies (prevents JavaScript access)
- ✅ Secure cookies (HTTPS only when available)
- ✅ SameSite=Strict (CSRF protection)
- ✅ Session regeneration (prevents fixation)
- ✅ IP and User-Agent validation (prevents hijacking)
- ✅ Automatic timeout (prevents abandoned sessions)

### Account Security
- ✅ Account lockout after 5 failed attempts
- ✅ 30-minute lockout duration
- ✅ Failed attempt tracking
- ✅ Automatic reset on successful login

### CSRF Protection
- ✅ Unique token per session
- ✅ Token validation on form submission
- ✅ Token regeneration after each attempt

### Remember Me Security
- ✅ Secure random token generation
- ✅ Token hashing before storage
- ✅ Device fingerprinting
- ✅ Token expiration (90 days)
- ✅ Automatic cleanup of expired tokens

### File Security
- ✅ Credentials file permissions (600)
- ✅ Whitelist file permissions (600)
- ✅ Setup page restricted to localhost
- ✅ No credentials in version control

---

## Troubleshooting

### Login Issues

**Problem**: Can't access setup page
- **Solution**: Must access from localhost (127.0.0.1)

**Problem**: "Invalid username or password" but credentials are correct
- **Check**: Password hash in credentials.txt
- **Check**: File permissions (should be 600)
- **Check**: No extra spaces in credentials file

**Problem**: Account locked
- **Solution**: Wait 30 minutes or reset via setup.php

**Problem**: Session expires too quickly
- **Solution**: Increase timeout in `session_check.php`

### IP Authorization Issues

**Problem**: "Access Denied" even on localhost
- **Check**: Accessing via `localhost` or `127.0.0.1`, not external IP
- **Check**: `auth_check.php` exists and is included

**Problem**: IP whitelisted but still denied
- **Check**: IP address matches exactly (no spaces)
- **Check**: CIDR notation is correct
- **Check**: Behind proxy (IP might be different)

### Database Connection Issues

**Problem**: "Access denied for user"
- **Check**: Database user exists in MySQL
- **Check**: Database credentials in session match MySQL user
- **Check**: Host matches (localhost vs %)
- **Check**: User has permissions on the database

**Problem**: "Unknown database"
- **Check**: Database name is correct
- **Check**: Database exists
- **Check**: User has permissions on the database

**Problem**: Can't create tables
- **Check**: User has CREATE privilege
- **Solution**: Grant CREATE permission:
  ```sql
  GRANT CREATE ON database_name.* TO 'username'@'localhost';
  FLUSH PRIVILEGES;
  ```

### Session Issues

**Problem**: Logged out immediately after login
- **Check**: Session storage path is writable
- **Check**: PHP session settings
- **Check**: IP or User-Agent changed

**Problem**: "Invalid security token"
- **Solution**: Refresh page and try again
- **Check**: Cookies are enabled
- **Check**: Not using multiple tabs with different sessions

### File Permission Issues

**Problem**: Can't write to credentials.txt
- **Solution**: Set correct permissions:
  ```bash
  chmod 600 login/credentials.txt
  chown www-data:www-data login/credentials.txt
  ```

**Problem**: Setup page can't create file
- **Check**: Directory is writable
- **Check**: PHP has write permissions
- **Solution**: Create file manually with 600 permissions

---

## Quick Reference

### Creating a New User

1. Access `login/setup.php` from localhost
2. Fill in username, password, and database credentials
3. Click "Add User"

### Adding IP to Whitelist

1. Edit `login/ipAllowed.txt`
2. Add IP address or CIDR range
3. Save file (no restart needed)

### Creating Database User

```sql
CREATE USER 'username'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON database_name.* TO 'username'@'localhost';
FLUSH PRIVILEGES;
```

### Checking User Permissions

```sql
SHOW GRANTS FOR 'username'@'localhost';
```

### Revoking Access

```sql
REVOKE ALL PRIVILEGES ON *.* FROM 'username'@'localhost';
DROP USER 'username'@'localhost';
FLUSH PRIVILEGES;
```

### Session Timeout

Default: 30 minutes
Location: `session_check.php`
Variable: `$sessionTimeout`

### Account Lockout

Threshold: 5 failed attempts
Duration: 30 minutes
Location: `login/login.php`

---

## Related Documentation

- [Authentication Guide](AUTHENTICATION_GUIDE.md) - Detailed authentication features
- [IP Authorization README](IP_AUTHORIZATION_README.md) - IP whitelist system
- [IP Functions README](../login/IP_FUNCTIONS_README.md) - IP utility functions
- [Security Recommendations](SECURITY_RECOMMENDATIONS.md) - Security best practices

---

**Last Updated**: 2025-01-15
**Version**: 1.0


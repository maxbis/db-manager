# File-Based Authentication Options

## Overview

Instead of storing user credentials in MySQL/MariaDB, you can use file-based storage. This keeps authentication separate from your application database and can be simpler to manage.

---

## Option 1: SQLite (⭐ RECOMMENDED)

### Description
SQLite is a lightweight, file-based SQL database that's built into PHP. It provides full SQL functionality without requiring a separate database server.

### Pros ✅
- **Built into PHP** - No installation needed (available since PHP 5.3)
- **SQL syntax** - Use familiar SQL queries
- **ACID compliant** - Reliable and safe transactions
- **Concurrent access** - Multiple users can access simultaneously
- **Portable** - Single file, easy to backup
- **Performance** - Fast for authentication purposes
- **Security** - Well-tested, battle-proven
- **No configuration** - Just point to a file

### Cons ❌
- Slight learning curve if unfamiliar with SQLite
- File permissions must be set correctly
- Large-scale use (1000s of users) may need optimization

### Best For
- Small to medium teams (1-500 users)
- Applications with separate auth database
- Easy backup requirements
- Portable applications

### Implementation

#### 1. Create SQLite Database File

```php
<?php
// auth_sqlite.php

class AuthDB {
    private $db;
    private $dbFile = 'users.db'; // File path
    
    public function __construct($dbPath = null) {
        if ($dbPath) {
            $this->dbFile = $dbPath;
        }
        
        // Create/open SQLite database
        try {
            $this->db = new PDO('sqlite:' . $this->dbFile);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->createTables();
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }
    
    private function createTables() {
        $sql = "
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            email TEXT,
            role TEXT DEFAULT 'viewer',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_login DATETIME,
            is_active INTEGER DEFAULT 1
        );
        
        CREATE INDEX IF NOT EXISTS idx_username ON users(username);
        CREATE INDEX IF NOT EXISTS idx_email ON users(email);
        ";
        
        $this->db->exec($sql);
    }
    
    public function createUser($username, $password, $email = null, $role = 'viewer') {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        $stmt = $this->db->prepare(
            "INSERT INTO users (username, password, email, role) 
             VALUES (:username, :password, :email, :role)"
        );
        
        return $stmt->execute([
            ':username' => $username,
            ':password' => $hashedPassword,
            ':email' => $email,
            ':role' => $role
        ]);
    }
    
    public function verifyUser($username, $password) {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE username = :username AND is_active = 1"
        );
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Update last login
            $updateStmt = $this->db->prepare(
                "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = :id"
            );
            $updateStmt->execute([':id' => $user['id']]);
            
            return $user;
        }
        
        return false;
    }
    
    public function getUser($username) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function updatePassword($username, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare(
            "UPDATE users SET password = :password WHERE username = :username"
        );
        return $stmt->execute([
            ':password' => $hashedPassword,
            ':username' => $username
        ]);
    }
    
    public function deleteUser($username) {
        $stmt = $this->db->prepare("DELETE FROM users WHERE username = :username");
        return $stmt->execute([':username' => $username]);
    }
    
    public function getAllUsers() {
        $stmt = $this->db->query("SELECT id, username, email, role, created_at, last_login FROM users");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Example usage:
$auth = new AuthDB('data/users.db');

// Create admin user
$auth->createUser('admin', 'admin_password', 'admin@example.com', 'admin');

// Verify login
$user = $auth->verifyUser('admin', 'admin_password');
if ($user) {
    echo "Login successful!";
    $_SESSION['user'] = $user;
}
?>
```

#### 2. Security Considerations

```php
<?php
// Secure the database file location
$dbPath = __DIR__ . '/data/users.db'; // Outside web root is better

// Set proper file permissions (Linux/Mac)
chmod($dbPath, 0600); // Read/write for owner only

// Ensure directory exists
if (!is_dir(dirname($dbPath))) {
    mkdir(dirname($dbPath), 0700, true);
}
?>
```

---

## Option 2: JSON File

### Description
Store user credentials in a JSON file with proper encryption.

### Pros ✅
- **Simple** - Easy to understand
- **No dependencies** - Just PHP's JSON functions
- **Readable** - Can manually inspect/edit
- **Portable** - Easy to move/backup

### Cons ❌
- **No concurrent access** - File locking needed
- **No transactions** - Can corrupt on errors
- **Performance** - Loads entire file into memory
- **Scalability** - Poor with many users (100+)
- **Manual indexing** - No automatic optimization

### Best For
- Very small teams (1-10 users)
- Quick prototypes
- Simple authentication needs

### Implementation

```php
<?php
// auth_json.php

class JSONAuth {
    private $file;
    
    public function __construct($filePath = 'users.json') {
        $this->file = $filePath;
        if (!file_exists($this->file)) {
            file_put_contents($this->file, json_encode(['users' => []]));
            chmod($this->file, 0600);
        }
    }
    
    private function loadUsers() {
        $data = json_decode(file_get_contents($this->file), true);
        return $data['users'] ?? [];
    }
    
    private function saveUsers($users) {
        // File locking to prevent corruption
        $fp = fopen($this->file, 'w');
        if (flock($fp, LOCK_EX)) {
            fwrite($fp, json_encode(['users' => $users], JSON_PRETTY_PRINT));
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }
    
    public function createUser($username, $password, $role = 'viewer') {
        $users = $this->loadUsers();
        
        // Check if user exists
        foreach ($users as $user) {
            if ($user['username'] === $username) {
                return false; // User exists
            }
        }
        
        $users[] = [
            'username' => $username,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'role' => $role,
            'created_at' => date('Y-m-d H:i:s'),
            'last_login' => null
        ];
        
        $this->saveUsers($users);
        return true;
    }
    
    public function verifyUser($username, $password) {
        $users = $this->loadUsers();
        
        foreach ($users as $index => $user) {
            if ($user['username'] === $username) {
                if (password_verify($password, $user['password'])) {
                    // Update last login
                    $users[$index]['last_login'] = date('Y-m-d H:i:s');
                    $this->saveUsers($users);
                    return $user;
                }
            }
        }
        
        return false;
    }
}

// Usage:
$auth = new JSONAuth('data/users.json');
$auth->createUser('admin', 'password123', 'admin');
$user = $auth->verifyUser('admin', 'password123');
?>
```

---

## Option 3: Encrypted Flat File

### Description
Store credentials in a custom encrypted format.

### Pros ✅
- **Simple** - No dependencies
- **Encrypted** - Better security than plain JSON
- **Small footprint** - Minimal code

### Cons ❌
- **Manual encryption** - Need to implement carefully
- **No structure** - Custom parsing needed
- **No concurrent access** - Race conditions possible
- **Maintenance** - More code to maintain

### Best For
- Maximum portability
- Minimal dependencies
- Very simple use cases

### Implementation

```php
<?php
// auth_encrypted.php

class EncryptedAuth {
    private $file;
    private $encryptionKey;
    
    public function __construct($filePath = 'users.dat', $key = null) {
        $this->file = $filePath;
        $this->encryptionKey = $key ?? $_ENV['AUTH_ENCRYPTION_KEY'] ?? 'default-key-change-me';
        
        if (!file_exists($this->file)) {
            file_put_contents($this->file, $this->encrypt(''));
            chmod($this->file, 0600);
        }
    }
    
    private function encrypt($data) {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt(
            $data, 
            'AES-256-CBC', 
            hash('sha256', $this->encryptionKey, true),
            0,
            $iv
        );
        return base64_encode($iv . $encrypted);
    }
    
    private function decrypt($data) {
        $data = base64_decode($data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt(
            $encrypted,
            'AES-256-CBC',
            hash('sha256', $this->encryptionKey, true),
            0,
            $iv
        );
    }
    
    private function loadUsers() {
        $encrypted = file_get_contents($this->file);
        $decrypted = $this->decrypt($encrypted);
        return $decrypted ? json_decode($decrypted, true) ?? [] : [];
    }
    
    private function saveUsers($users) {
        $json = json_encode($users);
        $encrypted = $this->encrypt($json);
        file_put_contents($this->file, $encrypted);
    }
    
    // Same methods as JSON version...
}
?>
```

---

## Option 4: .htpasswd File (Apache)

### Description
Use Apache's built-in authentication system.

### Pros ✅
- **Native** - Built into Apache
- **Simple** - Standard web server feature
- **Tested** - Very mature and reliable
- **Low overhead** - No PHP code needed

### Cons ❌
- **Apache only** - Won't work with Nginx without modifications
- **Basic auth** - Browser popup (not custom login page)
- **Limited features** - No roles, no sessions
- **No customization** - Can't add extra user fields

### Best For
- Quick and dirty protection
- Apache-only environments
- Basic authentication needs

### Implementation

```bash
# Create .htpasswd file
htpasswd -c /path/to/.htpasswd admin

# Add more users
htpasswd /path/to/.htpasswd user2
```

```apache
# .htaccess
AuthType Basic
AuthName "Database Manager"
AuthUserFile /path/to/.htpasswd
Require valid-user
```

---

## Comparison Table

| Feature | SQLite | JSON | Encrypted | .htpasswd |
|---------|--------|------|-----------|-----------|
| **Ease of Use** | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Performance** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐ |
| **Scalability** | ⭐⭐⭐⭐⭐ | ⭐⭐ | ⭐⭐ | ⭐⭐⭐ |
| **Security** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ |
| **Features** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐ |
| **Concurrent Access** | ⭐⭐⭐⭐⭐ | ⭐ | ⭐ | ⭐⭐⭐⭐ |
| **Portability** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐ |
| **User Limit** | 1000+ | 10-50 | 10-50 | 100+ |

---

## Recommendation by Use Case

### Use **SQLite** if:
- ✅ You want a proper database
- ✅ You need 10+ users
- ✅ You want role-based access
- ✅ You need good performance
- ✅ You want standard SQL
- ✅ **This is the best choice for most scenarios**

### Use **JSON** if:
- ✅ You have fewer than 10 users
- ✅ You want simplicity
- ✅ You need human-readable format
- ✅ You're prototyping

### Use **Encrypted File** if:
- ✅ You need maximum portability
- ✅ You can't use SQLite
- ✅ You need custom encryption
- ✅ You have very few users

### Use **.htpasswd** if:
- ✅ You just need basic protection
- ✅ You're using Apache
- ✅ Browser popup login is acceptable
- ✅ You don't need custom features

---

## Complete SQLite Authentication System

Here's a production-ready implementation:

```php
<?php
// File: auth_system.php

class AuthSystem {
    private $db;
    private $sessionTimeout = 3600; // 1 hour
    
    public function __construct($dbPath = 'data/users.db') {
        // Ensure directory exists
        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
        
        // Connect to SQLite
        $this->db = new PDO('sqlite:' . $dbPath);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Set proper file permissions
        chmod($dbPath, 0600);
        
        $this->initDatabase();
    }
    
    private function initDatabase() {
        $sql = "
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            email TEXT,
            role TEXT DEFAULT 'viewer',
            is_active INTEGER DEFAULT 1,
            failed_attempts INTEGER DEFAULT 0,
            locked_until DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_login DATETIME,
            last_activity DATETIME
        );
        
        CREATE TABLE IF NOT EXISTS sessions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            session_token TEXT UNIQUE NOT NULL,
            ip_address TEXT,
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id)
        );
        
        CREATE INDEX IF NOT EXISTS idx_username ON users(username);
        CREATE INDEX IF NOT EXISTS idx_session_token ON sessions(session_token);
        ";
        
        $this->db->exec($sql);
        
        // Create default admin if no users exist
        $count = $this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        if ($count == 0) {
            $this->createUser('admin', 'admin123', 'admin@localhost', 'admin');
        }
    }
    
    public function createUser($username, $password, $email = null, $role = 'viewer') {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        $stmt = $this->db->prepare(
            "INSERT INTO users (username, password, email, role) 
             VALUES (:username, :password, :email, :role)"
        );
        
        try {
            return $stmt->execute([
                ':username' => $username,
                ':password' => $hashedPassword,
                ':email' => $email,
                ':role' => $role
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return false; // Duplicate username
            }
            throw $e;
        }
    }
    
    public function login($username, $password) {
        // Get user
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return ['success' => false, 'error' => 'Invalid username or password'];
        }
        
        // Check if account is locked
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            return ['success' => false, 'error' => 'Account is temporarily locked'];
        }
        
        // Check if account is active
        if (!$user['is_active']) {
            return ['success' => false, 'error' => 'Account is disabled'];
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            // Increment failed attempts
            $this->incrementFailedAttempts($user['id']);
            return ['success' => false, 'error' => 'Invalid username or password'];
        }
        
        // Reset failed attempts
        $this->resetFailedAttempts($user['id']);
        
        // Update last login
        $stmt = $this->db->prepare(
            "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = :id"
        );
        $stmt->execute([':id' => $user['id']]);
        
        // Create session
        $sessionToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + $this->sessionTimeout);
        
        $stmt = $this->db->prepare(
            "INSERT INTO sessions (user_id, session_token, ip_address, user_agent, expires_at) 
             VALUES (:user_id, :token, :ip, :user_agent, :expires)"
        );
        
        $stmt->execute([
            ':user_id' => $user['id'],
            ':token' => $sessionToken,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ':expires' => $expiresAt
        ]);
        
        return [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ],
            'session_token' => $sessionToken
        ];
    }
    
    public function validateSession($sessionToken) {
        $stmt = $this->db->prepare(
            "SELECT u.*, s.expires_at 
             FROM sessions s 
             JOIN users u ON s.user_id = u.id 
             WHERE s.session_token = :token 
             AND s.expires_at > datetime('now')
             AND u.is_active = 1"
        );
        
        $stmt->execute([':token' => $sessionToken]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Update last activity
            $stmt = $this->db->prepare(
                "UPDATE users SET last_activity = CURRENT_TIMESTAMP WHERE id = :id"
            );
            $stmt->execute([':id' => $user['id']]);
            
            return $user;
        }
        
        return false;
    }
    
    public function logout($sessionToken) {
        $stmt = $this->db->prepare("DELETE FROM sessions WHERE session_token = :token");
        return $stmt->execute([':token' => $sessionToken]);
    }
    
    private function incrementFailedAttempts($userId) {
        $stmt = $this->db->prepare(
            "UPDATE users 
             SET failed_attempts = failed_attempts + 1,
                 locked_until = CASE 
                     WHEN failed_attempts >= 4 THEN datetime('now', '+30 minutes')
                     ELSE locked_until 
                 END
             WHERE id = :id"
        );
        $stmt->execute([':id' => $userId]);
    }
    
    private function resetFailedAttempts($userId) {
        $stmt = $this->db->prepare(
            "UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = :id"
        );
        $stmt->execute([':id' => $userId]);
    }
}

// Usage example:
session_start();
$auth = new AuthSystem('data/users.db');

// Login
if (isset($_POST['login'])) {
    $result = $auth->login($_POST['username'], $_POST['password']);
    if ($result['success']) {
        $_SESSION['session_token'] = $result['session_token'];
        $_SESSION['user'] = $result['user'];
        header('Location: table_data.php');
        exit;
    } else {
        $error = $result['error'];
    }
}

// Check session
if (isset($_SESSION['session_token'])) {
    $user = $auth->validateSession($_SESSION['session_token']);
    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit;
    }
}

// Logout
if (isset($_POST['logout'])) {
    $auth->logout($_SESSION['session_token']);
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
```

---

## Final Recommendation

**For your database manager application, use SQLite:**

1. **Performance**: Handles 100+ concurrent users easily
2. **Features**: Full SQL support for complex queries
3. **Security**: Industry-standard, well-tested
4. **Maintenance**: Easy to backup (single file)
5. **Portability**: Works on any platform with PHP
6. **Future-proof**: Can migrate to MySQL later if needed

---

## Quick Start Guide

1. Create the authentication system:
```bash
mkdir data
chmod 700 data
```

2. Copy the `AuthSystem` class code above to `auth_system.php`

3. Create a login page (see next section)

4. Update `auth_check.php` to check sessions

5. Done! You now have file-based authentication.

---

**Need help implementing this? Let me know which option you prefer and I can provide the complete working code!**


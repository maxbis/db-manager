# Database Sync - Installation Guide

## Quick Start

Follow these steps to set up the database sync functionality:

### Step 1: Verify Files on Local Server

Ensure all files are present in your `sync_db/` directory:

```
✅ sync_db/index.php           - Main client page
✅ sync_db/config.php           - Configuration (with API key)
✅ sync_db/sync.js              - Client-side JavaScript
✅ sync_db/sync_handler.php     - Local SQL handler
✅ sync_db/api.php              - Remote API endpoint
✅ sync_db/.gitignore           - Git ignore file
```

### Step 2: Copy API Key

1. Open `sync_db/config.php`
2. Copy the entire `SYNC_API_KEY` value
3. Save this key somewhere safe - you'll need it for the remote server

### Step 3: Deploy to Remote Server

Upload these files to your REMOTE server:

```
remote-server/
  ├── sync_db/
  │   ├── api.php         ⬅️ REQUIRED
  │   └── config.php      ⬅️ REQUIRED (with same API key!)
  └── login/
      └── ipAllowed.txt   ⬅️ Should already exist
```

**Important**: Update `config.php` on the remote server with the SAME API key from Step 2!

### Step 4: Configure Remote IP Whitelist

On the REMOTE server, add your LOCAL server's IP to `login/ipAllowed.txt`:

```bash
# Edit login/ipAllowed.txt
# Add your local server's IP address

127.0.0.1
123.456.789.012    # ⬅️ Add your local server's IP
```

### Step 5: Access the Sync Page

1. Log in to your local database manager
2. Navigate to: `http://your-local-server/sync_db/`
3. Fill in the form with your remote server details
4. Click "Test Connection" to verify everything works
5. Click "Start Sync" to begin synchronization

## Form Fields Explanation

| Field | Description | Example |
|-------|-------------|---------|
| **Remote Server URL** | Full URL to api.php on remote server | `https://remote.com/sync_db/api.php` |
| **API Key** | The key from config.php | `your-secure-api-key-here` |
| **Remote DB Host** | MySQL host on remote server | `localhost` |
| **Remote DB Username** | Database username on remote | `db_user` |
| **Remote DB Password** | Database password on remote | `SecurePass123` |
| **Remote Database Name** | Database to sync FROM | `production_db` |
| **Local Database Name** | Database to sync TO (will be created) | `local_copy_db` |
| **Chunk Size** | Rows per batch (default: 1000) | `1000` |

## Adding to Navigation Menu (Optional)

If you want to add the sync page to the main navigation menu, edit `templates/header.php`:

```php
// Around line 29, add this to the $menuItems array:

$menuItems = [
    // ... existing items ...
    [
        'id' => 'sync_db',
        'url' => 'sync_db/index.php',
        'icon' => '🔄',
        'name' => 'Database Sync'
    ]
];
```

## Testing the Setup

### Test 1: Access the Page

1. Navigate to `http://localhost/sync_db/` (or your local URL)
2. You should see the sync form
3. If you see login page, you need to log in first

### Test 2: Test Connection

1. Fill in all form fields
2. Click "Test Connection"
3. You should see: ✅ "Connection successful! Found X tables..."
4. If you see an error, check the troubleshooting section below

### Test 3: Small Sync Test

1. Choose a small test database first
2. Click "Start Sync"
3. Confirm the warning dialog
4. Watch the progress bar and logs
5. Wait for completion message

## Troubleshooting

### ❌ "Unauthorized: IP address not allowed"

**Problem**: Your local IP is not whitelisted on remote server

**Solution**:
1. Find your local server's IP address
2. Add it to `login/ipAllowed.txt` on the REMOTE server
3. Try again

### ❌ "Unauthorized: Invalid API key"

**Problem**: API keys don't match between local and remote

**Solution**:
1. Check `config.php` on LOCAL server
2. Check `config.php` on REMOTE server
3. Make sure `SYNC_API_KEY` values are EXACTLY the same
4. Copy-paste to avoid typos

### ❌ "Connection failed"

**Problem**: Cannot connect to remote server

**Solution**:
1. Check the Remote Server URL is correct
2. Make sure `api.php` exists on remote server
3. Verify remote server is accessible (try in browser)
4. Check for firewall issues

### ❌ "Failed to get table structure"

**Problem**: Database permissions issue

**Solution**:
1. Check remote database username/password
2. Verify user has SELECT and SHOW VIEW privileges
3. Try connecting directly to remote database

### ❌ "SQL execution failed"

**Problem**: Cannot create tables locally

**Solution**:
1. Check local database user has CREATE privileges
2. Verify local MySQL is running
3. Check for sufficient disk space
4. Look at specific error message in logs

## Security Checklist

Before using in production:

- [ ] Changed default API key to a strong random value
- [ ] Same API key on both local and remote servers
- [ ] Remote server uses HTTPS (not HTTP)
- [ ] IP whitelist configured on remote server
- [ ] Strong database passwords used
- [ ] `config.php` excluded from version control (.gitignore)
- [ ] Tested with small database first
- [ ] Reviewed and understand the warning about database replacement

## Next Steps

1. ✅ Test with a small database first
2. ✅ Verify all tables, views, procedures sync correctly
3. ✅ Set up regular sync schedule if needed
4. ✅ Monitor sync logs for any issues
5. ✅ Consider automating syncs with cron jobs

## Getting Help

If you encounter issues:

1. Check the main [README.md](README.md) for detailed documentation
2. Review error messages in browser console
3. Check `sync_log.txt` on remote server
4. Verify all configuration settings
5. Test each component individually (connection, tables, data)

## File Permissions

Make sure these files are writable:

```bash
# On remote server
chmod 644 sync_db/config.php
chmod 644 sync_db/api.php
chmod 666 sync_db/sync_log.txt  # If using logging

# On local server  
chmod 644 sync_db/config.php
chmod 644 sync_db/index.php
chmod 644 sync_db/sync_handler.php
chmod 644 sync_db/sync.js
```

## Success!

Once everything is working, you should see:

```
🎉 Database sync completed successfully!
📊 Summary: X tables, Y rows, Z views, ...
```

Your form settings will be saved in cookies for next time. Happy syncing! 🚀


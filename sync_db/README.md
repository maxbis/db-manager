# Database Sync Tool

## Overview

This tool allows you to sync a complete database from a remote server to your local server. It copies everything including:

- ✅ All tables (structure and data)
- ✅ All views
- ✅ All stored procedures
- ✅ All functions
- ✅ All triggers
- ✅ All keys and indexes

## Security Features

1. **IP Whitelist**: Uses the existing `ipAllowed.txt` file to restrict access
2. **API Key Authentication**: Requires a secure API key that must match on both servers
3. **Session-based Auth**: Requires login using the existing authentication system

## Setup Instructions

### Step 1: Configure API Key

1. Open `sync_db/config.php` on your LOCAL server
2. The API key has been auto-generated. Copy the entire `SYNC_API_KEY` value
3. Upload the `sync_db/` directory to your REMOTE server
4. Update `sync_db/config.php` on the REMOTE server with the SAME API key value

**Important**: Both servers must have the exact same API key value!

### Step 2: Configure IP Whitelist (Remote Server)

On the REMOTE server, ensure the IP address of your LOCAL server is added to `login/ipAllowed.txt`:

```
# Add your local server's IP
123.456.789.012
```

### Step 3: File Structure

**Files on LOCAL server:**
```
sync_db/
  ├── index.php          (Client UI page)
  ├── config.php         (Configuration with API key)
  ├── sync.js            (Client-side sync logic)
  └── sync_handler.php   (Local SQL execution handler)
```

**Files on REMOTE server:**
```
sync_db/
  ├── api.php            (API endpoint for sync requests)
  └── config.php         (Configuration with API key - MUST MATCH LOCAL)
login/
  └── ipAllowed.txt      (IP whitelist)
```

## Usage

### 1. Access the Sync Page

Navigate to: `http://your-local-server/sync_db/`

You must be logged in to access this page.

### 2. Fill in the Form

- **Remote Server URL**: Full URL to the API endpoint
  - Example: `https://example.com/sync_db/api.php`
  
- **API Key**: The secure key from `config.php`
  
- **Remote DB Host**: Usually `localhost` on the remote server
  
- **Remote DB Username**: Database username on remote server
  
- **Remote DB Password**: Database password on remote server
  
- **Remote Database Name**: Name of the database you want to sync FROM
  
- **Local Database Name**: Name of the database to create/replace locally
  - 🔒 Auto-syncs from Remote Database Name
  - 🔓 Becomes editable after Remote DB is specified
  - ✏️ Customize if you want a different local name
  
- **Chunk Size**: Number of rows to transfer per request (default: 1000)
  - Larger values = faster but more memory usage
  - Smaller values = slower but more stable for large databases

### 3. Test Connection (Optional)

Click "Test Connection" to verify that all settings are correct before starting the sync.

### 4. Start Sync

Click "Start Sync" to begin the synchronization process.

**WARNING**: This will completely replace the local database if it exists!

### 5. Monitor Progress

The page will show:
- Progress bar with percentage
- Real-time log of operations
- Statistics (tables synced, rows transferred, time elapsed)

## Features

### Cookie Storage

Form values are automatically saved in cookies with different expiration times:

**Regular Fields (3 months):**
- Remote Server URL
- Remote DB Host
- Remote DB Username
- Remote Database Name
- Local Database Name
- Chunk Size

**Password Fields (1 hour only):**
- API Key
- Remote DB Password

**Auto-renewal**: Cookie expiration dates are renewed every time you visit the page, so actively used cookies stay valid.

**Security Note**: Use the "Clear Form" button to immediately remove all saved data when done.

### Progress Tracking

Real-time progress updates showing:
- Current operation
- Tables synced
- Rows transferred
- Views, procedures, and functions created
- Elapsed time

### Error Handling

- Clear error messages if anything goes wrong
- Detailed logs for troubleshooting
- Transaction safety (if one table fails, others continue)

## Troubleshooting

### Connection Failed

**Problem**: Cannot connect to remote API

**Solutions**:
1. Check that the remote URL is correct
2. Verify the API key matches on both servers
3. Ensure your local IP is in the remote `ipAllowed.txt`
4. Check that `sync_db/api.php` exists on remote server

### SQL Execution Failed

**Problem**: Errors during table creation or data insertion

**Solutions**:
1. Check that local database user has CREATE/INSERT permissions
2. Verify the local MySQL version is compatible
3. Check for name conflicts (existing tables/views with same names)
4. Look at the error log for specific SQL errors

### Timeout Errors

**Problem**: Sync times out for large databases

**Solutions**:
1. Reduce chunk size (e.g., from 1000 to 500)
2. Increase PHP execution time limits in `config.php`
3. Sync tables individually if needed
4. Check server resources (memory, CPU)

### IP Whitelist Issues

**Problem**: "Unauthorized: IP address 'x.x.x.x' not allowed"

**Solutions**:
1. The error message displays your detected IP address
2. Add this exact IP to remote `ipAllowed.txt`
3. If behind a proxy, add the proxy IP instead
4. Check remote server logs (`sync_log.txt`) for more details

## Configuration Options

Edit `sync_db/config.php` to customize:

```php
// API key (must match on both servers)
define('SYNC_API_KEY', 'your-key-here');

// Maximum execution time (0 = unlimited)
define('SYNC_MAX_EXECUTION_TIME', 0);

// Maximum memory limit (-1 = unlimited)
define('SYNC_MEMORY_LIMIT', '-1');

// Chunk size for data transfer
define('SYNC_CHUNK_SIZE', 1000);

// Enable/disable logging
define('SYNC_ENABLE_LOGGING', true);

// Log file location
define('SYNC_LOG_FILE', __DIR__ . '/sync_log.txt');
```

## Security Best Practices

1. ✅ Use HTTPS for the remote server
2. ✅ Keep API keys secure and change them regularly
3. ✅ Restrict IP addresses in `ipAllowed.txt`
4. ✅ Use strong database passwords
5. ✅ Keep `config.php` out of version control (.gitignore)
6. ✅ Review sync logs regularly
7. ✅ Only grant necessary database permissions

## Performance Tips

For large databases:

1. **Increase chunk size** for faster transfers (if you have enough memory)
2. **Use local network** if possible (faster than internet)
3. **Schedule during off-peak hours** to avoid server load
4. **Monitor server resources** (memory, CPU, disk I/O)
5. **Consider database compression** at MySQL level

## API Endpoints

The remote API supports these actions:

- `list_databases` - List all databases
- `get_tables` - Get table list
- `get_table_structure` - Get CREATE TABLE statement
- `get_table_data` - Get table data (paginated)
- `get_views` - Get view list
- `get_view_structure` - Get CREATE VIEW statement
- `get_triggers` - Get trigger list
- `get_procedures` - Get procedure list
- `get_procedure_structure` - Get CREATE PROCEDURE statement
- `get_functions` - Get function list
- `get_function_structure` - Get CREATE FUNCTION statement

## Changelog

### Version 1.0
- Initial release
- Full database sync (tables, views, procedures, functions, triggers)
- IP whitelist + API key authentication
- Real-time progress tracking
- Cookie-based form persistence
- Error handling and logging

## Support

For issues or questions, please check:
1. The troubleshooting section above
2. Sync logs at `sync_db/sync_log.txt` on remote server
3. Browser console for JavaScript errors
4. PHP error logs on both servers


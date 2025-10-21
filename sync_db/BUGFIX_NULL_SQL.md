# Bug Fix: SQL Syntax Error "near 'null'" & Views Treated as Tables

## Issues

### Issue 1: SQL Syntax Error
Database sync was failing with the error:
```
SQL execution failed: You have an error in your SQL syntax; 
check the manual that corresponds to your MariaDB server version 
for the right syntax to use near 'null' at line 1
```

### Issue 2: Views Being Treated as Tables
The `get_tables` endpoint was using `SHOW TABLES` which returns **both tables and views**. When the sync tried to use `SHOW CREATE TABLE` on a view (like `all_submissions`), it would fail with:
```
Failed to retrieve CREATE TABLE statement for table: all_submissions
```

## Root Causes

### Root Cause 1: Null Values
When fetching table/view/procedure/function structures from the remote database, if the `SHOW CREATE TABLE/VIEW/PROCEDURE/FUNCTION` query returned an empty result or failed silently, the API would return a null value for the `create_statement` field.

This null value would then be:
1. Sent to the JavaScript client as JSON `null`
2. Converted to the string `"null"` when appending to FormData
3. Executed as SQL by the local database handler, causing a syntax error

### Root Cause 2: Views Mixed with Tables
The `SHOW TABLES` command returns both base tables AND views. The sync process was treating everything as a table and trying to use `SHOW CREATE TABLE` on views, which doesn't work.

## Files Modified

### 1. `sync_db/api.php`

#### A. Fixed `get_tables` endpoint (line 116-125)
**Changed from:**
```php
$result = $conn->query("SHOW TABLES");
```

**Changed to:**
```php
$result = $conn->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
```

**Why:** This ensures only actual tables are returned, not views. Views are handled separately by the `get_views` endpoint and synced in a different step.

#### B. Added validation for all CREATE statement endpoints:

- `get_table_structure` (line 139-147)
- `get_view_structure` (line 212-220)
- `get_procedure_structure` (line 260-268)
- `get_function_structure` (line 297-305)

**Changes:**
- Check if the query result row exists and contains the expected field
- Validate that the CREATE statement is not empty
- Return proper error responses instead of sending null values

**Example:**
```php
$row = $result->fetch_assoc();
if (!$row || !isset($row['Create Table'])) {
    sendResponse(false, null, 'Failed to retrieve CREATE TABLE statement for table: ' . $table, 500);
}

$createStatement = $row['Create Table'];
if (empty($createStatement)) {
    sendResponse(false, null, 'Empty CREATE TABLE statement for table: ' . $table, 500);
}
```

### 2. `sync_db/sync.js`
**Added client-side validation:**

#### a. SQL Execution Function (line 233-237)
Added validation before sending SQL to the server:
```javascript
if (!sql || sql === 'null' || sql === 'undefined') {
    throw new Error('Invalid SQL statement: SQL cannot be null or undefined');
}
```

#### b. Structure Data Validation (multiple locations)
Added validation after receiving structure data from API:

- **Tables** (line 448-451):
```javascript
if (!structureData || !structureData.create_statement) {
    throw new Error(`Failed to retrieve valid CREATE TABLE statement for table: ${table}`);
}
```

- **Views** (line 518-520)
- **Procedures** (line 540-542)
- **Functions** (line 562-564)

## How the Sync Process Works Now

The sync process properly separates database objects:

1. **Step 3**: Sync Tables (using `get_tables` - returns only BASE TABLEs)
2. **Step 4**: Sync Views (using `get_views` - returns only VIEWs)
3. **Step 5**: Sync Stored Procedures
4. **Step 6**: Sync Functions
5. **Step 7**: Sync Triggers

Each type uses the appropriate SQL command:
- Tables: `SHOW CREATE TABLE`
- Views: `SHOW CREATE VIEW`
- Procedures: `SHOW CREATE PROCEDURE`
- Functions: `SHOW CREATE FUNCTION`

## Benefits
1. **Correct Object Handling**: Tables and views are properly differentiated and handled with appropriate commands
2. **Early Detection**: Problems are caught at the API level with meaningful error messages
3. **Fail-Fast**: Client validates data before attempting to execute SQL
4. **Better Debugging**: Clear error messages indicate exactly what failed and where
5. **Prevents Silent Failures**: No more null values slipping through validation
6. **Complete Replication**: All database objects (tables, views, procedures, functions, triggers) are now properly synced

## Testing
To verify the fix works:

1. **Normal Operation**: Test sync with a valid database - should work as before
2. **Invalid Table**: Try syncing a non-existent table - should show clear error message
3. **Empty Database**: Try syncing an empty database - should handle gracefully

## Related Error Messages
After this fix, you'll see these improved error messages:

- `"Failed to retrieve CREATE TABLE statement for table: <name>"`
- `"Empty CREATE TABLE statement for table: <name>"`
- `"Invalid SQL statement: SQL cannot be null or undefined"`
- `"Failed to retrieve valid CREATE [TABLE|VIEW|PROCEDURE|FUNCTION] statement for [name]"`

These are much more helpful than the generic "syntax error near 'null'".


# 💾 Saved Queries Feature - Setup & Usage Guide

## Overview

The Saved Queries feature allows you to store frequently used SQL queries and reuse them easily. This is perfect for:
- Complex queries you use often
- Report generation queries
- Data analysis queries
- Maintenance queries

## 🚀 Setup Instructions

### Step 1: Create the Database Table

Before using the saved queries feature, you need to create the required database table.

**Option A: Run the setup script directly**
```bash
php setup_saved_queries.php
```

**Option B: Run manually via browser**
Navigate to: `http://your-domain/setup_saved_queries.php`

**Option C: Run SQL directly**
Execute this SQL in your database:
```sql
CREATE TABLE IF NOT EXISTS `saved_queries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `query_name` VARCHAR(255) NOT NULL,
    `query_sql` TEXT NOT NULL,
    `table_name` VARCHAR(255) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_used_at` TIMESTAMP NULL DEFAULT NULL,
    `use_count` INT DEFAULT 0,
    INDEX `idx_table_name` (`table_name`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 📖 How to Use

### Saving a Query

1. **Navigate to Query Builder**: Go to `query.php`
2. **Select a table** (optional, but recommended)
3. **Write your SQL query** in the large text box
4. **Click "💾 Save Query"** button (or the "+" button in the saved queries panel)
5. **Fill in the modal**:
   - **Query Name** (required): Give your query a descriptive name
   - **Description** (optional): Add details about what the query does
   - **SQL Query**: Auto-filled with your current query
6. **Click "💾 Save"**

### Loading a Saved Query

1. **View Saved Queries**: The right panel shows all your saved queries
2. **Click "📂 Load"** on any saved query
3. The query will be loaded into the editor
4. If the query was saved for a specific table, the table selector will automatically switch

### Deleting a Saved Query

1. Find the query in the saved queries panel
2. Click the **🗑️ Delete button**
3. Confirm the deletion

## 🎯 Features

### Smart Organization

- **Table-Specific Queries**: Queries saved for a specific table are shown when you select that table
- **Global Queries**: Queries saved without a table selection are always visible
- **Most Recent First**: Queries are sorted by last used date, then by creation date

### Usage Tracking

- **Use Count**: Track how many times you've used each query
- **Last Used**: See when you last used each query
- **Popular Queries**: Frequently used queries appear at the top

### Query Information Display

Each saved query shows:
- 📋 **Query Name**: The name you gave it
- 🔍 **Preview**: First 50 characters of the SQL
- 📝 **Description**: Optional description (if provided)
- 🏷️ **Table Badge**: Which table it's associated with (if any)
- 📊 **Usage Stats**: How many times it's been used

## 💡 Best Practices

### Naming Conventions

Use clear, descriptive names:
- ✅ Good: "Get Active Users by Registration Date"
- ✅ Good: "Monthly Sales Report"
- ❌ Bad: "Query 1"
- ❌ Bad: "test"

### Descriptions

Add helpful descriptions:
```
Name: "Customer Purchase History"
Description: "Returns last 30 days of purchases for customers with >$100 total spend"
```

### Organization

- **Use table associations**: Save queries for specific tables to keep them organized
- **Global queries**: Save utility queries (like `SHOW TABLES`) without a table association
- **Regular cleanup**: Delete queries you no longer use

## 🗂️ Database Structure

### Table: `saved_queries`

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `query_name` | VARCHAR(255) | Name of the query |
| `query_sql` | TEXT | The actual SQL query |
| `table_name` | VARCHAR(255) | Associated table (optional) |
| `description` | TEXT | Query description (optional) |
| `created_at` | TIMESTAMP | When the query was created |
| `last_used_at` | TIMESTAMP | Last time query was loaded |
| `use_count` | INT | Number of times query was used |

## 🔧 API Endpoints

The following API endpoints are available:

### Save Query
```
POST api.php
action: saveQuery
queryName: string (required)
querySql: string (required)
tableName: string (optional)
description: string (optional)
```

### Get Saved Queries
```
GET api.php?action=getSavedQueries&table={tableName}
- Omit table parameter to get all queries
- Include table parameter to filter by table
```

### Load Saved Query
```
POST api.php
action: loadSavedQuery
queryId: int (required)
- Updates usage statistics automatically
```

### Delete Saved Query
```
POST api.php
action: deleteSavedQuery
queryId: int (required)
```

## 🎨 UI Features

### Three-Column Layout

1. **Left Panel**: Table fields (click to insert into query)
2. **Center Panel**: Query editor with Execute, Clear, and Save buttons
3. **Right Panel**: Saved queries list with Load and Delete actions

### Responsive Design

- **Desktop (>1200px)**: Full 3-column layout
- **Tablet (1024-1200px)**: Narrower panels
- **Mobile (<1024px)**: Single column, stacked vertically

### Visual Feedback

- ✅ Success toasts when saving/loading/deleting
- ⚠️ Warning toasts for validation errors
- ❌ Error toasts for failures
- 🎯 Hover effects on all interactive elements

## 🔒 Security

- **Prepared statements**: All database queries use prepared statements
- **Input validation**: Query names and descriptions are validated
- **SQL injection protection**: Parameters are properly escaped
- **Confirmation dialogs**: Delete operations require confirmation

## 🐛 Troubleshooting

### "No saved queries yet" message

**Solution**: The table might not exist. Run `setup_saved_queries.php`

### Saved queries not loading

**Check**:
1. Database connection in `db_config.php`
2. Table `saved_queries` exists
3. Browser console for JavaScript errors

### Can't save queries

**Check**:
1. Query name is filled in
2. Query text is not empty
3. Database user has INSERT permissions

## 📝 Examples

### Example 1: User Report Query
```sql
-- Name: Active Users Report
-- Description: Get all users who logged in within last 7 days
SELECT 
    user_id, 
    username, 
    email, 
    last_login_date
FROM users 
WHERE last_login_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY last_login_date DESC
LIMIT 100
```

### Example 2: Sales Summary
```sql
-- Name: Monthly Sales Summary
-- Description: Total sales grouped by month
SELECT 
    DATE_FORMAT(order_date, '%Y-%m') as month,
    COUNT(*) as total_orders,
    SUM(order_total) as total_revenue
FROM orders
WHERE order_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
GROUP BY DATE_FORMAT(order_date, '%Y-%m')
ORDER BY month DESC
```

### Example 3: Data Quality Check
```sql
-- Name: Find Duplicate Emails
-- Description: Identify users with duplicate email addresses
SELECT 
    email, 
    COUNT(*) as count
FROM users
GROUP BY email
HAVING count > 1
ORDER BY count DESC
LIMIT 100
```

## 🎯 Future Enhancements (Ideas)

- 📁 Query folders/categories
- 🏷️ Tags for better organization
- 👥 Share queries with team members
- ⭐ Favorite/star queries
- 📋 Export/Import queries
- 🔄 Query history/versions
- 🔍 Search saved queries
- 📊 Query performance tracking

---

**Need Help?** Check the browser console for detailed error messages or review the API response in the Network tab of your browser's developer tools.


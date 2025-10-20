# Page Template System Guide

## Overview
The database manager uses a template system to eliminate code duplication and ensure consistency across all pages. The entire page header (title, controls, navigation menu) and footer are now templates.

## Template Files

All template files are located in the `templates/` directory for better organization.

### 1. `templates/header.php`
Contains the complete page header including:
- Page title with icon
- Custom controls section
- User info and logout button (optional)
- Navigation menu with active highlighting

### 2. `templates/footer.php`
Closes the HTML divs opened by the header template.

## Usage

### Basic Setup in Any Page

Add this code after your `</head>` tag:

```php
<body>
    <?php
    $pageConfig = [
        'id' => 'page_identifier',           // Unique page ID
        'title' => 'Page Title',             // Page title (without icon)
        'icon' => '📊',                      // Icon/emoji
        'show_user_info' => true,            // Show username/logout (default: false)
        'controls_html' => '                 // Custom controls HTML
            <div class="control-group">
                <!-- Your custom controls here -->
            </div>
        '
    ];
    include 'templates/header.php';
    ?>
    
    <!-- Your page content here -->
    
    <script>
        // Your JavaScript here
    </script>

    <?php include 'templates/footer.php'; ?>
</body>
</html>
```

## Configuration Options

### `$pageConfig` Array

| Key | Type | Required | Description |
|-----|------|----------|-------------|
| `id` | string | Yes | Unique identifier for the page (used for active menu highlighting) |
| `title` | string | Yes | Page title displayed in the header (without icon) |
| `icon` | string | Yes | Icon or emoji to display before the title |
| `controls_html` | string | No | Custom HTML for page-specific controls (default: empty) |

**Note:** User info and logout button are always displayed automatically when a user is logged in.

### Available Page IDs

- `database_manager` - Database Manager (database_manager.php) - *First tab*
- `index` - Data Manager (table_data.php)
- `table_structure` - Table Structure (table_structure.php)
- `query` - SQL Query Builder (query.php)

## Examples

### Example 1: Simple Page with Table Selector

```php
<?php
$pageConfig = [
    'id' => 'query',
    'title' => 'SQL Query Builder',
    'icon' => '⚡',
    'controls_html' => '
        <div class="control-group">
            <label for="tableSelect">Select Table:</label>
            <select id="tableSelect">
                <option value="">-- Choose a table --</option>
            </select>
        </div>
    '
];
include 'header_template.php';
?>
```

### Example 2: Page with User Info and Multiple Controls

```php
<?php
$pageConfig = [
    'id' => 'index',
    'title' => 'Database CRUD Manager',
    'icon' => '📊',
    'controls_html' => '
        <div class="control-group">
            <label for="tableSelect">Select Table:</label>
            <select id="tableSelect">
                <option value="">-- Choose a table --</option>
            </select>
        </div>
        <button id="addRecordBtn" style="display: none;">➕ Add New Record</button>
    '
];
include 'header_template.php';
?>
```

### Example 3: Page with Custom Selector

```php
<?php
$pageConfig = [
    'id' => 'database_manager',
    'title' => 'Database Manager',
    'icon' => '🗄️',
    'controls_html' => '
        <div class="control-group">
            <label for="databaseSelect">Current Database:</label>
            <select id="databaseSelect">
                <option value="">-- Loading databases --</option>
            </select>
        </div>
        <button id="refreshBtn">🔄 Refresh</button>
    '
];
include 'header_template.php';
?>
```

## Adding a New Page

To add a new page to the system:

1. **Create your page file** (e.g., `reports.php`)

2. **Add the page to the menu** by editing `templates/header.php`:
```php
$menuItems = [
    // ... existing items ...
    [
        'id' => 'reports',
        'url' => 'reports.php',
        'icon' => '📈',
        'name' => 'Reports'
    ]
];
```

3. **Use the templates in your page**:
```php
<?php
require_once 'login/auth_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Database Manager</title>
    <!-- Include your CSS here -->
</head>
<body>
    <?php
    $pageConfig = [
        'id' => 'reports',
        'title' => 'Reports',
        'icon' => '📈',
        'show_user_info' => false,
        'controls_html' => '<!-- Your controls -->'
    ];
    include 'templates/header.php';
    ?>
    
    <!-- Your content here -->
    
    <script>
        // Your JavaScript
    </script>

    <?php include 'templates/footer.php'; ?>
</body>
</html>
```

## Features

### ✅ Automatic Table Parameter Preservation
When a user selects a table, the template automatically adds `?table=...` to all navigation links (except Database Manager), allowing seamless switching between pages.

### ✅ Active State Highlighting
The current page is automatically highlighted in the navigation menu based on the `id` in `$pageConfig`.

### ✅ Security
All user-supplied values are properly escaped with `htmlspecialchars()` to prevent XSS attacks.

### ✅ Responsive Design
The navigation menu automatically adapts to mobile and desktop layouts using existing CSS.

## Benefits

1. **DRY Principle** - Header and footer defined once, used everywhere
2. **Easy Maintenance** - Update menu in one place, affects all pages
3. **Consistency** - All pages have identical structure and styling
4. **Flexibility** - Each page can have custom controls while maintaining overall structure
5. **Type Safety** - Array structure prevents configuration errors
6. **Reduced Code** - Each page is now ~30-40 lines shorter

## Troubleshooting

### Menu not highlighting correctly?
Make sure the `id` in your `$pageConfig` matches exactly one of the menu item IDs in `templates/header.php`.

### User info not showing?
Ensure the session contains a `username` key. The user info and logout button are displayed automatically when a user is logged in.

### Custom controls not appearing?
Check that your `controls_html` contains valid HTML and is properly escaped if needed.

### Navigation links not preserving table selection?
The template automatically handles this. Make sure you're not overriding the links with JavaScript.


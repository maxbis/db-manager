# Templates Directory

This directory contains reusable PHP templates for the Database Manager application.

## Files

### `header.php`
The main header template that includes:
- Page title with customizable icon
- Controls section (customizable per page)
- User info display and logout button (automatic when logged in)
- Navigation menu with active state highlighting
- Automatic table parameter preservation in links

**Usage:**
```php
<?php
$pageConfig = [
    'id' => 'page_id',
    'title' => 'Page Title',
    'icon' => '📊',
    'controls_html' => '<!-- Custom controls HTML -->'
];
include 'templates/header.php';
?>
```

**Note:** User info and logout button are automatically displayed when a user is logged in.

### `footer.php`
Closes the container divs opened by `header.php`.

**Usage:**
```php
<?php include 'templates/footer.php'; ?>
```

## Benefits

✅ **Single source of truth** - Update menu in one place  
✅ **Consistency** - All pages use identical structure  
✅ **Maintainability** - Easy to add/modify pages  
✅ **DRY principle** - No duplicate code  

## Documentation

For complete documentation, see:
- `/docs/TEMPLATE_GUIDE.md` - Full usage guide
- `/docs/TEMPLATE_BEFORE_AFTER.md` - Before/after comparison

## Adding a New Menu Item

Edit `header.php` and add to the `$menuItems` array:

```php
[
    'id' => 'new_page',
    'url' => 'new_page.php',
    'icon' => '🎯',
    'name' => 'Page Name'
]
```

That's it! All pages automatically get the new menu item.


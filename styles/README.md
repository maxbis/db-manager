# CSS Architecture

## Overview

This directory contains the shared CSS file used across all database manager pages.

## File Structure

- **common.css** - Shared styles used by all pages (database_manager, table_data, table_structure, query, view_fixer)

## Pages Using common.css

- **database_manager.php** - Database and table management
- **table_data.php** - Data manager (CRUD operations)
- **table_structure.php** - Table structure viewer/editor
- **query.php** - SQL query builder
- **view_fixer.php** - View definer fixer tool

## What's in common.css?

### CSS Variables
- Complete Sapphire Nightfall Whisper Color Palette
- All color definitions (primary, success, danger, warning, text, background, border)
- Shadow and overlay definitions

### Global Styles
- Reset styles (`* { margin: 0; padding: 0; box-sizing: border-box; }`)
- Body styling with gradient background and fade-in animation
- Page transition effects

### Layout Components
- `.container` - Main page wrapper
- `.header` - Page header with gradient background
- `.content` - Main content area
- `.controls` - Header control group
- `.control-group` - Individual control groups

### Navigation
- `.nav-menu` - Navigation menu container
- Navigation link styles (default, hover, active states)

### Form Elements
- Styled inputs, selects, textareas, and buttons
- Button variants:
  - `.btn-secondary` - Gray buttons
  - `.btn-danger` - Red/danger buttons
  - `.btn-success` - Green/success buttons
  - `.btn-warning` - Yellow/warning buttons
  - `.btn-primary` - Primary action buttons

### Modal System
- `.modal` - Modal overlay
- `.modal-content` - Modal container
- `.modal-header` - Modal header
- `.modal-body` - Modal body
- `.modal-footer` - Modal footer
- `.modal-close` - Close button

### Form Components
- `.form-group` - Form field container
- Form element styling

### UI Components
- `.empty-state` - Empty state message
- `.loading` - Loading state
- `.spinner` - Loading spinner animation
- `.toast` - Toast notification (with variants: success, error, warning)

### Animations
- `pageLoadFadeIn` - Page load animation
- `fadeIn` - General fade in
- `slideIn` - Modal slide in
- `slideInRight` - Toast slide in
- `spin` - Spinner rotation

### Responsive Design
- Mobile-first responsive styles
- Breakpoint: 768px for mobile devices
- Adjustments for header, navigation, controls, and content

## Page-Specific Styles

Each page keeps its own unique styles in the `<style>` tag:

### database_manager.php
- Actions dropdown
- Column builder and drag-and-drop
- Dashboard grid and cards
- Database list and items
- Table list and items
- Statistics grid
- Size bars and badges
- Searchbar

### table_data.php
- Table wrapper and data tables
- Filter rows
- Pagination
- Confirmation dialog

### table_structure.php
- Table info panel
- Structure table
- Field attributes and badges
- Form rows and checkbox groups
- Info tooltips
- Stats grid
- Back link

### query.php
- Query layout (3-column grid)
- Fields panel
- Saved queries panel
- Query input and actions
- Results section and table
- Query examples
- Custom button styles (btn-load, btn-delete-saved, btn-save-query)

### view_fixer.php
- Info box styling
- Stats grid
- Action bar
- Table wrapper and data tables
- Status badges (ok, error)

## Usage

To use the common styles in a page:

```html
<head>
    <link rel="stylesheet" href="styles/common.css">
    <style>
        /* Page-specific styles here */
    </style>
</head>
```

## Benefits of This Architecture

1. **DRY Principle** - No code duplication across pages
2. **Consistency** - All pages use the same color palette and styling
3. **Maintainability** - Update common styles in one place
4. **Performance** - Browser caches common.css across all pages
5. **Clarity** - Easy to see which styles are shared vs. page-specific

## Updating Styles

- **Common changes** - Edit `styles/common.css`
- **Page-specific changes** - Edit the `<style>` section in the respective PHP file
- **New page** - Link to `common.css` and add page-specific styles as needed


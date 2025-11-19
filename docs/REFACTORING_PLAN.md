# Database Manager - Complete Refactoring Plan

**Project:** db-manager Codebase Optimization
**Date:** 2025-10-20
**Estimated Time:** 6-8 hours
**Expected Line Reduction:** 55% (10,335 → 4,670 lines)

---

## Executive Summary

This plan refactors the Database Manager application to eliminate code duplication, separate concerns, and improve maintainability. The primary goals are:

1. Extract 2,000+ lines of duplicate CSS into shared files
2. Extract 1,500+ lines of duplicate JavaScript into shared files  
3. Remove duplicate file (database_manager2.php)
4. Reduce each PHP file from 1,300-2,300 lines to 200-400 lines
5. Maintain 100% functionality throughout

---

## Pre-Refactoring Checklist

- [ ] Create Git backup branch: `git checkout -b refactor-backup`
- [ ] Commit current state: `git commit -am "Pre-refactoring checkpoint"`
- [ ] Take note of current working features for post-refactoring testing
- [ ] Ensure local development environment is running
- [ ] Open browser dev tools for console monitoring

---

## Phase 1: CSS Extraction (2-3 hours)

### 1.1 Create Directory Structure

Create the following folders:
```
mkdir -p assets/css/pages
```

Expected result:
```
assets/
  css/
    pages/
```

### 1.2 Extract CSS Variables

**Source:** database_manager.php lines 15-100

**Action:** Create `assets/css/variables.css`

**Content to extract:**
```css
/**
 * CSS Variables - Sapphire Nightfall Whisper Theme
 * Centralized color palette and design tokens
 * Extracted from: database_manager.php
 */

:root {
    /* Sapphire Nightfall Whisper Color Palette */
    --color-sapphire-bright: #0474C4;
    --color-sapphire-muted: #5379AE;
    --color-sapphire-dark: #2C444C;
    --color-sapphire-light: #A8C4EC;
    --color-sapphire-rich: #06457F;
    --color-sapphire-navy: #262B40;
    
    /* [EXTRACT ALL CSS VARIABLES FROM :root BLOCK] */
}
```

**Files to extract from:**
- database_manager.php (lines 15-100)
- Same variables appear in: database_manager2.php, table_data.php, query.php, table_structure.php

**Verification:** All variables start with `--` and are identical across files

### 1.3 Extract Base Styles

**Action:** Create `assets/css/base.css`

**Content to extract:**

From database_manager.php lines 102-680:
- Reset styles (*, body)
- Typography defaults
- Container layouts
- Animation keyframes (@keyframes pageLoadFadeIn, spin, fadeIn, slideIn, slideInRight)
- Loading spinner styles

**Key sections:**
```css
/**
 * Base Styles - Global Layout & Typography
 * Extracted from: multiple PHP files
 */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, var(--color-bg-body-start) 0%, var(--color-bg-body-end) 100%);
    /* ... rest of body styles */
}

/* [EXTRACT ALL BASE STYLES] */
```

### 1.4 Extract Component Styles

**Action:** Create `assets/css/components.css`

**Components to extract from database_manager.php:**

Lines 133-168: `.container`, `.header`, `.controls`
Lines 164-203: `.nav-menu` and all navigation styles
Lines 205-250: `select`, `button`, and form elements
Lines 267-317: `.btn-danger`, `.btn-success`, `.btn-warning`, `.btn-secondary`
Lines 298-316: `.actions-dropdown` and dropdown menu
Lines 318-328: `.column-row`, `.drag-handle` (if present)
Lines 342-407: `.dashboard-grid`, `.dashboard-card`, `.stat-item`
Lines 409-583: `.database-list`, `.database-item`, `.database-size-*`
Lines 585-661: `.table-list`, `.table-item`, `.table-actions`
Lines 664-721: `.empty-state`, `.loading`, `.spinner`
Lines 724-885: `.modal` and all modal styles
Lines 887-928: `.toast` notification styles
Lines 930-943: `.searchbar`, `.badge-current`, `.database-item.active`

**Structure:**
```css
/**
 * Reusable Components
 * Button styles, modals, tables, forms, etc.
 */

/* Header Component */
.header { /* ... */ }

/* Navigation Component */
.nav-menu { /* ... */ }

/* Button Components */
button { /* ... */ }
.btn-secondary { /* ... */ }
.btn-danger { /* ... */ }
.btn-success { /* ... */ }
.btn-warning { /* ... */ }

/* Modal Component */
.modal { /* ... */ }

/* Toast Component */
.toast { /* ... */ }

/* Table Components */
.table-list { /* ... */ }

/* [EXTRACT ALL REUSABLE COMPONENTS] */
```

### 1.5 Extract Page-Specific Styles

**Action:** Create three page-specific CSS files

**File 1:** `assets/css/pages/database.css`
- Column builder styles (lines 318-328 from database_manager.php)
- Database-specific layout overrides
- Split actions dropdown (lines 932-943)

**File 2:** `assets/css/pages/table.css`
- Table structure specific styles from table_structure.php
- Field attributes and badges (lines 318-381)
- Structure table wrapper

**File 3:** `assets/css/pages/query.css`
- Query layout (lines 271-287 from query.php)
- Fields panel (lines 279-324)
- Saved queries panel (lines 326-434)
- Query input wrapper (lines 442-465)
- Query examples box (lines 642-698)

### 1.6 Update templates/header.php

**Current location:** Line 134 (before closing `</head>`)

**Add before existing `</head>` tag:**
```php
    <!-- CSS Files - Refactored Architecture -->
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <?php if (isset($pageConfig['page_css'])): ?>
    <link rel="stylesheet" href="assets/css/pages/<?php echo $pageConfig['page_css']; ?>.css">
    <?php endif; ?>
</head>
```

### 1.7 Update Page Configuration Arrays

**File:** database_manager.php (line 1003-1017)

**Change from:**
```php
$pageConfig = [
    'id' => 'database_manager',
    'title' => 'Database Manager',
    'icon' => '🗄️',
    'controls_html' => '...'
];
```

**Change to:**
```php
$pageConfig = [
    'id' => 'database_manager',
    'title' => 'Database Manager',
    'icon' => '🗄️',
    'page_css' => 'database',  // NEW: Load database.css
    'controls_html' => '...'
];
```

**Apply same pattern to:**
- table_data.php → `'page_css' => 'data'` (will need to create data.css if unique styles exist)
- table_structure.php → `'page_css' => 'table'`
- query.php → `'page_css' => 'query'`

### 1.8 Remove Inline CSS from PHP Files

**Files to modify:**
- database_manager.php: Remove lines 15-999 (`<style>...</style>`)
- database_manager2.php: Remove lines 15-999 (will delete this file later)
- table_data.php: Remove lines 14-718 (`<style>...</style>`)
- query.php: Remove lines 14-901 (`<style>...</style>`)
- table_structure.php: Remove lines 14-774 (`<style>...</style>`)

**Result:** Each file reduces by 500-900 lines

### 1.9 Phase 1 Testing

**Test each page:**
- [ ] database_manager.php loads with correct styling
- [ ] table_data.php loads with correct styling
- [ ] query.php loads with correct styling
- [ ] table_structure.php loads with correct styling
- [ ] All buttons maintain hover effects
- [ ] Modals open/close with correct styling
- [ ] Toast notifications appear correctly
- [ ] Navigation menu highlights active page
- [ ] Responsive behavior works (test at 768px, 375px)

**Check browser console:**
- [ ] No 404 errors for CSS files
- [ ] No CSS warnings

**Git checkpoint:**
```bash
git add assets/css/
git add templates/header.php
git add database_manager.php table_data.php query.php table_structure.php
git commit -m "Phase 1: Extract CSS to separate files"
```

---

## Phase 2: JavaScript Extraction (3-4 hours)

### 2.1 Create JavaScript Directory Structure

```bash
mkdir -p assets/js/components
mkdir -p assets/js/pages
```

Expected result:
```
assets/
  js/
    components/
    pages/
```

### 2.2 Extract Utility Functions

**Action:** Create `assets/js/utils.js`

**Functions to extract:**

From database_manager.php (lines 1489-1492, 2287-2294):
```javascript
/**
 * Utility Functions
 * Common helper functions used across all pages
 */

// Debounce helper
function debounce(fn, delay = 250) {
    let t;
    return function(...args) {
        clearTimeout(t);
        t = setTimeout(() => fn.apply(this, args), delay);
    };
}

// Format bytes to human readable
function formatBytes(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Escape HTML
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Smooth page transitions
function initPageTransitions() {
    $('.nav-link').click(function(e) {
        const href = $(this).attr('href');
        if ($(this).hasClass('active')) {
            e.preventDefault();
            return;
        }
        e.preventDefault();
        $('body').addClass('page-transitioning');
        setTimeout(function() {
            window.location.href = href;
        }, 200);
    });
}

// Initialize on document ready
$(document).ready(function() {
    initPageTransitions();
});
```

### 2.3 Extract Toast Component

**Action:** Create `assets/js/components/toast.js`

**Extract from:** All PHP files contain identical showToast function

From database_manager.php (lines 2274-2285):
```javascript
/**
 * Toast Notification Component
 * Displays temporary notification messages
 */

function showToast(message, type = 'success') {
    const toast = $('#toast');
    toast.text(message);
    toast.removeClass('success error warning');
    toast.addClass(type);
    toast.addClass('active');
    
    setTimeout(function() {
        toast.removeClass('active');
    }, 4000);
}
```

### 2.4 Extract Modal Component

**Action:** Create `assets/js/components/modal.js`

**Extract modal functions from all files:**

```javascript
/**
 * Modal Component
 * Reusable modal open/close functionality
 */

function openModal(modalId) {
    $('#' + modalId).addClass('active');
}

function closeModal(modalId) {
    $('#' + modalId).removeClass('active');
}

// Close modal on outside click
$(document).on('click', function(e) {
    if ($(e.target).hasClass('modal')) {
        closeModal($(e.target).attr('id'));
    }
});
```

### 2.5 Extract API Wrapper

**Action:** Create `assets/js/api.js`

**Create standardized API calling wrapper:**

```javascript
/**
 * API Wrapper
 * Centralized AJAX calls to api.php
 */

const API = {
    // Helper for GET requests
    get: function(action, params = {}) {
        const queryString = new URLSearchParams({action, ...params}).toString();
        return $.ajax({
            url: `api.php?${queryString}`,
            method: 'GET',
            dataType: 'json'
        });
    },
    
    // Helper for POST requests
    post: function(action, data = {}) {
        return $.ajax({
            url: 'api.php',
            method: 'POST',
            data: {action, ...data},
            dataType: 'json'
        });
    },
    
    // Database operations
    getDatabases: function() {
        return this.get('getDatabases');
    },
    
    createDatabase: function(name, charset, collation) {
        return this.post('createDatabase', {name, charset, collation});
    },
    
    deleteDatabase: function(name) {
        return this.post('deleteDatabase', {name});
    },
    
    // Table operations
    getTables: function(database) {
        return this.get('getTables', {database});
    },
    
    getTableInfo: function(table) {
        return this.get('getTableInfo', {table});
    },
    
    createTable: function(database, name, columns, engine) {
        return this.post('createTable', {database, name, columns, engine});
    },
    
    deleteTable: function(database, name) {
        return this.post('deleteTable', {database, name});
    },
    
    // Record operations
    getRecords: function(table, offset, limit, sortColumn, sortOrder, filters) {
        return this.get('getRecords', {
            table, offset, limit, sortColumn, sortOrder,
            filters: JSON.stringify(filters)
        });
    },
    
    getRecord: function(table, primaryKey, primaryValue) {
        return this.post('getRecord', {table, primaryKey, primaryValue});
    },
    
    insertRecord: function(table, data) {
        return this.post('insertRecord', {table, data: JSON.stringify(data)});
    },
    
    updateRecord: function(table, primaryKey, primaryValue, data) {
        return this.post('updateRecord', {
            table, primaryKey, primaryValue, 
            data: JSON.stringify(data)
        });
    },
    
    deleteRecord: function(table, primaryKey, primaryValue) {
        return this.post('deleteRecord', {table, primaryKey, primaryValue});
    },
    
    // Query operations
    executeQuery: function(query) {
        return this.post('executeQuery', {query});
    },
    
    // Column operations
    addColumn: function(table, data) {
        return this.post('addColumn', {table, data: JSON.stringify(data)});
    },
    
    updateColumn: function(table, oldName, data) {
        return this.post('updateColumn', {table, oldName, data: JSON.stringify(data)});
    },
    
    deleteColumn: function(table, columnName) {
        return this.post('deleteColumn', {table, columnName});
    },
    
    // Database management
    setCurrentDatabase: function(database) {
        return this.post('setCurrentDatabase', {database});
    },
    
    exportDatabase: function(name, includeCreateDatabase, dataOnly) {
        return this.post('exportDatabase', {name, includeCreateDatabase, dataOnly});
    },
    
    importDatabase: function(formData) {
        return $.ajax({
            url: 'api.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json'
        });
    }
};
```

### 2.6 Extract Database Page JavaScript

**Action:** Create `assets/js/pages/database.js`

**Extract from:** database_manager.php lines 1309-2313

**Content:** All JavaScript specific to database_manager.php including:
- Global state variables (currentDatabase, databases, tables, selectedTable, etc.)
- Document ready initialization
- loadDatabases() function
- loadTables() function
- displayDatabases() function
- displayTables() function
- addColumnRow() function
- Database selection handlers
- Modal handlers (create, delete, export, import)
- All database-specific UI logic

**Note:** Replace inline AJAX calls with API wrapper:
```javascript
// OLD:
$.ajax({
    url: 'api.php?action=getDatabases',
    method: 'GET',
    dataType: 'json',
    success: function(response) { /* ... */ }
});

// NEW:
API.getDatabases()
    .then(function(response) { /* ... */ })
    .catch(function(xhr) { /* ... */ });
```

### 2.7 Extract Index Page JavaScript

**Action:** Create `assets/js/pages/index.js`

**Extract from:** table_data.php lines 803-1437

**Content:**
- Global state (currentTable, tableInfo, currentOffset, etc.)
- loadTables() function
- loadTableInfo() function
- loadRecords() function
- displayRecords() function
- Modal handlers for record editing
- Pagination logic
- Filter and sort handlers

### 2.8 Extract Query Page JavaScript

**Action:** Create `assets/js/pages/query.js`

**Extract from:** query.php lines 1031-1733

**Content:**
- Global state (currentTable, tableInfo)
- loadTables() function
- loadTableInfo() function
- executeQuery() function
- displayResults() function
- Saved queries management (localStorage-based)
- Query save/load/delete functions
- Import/export query functions

### 2.9 Extract Table Structure Page JavaScript

**Action:** Create `assets/js/pages/table-structure.js`

**Extract from:** table_structure.php lines 856-1303

**Content:**
- Global state (currentTable, tableInfo, currentEditColumn)
- loadTables() function
- loadTableStructure() function
- displayTableInfo() function
- displayStructureTable() function
- Column modal handlers
- Add/Edit/Delete column functions

### 2.10 Update templates/footer.php

**Current:** footer.php (lines 1-14) just closes divs

**New footer.php:**
```php
<?php
/**
 * Reusable Footer Template
 * 
 * Closes the divs opened by templates/header.php
 * Includes JavaScript files
 * 
 * Usage:
 * include 'templates/footer.php';
 */
?>
    </div><!-- End .content -->
</div><!-- End .container -->

<!-- JavaScript Files - Refactored Architecture -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="assets/js/utils.js"></script>
<script src="assets/js/api.js"></script>
<script src="assets/js/components/modal.js"></script>
<script src="assets/js/components/toast.js"></script>
<?php if (isset($pageConfig['page_js'])): ?>
<script src="assets/js/pages/<?php echo $pageConfig['page_js']; ?>.js"></script>
<?php endif; ?>
```

### 2.11 Update Page Configuration Arrays

**Add page_js parameter to each file:**

database_manager.php:
```php
$pageConfig = [
    'id' => 'database_manager',
    'title' => 'Database Manager',
    'icon' => '🗄️',
    'page_css' => 'database',
    'page_js' => 'database',  // NEW
    'controls_html' => '...'
];
```

table_data.php:
```php
$pageConfig = [
    'id' => 'index',
    'title' => 'Database CRUD Manager',
    'icon' => '📊',
    'page_css' => 'index',
    'page_js' => 'index',  // NEW
    'controls_html' => '...'
];
```

query.php:
```php
$pageConfig = [
    'id' => 'query',
    'title' => 'SQL Query Builder',
    'icon' => '⚡',
    'page_css' => 'query',
    'page_js' => 'query',  // NEW
    'controls_html' => '...'
];
```

table_structure.php:
```php
$pageConfig = [
    'id' => 'table_structure',
    'title' => 'Table Structure Viewer/Editor',
    'icon' => '🔍',
    'page_css' => 'table',
    'page_js' => 'table-structure',  // NEW
    'controls_html' => '...'
];
```

### 2.12 Remove Inline JavaScript from PHP Files

**Files to modify:**

1. **database_manager.php:**
   - Remove lines 1308-2314 (entire `<script>` block)
   - Keep only the jQuery CDN include temporarily in footer

2. **table_data.php:**
   - Remove lines 802-1437 (entire `<script>` block)

3. **query.php:**
   - Remove lines 1030-1733 (entire `<script>` block)

4. **table_structure.php:**
   - Remove lines 855-1303 (entire `<script>` block)

**Result:** Each file reduces by another 400-800 lines

### 2.13 Phase 2 Testing

**Functional tests for each page:**

**database_manager.php:**
- [ ] Database list loads on page load
- [ ] Can select a database from dropdown
- [ ] Statistics update when database selected
- [ ] Create Database modal opens and works
- [ ] Create Table modal opens and works
- [ ] Column builder add/remove works
- [ ] Drag and drop column reordering works
- [ ] Export database works
- [ ] Import database works
- [ ] Export all databases works
- [ ] Delete database confirmation and execution works
- [ ] Delete table confirmation and execution works
- [ ] Search and sort databases works
- [ ] Refresh button works

**table_data.php:**
- [ ] Table list loads
- [ ] Table selection works
- [ ] Records display in table
- [ ] Pagination works (prev/next)
- [ ] Sorting by column works
- [ ] Filtering by column works
- [ ] Click on row opens edit modal
- [ ] Add new record button works
- [ ] Edit record saves correctly
- [ ] Delete record works with confirmation
- [ ] Form validation works
- [ ] Different field types render correctly

**query.php:**
- [ ] Table list loads
- [ ] Field list displays when table selected
- [ ] Click on field inserts into query
- [ ] Execute query works
- [ ] Results display correctly
- [ ] Save query modal opens
- [ ] Save query to localStorage works
- [ ] Saved queries list displays
- [ ] Load saved query works
- [ ] Delete saved query works
- [ ] Export queries to JSON works
- [ ] Import queries from JSON works
- [ ] Query examples box shows/hides
- [ ] Auto-save query to localStorage works

**table_structure.php:**
- [ ] Table list loads
- [ ] Table structure displays when selected
- [ ] Statistics cards show correct counts
- [ ] Click on column row opens edit modal
- [ ] Add column button works
- [ ] Edit column saves correctly
- [ ] Delete column works with confirmation
- [ ] Column attributes display correctly
- [ ] Info tooltip shows on hover

**Check browser console:**
- [ ] No JavaScript errors on any page
- [ ] No 404 errors for JS files
- [ ] API calls complete successfully
- [ ] Toast notifications work on all pages

**Git checkpoint:**
```bash
git add assets/js/
git add templates/footer.php
git add database_manager.php table_data.php query.php table_structure.php
git commit -m "Phase 2: Extract JavaScript to separate files"
```

---

## Phase 3: Remove Duplicate Files (30 minutes)

### 3.1 Compare Files

**Action:** Compare database_manager.php vs database_manager2.php

**Focus areas:**
- Line count differences
- Feature differences in JavaScript
- CSS differences
- Any unique functionality

### 3.2 Document Differences

**Create comparison notes:**
```
Database Manager Comparison:
- database_manager.php: 2,319 lines
- database_manager2.php: 2,162 lines

Key differences:
1. database_manager.php has column builder with drag-drop
2. database_manager2.php has simpler textarea-based column input
3. [Document any other significant differences]

Decision: Keep database_manager.php (more features)
```

### 3.3 Merge Any Unique Features (if needed)

If database_manager2.php has any superior implementations:
- Identify the specific feature
- Copy it to database_manager.php
- Test that it works
- Document the merge

**Expected:** database_manager.php already has more features, likely no merge needed

### 3.4 Delete Duplicate File

```bash
git rm database_manager2.php
```

### 3.5 Update Navigation References

**Check these files for references to database_manager2.php:**
- templates/header.php (navigation menu) - Should already only reference database_manager.php
- Any documentation files
- README if exists

**Expected:** No references found (database_manager2.php doesn't appear in nav)

### 3.6 Phase 3 Testing

- [ ] Verify database_manager.php still works after any merges
- [ ] Confirm no broken links anywhere
- [ ] Check that navigation works from all pages

**Git checkpoint:**
```bash
git commit -m "Phase 3: Remove duplicate database_manager2.php file"
```

---

## Phase 4: Final Cleanup & Optimization (1 hour)

### 4.1 Remove jQuery CDN from Individual Files

Since footer.php now includes jQuery, remove duplicate includes from:
- Any remaining `<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>` in PHP files

### 4.2 Clean Up Empty Lines

Remove excessive empty lines (more than 2 consecutive) from:
- All PHP files
- All CSS files
- All JS files

### 4.3 Add File Headers

Ensure all new files have proper headers:

**CSS files:**
```css
/**
 * [Filename]
 * Database Manager - Refactored Architecture
 * 
 * Description: [Purpose of this file]
 * Dependencies: variables.css
 */
```

**JS files:**
```javascript
/**
 * [Filename]
 * Database Manager - Refactored Architecture
 * 
 * Description: [Purpose of this file]
 * Dependencies: jQuery, utils.js, api.js
 */
```

### 4.4 Update Documentation

If a README.md exists, update it to reflect new structure:
```markdown
## Project Structure

### CSS Files
- `assets/css/variables.css` - CSS custom properties and theme colors
- `assets/css/base.css` - Base styles and typography
- `assets/css/components.css` - Reusable UI components
- `assets/css/pages/` - Page-specific styles

### JavaScript Files
- `assets/js/utils.js` - Utility functions
- `assets/js/api.js` - API wrapper for backend calls
- `assets/js/components/` - Reusable JavaScript components
- `assets/js/pages/` - Page-specific JavaScript
```

### 4.5 Create .gitignore (if doesn't exist)

```
# Development files
.DS_Store
Thumbs.db
*.log

# Editor files
.vscode/
.idea/
*.swp
*.swo

# Temporary files
tmp/
temp/

# Do not ignore assets (we need these)
!assets/
```

---

## Phase 5: Comprehensive Testing (1 hour)

### 5.1 Browser Testing

**Test in multiple browsers:**
- [ ] Chrome/Edge (latest)
- [ ] Firefox (latest)
- [ ] Safari (if available)

**For each browser, test all pages:**
- [ ] Page loads without errors
- [ ] All styles render correctly
- [ ] All JavaScript functions work
- [ ] Console shows no errors

### 5.2 Responsive Testing

**Test at different viewport sizes:**
- [ ] Desktop: 1920x1080
- [ ] Laptop: 1366x768
- [ ] Tablet: 768x1024
- [ ] Mobile: 375x667

**Check responsive behavior:**
- [ ] Navigation collapses on mobile
- [ ] Tables are scrollable on small screens
- [ ] Modals fit within viewport
- [ ] Forms are usable on mobile

### 5.3 Feature Testing Matrix

Create a testing checklist covering all features:

**Database Management:**
- [ ] List all databases
- [ ] Create new database
- [ ] Delete database (with confirmation)
- [ ] Export single database
- [ ] Export all databases
- [ ] Import database
- [ ] Select database from dropdown
- [ ] Search databases
- [ ] Sort databases (name, size, tables)

**Table Management:**
- [ ] List tables in selected database
- [ ] Create new table with columns
- [ ] Delete table (with confirmation)
- [ ] View table structure
- [ ] Add column to table
- [ ] Edit column properties
- [ ] Delete column
- [ ] Navigate between database manager and table pages

**Data Management:**
- [ ] View records in table
- [ ] Add new record
- [ ] Edit existing record
- [ ] Delete record (with confirmation)
- [ ] Sort records by column
- [ ] Filter records by column
- [ ] Paginate through records
- [ ] Handle different field types (text, number, date, enum, etc.)

**Query Builder:**
- [ ] Execute SELECT queries
- [ ] Execute INSERT queries
- [ ] Execute UPDATE queries
- [ ] Execute DELETE queries
- [ ] View query results in table
- [ ] Save query with name and description
- [ ] Load saved query
- [ ] Delete saved query
- [ ] Export queries to JSON
- [ ] Import queries from JSON
- [ ] Insert field names into query
- [ ] Auto-save current query

**Authentication (DO NOT TEST if this breaks existing functionality):**
- [ ] Login page works
- [ ] Logout works
- [ ] Remember me works
- [ ] IP authorization works

### 5.4 Performance Check

**Monitor performance:**
- [ ] Page load time < 2 seconds
- [ ] No blocking resources
- [ ] CSS and JS files load correctly
- [ ] API calls complete in reasonable time
- [ ] No memory leaks (check with dev tools)

### 5.5 Console Audit

**Check browser console for:**
- [ ] No JavaScript errors
- [ ] No CSS errors or warnings
- [ ] No 404 errors for missing files
- [ ] No CORS errors
- [ ] No mixed content warnings

---

## Post-Refactoring Verification

### Line Count Comparison

**Before refactoring:**
```
database_manager.php:    2,319 lines
database_manager2.php:   2,162 lines (deleted)
table_data.php:          1,443 lines
query.php:               1,740 lines
table_structure.php:     1,308 lines
api.php:                 1,363 lines (not modified)
templates/header.php:      134 lines
templates/footer.php:       14 lines
─────────────────────────────────────
TOTAL:                  10,483 lines
```

**After refactoring (expected):**
```
database_manager.php:      ~400 lines
table_data.php:            ~300 lines
query.php:                 ~350 lines
table_structure.php:       ~280 lines
api.php:                 1,363 lines (unchanged)
templates/header.php:      ~145 lines
templates/footer.php:       ~30 lines

assets/css/variables.css:  ~100 lines
assets/css/base.css:       ~300 lines
assets/css/components.css: ~500 lines
assets/css/pages/*.css:    ~400 lines (total)

assets/js/utils.js:        ~100 lines
assets/js/api.js:          ~200 lines
assets/js/components/*.js: ~100 lines (total)
assets/js/pages/*.js:    ~1,200 lines (total)
─────────────────────────────────────
TOTAL:                   ~5,268 lines

REDUCTION: 49.7% (~5,215 lines removed)
```

### Final Checklist

- [ ] All duplicate code eliminated
- [ ] CSS properly organized in separate files
- [ ] JavaScript properly organized in separate files
- [ ] All pages load and function correctly
- [ ] No console errors
- [ ] Responsive design works
- [ ] All features tested and working
- [ ] Git history clean with meaningful commits
- [ ] Documentation updated

### Rollback Plan (if needed)

If critical issues are discovered:
```bash
# Return to pre-refactoring state
git checkout refactor-backup
git branch -D main
git checkout -b main
```

Or keep changes but revert specific commits:
```bash
# Revert Phase 2 if JS issues
git revert [phase-2-commit-hash]

# Revert Phase 1 if CSS issues
git revert [phase-1-commit-hash]
```

---

## Success Metrics

**Quantitative:**
- [x] 50%+ reduction in total lines of code
- [x] Zero duplicate files
- [x] CSS extracted to < 5 files
- [x] JavaScript extracted to < 10 files
- [x] Each PHP file < 500 lines

**Qualitative:**
- [x] Easier to maintain (changes in one place)
- [x] Better separation of concerns
- [x] Consistent code organization
- [x] Improved code reusability
- [x] No loss of functionality

---

## Timeline Summary

**Phase 1 (CSS):** 2-3 hours
**Phase 2 (JavaScript):** 3-4 hours
**Phase 3 (Duplicates):** 30 minutes
**Phase 4 (Cleanup):** 1 hour
**Phase 5 (Testing):** 1 hour

**Total Estimated Time:** 6-8 hours

**Recommended approach:** Complete one phase per session, test thoroughly, then commit before starting next phase.

---

## Final Notes

1. **Keep backups:** The `refactor-backup` branch is your safety net
2. **Test frequently:** After each phase, test thoroughly before moving on
3. **Commit often:** Small, focused commits make it easier to track changes
4. **Document issues:** Note any problems encountered for future reference
5. **Don't rush:** Quality is more important than speed

**Files to NEVER modify:**
- `login/*` - Authentication system is working well
- `docs/*` - Documentation is complete
- `db_connection.php` - Database connection management
- `api.php` - Backend API (can be refactored later in future phase)

---

## Appendix: Quick Reference

### File Paths Reference
```
OLD (monolithic):
database_manager.php (2,319 lines)
database_manager2.php (2,162 lines)
table_data.php (1,443 lines)
query.php (1,740 lines)
table_structure.php (1,308 lines)

NEW (organized):
database_manager.php (~400 lines HTML)
table_data.php (~300 lines HTML)
query.php (~350 lines HTML)
table_structure.php (~280 lines HTML)

assets/css/variables.css
assets/css/base.css
assets/css/components.css
assets/css/pages/database.css
assets/css/pages/table.css
assets/css/pages/query.css

assets/js/utils.js
assets/js/api.js
assets/js/components/modal.js
assets/js/components/toast.js
assets/js/pages/database.js
assets/js/pages/index.js
assets/js/pages/query.js
assets/js/pages/table-structure.js
```

### Git Commands Reference
```bash
# Create backup branch
git checkout -b refactor-backup

# After Phase 1
git add assets/css/ templates/header.php *.php
git commit -m "Phase 1: Extract CSS to separate files"

# After Phase 2
git add assets/js/ templates/footer.php *.php
git commit -m "Phase 2: Extract JavaScript to separate files"

# After Phase 3
git rm database_manager2.php
git commit -m "Phase 3: Remove duplicate database_manager2.php"

# Final commit
git add -A
git commit -m "Refactoring complete: 50% code reduction achieved"

# Push changes
git push origin main
```

---

**Document Version:** 1.0  
**Last Updated:** 2025-10-20  
**Status:** Ready for execution


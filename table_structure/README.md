# Table Structure Module

## Overview
The **Table Structure** module provides a comprehensive interface for viewing and modifying database table structures. It offers a visual representation of table columns with their properties and generates SQL queries for structural modifications.

---

## 📁 File Structure

```
table_structure/
├── index.php              (79 lines)   - Clean entry point
├── table_structure.css    (297 lines)  - All styling
├── table_structure.js     (635 lines)  - All logic
├── modals.php             (16 lines)   - Modal dialogs
└── README.md              - This documentation
```

**Total:** 1,027 lines across 4 files (previously 1,023 lines in 1 monolithic file)

---

## ✨ Features

### 📊 Table Structure Viewing
- **Column Overview:** View all columns with their properties
- **Visual Attributes:** Color-coded badges for column attributes
- **Statistics Dashboard:** Quick stats (total columns, primary keys, nullable fields, auto-increment)
- **Type Detection:** Automatically detects and displays table vs view
- **View Support:** Special handling for database VIEWs with source viewing

### ✏️ Column Editing
- **Add New Columns:** Create new columns with positioning options
- **Modify Existing:** Edit column properties (type, default, attributes)
- **Visual Editor:** User-friendly form interface
- **SQL Generation:** Generates ALTER TABLE statements instead of direct execution
- **Position Control:** Place new columns at beginning, end, or after specific columns

### 🔧 Column Attributes
- **Primary Key:** Mark columns as primary keys
- **Unique:** Set unique constraints
- **Auto Increment:** Enable auto-increment for numeric columns
- **Nullable:** Control NULL/NOT NULL
- **Default Values:** Set default column values
- **Extra Attributes:** Advanced MySQL attributes (comments, character sets, etc.)

### 👁️ View Source Viewing
- **View Definition:** See CREATE VIEW statements
- **Copy to Clipboard:** Easily copy SQL for reuse
- **Formatted Display:** Syntax-highlighted view source

### 🎯 Safety Features
- **SQL Preview:** Never executes directly - generates SQL for review
- **Query Builder Integration:** Redirects to query builder for execution
- **Confirmation Dialogs:** Prompts before generating delete statements
- **View Protection:** Disables editing for database VIEWs

---

## 🎨 User Interface

### Main Components

#### 1. **Table Selection**
```html
<select id="tableSelect">
    <option value="">-- Choose a table --</option>
    <!-- Populated dynamically from API -->
</select>
```
- Dropdown showing all tables and views
- Views marked with 👁️ icon
- URL parameter support (`?table=tablename`)

#### 2. **Statistics Dashboard**
Four metric cards showing:
- Total Columns
- Primary Keys
- Nullable Fields
- Auto Increment columns

#### 3. **Structure Table**
Displays column information:
- Field name
- Data type (monospace badge)
- Null allowance
- Key type (PRI, UNI, MUL)
- Default value
- Extra attributes
- Visual attribute badges

#### 4. **Attribute Badges**
Color-coded badges indicate column properties:
- 🟢 **PRIMARY** (green) - Primary key
- 🔵 **UNIQUE** (blue) - Unique constraint
- 🟣 **INDEX** (purple) - Indexed column
- 🔴 **NOT NULL** (red) - Required field
- 🟡 **AUTO_INCREMENT** (yellow) - Auto-incrementing
- Dimmed badges show inactive attributes

---

## 🔌 API Integration

### API Endpoints Used

#### Get Tables
```javascript
GET ../api/?action=getTables
Response: { success: true, tables: [...] }
```

#### Get Table Info
```javascript
GET ../api/?action=getTableInfo&table=tablename
Response: { 
    success: true, 
    columns: [...],
    tableType: "BASE TABLE",
    primaryKey: "id",
    isView: false
}
```

#### Get View Source
```javascript
GET ../api/?action=getViewSource&table=viewname
Response: { 
    success: true, 
    viewName: "...",
    createStatement: "CREATE VIEW ..."
}
```

---

## 💻 JavaScript Functions

### Core Functions

| Function | Purpose |
|----------|---------|
| `loadTables()` | Load all tables/views into dropdown |
| `loadTableStructure()` | Fetch and display table structure |
| `displayTableInfo()` | Render table metadata and stats |
| `displayStructureTable()` | Populate structure table with columns |
| `openAddColumnModal()` | Open modal for new column |
| `openEditColumnModal()` | Open modal for editing column |
| `buildColumnForm()` | Generate dynamic form fields |
| `saveColumn()` | Generate SQL and redirect |
| `generateColumnSQL()` | Build ALTER TABLE statements |
| `deleteColumn()` | Generate DROP COLUMN statement |
| `showViewSource()` | Display VIEW creation SQL |
| `copyViewSource()` | Copy SQL to clipboard |

### State Management
```javascript
let currentTable = '';      // Currently selected table
let tableInfo = null;       // Table structure data
let currentEditColumn = null; // Column being edited
```

---

## 🎨 CSS Styling

### Key Style Classes

#### Structure Display
- `.structure-table` - Main table container
- `.structure-table thead` - Gradient header
- `.field-type` - Monospace type badge
- `.field-attributes` - Attribute badge container
- `.attribute-badge` - Individual attribute badges
  - `.primary` - Green for primary keys
  - `.required` - Red for NOT NULL
  - `.auto-increment` - Yellow for auto-increment
  - `.unique` - Blue for unique
  - `.index` - Purple for indexed
  - `.dimmed` - Grayed out inactive attributes

#### Information Display
- `.table-info` - Table metadata container
- `.stats-grid` - Statistics card grid
- `.stat-card` - Individual stat display

#### Form Elements
- `.form-row` - Two-column form layout
- `.checkbox-group` - Attribute checkboxes
- `.info-button` - Help tooltip trigger
- `.info-tooltip` - Contextual help popup

#### Action Buttons
- `.btn-add-column` - Add column button
- `.back-link` - Navigation link

---

## 🔄 Workflow Examples

### Adding a New Column

1. Select table from dropdown
2. Click "➕ Add Column" button
3. Fill in column details:
   - Column name
   - Position (end, first, or after specific column)
   - Data type
   - Default value
   - Attributes (NULL, Primary, Unique, Auto-increment)
   - Extra attributes (comments, etc.)
4. Click "⚡ Generate SQL"
5. Review SQL in Query Builder
6. Execute when ready

**Generated SQL Example:**
```sql
ALTER TABLE `users` ADD COLUMN `email` VARCHAR(255) NOT NULL AFTER `username`;
ALTER TABLE `users` ADD UNIQUE (`email`);
```

### Editing an Existing Column

1. Select table from dropdown
2. Click on any row in the structure table
3. Modify column properties
4. Click "⚡ Generate SQL"
5. Review SQL in Query Builder

**Generated SQL Example:**
```sql
ALTER TABLE `users` MODIFY COLUMN `email` VARCHAR(255) NOT NULL DEFAULT '';
```

### Viewing a Database View

1. Select view from dropdown (marked with 👁️)
2. View structure is displayed (read-only)
3. Click "🔍 View Source" button
4. See full CREATE VIEW statement
5. Click "📋 Copy SQL" to copy to clipboard

---

## 🛡️ Safety & Validation

### Input Validation
- ✅ Column name required
- ✅ Data type required
- ✅ Default value validation based on type
- ✅ Confirmation dialogs for deletions

### View Protection
- ❌ Add Column button hidden for VIEWs
- ❌ Edit functionality disabled for VIEWs
- ⚠️ Warning message displayed
- ✅ View Source button shown instead

### SQL Generation Safety
- Never executes SQL directly
- Always redirects to Query Builder
- User reviews before execution
- Full SQL visibility

---

## 📱 Responsive Design

### Breakpoints
```css
@media (max-width: 768px) {
    .structure-table { font-size: 14px; }
    .structure-table th, td { padding: 8px; }
}
```

### Mobile Features
- Smaller font sizes on mobile
- Compact padding
- Maintains full functionality
- Touch-friendly interface

---

## 🔗 Integration Points

### Query Builder Integration
```javascript
window.location.href = `../query_builder/?table=${tableParam}&sql=${queryParam}`;
```

### Navigation Links
- Auto-updates nav links with current table
- Maintains table context across pages
- Database badge updates with table name

### URL Parameters
```
?table=tablename    # Pre-select table on load
```

---

## 🎯 Column Type Support

### Supported Data Types
- `VARCHAR(255)` - Variable character
- `INT` - Integer
- `TINYINT` - Tiny integer
- `BIGINT` - Big integer
- `TEXT` - Long text
- `DATETIME` - Date and time
- `DATE` - Date only
- `TIME` - Time only
- `DECIMAL(10,2)` - Decimal numbers
- `FLOAT` - Floating point
- `BOOLEAN` - True/false
- `ENUM` - Enumeration

### Extra Attributes Examples
```
COMMENT 'User email address'
ON UPDATE CURRENT_TIMESTAMP
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci
GENERATED ALWAYS AS (expression) STORED
UNSIGNED ZEROFILL
```

---

## 🐛 Error Handling

### API Errors
```javascript
error: function(xhr) {
    let errorMsg = 'Error loading table structure';
    try {
        const response = JSON.parse(xhr.responseText);
        if (response.error) {
            errorMsg += ': ' + response.error;
        }
    } catch (e) {
        errorMsg += ': ' + xhr.responseText;
    }
    showError(errorMsg);
}
```

### User Feedback
- Loading spinner during API calls
- Alert dialogs for errors
- Empty state when no table selected
- Success messages for clipboard operations

---

## 🚀 Performance Optimizations

### Browser Caching
- ✅ CSS cached separately
- ✅ JavaScript cached separately
- ✅ Minimal HTML payload
- ✅ CDN-ready static assets

### Lazy Loading
- Tables loaded only when needed
- Structure fetched on selection
- Modals built dynamically

---

## 📊 Module Statistics

| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| **Main File** | 1,023 lines | 79 lines | **92% reduction** |
| **Files** | 1 monolithic | 4 modular | Better organization |
| **CSS** | Inline | Separate (297 lines) | Cacheable |
| **JS** | Inline | Separate (635 lines) | Cacheable |
| **Modals** | Inline | Separate (16 lines) | Reusable |

---

## 🎓 Key Concepts

### Attribute Badge System
All columns show all 5 possible attributes:
- Active attributes: Fully visible and colored
- Inactive attributes: Dimmed (20% opacity)
- Provides consistent visual layout
- Easy to spot active attributes

### SQL Generation Pattern
```javascript
// Build column definition
let columnDef = `\`${name}\` ${type}`;
if (!null) columnDef += ' NOT NULL';
if (default) columnDef += ` DEFAULT '${default}'`;
if (auto_increment) columnDef += ' AUTO_INCREMENT';

// Build ALTER TABLE
let sql = `ALTER TABLE \`${table}\` ADD COLUMN ${columnDef}`;
```

### Position Handling
```javascript
if (position === 'first') {
    sql += ' FIRST';
} else if (position.startsWith('after_')) {
    const afterColumn = position.substring(6);
    sql += ` AFTER \`${afterColumn}\``;
}
```

---

## 🔮 Future Enhancements

### Potential Features
- Foreign key management
- Index visualization
- Column reordering (drag & drop)
- Batch operations
- Export table structure as DDL
- Compare structures between tables
- Table designer visual mode
- Constraint management

---

## 📝 Maintenance Notes

### Adding New Data Types
Edit the `<select id="fieldType">` in `buildColumnForm()`:
```javascript
<option value="NEWTYPE">NEWTYPE</option>
```

### Customizing Attribute Badges
Modify the `allAttributes` array in `displayStructureTable()`:
```javascript
const allAttributes = [
    { key: 'primary', text: 'PRIMARY', class: 'primary' },
    // Add new attribute here
];
```

### Changing Default Position
Modify the position dropdown default in `buildColumnForm()`.

---

## 🏆 Best Practices

### When Using This Module
1. **Always review generated SQL** before executing
2. **Backup data** before structural changes
3. **Test on development** database first
4. **Use meaningful column names** (snake_case recommended)
5. **Set appropriate defaults** for NOT NULL columns
6. **Document with comments** using COMMENT attribute

### Performance Tips
- Create indexes for frequently queried columns
- Use appropriate data types (don't use VARCHAR(255) for everything)
- Set NOT NULL when possible (faster queries)
- Use UNSIGNED for non-negative numbers
- Consider DECIMAL over FLOAT for currency

---

## 📞 Support & Documentation

- Main documentation: `/docs/`
- Common CSS: `../styles/common.css`
- API reference: `../api/`
- Template system: `../templates/`

---

**Last Updated:** October 21, 2025  
**Status:** ✅ Production Ready  
**Refactoring:** Complete (92% reduction in main file size)


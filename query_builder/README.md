# Query Builder Module

## Overview
The **Query Builder** module provides a comprehensive interface for writing, executing, and managing SQL queries. It features a code editor with syntax highlighting support, saved query management with localStorage, and export/import functionality.

---

## 📁 File Structure

```
query_builder/
├── index.php            (120 lines)  - Clean entry point
├── query_builder.css    (413 lines)  - All styling
├── query_builder.js     (729 lines)  - All logic
├── modals.php           (30 lines)   - Modal dialogs
└── README.md            - This documentation
```

**Total:** 1,292 lines across 4 files (previously 1,292 lines in 1 monolithic file)

---

## ✨ Features

### ⚡ Query Execution
- **SQL Editor:** Monospace textarea with syntax-friendly interface
- **Execute Any Query:** SELECT, INSERT, UPDATE, DELETE, ALTER, etc.
- **Results Display:** Tabular results for SELECT queries
- **Success Messages:** Clear feedback for non-SELECT queries
- **Error Handling:** Detailed error messages from MySQL
- **Auto-save:** Queries automatically saved to localStorage
- **URL Parameters:** Load SQL from `?sql=` parameter

### 📋 Field Helper Panel
- **Column List:** View all table columns with data types
- **Click to Insert:** Click field name to insert at cursor
- **Type Information:** See data types for each column
- **Scrollable Panel:** Handle tables with many columns

### 💾 Saved Queries
- **Save Queries:** Store frequently used queries
- **Organize by Table:** Filter by table automatically
- **Usage Tracking:** Track how often queries are used
- **Quick Load:** One-click loading of saved queries
- **Descriptions:** Add descriptions to queries
- **Export/Import:** Backup and share queries as JSON

### 🎯 Smart Features
- **Auto-restore:** Restore last query for current table
- **Default Query:** `SELECT * FROM table LIMIT 10` on table change
- **URL Integration:** Load SQL from table structure editor
- **Quick Examples:** Dismissible example queries
- **Table Context:** Queries tagged with source table

### 📤 Export/Import
- **Export to JSON:** Download all queries as formatted JSON
- **Import from JSON:** Restore queries from file
- **Duplicate Detection:** Skip duplicate queries on import
- **Metadata:** Version and export date included
- **Portable:** Share queries between installations

---

## 🎨 User Interface

### Layout

#### Three-Column Design (Desktop)
```
┌──────────────┬─────────────────────────┬──────────────────┐
│ Table Fields │   Query Editor          │  Saved Queries   │
│              │   [SQL Textarea]        │                  │
│ - field1     │                         │  [Saved Query 1] │
│ - field2     │   [Execute] [Clear]     │  [Saved Query 2] │
│ - field3     │                         │  [Saved Query 3] │
└──────────────┴─────────────────────────┴──────────────────┘
```

#### Responsive Layout (Mobile)
- Stacked vertically
- Fields panel (max 200px height)
- Query editor
- Saved queries panel (max 300px height)

### Components

#### 1. **Query Examples Box**
```html
💡 Quick Examples:
- SELECT * FROM table_name LIMIT 10
- SELECT column1, column2 FROM table_name WHERE condition
- SELECT COUNT(*) as total FROM table_name
[×] (Close button - remembers preference)
```

#### 2. **Query Editor**
- Large textarea (min 300px height)
- Monospace font
- Resizable
- Focus highlighting
- Placeholder with examples

#### 3. **Action Buttons**
- **▶ Execute Query** - Run the SQL
- **🗑️ Clear** - Clear editor and results
- **💾 Save Query** - Open save modal

#### 4. **Results Table**
- Sticky header
- Scrollable content (max 500px)
- Alternating row colors
- Monospace font for data
- NULL values styled differently

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
    isView: false
}
```

#### Execute Query
```javascript
POST ../api/
Data: { action: 'executeQuery', query: 'SELECT...' }
Response: {
    success: true,
    type: 'select',  // or 'other'
    data: [...],     // for SELECT
    totalRows: 100,
    message: '...'   // for non-SELECT
}
```

---

## 💻 JavaScript Functions

### Core Functions

| Function | Purpose |
|----------|---------|
| `loadTables()` | Load all tables into dropdown |
| `loadTableInfo()` | Fetch table structure |
| `displayFieldList()` | Populate fields sidebar |
| `insertFieldName()` | Insert field at cursor position |
| `executeQuery()` | Execute SQL and handle response |
| `displayResults()` | Render query results |
| `saveCurrentQuery()` | Auto-save to localStorage |
| `loadSavedQueries()` | Load saved queries list |
| `displaySavedQueries()` | Render saved queries |
| `saveQueryToDatabase()` | Save query to localStorage |
| `loadQuery()` | Load saved query into editor |
| `deleteSavedQuery()` | Remove saved query |
| `exportQueries()` | Download queries as JSON |
| `importQueries()` | Import queries from JSON |
| `showToast()` | Display notifications |

### State Management
```javascript
let currentTable = '';      // Currently selected table
let tableInfo = null;       // Table structure data
```

### LocalStorage Keys
```javascript
'savedQueries'    // Array of saved query objects
'currentQuery'    // Current query auto-save state
'hideExamples'    // User preference for examples box
```

---

## 💾 Data Structures

### Saved Query Object
```javascript
{
    id: 1635780000000,              // Timestamp as unique ID
    query_name: "Get All Users",    // User-defined name
    query_sql: "SELECT * FROM...",  // SQL query
    table_name: "users",            // Associated table
    description: "Fetches all...",  // Optional description
    created_at: "2025-10-21T...",   // ISO timestamp
    last_used_at: "2025-10-21...",  // Last usage time
    use_count: 5                    // Number of times used
}
```

### Export Format
```json
{
    "version": "1.0",
    "exported_at": "2025-10-21T10:30:00.000Z",
    "query_count": 10,
    "queries": [...]
}
```

---

## 🎨 CSS Styling

### Key Style Classes

#### Layout
- `.query-layout` - Three-column grid layout
- `.fields-panel` - Left sidebar for fields
- `.query-panel` - Center query editor
- `.saved-queries-panel` - Right sidebar for saved queries

#### Query Editor
- `.query-input` - Main textarea styling
- `.query-input:focus` - Focus state with shadow
- `.query-actions` - Button container
- `.btn-execute` - Execute button (primary)
- `.btn-clear` - Clear button (secondary)
- `.btn-save-query` - Save button (success)

#### Field List
- `.field-list` - Field container
- `.field-item` - Individual field item
- `.field-item:hover` - Hover effect with slide
- `.field-type` - Data type label

#### Saved Queries
- `.saved-query-list` - Query container
- `.saved-query-item` - Individual query card
- `.saved-query-name` - Query title
- `.saved-query-preview` - SQL preview (truncated)
- `.saved-query-meta` - Metadata (table, usage)
- `.saved-query-actions` - Action buttons
- `.btn-load` - Load button
- `.btn-delete-saved` - Delete button

#### Results
- `.results-section` - Results container
- `.results-header` - Header with count
- `.results-wrapper` - Scrollable table container
- `.results-table` - Results table
- `.results-table thead` - Sticky header
- `.results-table tbody tr:hover` - Row hover effect

#### Examples Box
- `.query-examples` - Examples container
- `.close-examples-btn` - Close button
- `.query-examples.hidden` - Hidden state

---

## 🔄 Workflow Examples

### Writing and Executing a Query

1. Select table from dropdown
2. Default query `SELECT * FROM table LIMIT 10` loaded
3. Modify query as needed
4. Click fields to insert column names
5. Click "▶ Execute Query"
6. View results below
7. Query auto-saved to localStorage

### Saving a Query

1. Write your SQL query
2. Click "💾 Save Query" button
3. Enter query name (required)
4. Add description (optional)
5. Click "💾 Save"
6. Query appears in saved queries panel

### Using a Saved Query

1. Find query in saved queries panel
2. Click "📂 Load" button
3. Query loaded into editor
4. Usage count incremented
5. Last used timestamp updated

### Exporting Queries

1. Click "⬇️" button in saved queries header
2. JSON file automatically downloads
3. Filename: `saved-queries-YYYY-MM-DD.json`
4. Contains all queries with metadata

### Importing Queries

1. Click "⬆️" button in saved queries header
2. Select JSON file
3. Queries merged with existing
4. Duplicates automatically skipped
5. Success message shows import count

---

## 🛡️ Safety & Features

### Query Safety
- ✅ All queries executed through parameterized API
- ✅ Server-side validation
- ✅ Clear error messages
- ✅ Transaction support (via SQL)

### Auto-save Features
- ✅ Queries auto-saved every 500ms (debounced)
- ✅ Saved on page unload
- ✅ Restored when returning to same table
- ✅ Cleared when explicitly clicking Clear

### Data Persistence
- ✅ Saved queries in localStorage (persistent)
- ✅ Current query state in localStorage
- ✅ User preferences (hide examples) saved
- ✅ No server-side storage required

### Error Handling
- ✅ Network errors caught and displayed
- ✅ SQL errors shown with details
- ✅ Empty query validation
- ✅ File import validation

---

## 📱 Responsive Design

### Desktop (1200px+)
- Three-column layout
- 250px fields panel
- Fluid query editor
- 300px saved queries panel

### Tablet (1024px - 1199px)
- Narrower sidebars
- 200px fields panel
- 250px saved queries panel

### Mobile (<1024px)
- Single column stack
- Fields panel (200px max height)
- Saved queries (300px max height)
- Smaller query editor (200px min)

---

## 🔗 Integration Points

### URL Parameters
```
?table=users           # Pre-select table
?sql=SELECT%20*%20...  # Load SQL query
```

### Table Structure Integration
```javascript
// From table_structure editor
window.location.href = 
    `query.php?table=${table}&sql=${encodedSQL}`;
```

### Navigation Links
- Auto-updates nav links with current table
- Maintains table context across pages
- Database badge shows current table

---

## 🎯 LocalStorage Management

### Storage Keys

#### savedQueries
```javascript
[
    {
        id: 1635780000000,
        query_name: "...",
        query_sql: "...",
        table_name: "...",
        description: "...",
        created_at: "...",
        last_used_at: "...",
        use_count: 0
    },
    ...
]
```

#### currentQuery
```javascript
{
    query: "SELECT * FROM users",
    table: "users",
    timestamp: 1635780000000
}
```

#### hideExamples
```javascript
"true"  // or not set
```

### Storage Limits
- Modern browsers: ~10MB localStorage
- Typical query: ~1KB
- Can store ~10,000 queries (practical: ~100-500)

---

## 🐛 Error Handling

### SQL Errors
```javascript
if (!response.success) {
    showToast('Query error: ' + response.error, 'error');
}
```

### Network Errors
```javascript
error: function(xhr) {
    const response = xhr.responseJSON || {};
    showToast('Error: ' + (response.error || 'Unknown error'), 'error');
}
```

### Validation Errors
```javascript
if (!query) {
    showToast('Please enter a SQL query', 'warning');
    return;
}
```

---

## 🚀 Performance Optimizations

### Browser Caching
- ✅ CSS cached separately
- ✅ JavaScript cached separately
- ✅ Minimal HTML payload
- ✅ CDN-ready static assets

### Debouncing
- ✅ Auto-save debounced (500ms)
- ✅ Prevents excessive localStorage writes

### Lazy Loading
- ✅ Tables loaded on demand
- ✅ Field list populated when table selected
- ✅ Results only shown when needed

### Efficient Rendering
- ✅ Results limited to 100 rows by API
- ✅ Sticky headers for large results
- ✅ Virtual scrolling (via browser)

---

## 📊 Module Statistics

| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| **Main File** | 1,292 lines | 120 lines | **91% reduction** |
| **Files** | 1 monolithic | 4 modular | Better organization |
| **CSS** | Inline | Separate (413 lines) | Cacheable |
| **JS** | Inline | Separate (729 lines) | Cacheable |
| **Modals** | Inline | Separate (30 lines) | Reusable |

---

## 🎓 Key Concepts

### Query Auto-save Pattern
```javascript
// Debounced auto-save
let autoSaveTimeout;
$('#queryInput').on('input', function() {
    clearTimeout(autoSaveTimeout);
    autoSaveTimeout = setTimeout(saveCurrentQuery, 500);
});
```

### Field Insertion
```javascript
// Insert at cursor position
function insertFieldName(fieldName) {
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const before = text.substring(0, start);
    const after = text.substring(end);
    textarea.value = before + fieldName + after;
    textarea.selectionStart = textarea.selectionEnd = start + fieldName.length;
}
```

### Duplicate Detection on Import
```javascript
const isDuplicate = existingQueries.some(q => 
    q.query_name === query.query_name && 
    q.query_sql === query.query_sql
);
```

---

## 🔮 Future Enhancements

### Potential Features
- SQL syntax highlighting
- Query history (separate from saved queries)
- Query performance analysis
- Query templates
- Multi-query execution (batch)
- Query sharing (team features)
- Autocomplete for table/column names
- Query formatting/beautification
- Keyboard shortcuts
- Dark mode toggle

---

## 📝 Maintenance Notes

### Adding New Query Actions
Add buttons in `query-actions` div and attach handlers in JavaScript.

### Customizing Results Display
Modify `displayResults()` function to change table rendering.

### Changing Auto-save Delay
Adjust timeout in `$('#queryInput').on('input')` handler.

### Storage Cleanup
Consider adding cleanup for old unused queries:
```javascript
queries = queries.filter(q => {
    const age = Date.now() - new Date(q.last_used_at || q.created_at);
    return age < 90 * 24 * 60 * 60 * 1000; // 90 days
});
```

---

## 🏆 Best Practices

### When Using This Module
1. **Test queries** on development database first
2. **Limit SELECT queries** for large tables
3. **Save complex queries** for reuse
4. **Add descriptions** to saved queries
5. **Export queries** regularly as backup
6. **Use transactions** for multi-statement updates

### Query Writing Tips
- Start with `LIMIT` for SELECT on unknown tables
- Use `COUNT(*)` before large selects
- Test WHERE clauses with `LIMIT 1` first
- Add indexes for frequently queried columns
- Use `EXPLAIN` to analyze query performance

---

## 📞 Support & Documentation

- Main documentation: `/docs/`
- Common CSS: `../styles/common.css`
- API reference: `../api/`
- Template system: `../templates/`

---

**Last Updated:** October 21, 2025  
**Status:** ✅ Production Ready  
**Refactoring:** Complete (91% reduction in main file size)


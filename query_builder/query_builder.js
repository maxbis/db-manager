// SQL Query Builder - JavaScript Module

// Global state
let currentTable = '';
let tableInfo = null;

// Initialize
$(document).ready(function() {
    loadTables();
    loadSavedQueries();
    
    // Check if examples box should be hidden (user previously closed it)
    if (localStorage.getItem('hideExamples') === 'true') {
        $('#queryExamples').hide();
    }
    
    // Update navigation links with current table
    function updateNavLinks() {
        const selectedTable = $('#tableSelect').val();
        if (selectedTable) {
            $('.nav-link').each(function() {
                const baseUrl = $(this).attr('href').split('?')[0];
                $(this).attr('href', baseUrl + '?table=' + encodeURIComponent(selectedTable));
            });
        }
    }

    // Update database badge in header
    function updateDatabaseBadge() {
        const databaseBadge = document.querySelector('.control-group span span');
        if (databaseBadge) {
            const databaseName = databaseBadge.textContent.replace('🗄️ ', '');
            const tableName = $('#tableSelect').val();
            
            let displayText = '🗄️ ' + databaseName;
            if (tableName) {
                // Extract just the database name (remove any existing table part)
                const dbName = databaseName.split(' - ')[0];
                displayText = '🗄️ ' + dbName + ' -  ' + tableName;
            }
            databaseBadge.textContent = displayText;
        }
    }
    
    // Save current query to localStorage before leaving the page
    function saveCurrentQuery() {
        const query = $('#queryInput').val();
        const table = $('#tableSelect').val();
        if (query && table) {
            const queryState = {
                query: query,
                table: table,
                timestamp: Date.now()
            };
            localStorage.setItem('currentQuery', JSON.stringify(queryState));
        }
    }
    
    // Auto-save query when typing (with debounce)
    let autoSaveTimeout;
    $('#queryInput').on('input', function() {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(saveCurrentQuery, 500);
    });
    
    // Save query when leaving the page
    $(window).on('beforeunload', function() {
        saveCurrentQuery();
    });
    
    // Check for SQL parameter in URL
    const urlParams = new URLSearchParams(window.location.search);
    const sqlParam = urlParams.get('sql');
    
    $('#tableSelect').change(function() {
        const previousTable = currentTable;
        currentTable = $(this).val();
        updateNavLinks();
        updateDatabaseBadge();
        
        if (currentTable) {
            loadTableInfo();
            
            // Check if SQL query was passed via URL parameter (takes priority)
            if (sqlParam && !$('#queryInput').data('sql-loaded')) {
                $('#queryInput').val(decodeURIComponent(sqlParam));
                $('#queryInput').data('sql-loaded', true);
                // Show a notification
                showToast('SQL query loaded from table structure editor', 'success');
            } 
            // Check if we have a saved query for this table
            else {
                const savedQueryState = localStorage.getItem('currentQuery');
                if (savedQueryState) {
                    try {
                        const queryState = JSON.parse(savedQueryState);
                        // Restore query if it's for the same table
                        if (queryState.table === currentTable) {
                            $('#queryInput').val(queryState.query);
                        } else {
                            // Different table selected, clear and set default query
                            $('#queryInput').val(`SELECT * FROM ${currentTable} LIMIT 10`);
                        }
                    } catch (e) {
                        $('#queryInput').val(`SELECT * FROM ${currentTable} LIMIT 10`);
                    }
                } else {
                    $('#queryInput').val(`SELECT * FROM ${currentTable} LIMIT 10`);
                }
            }
            
            loadSavedQueries(currentTable);
        } else {
            showEmptyState();
        }
    });

    $('#executeBtn').click(function() {
        executeQuery();
    });

    $('#clearBtn').click(function() {
        $('#queryInput').val('');
        $('#resultsSection').hide();
        // Clear saved query state when explicitly clearing
        localStorage.removeItem('currentQuery');
    });

    $('#saveQueryBtn, #saveQueryBtn2').click(function() {
        openSaveModal();
    });

    $('#confirmSaveBtn').click(function() {
        saveQueryToDatabase();
    });

    $('#exportQueriesBtn').click(function() {
        exportQueries();
    });

    $('#importQueriesBtn').click(function() {
        $('#importFileInput').click();
    });

    $('#importFileInput').change(function(e) {
        importQueries(e);
    });

    // Close examples box
    $('#closeExamplesBtn').click(function() {
        $('#queryExamples').fadeOut(300);
        localStorage.setItem('hideExamples', 'true');
    });

    // Click on field to insert into query
    $(document).on('click', '.field-item', function() {
        const fieldName = $(this).data('field');
        insertFieldName(fieldName);
    });

    // Close modal on outside click
    $(document).click(function(e) {
        if ($(e.target).is('#saveQueryModal')) {
            closeSaveModal();
        }
    });
});

// Load all tables
function loadTables() {
    $.ajax({
        url: '../api/?action=getTables',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const select = $('#tableSelect');
                select.empty();
                select.append('<option value="">-- Choose a table --</option>');
                
                response.tables.forEach(function(table) {
                    // Handle both old format (string) and new format (object)
                    const tableName = typeof table === 'string' ? table : table.name;
                    const tableType = typeof table === 'object' ? table.type : 'BASE TABLE';
                    const label = tableType === 'VIEW' ? `${tableName} 👁️ (view)` : tableName;
                    
                    select.append(`<option value="${tableName}" data-type="${tableType}">${label}</option>`);
                });
                
                // Check for table parameter in URL and select it
                const urlParams = new URLSearchParams(window.location.search);
                const tableParam = urlParams.get('table');
                if (tableParam) {
                    const tableNames = response.tables.map(t => typeof t === 'string' ? t : t.name);
                    if (tableNames.includes(tableParam)) {
                        select.val(tableParam).trigger('change');
                    }
                }
            }
            $('#loading').removeClass('active');
        },
        error: function(xhr) {
            showToast('Error loading tables: ' + xhr.responseText, 'error');
            $('#loading').removeClass('active');
        }
    });
}

// Load table structure information
function loadTableInfo() {
    $('#loading').addClass('active');
    
    $.ajax({
        url: '../api/?action=getTableInfo&table=' + encodeURIComponent(currentTable),
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                tableInfo = response;
                displayFieldList();
                $('#queryInterface').show();
                $('#emptyState').hide();
                
                // Show info if it's a view
                if (tableInfo.isView) {
                    showToast('👁️ Querying a database VIEW', 'warning');
                }
            }
            $('#loading').removeClass('active');
        },
        error: function(xhr) {
            showToast('Error loading table info', 'error');
            $('#loading').removeClass('active');
        }
    });
}

// Display field list in sidebar
function displayFieldList() {
    const fieldList = $('#fieldList');
    fieldList.empty();
    
    if (!tableInfo || !tableInfo.columns) {
        return;
    }
    
    tableInfo.columns.forEach(function(col) {
        const fieldItem = $(`
            <li class="field-item" data-field="${col.name}">
                <strong>${col.name}</strong>
                <span class="field-type">${col.type}</span>
            </li>
        `);
        fieldList.append(fieldItem);
    });
}

// Insert field name at cursor position
function insertFieldName(fieldName) {
    const textarea = document.getElementById('queryInput');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    const before = text.substring(0, start);
    const after = text.substring(end, text.length);
    
    textarea.value = before + fieldName + after;
    textarea.selectionStart = textarea.selectionEnd = start + fieldName.length;
    textarea.focus();
}

// Execute SQL query
function executeQuery() {
    const query = $('#queryInput').val().trim();
    
    if (!query) {
        showToast('Please enter a SQL query', 'warning');
        return;
    }
    
    $('#loading').addClass('active');
    $('#executeBtn').prop('disabled', true);
    
    $.ajax({
        url: '../api/',
        method: 'POST',
        data: {
            action: 'executeQuery',
            query: query
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                displayResults(response);
                showToast('Query executed successfully', 'success');
            } else {
                showToast('Query error: ' + response.error, 'error');
            }
            $('#loading').removeClass('active');
            $('#executeBtn').prop('disabled', false);
        },
        error: function(xhr) {
            const response = xhr.responseJSON || {};
            showToast('Error: ' + (response.error || 'Unknown error'), 'error');
            $('#loading').removeClass('active');
            $('#executeBtn').prop('disabled', false);
        }
    });
}

// Display query results
function displayResults(response) {
    const resultsSection = $('#resultsSection');
    const resultsHead = $('#resultsHead');
    const resultsBody = $('#resultsBody');
    const resultsInfo = $('#resultsInfo');
    
    resultsHead.empty();
    resultsBody.empty();
    
    if (response.type === 'select') {
        const data = response.data || [];
        const rowCount = data.length;
        const totalRows = response.totalRows || rowCount;
        
        resultsInfo.text(`${rowCount} rows returned${totalRows > 100 ? ' (limited to first 100)' : ''}`);
        
        if (rowCount === 0) {
            resultsBody.append('<tr><td colspan="100" style="text-align: center; padding: 40px;">No results found</td></tr>');
        } else {
            // Build header
            const columns = Object.keys(data[0]);
            let headerRow = '<tr>';
            columns.forEach(function(col) {
                headerRow += `<th>${escapeHtml(col)}</th>`;
            });
            headerRow += '</tr>';
            resultsHead.html(headerRow);
            
            // Build rows
            data.forEach(function(row) {
                let rowHtml = '<tr>';
                columns.forEach(function(col) {
                    const value = row[col];
                    if (value === null) {
                        rowHtml += '<td><em style="color: var(--color-text-muted);">NULL</em></td>';
                    } else {
                        rowHtml += `<td>${escapeHtml(String(value))}</td>`;
                    }
                });
                rowHtml += '</tr>';
                resultsBody.append(rowHtml);
            });
        }
    } else {
        // Non-SELECT query (INSERT, UPDATE, DELETE, etc.)
        resultsInfo.text(response.message || 'Query executed successfully');
        resultsHead.html('<tr><th>Result</th></tr>');
        resultsBody.html(`<tr><td>${response.message || 'Success'}</td></tr>`);
    }
    
    resultsSection.show();
}

// Show empty state
function showEmptyState() {
    $('#queryInterface').hide();
    $('#emptyState').show();
    $('#resultsSection').hide();
}

// Show toast notification
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

// Load saved queries from LocalStorage
function loadSavedQueries(tableName = null) {
    try {
        // Get queries from localStorage
        const queriesJson = localStorage.getItem('savedQueries');
        let queries = queriesJson ? JSON.parse(queriesJson) : [];
        
        // Filter by table if specified
        if (tableName) {
            queries = queries.filter(q => q.table_name === tableName || !q.table_name);
        }
        
        // Sort by last used (most recent first), then by created date
        queries.sort((a, b) => {
            const aDate = a.last_used_at || a.created_at;
            const bDate = b.last_used_at || b.created_at;
            return new Date(bDate) - new Date(aDate);
        });
        
        displaySavedQueries(queries);
    } catch (e) {
        console.error('Error loading saved queries from localStorage:', e);
        displaySavedQueries([]);
    }
}

// Display saved queries list
function displaySavedQueries(queries) {
    const savedQueryList = $('#savedQueryList');
    savedQueryList.empty();
    
    if (queries.length === 0) {
        savedQueryList.append(`
            <li style="text-align: center; padding: 20px; color: var(--color-text-muted); font-size: 13px;">
                No saved queries yet.<br>Save your first query!
            </li>
        `);
        return;
    }
    
    queries.forEach(function(query) {
        const queryPreview = query.query_sql.substring(0, 50) + (query.query_sql.length > 50 ? '...' : '');
        const useCount = query.use_count || 0;
        const tableBadge = query.table_name ? 
            `<span style="background: var(--color-primary-pale); color: var(--color-primary); padding: 2px 6px; border-radius: 3px; font-size: 10px;">${query.table_name}</span>` : 
            '';
        
        const queryItem = $(`
            <li class="saved-query-item" data-query-id="${query.id}">
                <div class="saved-query-name">${escapeHtml(query.query_name)}</div>
                <div class="saved-query-preview">${escapeHtml(queryPreview)}</div>
                ${query.description ? `<div style="font-size: 11px; color: var(--color-text-muted); margin-bottom: 4px;">${escapeHtml(query.description)}</div>` : ''}
                <div class="saved-query-meta">
                    <span>${tableBadge} Used: ${useCount}x</span>
                </div>
                <div class="saved-query-actions">
                    <button class="btn-load" onclick="loadQuery(${query.id}); event.stopPropagation();">📂 Load</button>
                    <button class="btn-delete-saved" onclick="deleteSavedQueryConfirm(${query.id}, '${escapeHtml(query.query_name)}'); event.stopPropagation();">🗑️</button>
                </div>
            </li>
        `);
        
        savedQueryList.append(queryItem);
    });
}

// Open save query modal
function openSaveModal() {
    const query = $('#queryInput').val().trim();
    
    if (!query) {
        showToast('Please enter a query first', 'warning');
        return;
    }
    
    $('#saveQueryName').val('');
    $('#saveQueryDescription').val('');
    $('#saveQuerySql').val(query);
    $('#saveQueryModal').addClass('active');
}

// Close save query modal
function closeSaveModal() {
    $('#saveQueryModal').removeClass('active');
}

// Save query to LocalStorage
function saveQueryToDatabase() {
    const queryName = $('#saveQueryName').val().trim();
    const queryDescription = $('#saveQueryDescription').val().trim();
    const querySql = $('#saveQuerySql').val();
    
    if (!queryName) {
        showToast('Please enter a query name', 'warning');
        return;
    }
    
    try {
        // Get existing queries from localStorage
        const queriesJson = localStorage.getItem('savedQueries');
        let queries = queriesJson ? JSON.parse(queriesJson) : [];
        
        // Create new query object
        const newQuery = {
            id: Date.now(), // Use timestamp as unique ID
            query_name: queryName,
            query_sql: querySql,
            table_name: currentTable || null,
            description: queryDescription || null,
            created_at: new Date().toISOString(),
            last_used_at: null,
            use_count: 0
        };
        
        // Add to queries array
        queries.push(newQuery);
        
        // Save back to localStorage
        localStorage.setItem('savedQueries', JSON.stringify(queries));
        
        showToast('Query saved successfully!', 'success');
        closeSaveModal();
        loadSavedQueries(currentTable);
        
    } catch (e) {
        console.error('Error saving query to localStorage:', e);
        showToast('Error: ' + e.message, 'error');
    }
}

// Load a saved query from LocalStorage
function loadQuery(queryId) {
    try {
        // Get queries from localStorage
        const queriesJson = localStorage.getItem('savedQueries');
        let queries = queriesJson ? JSON.parse(queriesJson) : [];
        
        // Find the query
        const queryIndex = queries.findIndex(q => q.id === queryId);
        
        if (queryIndex === -1) {
            showToast('Query not found', 'error');
            return;
        }
        
        const query = queries[queryIndex];
        
        // Update usage statistics
        queries[queryIndex].last_used_at = new Date().toISOString();
        queries[queryIndex].use_count = (queries[queryIndex].use_count || 0) + 1;
        
        // Save updated queries back to localStorage
        localStorage.setItem('savedQueries', JSON.stringify(queries));
        
        // Load the query into the editor
        $('#queryInput').val(query.query_sql);
        showToast('Query loaded successfully!', 'success');
        
        // If query has a specific table and it's different from current, change table
        if (query.table_name && query.table_name !== currentTable) {
            $('#tableSelect').val(query.table_name).trigger('change');
        } else {
            // Reload the saved queries to show updated usage count
            loadSavedQueries(currentTable);
        }
        
    } catch (e) {
        console.error('Error loading query from localStorage:', e);
        showToast('Error: ' + e.message, 'error');
    }
}

// Delete saved query with confirmation
function deleteSavedQueryConfirm(queryId, queryName) {
    if (confirm(`Are you sure you want to delete the query "${queryName}"?`)) {
        deleteSavedQuery(queryId);
    }
}

// Delete a saved query from LocalStorage
function deleteSavedQuery(queryId) {
    try {
        // Get queries from localStorage
        const queriesJson = localStorage.getItem('savedQueries');
        let queries = queriesJson ? JSON.parse(queriesJson) : [];
        
        // Filter out the query to delete
        queries = queries.filter(q => q.id !== queryId);
        
        // Save back to localStorage
        localStorage.setItem('savedQueries', JSON.stringify(queries));
        
        showToast('Query deleted successfully!', 'success');
        loadSavedQueries(currentTable);
        
    } catch (e) {
        console.error('Error deleting query from localStorage:', e);
        showToast('Error: ' + e.message, 'error');
    }
}

// Export queries to JSON file
function exportQueries() {
    try {
        // Get queries from localStorage
        const queriesJson = localStorage.getItem('savedQueries');
        const queries = queriesJson ? JSON.parse(queriesJson) : [];
        
        if (queries.length === 0) {
            showToast('No queries to export', 'warning');
            return;
        }
        
        // Create export object with metadata
        const exportData = {
            version: '1.0',
            exported_at: new Date().toISOString(),
            query_count: queries.length,
            queries: queries
        };
        
        // Convert to JSON string with pretty formatting
        const jsonString = JSON.stringify(exportData, null, 2);
        
        // Create blob and download
        const blob = new Blob([jsonString], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `saved-queries-${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        
        showToast(`Exported ${queries.length} queries successfully!`, 'success');
        
    } catch (e) {
        console.error('Error exporting queries:', e);
        showToast('Error: ' + e.message, 'error');
    }
}

// Import queries from JSON file
function importQueries(event) {
    const file = event.target.files[0];
    
    if (!file) {
        return;
    }
    
    const reader = new FileReader();
    
    reader.onload = function(e) {
        try {
            const importData = JSON.parse(e.target.result);
            
            // Validate import data
            if (!importData.queries || !Array.isArray(importData.queries)) {
                showToast('Invalid import file format', 'error');
                return;
            }
            
            // Get existing queries
            const queriesJson = localStorage.getItem('savedQueries');
            let existingQueries = queriesJson ? JSON.parse(queriesJson) : [];
            
            // Merge strategy: add imported queries with new IDs to avoid conflicts
            let importCount = 0;
            let duplicateCount = 0;
            
            importData.queries.forEach(function(query) {
                // Check if query with same name and SQL already exists
                const isDuplicate = existingQueries.some(q => 
                    q.query_name === query.query_name && q.query_sql === query.query_sql
                );
                
                if (!isDuplicate) {
                    // Assign new ID to avoid conflicts
                    query.id = Date.now() + importCount;
                    existingQueries.push(query);
                    importCount++;
                } else {
                    duplicateCount++;
                }
            });
            
            // Save merged queries back to localStorage
            localStorage.setItem('savedQueries', JSON.stringify(existingQueries));
            
            // Show results
            let message = `Imported ${importCount} queries`;
            if (duplicateCount > 0) {
                message += ` (${duplicateCount} duplicates skipped)`;
            }
            showToast(message, 'success');
            
            // Reload display
            loadSavedQueries(currentTable);
            
        } catch (e) {
            console.error('Error importing queries:', e);
            showToast('Error: ' + e.message, 'error');
        }
    };
    
    reader.onerror = function() {
        showToast('Error reading file', 'error');
    };
    
    reader.readAsText(file);
    
    // Reset file input so same file can be imported again
    event.target.value = '';
}

// Smooth page transitions
$('.nav-link').click(function(e) {
    const href = $(this).attr('href');
    
    // Don't apply transition if it's the current page
    if ($(this).hasClass('active')) {
        e.preventDefault();
        return;
    }
    
    e.preventDefault();
    $('body').addClass('page-transitioning');
    
    // Navigate after fade out
    setTimeout(function() {
        window.location.href = href;
    }, 200);
});

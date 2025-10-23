/**
 * Data Manager - JavaScript Module
 * Handles all client-side interactions for the data manager
 */

// Global state
let currentTable = '';
let tableInfo = null;
let currentOffset = 0;
let currentLimit = 20;
let totalRecords = 0;
let sortColumn = '';
let sortOrder = 'ASC';
let filters = {};
let filterTimeout = null;
let currentEditRecord = null;

// Initialize
$(document).ready(function() {
    // Get current table from session first
    $.ajax({
        url: '../api/?action=getCurrentTable',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.table) {
                currentTable = response.table;
                console.log('Restored current table from session:', currentTable);
            } else {
                console.log('No table in session to restore');
            }
            // Then load tables (which will select the current one if set)
            loadTables();
        },
        error: function(err) {
            console.error('Error getting current table:', err);
            // If error, just load tables without pre-selection
            loadTables();
        }
    });

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
    
    // Check for table parameter in URL (for backward compatibility)
    const urlParams = new URLSearchParams(window.location.search);
    const tableParam = urlParams.get('table');
    
    $('#tableSelect').change(function() {
        currentTable = $(this).val();
        
        // Update session cache
        $.ajax({
            url: '../api/',
            method: 'POST',
            data: {
                action: 'setCurrentTable',
                table: currentTable
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    updateDatabaseBadge();
                }
            }
        });
        
        if (currentTable) {
            loadTableInfo();
        } else {
            showEmptyState();
        }
    });

    $('#addRecordBtn').click(function() {
        openInsertModal();
    });

    $('#saveRecordBtn').click(function() {
        saveRecord();
    });

    $('#deleteRecordBtn').click(function() {
        showDeleteConfirmation();
    });

    $('#prevBtn').click(function() {
        if (currentOffset > 0) {
            currentOffset -= currentLimit;
            loadRecords(false); // Don't show loading spinner for pagination
        }
    });

    $('#nextBtn').click(function() {
        if (currentOffset + currentLimit < totalRecords) {
            currentOffset += currentLimit;
            loadRecords(false); // Don't show loading spinner for pagination
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
                
                // Check for current table from session or URL parameter and select it
                const urlParams = new URLSearchParams(window.location.search);
                const tableParam = urlParams.get('table');
                const tableToSelect = currentTable || tableParam;
                
                if (tableToSelect) {
                    const tableNames = response.tables.map(t => typeof t === 'string' ? t : t.name);
                    if (tableNames.includes(tableToSelect)) {
                        select.val(tableToSelect).trigger('change');
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
    $('#tableContent').hide();
    
    $.ajax({
        url: '../api/?action=getTableInfo&table=' + encodeURIComponent(currentTable),
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                tableInfo = response;
                currentOffset = 0;
                sortColumn = '';
                sortOrder = 'ASC';
                filters = {};
                buildTableHeader();
                loadRecords();
                
                // Show/hide add button based on table type
                if (tableInfo.isView) {
                    $('#addRecordBtn').hide();
                    showToast('📖 Viewing a database VIEW - Read-only mode (no editing allowed)', 'warning');
                } else {
                    $('#addRecordBtn').show();
                }
            }
        },
        error: function(xhr) {
            showToast('Error loading table info', 'error');
            $('#loading').removeClass('active');
        }
    });
}

// Build table header with filter inputs
function buildTableHeader() {
    const thead = $('#tableHead');
    thead.empty();
    
    // Header row with column names
    let headerRow = '<tr>';
    tableInfo.columns.forEach(function(col) {
        const sortIndicator = sortColumn === col.name ? 
            (sortOrder === 'ASC' ? '▲' : '▼') : '↕';
        headerRow += `<th data-column="${col.name}">
            ${col.name} 
            <span class="sort-indicator">${sortIndicator}</span>
        </th>`;
    });
    headerRow += '</tr>';
    
    // Filter row
    let filterRow = '<tr class="filter-row">';
    tableInfo.columns.forEach(function(col) {
        filterRow += `<th>
            <input type="text" 
                   class="filter-input" 
                   data-column="${col.name}" 
                   placeholder="Filter...">
        </th>`;
    });
    filterRow += '</tr>';
    
    thead.append(headerRow);
    thead.append(filterRow);
    
    // Add sorting click handlers
    thead.find('tr:first th').click(function() {
        const column = $(this).data('column');
        if (sortColumn === column) {
            sortOrder = sortOrder === 'ASC' ? 'DESC' : 'ASC';
        } else {
            sortColumn = column;
            sortOrder = 'ASC';
        }
        currentOffset = 0;
        updateSortIndicators(); // Only update indicators, don't rebuild header
        loadRecords(false); // Don't show loading spinner for sorting
    });
    
    // Add filter input handlers
    thead.find('.filter-input').on('input', function() {
        const column = $(this).data('column');
        const value = $(this).val();
        
        filters[column] = value;
        
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(function() {
            currentOffset = 0;
            loadRecords(false); // Don't show loading spinner for filtering
        }, 300); // Reduced debounce time for more responsive filtering
    });
}

// Update only the sort indicators without rebuilding the entire header
function updateSortIndicators() {
    const thead = $('#tableHead');
    thead.find('tr:first th').each(function() {
        const $th = $(this);
        const column = $th.data('column');
        const sortIndicator = sortColumn === column ? 
            (sortOrder === 'ASC' ? '▲' : '▼') : '↕';
        $th.find('.sort-indicator').text(sortIndicator);
    });
}

// Load records
function loadRecords(showLoading = true) {
    if (showLoading) {
        $('#loading').addClass('active');
    }
    
    const params = {
        action: 'getRecords',
        table: currentTable,
        offset: currentOffset,
        limit: currentLimit,
        sortColumn: sortColumn,
        sortOrder: sortOrder,
        filters: JSON.stringify(filters)
    };
    
    $.ajax({
        url: '../api/?' + $.param(params),
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                totalRecords = response.total;
                displayRecords(response.records);
                updatePagination();
                $('#tableContent').show();
                $('#emptyState').hide();
            }
            if (showLoading) {
                $('#loading').removeClass('active');
            }
        },
        error: function(xhr) {
            let errorMsg = 'Error loading records';
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.error) {
                    errorMsg += ': ' + response.error;
                }
            } catch (e) {
                errorMsg += ': ' + xhr.responseText;
            }
            showToast(errorMsg, 'error');
            console.error('Full error:', xhr);
            if (showLoading) {
                $('#loading').removeClass('active');
            }
        }
    });
}

// Display records in table
function displayRecords(records) {
    const tbody = $('#tableBody');
    
    // Add smooth transition for table updates
    tbody.css('opacity', '0.7');
    
    // Use requestAnimationFrame for smoother updates
    requestAnimationFrame(function() {
        tbody.empty();
        
        if (records.length === 0) {
            let colspan = tableInfo.columns.length;
            tbody.append(`<tr><td colspan="${colspan}" style="text-align: center; padding: 40px;">
                No records found
            </td></tr>`);
        } else {
            records.forEach(function(record) {
                // For views without primary key, use first column as identifier
                const primaryValue = tableInfo.primaryKey ? record[tableInfo.primaryKey] : (tableInfo.columns.length > 0 ? record[tableInfo.columns[0].name] : '');
                let row = '<tr data-primary-value="' + primaryValue + '">';
                tableInfo.columns.forEach(function(col) {
                    let value = record[col.name];
                    if (value === null) {
                        value = '<em style="color: var(--color-text-muted);">NULL</em>';
                    }
                    row += `<td title="${escapeHtml(String(record[col.name] || ''))}">${value}</td>`;
                });
                row += '</tr>';
                tbody.append(row);
            });
        }
        
        // Add click handlers to rows (only for tables, not views)
        if (!tableInfo.isView) {
            tbody.find('tr').click(function() {
                const primaryValue = $(this).data('primary-value');
                openEditModal(primaryValue);
            });
        } else {
            // For views, change cursor to indicate non-clickable
            tbody.find('tr').css('cursor', 'default');
        }
        
        // Restore full opacity
        tbody.css('opacity', '1');
    });
}

// Update pagination info
function updatePagination() {
    const start = totalRecords > 0 ? currentOffset + 1 : 0;
    const end = Math.min(currentOffset + currentLimit, totalRecords);
    
    $('#paginationInfo').text(`Showing ${start} to ${end} of ${totalRecords} records`);
    
    $('#prevBtn').prop('disabled', currentOffset === 0);
    $('#nextBtn').prop('disabled', currentOffset + currentLimit >= totalRecords);
}

// Open insert modal
function openInsertModal() {
    currentEditRecord = null;
    $('#modalTitle').text('➕ Add New Record');
    $('#deleteRecordBtn').hide();
    buildFormFields(null);
    $('#recordModal').addClass('active');
}

// Open edit modal
function openEditModal(primaryValue) {
    $('#loading').addClass('active');
    
    $.ajax({
        url: '../api/',
        method: 'POST',
        data: {
            action: 'getRecord',
            table: currentTable,
            primaryKey: tableInfo.primaryKey,
            primaryValue: primaryValue
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                currentEditRecord = response.record;
                $('#modalTitle').text('✏️ Edit Record');
                $('#deleteRecordBtn').show();
                buildFormFields(response.record);
                $('#recordModal').addClass('active');
            }
            $('#loading').removeClass('active');
        },
        error: function(xhr) {
            showToast('Error loading record', 'error');
            $('#loading').removeClass('active');
        }
    });
}

// Build form fields based on column types
function buildFormFields(record) {
    const modalBody = $('#modalBody');
    modalBody.empty();
    
    tableInfo.columns.forEach(function(col) {
        // Skip auto-increment primary keys for insert
        if (!record && col.extra.toLowerCase().includes('auto_increment')) {
            return;
        }
        
        const value = record ? (record[col.name] || '') : '';
        const isRequired = !col.null && !col.extra.toLowerCase().includes('auto_increment');
        const isPrimaryKey = col.name === tableInfo.primaryKey;
        const disabled = record && isPrimaryKey ? 'disabled' : '';
        
        let formGroup = `<div class="form-group">
            <label for="field_${col.name}">
                ${col.name}
                ${isRequired ? '<span class="required">*</span>' : ''}
            </label>`;
        
        // Generate appropriate input based on type
        const baseType = col.baseType;
        
        if (baseType === 'enum' || baseType === 'set') {
            // Enum/Set: dropdown
            formGroup += `<select id="field_${col.name}" name="${col.name}" ${disabled}>
                <option value="">-- Select --</option>`;
            col.enumValues.forEach(function(enumVal) {
                const selected = value === enumVal ? 'selected' : '';
                formGroup += `<option value="${enumVal}" ${selected}>${enumVal}</option>`;
            });
            formGroup += `</select>`;
            
        } else if (baseType === 'date') {
            // Date: date input
            formGroup += `<input type="date" 
                                 id="field_${col.name}" 
                                 name="${col.name}" 
                                 value="${value}" 
                                 ${disabled}>`;
            
        } else if (baseType === 'datetime' || baseType === 'timestamp') {
            // DateTime: datetime-local input
            let datetimeValue = value;
            if (datetimeValue) {
                // Convert MySQL datetime to HTML datetime-local format
                datetimeValue = datetimeValue.replace(' ', 'T');
                if (datetimeValue.length === 16) {
                    // Add seconds if not present
                    datetimeValue += ':00';
                }
                datetimeValue = datetimeValue.substring(0, 16);
            }
            formGroup += `<input type="datetime-local" 
                                 id="field_${col.name}" 
                                 name="${col.name}" 
                                 value="${datetimeValue}" 
                                 ${disabled}>`;
            
        } else if (baseType === 'time') {
            // Time: time input
            formGroup += `<input type="time" 
                                 id="field_${col.name}" 
                                 name="${col.name}" 
                                 value="${value}" 
                                 ${disabled}>`;
            
        } else if (['int', 'integer', 'tinyint', 'smallint', 'mediumint', 'bigint', 'decimal', 'float', 'double'].includes(baseType)) {
            // Numeric: number input
            const step = ['decimal', 'float', 'double'].includes(baseType) ? 'any' : '1';
            formGroup += `<input type="number" 
                                 id="field_${col.name}" 
                                 name="${col.name}" 
                                 value="${value}" 
                                 step="${step}" 
                                 ${disabled}>`;
            
        } else if (['text', 'mediumtext', 'longtext'].includes(baseType)) {
            // Large text: textarea
            formGroup += `<textarea id="field_${col.name}" 
                                   name="${col.name}" 
                                   ${disabled}>${escapeHtml(value)}</textarea>`;
            
        } else if (baseType === 'varchar' || baseType === 'char') {
            // Check length for textarea vs input
            const length = parseInt(col.length) || 0;
            if (length > 80) {
                formGroup += `<textarea id="field_${col.name}" 
                                       name="${col.name}" 
                                       maxlength="${length}" 
                                       ${disabled}>${escapeHtml(value)}</textarea>`;
            } else {
                formGroup += `<input type="text" 
                                     id="field_${col.name}" 
                                     name="${col.name}" 
                                     value="${escapeHtml(value)}" 
                                     maxlength="${length}" 
                                     ${disabled}>`;
            }
            
        } else {
            // Default: text input
            formGroup += `<input type="text" 
                                 id="field_${col.name}" 
                                 name="${col.name}" 
                                 value="${escapeHtml(value)}" 
                                 ${disabled}>`;
        }
        
        formGroup += `<div class="field-info">${col.type}${!col.null ? ' • Required' : ''}</div>`;
        formGroup += `</div>`;
        
        modalBody.append(formGroup);
    });
}

// Save record (insert or update)
function saveRecord() {
    const formData = {};
    let hasError = false;
    
    $('#modalBody input, #modalBody textarea, #modalBody select').each(function() {
        const field = $(this);
        const name = field.attr('name');
        const value = field.val();
        
        if (!field.prop('disabled')) {
            formData[name] = value;
        }
    });
    
    const action = currentEditRecord ? 'updateRecord' : 'insertRecord';
    const data = {
        action: action,
        table: currentTable,
        data: JSON.stringify(formData)
    };
    
    if (currentEditRecord) {
        data.primaryKey = tableInfo.primaryKey;
        data.primaryValue = currentEditRecord[tableInfo.primaryKey];
    }
    
    $('#loading').addClass('active');
    
    $.ajax({
        url: '../api/',
        method: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showToast(response.message, 'success');
                closeModal();
                loadRecords();
            } else {
                showToast('Error: ' + response.error, 'error');
            }
            $('#loading').removeClass('active');
        },
        error: function(xhr) {
            const response = JSON.parse(xhr.responseText);
            showToast('Error: ' + (response.error || 'Unknown error'), 'error');
            $('#loading').removeClass('active');
        }
    });
}

// Show delete confirmation
function showDeleteConfirmation() {
    $('#confirmDialog').addClass('active');
}

// Close confirmation dialog
function closeConfirmDialog() {
    $('#confirmDialog').removeClass('active');
}

// Delete record
$('#confirmDeleteBtn').click(function() {
    if (!currentEditRecord) return;
    
    $('#loading').addClass('active');
    closeConfirmDialog();
    
    $.ajax({
        url: '../api/',
        method: 'POST',
        data: {
            action: 'deleteRecord',
            table: currentTable,
            primaryKey: tableInfo.primaryKey,
            primaryValue: currentEditRecord[tableInfo.primaryKey]
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showToast(response.message, 'success');
                closeModal();
                loadRecords();
            }
            $('#loading').removeClass('active');
        },
        error: function(xhr) {
            const response = JSON.parse(xhr.responseText);
            showToast('Error: ' + (response.error || 'Unknown error'), 'error');
            $('#loading').removeClass('active');
        }
    });
});

// Close modal
function closeModal() {
    $('#recordModal').removeClass('active');
    currentEditRecord = null;
}

// Show empty state
function showEmptyState() {
    $('#tableContent').hide();
    $('#emptyState').show();
    $('#addRecordBtn').hide();
    $('#loading').removeClass('active');
}

// Show toast notification
function showToast(message, type = 'success') {
    const toast = $('#toast');
    toast.text(message);
    toast.removeClass('success error');
    toast.addClass(type);
    toast.addClass('active');
    
    setTimeout(function() {
        toast.removeClass('active');
    }, 3000);
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

// Close modal on outside click
$(document).click(function(e) {
    if ($(e.target).is('#recordModal')) {
        closeModal();
    }
    if ($(e.target).is('#confirmDialog')) {
        closeConfirmDialog();
    }
});

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


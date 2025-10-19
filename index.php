<?php
/**
 * Database CRUD Manager - Main Interface
 * IP Authorization Check
 */
require_once 'login/auth_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database CRUD Manager</title>
    <style>
        :root {
            /* Sapphire Nightfall Whisper Color Palette */
            --color-sapphire-bright: #0474C4;      /* Vibrant medium blue */
            --color-sapphire-muted: #5379AE;       /* Dusty periwinkle blue */
            --color-sapphire-dark: #2C444C;        /* Dark desaturated teal */
            --color-sapphire-light: #A8C4EC;       /* Light pastel sky blue */
            --color-sapphire-rich: #06457F;        /* Deep rich sapphire */
            --color-sapphire-navy: #262B40;        /* Dark navy blue */
            
            /* Primary Colors - Based on Sapphire Palette */
            --color-primary: var(--color-sapphire-rich);
            --color-primary-light: var(--color-sapphire-bright);
            --color-primary-lighter: var(--color-sapphire-muted);
            --color-primary-lightest: var(--color-sapphire-light);
            --color-primary-pale: #E8F2FF;
            
            /* Success Colors - Teal from Sapphire Dark */
            --color-success: var(--color-sapphire-dark);
            --color-success-light: #4A6B73;
            --color-success-lighter: #6B8A94;
            --color-success-lightest: #8CA9B5;
            
            /* Danger Colors - Complementary to Sapphire */
            --color-danger: #C44704;
            --color-danger-light: #E06B3A;
            --color-danger-lighter: #F08F70;
            --color-danger-lightest: #FFB3A6;
            
            /* Warning Colors - Warm accent */
            --color-warning: #C4A004;
            --color-warning-light: #E0C63A;
            --color-warning-lighter: #F0D670;
            --color-warning-lightest: #FFE6A6;
            --color-warning-pale: #FFF9E6;
            
            /* Text Colors - Navy and grays */
            --color-text-primary: var(--color-sapphire-navy);
            --color-text-secondary: var(--color-sapphire-dark);
            --color-text-tertiary: var(--color-sapphire-muted);
            --color-text-muted: #8A9BA8;
            
            /* Background Colors - Light sapphire tones */
            --color-bg-body-start: #F8FAFC;
            --color-bg-body-end: #F0F4F8;
            --color-bg-white: #FFFFFF;
            --color-bg-light: #F5F8FC;
            --color-bg-lighter: #E8F2FF;
            --color-bg-hover: #F0F7FF;
            --color-bg-active: #E0EFFF;
            
            /* Border Colors - Sapphire variations */
            --color-border-primary: var(--color-sapphire-light);
            --color-border-light: #D1E0F0;
            --color-border-lighter: #E0E8F0;
            --color-border-input: #C4D0E0;
            --color-border-gray: #B8C4D0;
            
            /* Neutral Colors - Blue-tinted grays */
            --color-gray-100: #F5F7FA;
            --color-gray-200: #E8ECF1;
            --color-gray-300: #D1D8E0;
            --color-gray-400: #B8C4D0;
            
            /* Accent Colors */
            --color-required: #E06B3A;
            
            /* Shadows - Sapphire-tinted */
            --shadow-sm: 0 2px 8px rgba(6, 69, 127, 0.08);
            --shadow-md: 0 4px 12px rgba(6, 69, 127, 0.12);
            --shadow-lg: 0 4px 20px rgba(6, 69, 127, 0.10);
            --shadow-xl: 0 10px 40px rgba(6, 69, 127, 0.15);
            --shadow-xxl: 0 10px 40px rgba(6, 69, 127, 0.20);
            
            /* Overlays - Sapphire-tinted */
            --overlay-light: rgba(6, 69, 127, 0.15);
            --overlay-dark: rgba(6, 69, 127, 0.25);
            --overlay-primary: rgba(4, 116, 196, 0.08);
            --overlay-focus: rgba(168, 196, 236, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--color-bg-body-start) 0%, var(--color-bg-body-end) 100%);
            color: var(--color-text-primary);
            padding: 20px;
            min-height: 100vh;
            opacity: 0;
            animation: pageLoadFadeIn 0.3s ease forwards;
        }

        @keyframes pageLoadFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        body.page-transitioning {
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: var(--color-bg-white);
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, var(--color-primary-lightest) 0%, var(--color-bg-white) 100%);
            padding: 25px 30px 0 30px;
            border-bottom: 3px solid var(--color-primary-light);
        }

        .header h1 {
            color: var(--color-primary);
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .controls {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
            justify-content: flex-start;
            margin-bottom: 15px;
        }

        .nav-menu {
            display: flex;
            gap: 0;
            margin: 0 -30px -1px -30px;
            border-top: 1px solid var(--color-border-lighter);
        }

        .nav-menu a {
            flex: 1;
            text-align: center;
            padding: 8px 20px;
            text-decoration: none;
            color: var(--color-text-tertiary);
            font-weight: 500;
            font-size: 14px;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .nav-menu a:hover {
            background: rgba(255, 255, 255, 0.5);
            color: var(--color-primary-light);
        }

        .nav-menu a.active {
            color: var(--color-primary);
            background: rgba(255, 255, 255, 0.3);
            border-bottom-color: var(--color-primary-light);
            font-weight: 600;
        }

        .nav-menu a .nav-icon {
            font-size: 18px;
        }


        .control-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        label {
            font-weight: 500;
            color: var(--color-primary);
            font-size: 14px;
        }

        select, input, textarea, button {
            font-family: inherit;
            font-size: 14px;
            padding: 10px 15px;
            border: 2px solid var(--color-border-primary);
            border-radius: 6px;
            outline: none;
            transition: all 0.3s ease;
        }

        select {
            background: var(--color-bg-white);
            cursor: pointer;
            min-width: 200px;
        }

        select:hover, select:focus {
            border-color: var(--color-primary-light);
        }

        input, textarea {
            background: var(--color-bg-white);
        }

        input:focus, textarea:focus {
            border-color: var(--color-primary-light);
            box-shadow: 0 0 0 3px var(--overlay-focus);
        }

        button {
            background: linear-gradient(135deg, var(--color-primary-light) 0%, var(--color-primary-lighter) 100%);
            color: var(--color-bg-white);
            border: 2px solid var(--color-primary);
            cursor: pointer;
            font-weight: 600;
            padding: 10px 20px;
            transition: all 0.3s ease;
        }

        button:hover {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-light) 100%);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        button:active {
            transform: translateY(0);
        }

        button.delete-btn {
            background: linear-gradient(135deg, var(--color-danger-lighter) 0%, var(--color-danger-lightest) 100%);
            color: var(--color-danger);
            border: 2px solid var(--color-danger-light);
        }

        button.delete-btn:hover {
            background: linear-gradient(135deg, var(--color-danger-light) 0%, var(--color-danger-lighter) 100%);
        }

        /* Compact button for header controls */
        .controls button {
            padding: 4px 4px;
            font-size: 12px;
            border-width: 1px;
        }
        
        .controls button:hover {
            transform: translateY(-1px);
        }

        .content {
            padding: 30px;
        }

        .table-wrapper {
            overflow-x: auto;
            border: 2px solid var(--color-border-light);
            border-radius: 8px;
            margin-top: 20px;
            background: var(--color-bg-white);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
            table-layout: fixed; /* Prevent column width changes */
        }

        thead {
            background: linear-gradient(135deg, var(--color-primary-lightest) 0%, var(--color-primary-pale) 100%);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        th {
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            color: var(--color-sapphire-navy);
            border-bottom: 2px solid var(--color-primary-light);
            cursor: pointer;
            user-select: none;
            white-space: nowrap;
            position: relative;
        }

        th:hover {
            background: linear-gradient(135deg, var(--color-primary-light) 0%, var(--color-primary-lighter) 100%);
        }

        th .sort-indicator {
            display: inline-block;
            margin-left: 5px;
            font-size: 12px;
        }

        .filter-row {
            background: var(--color-bg-light);
        }

        .filter-row th {
            padding: 8px 12px;
            background: var(--color-primary-pale);
            cursor: default;
        }

        .filter-row th:hover {
            background: var(--color-primary-pale);
        }

        .filter-input {
            width: 100%;
            padding: 6px 8px;
            font-size: 13px;
            border: 1px solid var(--color-border-input);
            border-radius: 4px;
        }

        .filter-input:focus {
            border-color: var(--color-primary-light);
            box-shadow: 0 0 0 2px var(--overlay-focus);
        }

        tbody tr {
            border-bottom: 1px solid var(--color-border-lighter);
            transition: all 0.2s ease;
            cursor: pointer;
        }

        #tableBody {
            transition: opacity 0.15s ease;
        }

        tbody tr:hover {
            background: var(--color-bg-hover);
            box-shadow: var(--shadow-sm);
        }

        tbody tr:active {
            background: var(--color-bg-active);
        }

        td {
            padding: 12px;
            color: var(--color-text-secondary);
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--color-text-muted);
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding: 15px;
            background: var(--color-bg-light);
            border-radius: 8px;
        }

        .pagination-info {
            color: var(--color-text-tertiary);
            font-size: 14px;
        }

        .pagination-buttons {
            display: flex;
            gap: 10px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: var(--overlay-light);
            animation: fadeIn 0.3s ease;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: var(--color-bg-white);
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow: hidden;
            animation: slideIn 0.3s ease;
            box-shadow: var(--shadow-xl);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--color-primary-lightest) 0%, var(--color-primary-pale) 100%);
            padding: 20px 25px;
            border-bottom: 2px solid var(--color-primary-lighter);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            color: var(--color-primary);
            font-size: 22px;
            font-weight: 600;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 28px;
            color: var(--color-primary);
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .modal-close:hover {
            background: var(--overlay-primary);
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 25px;
            overflow-y: auto;
            max-height: calc(80vh - 150px);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--color-text-primary);
            font-weight: 500;
        }

        .form-group label .required {
            color: var(--color-required);
            margin-left: 4px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-group .field-info {
            font-size: 12px;
            color: var(--color-text-muted);
            margin-top: 4px;
        }

        .modal-footer {
            padding: 20px 25px;
            background: var(--color-bg-light);
            border-top: 1px solid var(--color-border-light);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--color-gray-300) 0%, var(--color-gray-200) 100%);
            color: var(--color-text-secondary);
            border: 2px solid var(--color-border-gray);
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, var(--color-gray-400) 0%, var(--color-gray-300) 100%);
        }

        /* Confirmation Dialog */
        .confirm-dialog {
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: none;
            align-items: center;
            justify-content: center;
        }

        .confirm-dialog.active {
            display: flex;
        }

        .confirm-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            text-align: center;
        }

        .confirm-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .confirm-content h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 20px;
        }

        .confirm-content p {
            color: #666;
            margin-bottom: 25px;
        }

        .confirm-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        /* Loading Spinner */
        .loading {
            display: none;
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .loading.active {
            display: block;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #8ec5e0;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header {
                padding: 20px 20px 0 20px;
            }

            .header h1 {
                font-size: 22px;
            }

            .nav-menu {
                margin: 0 -20px 15px -20px;
                flex-direction: column;
            }

            .nav-menu a {
                border-bottom: 1px solid var(--color-border-lighter);
                border-bottom-width: 1px;
                padding: 12px 15px;
            }

            .nav-menu a.active {
                border-bottom-width: 1px;
                border-left: 3px solid var(--color-primary-light);
            }

            .controls {
                flex-direction: column;
                align-items: stretch;
            }

            .control-group {
                flex-direction: column;
                align-items: stretch;
            }

            select {
                width: 100%;
            }

            .content {
                padding: 15px;
            }

            .modal-content {
                width: 95%;
            }

            .pagination {
                flex-direction: column;
                gap: 15px;
            }
        }

        /* Notification Toast */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #2c5f7e;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 3000;
            animation: slideInRight 0.3s ease;
            display: none;
        }

        .toast.active {
            display: block;
        }

        .toast.success {
            background: #2d6848;
        }

        .toast.error {
            background: #8b2e3a;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
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
    include 'templates/header.php';
    ?>
            <div class="loading active" id="loading">
                <div class="spinner"></div>
                <p>Loading...</p>
            </div>

            <div id="tableContent" style="display: none;">
                <div class="table-wrapper">
                    <table id="dataTable">
                        <thead id="tableHead"></thead>
                        <tbody id="tableBody"></tbody>
                    </table>
                </div>

                <div class="pagination">
                    <div class="pagination-info" id="paginationInfo"></div>
                    <div class="pagination-buttons">
                        <button id="prevBtn">◀ Previous</button>
                        <button id="nextBtn">Next ▶</button>
                    </div>
                </div>
            </div>

            <div class="empty-state" id="emptyState">
                <div class="empty-state-icon">📋</div>
                <h3>No Table Selected</h3>
                <p>Please select a table from the dropdown above to view and manage records.</p>
            </div>
        </div>
    </div>

    <!-- Edit/Insert Modal -->
    <div class="modal" id="recordModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Edit Record</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Form fields will be generated dynamically -->
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeModal()">Cancel</button>
                <button id="deleteRecordBtn" class="delete-btn" style="display: none;">🗑️ Delete</button>
                <button id="saveRecordBtn">💾 Save</button>
            </div>
        </div>
    </div>

    <!-- Confirmation Dialog -->
    <div class="confirm-dialog" id="confirmDialog">
        <div class="confirm-content">
            <div class="confirm-icon">⚠️</div>
            <h3>Confirm Deletion</h3>
            <p id="confirmMessage">Are you sure you want to delete this record? This action cannot be undone.</p>
            <div class="confirm-buttons">
                <button class="btn-secondary" onclick="closeConfirmDialog()">Cancel</button>
                <button class="delete-btn" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
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
            loadTables();
            
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
            
            // Check for table parameter in URL
            const urlParams = new URLSearchParams(window.location.search);
            const tableParam = urlParams.get('table');
            
            $('#tableSelect').change(function() {
                currentTable = $(this).val();
                updateNavLinks();
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
                url: 'api.php?action=getTables',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const select = $('#tableSelect');
                        select.empty();
                        select.append('<option value="">-- Choose a table --</option>');
                        
                        response.tables.forEach(function(table) {
                            select.append(`<option value="${table}">${table}</option>`);
                        });
                        
                        // Check for table parameter in URL and select it
                        const urlParams = new URLSearchParams(window.location.search);
                        const tableParam = urlParams.get('table');
                        if (tableParam && response.tables.includes(tableParam)) {
                            select.val(tableParam).trigger('change');
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
                url: 'api.php?action=getTableInfo&table=' + encodeURIComponent(currentTable),
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
                        $('#addRecordBtn').show();
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
                url: 'api.php?' + $.param(params),
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
                    showToast('Error loading records', 'error');
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
                        let row = '<tr data-primary-value="' + record[tableInfo.primaryKey] + '">';
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
                
                // Add click handlers to rows
                tbody.find('tr').click(function() {
                    const primaryValue = $(this).data('primary-value');
                    openEditModal(primaryValue);
                });
                
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
                url: 'api.php',
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
                url: 'api.php',
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
                url: 'api.php',
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
    </script>

    <?php include 'templates/footer.php'; ?>
</body>
</html>


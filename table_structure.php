<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Table Structure - Database CRUD Manager</title>
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
            margin-bottom: 20px;
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

        select, button {
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

        button {
            background: linear-gradient(135deg, var(--color-success-lighter) 0%, var(--color-success-lightest) 100%);
            color: var(--color-success);
            border: 2px solid var(--color-success-light);
            cursor: pointer;
            font-weight: 600;
            padding: 10px 20px;
            transition: all 0.3s ease;
        }

        button:hover {
            background: linear-gradient(135deg, var(--color-success-light) 0%, var(--color-success-lighter) 100%);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .content {
            padding: 30px;
        }

        .table-info {
            background: var(--color-bg-lighter);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid var(--color-border-light);
        }

        .table-info h2 {
            color: var(--color-primary);
            font-size: 22px;
            margin-bottom: 10px;
        }

        .table-info p {
            color: var(--color-text-secondary);
            margin-bottom: 5px;
        }

        .structure-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--color-bg-white);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .structure-table thead {
            background: linear-gradient(135deg, var(--color-primary-lightest) 0%, var(--color-primary-pale) 100%);
        }

        .structure-table th {
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            color: var(--color-sapphire-navy);
            border-bottom: 2px solid var(--color-primary-light);
            white-space: nowrap;
        }

        .structure-table td {
            padding: 12px;
            color: var(--color-text-secondary);
            border-bottom: 1px solid var(--color-border-lighter);
            vertical-align: top;
        }

        .structure-table tbody tr:hover {
            background: var(--color-bg-hover);
        }

        .structure-table tbody tr {
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .structure-table tbody tr:hover {
            background: var(--color-bg-hover);
            box-shadow: var(--shadow-sm);
        }

        .field-type {
            font-family: 'Courier New', monospace;
            background: var(--color-bg-light);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
            color: var(--color-primary);
        }

        .field-attributes {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-top: 4px;
        }

        .attribute-badge {
            background: var(--color-primary-pale);
            color: var(--color-primary);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
            border: 1px solid var(--color-border-primary);
            display: inline-block;
            min-width: 60px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .attribute-badge.primary {
            background: var(--color-success-lightest);
            color: var(--color-success);
            border-color: var(--color-success-light);
        }

        .attribute-badge.required {
            background: var(--color-danger-lightest);
            color: var(--color-danger);
            border-color: var(--color-danger-light);
        }

        .attribute-badge.auto-increment {
            background: var(--color-warning-lightest);
            color: var(--color-warning);
            border-color: var(--color-warning-light);
        }

        .attribute-badge.unique {
            background: var(--color-primary-lightest);
            color: var(--color-primary);
            border-color: var(--color-primary-light);
        }

        .attribute-badge.index {
            background: var(--color-sapphire-light);
            color: var(--color-sapphire-muted);
            border-color: var(--color-sapphire-muted);
        }

        .attribute-badge.dimmed {
            opacity: 0.2;
            cursor: default;
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

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid var(--color-border-primary);
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
            outline: none;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--color-primary-light);
            box-shadow: 0 0 0 3px var(--overlay-focus);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 60px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .checkbox-item input[type="checkbox"] {
            width: auto;
            margin: 0;
        }

        .info-button {
            display: inline-block;
            width: 16px;
            height: 16px;
            background: var(--color-primary-light);
            color: var(--color-bg-white);
            border-radius: 50%;
            text-align: center;
            line-height: 16px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            margin-left: 8px;
            transition: all 0.3s ease;
            position: relative;
        }

        .info-button:hover {
            background: var(--color-primary);
            transform: scale(1.1);
        }

        .info-tooltip {
            position: absolute;
            top: 25px;
            left: 0;
            background: var(--color-sapphire-navy);
            color: var(--color-bg-white);
            padding: 16px 20px;
            border-radius: 8px;
            font-size: 12px;
            line-height: 1.5;
            text-align: left;
            z-index: 1001;
            box-shadow: var(--shadow-lg);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            width: 400px;
            white-space: normal;
        }

        .info-tooltip::after {
            content: '';
            position: absolute;
            bottom: 100%;
            left: 20px;
            border: 6px solid transparent;
            border-bottom-color: var(--color-sapphire-navy);
        }

        .info-button:hover .info-tooltip {
            opacity: 1;
            visibility: visible;
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
            cursor: pointer;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, var(--color-gray-400) 0%, var(--color-gray-300) 100%);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--color-danger-lighter) 0%, var(--color-danger-lightest) 100%);
            color: var(--color-danger);
            border: 2px solid var(--color-danger-light);
            cursor: pointer;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, var(--color-danger-light) 0%, var(--color-danger-lighter) 100%);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--color-success-lighter) 0%, var(--color-success-lightest) 100%);
            color: var(--color-success);
            border: 2px solid var(--color-success-light);
            cursor: pointer;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--color-success-light) 0%, var(--color-success-lighter) 100%);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

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

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--color-text-muted);
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 40px;
            color: var(--color-text-muted);
        }

        .loading.active {
            display: block;
        }

        .spinner {
            border: 4px solid var(--color-gray-200);
            border-top: 4px solid var(--color-primary-light);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--color-primary);
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: var(--color-primary-light);
            transform: translateX(-2px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: var(--color-bg-white);
            border: 1px solid var(--color-border-light);
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }

        .stat-card h3 {
            color: var(--color-primary);
            font-size: 24px;
            margin-bottom: 5px;
        }

        .stat-card p {
            color: var(--color-text-secondary);
            font-size: 14px;
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

            .structure-table {
                font-size: 14px;
            }

            .structure-table th,
            .structure-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Table Structure Viewer/Editor</h1>
            
            <div class="controls">
                <div class="control-group">
                    <label for="tableSelect">Select Table:</label>
                    <select id="tableSelect">
                        <option value="">-- Choose a table --</option>
                    </select>
                </div>
                <button id="addColumnBtn" style="display: none;">➕ Add Column</button>
            </div>
            
            <!-- Navigation Menu -->
            <nav class="nav-menu">
                <a href="index.php" class="nav-link">
                    <span class="nav-icon">📊</span>
                    <span>Data Manager</span>
                </a>
                <a href="table_structure.php" class="active nav-link">
                    <span class="nav-icon">🔍</span>
                    <span>Table Structure</span>
                </a>
                <a href="query.php" class="nav-link">
                    <span class="nav-icon">⚡</span>
                    <span>SQL Query Builder</span>
                </a>
            </nav>
        </div>

        <div class="content">
            <div class="loading active" id="loading">
                <div class="spinner"></div>
                <p>Loading...</p>
            </div>

            <div id="tableStructure" style="display: none;">
                <div class="table-info" id="tableInfo">
                    <!-- Table information will be populated here -->
                </div>

                <div class="stats-grid" id="statsGrid">
                    <!-- Statistics will be populated here -->
                </div>

                <div class="structure-table-wrapper">
                    <table class="structure-table" id="structureTable">
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Type</th>
                                <th>Null</th>
                                <th>Key</th>
                                <th>Default</th>
                                <th>Extra</th>
                                <th>Attributes</th>
                            </tr>
                        </thead>
                        <tbody id="structureBody">
                            <!-- Structure data will be populated here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="empty-state" id="emptyState">
                <div class="empty-state-icon">📋</div>
                <h3>No Table Selected</h3>
                <p>Please select a table from the dropdown above to view its structure.</p>
            </div>
        </div>
    </div>

    <!-- Column Edit Modal -->
    <div class="modal" id="columnModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Edit Column</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Form fields will be generated dynamically -->
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeModal()">Cancel</button>
                <button class="btn-danger" id="deleteColumnBtn" style="display: none;">🗑️ Delete Column</button>
                <button class="btn-primary" id="saveColumnBtn">💾 Save</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Global state
        let currentTable = '';
        let tableInfo = null;
        let currentEditColumn = null;

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
            
            $('#tableSelect').change(function() {
                currentTable = $(this).val();
                updateNavLinks();
                if (currentTable) {
                    loadTableStructure();
                    $('#addColumnBtn').show();
                } else {
                    showEmptyState();
                    $('#addColumnBtn').hide();
                }
            });

            $('#addColumnBtn').click(function() {
                openAddColumnModal();
            });

            $('#saveColumnBtn').click(function() {
                saveColumn();
            });

            $('#deleteColumnBtn').click(function() {
                deleteColumn();
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
                    showError('Error loading tables: ' + xhr.responseText);
                    $('#loading').removeClass('active');
                }
            });
        }

        // Load table structure
        function loadTableStructure() {
            $('#loading').addClass('active');
            $('#tableStructure').hide();
            
            $.ajax({
                url: 'api.php?action=getTableInfo&table=' + encodeURIComponent(currentTable),
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        tableInfo = response;
                        displayTableInfo();
                        displayStructureTable();
                        $('#tableStructure').show();
                        $('#emptyState').hide();
                    }
                    $('#loading').removeClass('active');
                },
                error: function(xhr) {
                    showError('Error loading table structure');
                    $('#loading').removeClass('active');
                }
            });
        }

        // Display table information
        function displayTableInfo() {
            const tableInfoDiv = $('#tableInfo');
            const statsGrid = $('#statsGrid');
            
            // Count different types of columns
            const totalColumns = tableInfo.columns.length;
            const primaryKeys = tableInfo.columns.filter(col => col.key === 'PRI').length;
            const nullableColumns = tableInfo.columns.filter(col => col.null).length;
            const autoIncrementColumns = tableInfo.columns.filter(col => col.extra.toLowerCase().includes('auto_increment')).length;
            
            tableInfoDiv.html(`
                <h2>📊 Table: ${currentTable}</h2>
                <p><strong>Primary Key:</strong> ${tableInfo.primaryKey || 'None'}</p>
                <p><strong>Total Columns:</strong> ${totalColumns}</p>
            `);
            
            statsGrid.html(`
                <div class="stat-card">
                    <h3>${totalColumns}</h3>
                    <p>Total Columns</p>
                </div>
                <div class="stat-card">
                    <h3>${primaryKeys}</h3>
                    <p>Primary Keys</p>
                </div>
                <div class="stat-card">
                    <h3>${nullableColumns}</h3>
                    <p>Nullable Fields</p>
                </div>
                <div class="stat-card">
                    <h3>${autoIncrementColumns}</h3>
                    <p>Auto Increment</p>
                </div>
            `);
        }

        // Display structure table
        function displayStructureTable() {
            const tbody = $('#structureBody');
            tbody.empty();
            
            // Define all possible attributes
            const allAttributes = [
                { key: 'primary', text: 'PRIMARY', class: 'primary' },
                { key: 'unique', text: 'UNIQUE', class: 'unique' },
                { key: 'index', text: 'INDEX', class: 'index' },
                { key: 'required', text: 'NOT NULL', class: 'required' },
                { key: 'auto_increment', text: 'AUTO_INCREMENT', class: 'auto-increment' }
            ];
            
            tableInfo.columns.forEach(function(col) {
                // Determine which attributes are applicable
                const applicableAttributes = [];
                applicableAttributes.push(col.key === 'PRI' ? 'primary' : null);
                applicableAttributes.push(col.key === 'UNI' ? 'unique' : null);
                applicableAttributes.push(col.key === 'MUL' ? 'index' : null);
                applicableAttributes.push(!col.null ? 'required' : null);
                applicableAttributes.push(col.extra.toLowerCase().includes('auto_increment') ? 'auto_increment' : null);
                
                // Create attribute buttons - all attributes, dimmed if not applicable
                const attributesHtml = allAttributes.map(attr => {
                    const isApplicable = applicableAttributes.includes(attr.key);
                    const dimmedClass = isApplicable ? '' : 'dimmed';
                    return `<span class="attribute-badge ${attr.class} ${dimmedClass}">${attr.text}</span>`;
                }).join('');
                
                const row = `
                    <tr data-column-name="${col.name}">
                        <td><strong>${col.name}</strong></td>
                        <td><span class="field-type">${col.type}</span></td>
                        <td>${col.null ? 'YES' : 'NO'}</td>
                        <td>${col.key || ''}</td>
                        <td>${col.default !== null ? col.default : '<em>NULL</em>'}</td>
                        <td>${col.extra || ''}</td>
                        <td><div class="field-attributes">${attributesHtml}</div></td>
                    </tr>
                `;
                tbody.append(row);
            });
            
            // Add click handlers to rows
            tbody.find('tr').click(function() {
                const columnName = $(this).data('column-name');
                openEditColumnModal(columnName);
            });
        }

        // Open add column modal
        function openAddColumnModal() {
            currentEditColumn = null;
            $('#modalTitle').text('➕ Add New Column');
            $('#deleteColumnBtn').hide();
            buildColumnForm(null);
            $('#columnModal').addClass('active');
        }

        // Open edit column modal
        function openEditColumnModal(columnName) {
            const column = tableInfo.columns.find(col => col.name === columnName);
            if (!column) return;
            
            currentEditColumn = column;
            $('#modalTitle').text('✏️ Edit Column: ' + columnName);
            $('#deleteColumnBtn').show();
            buildColumnForm(column);
            $('#columnModal').addClass('active');
        }

        // Build column form
        function buildColumnForm(column) {
            const modalBody = $('#modalBody');
            modalBody.empty();
            
            const isNew = !column;
            
            modalBody.html(`
                <div class="form-group">
                    <label for="fieldName">Column Name:</label>
                    <input type="text" id="fieldName" name="name" value="${column ? column.name : ''}" ${isNew ? '' : 'readonly'}>
                </div>
                
                ${isNew ? `
                <div class="form-group">
                    <label for="fieldPosition">Position:</label>
                    <select id="fieldPosition" name="position">
                        <option value="end">At the end (default)</option>
                        <option value="first">At the beginning</option>
                        ${tableInfo ? tableInfo.columns.map((col, index) => 
                            `<option value="after_${col.name}">After ${col.name}</option>`
                        ).join('') : ''}
                    </select>
                </div>
                ` : ''}
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fieldType">Data Type:</label>
                        <select id="fieldType" name="type">
                            <option value="VARCHAR(255)" ${column && column.type.startsWith('VARCHAR') ? 'selected' : ''}>VARCHAR(255)</option>
                            <option value="INT" ${column && column.type === 'INT' ? 'selected' : ''}>INT</option>
                            <option value="BIGINT" ${column && column.type === 'BIGINT' ? 'selected' : ''}>BIGINT</option>
                            <option value="TEXT" ${column && column.type === 'TEXT' ? 'selected' : ''}>TEXT</option>
                            <option value="DATETIME" ${column && column.type === 'DATETIME' ? 'selected' : ''}>DATETIME</option>
                            <option value="DATE" ${column && column.type === 'DATE' ? 'selected' : ''}>DATE</option>
                            <option value="TIME" ${column && column.type === 'TIME' ? 'selected' : ''}>TIME</option>
                            <option value="DECIMAL(10,2)" ${column && column.type.startsWith('DECIMAL') ? 'selected' : ''}>DECIMAL(10,2)</option>
                            <option value="FLOAT" ${column && column.type === 'FLOAT' ? 'selected' : ''}>FLOAT</option>
                            <option value="BOOLEAN" ${column && column.type === 'BOOLEAN' ? 'selected' : ''}>BOOLEAN</option>
                            <option value="ENUM" ${column && column.type.startsWith('ENUM') ? 'selected' : ''}>ENUM</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="fieldDefault">Default Value:</label>
                        <input type="text" id="fieldDefault" name="default" value="${column ? (column.default || '') : ''}" placeholder="Leave empty for NULL">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Column Attributes:</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="checkbox" id="attrNull" name="null" ${column && column.null ? 'checked' : ''}>
                            <label for="attrNull">Allow NULL</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="attrPrimary" name="primary" ${column && column.key === 'PRI' ? 'checked' : ''}>
                            <label for="attrPrimary">Primary Key</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="attrUnique" name="unique" ${column && column.key === 'UNI' ? 'checked' : ''}>
                            <label for="attrUnique">Unique</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="attrAutoIncrement" name="auto_increment" ${column && column.extra.toLowerCase().includes('auto_increment') ? 'checked' : ''}>
                            <label for="attrAutoIncrement">Auto Increment</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="fieldExtra">
                        Extra Attributes:
                        <span class="info-button" title="Click for help">i
                            <div class="info-tooltip">
                                <strong>Common Extra Attributes:</strong><br>
                                • <code>COMMENT 'description'</code> - Column comment<br>
                                • <code>ON UPDATE CURRENT_TIMESTAMP</code> - Auto-update timestamps<br>
                                • <code>CHARACTER SET utf8mb4</code> - Character set<br>
                                • <code>COLLATE utf8mb4_unicode_ci</code> - Collation<br>
                                • <code>GENERATED ALWAYS AS (expression)</code> - Generated columns<br>
                                • <code>ZEROFILL</code> - Zero-padded numbers<br>
                                • <code>UNSIGNED</code> - Unsigned numbers<br>
                                • <code>STORED</code> or <code>VIRTUAL</code> - For generated columns
                            </div>
                        </span>
                    </label>
                    <textarea id="fieldExtra" name="extra" placeholder="Additional MySQL attributes like 'COMMENT \'description\'' or 'ON UPDATE CURRENT_TIMESTAMP'">${column ? (column.extra || '') : ''}</textarea>
                </div>
            `);
        }

        // Save column
        function saveColumn() {
            const formData = {
                name: $('#fieldName').val(),
                type: $('#fieldType').val(),
                default: $('#fieldDefault').val() || null,
                null: $('#attrNull').is(':checked'),
                primary: $('#attrPrimary').is(':checked'),
                unique: $('#attrUnique').is(':checked'),
                auto_increment: $('#attrAutoIncrement').is(':checked'),
                extra: $('#fieldExtra').val()
            };
            
            // Add position for new columns
            if (!currentEditColumn) {
                formData.position = $('#fieldPosition').val();
            }
            
            if (!formData.name) {
                alert('Please enter a column name');
                return;
            }
            
            const action = currentEditColumn ? 'updateColumn' : 'addColumn';
            const data = {
                action: action,
                table: currentTable,
                data: JSON.stringify(formData)
            };
            
            if (currentEditColumn) {
                data.oldName = currentEditColumn.name;
            }
            
            $.ajax({
                url: 'api.php',
                method: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        closeModal();
                        loadTableStructure();
                    } else {
                        alert('Error: ' + response.error);
                    }
                },
                error: function(xhr) {
                    const response = JSON.parse(xhr.responseText);
                    alert('Error: ' + (response.error || 'Unknown error'));
                }
            });
        }

        // Delete column
        function deleteColumn() {
            if (!currentEditColumn) return;
            
            if (confirm('Are you sure you want to delete the column "' + currentEditColumn.name + '"? This action cannot be undone.')) {
                $.ajax({
                    url: 'api.php',
                    method: 'POST',
                    data: {
                        action: 'deleteColumn',
                        table: currentTable,
                        columnName: currentEditColumn.name
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(response.message);
                            closeModal();
                            loadTableStructure();
                        } else {
                            alert('Error: ' + response.error);
                        }
                    },
                    error: function(xhr) {
                        const response = JSON.parse(xhr.responseText);
                        alert('Error: ' + (response.error || 'Unknown error'));
                    }
                });
            }
        }

        // Close modal
        function closeModal() {
            $('#columnModal').removeClass('active');
            currentEditColumn = null;
        }

        // Show empty state
        function showEmptyState() {
            $('#tableStructure').hide();
            $('#emptyState').show();
            $('#loading').removeClass('active');
        }

        // Show error
        function showError(message) {
            alert('Error: ' + message);
        }
    </script>
</body>
</html>

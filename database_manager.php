<?php
/**
 * Database Manager - Database CRUD Manager
 * IP Authorization Check
 */
require_once 'login/auth_check.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Manager - Database CRUD Manager</title>
    <link rel="stylesheet" href="styles/common.css">
    <style>
        /* Page-specific styles for database_manager.php */

        /* Actions dropdown */
        .actions-dropdown { position: relative; }
        .actions-dropdown .dropdown-toggle {
            padding: 6px 12px; font-size: 12px; border: 1px solid var(--color-border-light);
            background: var(--color-bg-white); color: var(--color-text-primary); border-radius: 8px;
        }
        .actions-dropdown .dropdown-menu {
            position: absolute; right: 0; top: 100%; margin-top: 6px; min-width: 180px;
            background: var(--color-bg-white); border: 1px solid var(--color-border-light);
            border-radius: 10px; box-shadow: var(--shadow-lg); padding: 8px; display: none; z-index: 100;
        }
        .actions-dropdown .dropdown-menu.show { display: block; }
        .actions-dropdown .dropdown-menu ul { list-style: none; margin: 0; padding: 0; }
        .actions-dropdown .dropdown-menu li { margin: 0; }
        .actions-dropdown .dropdown-menu .menu-item {
            width: 100%; text-align: left; padding: 8px 10px; font-size: 12px; border: none; background: transparent;
            border-radius: 6px; color: var(--color-text-primary); cursor: pointer;
        }
        .actions-dropdown .dropdown-menu .menu-item:hover { background: var(--color-bg-hover); color: var(--color-primary); }
        .actions-dropdown .dropdown-menu .menu-item:disabled { opacity: 0.5; cursor: not-allowed; }

        /* Column builder grouping */
        #columnsBuilder { margin-top: 4px; }
        #columnsBuilder .column-rows { display: flex; flex-direction: column; gap: 10px; }
        .column-row { border: 1px solid var(--color-border-light); background: var(--color-bg-lighter); border-radius: 10px; padding: 10px; box-shadow: var(--shadow-sm); position: relative; }
        .column-row .row-line { display: flex; gap: 8px; align-items: center; flex-wrap: nowrap; }
        .column-row .col-badge { background: var(--color-primary-pale); color: var(--color-primary); font-weight: 600; font-size: 12px; padding: 2px 8px; border-radius: 999px; }
        .column-row.dragging { opacity: 0.6; }
        .column-row.drop-before { outline: 2px dashed var(--color-primary-light); outline-offset: -6px; }
        .column-row.drop-after { outline: 2px dashed var(--color-primary-light); outline-offset: -6px; }
        .drag-handle { cursor: grab; user-select: none; font-size: 16px; color: var(--color-primary-lighter); }
        .drag-handle:active { cursor: grabbing; }


        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .dashboard-card {
            background: var(--color-bg-white);
            border: 2px solid var(--color-border-light);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .dashboard-card:hover {
            box-shadow: var(--shadow-md);
        }

        .dashboard-card h3 {
            color: var(--color-primary);
            font-size: 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dashboard-card .card-icon {
            font-size: 24px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 10px;
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            background: var(--color-bg-lighter);
            border-radius: 8px;
            border: 1px solid var(--color-border-lighter);
        }

        .stat-value {
            font-size: 24px;
            font-weight: 600;
            color: var(--color-primary);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 12px;
            color: var(--color-text-tertiary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }

        .action-buttons button {
            flex: 1;
            min-width: 120px;
            padding: 8px 12px;
            font-size: 13px;
        }

        .database-list {
            background: var(--color-bg-white);
            border: 2px solid var(--color-border-light);
            border-radius: 8px;
            overflow: hidden;
        }

        .database-list-header {
            background: linear-gradient(135deg, var(--color-primary-lightest) 0%, var(--color-primary-pale) 100%);
            padding: 15px 20px;
            border-bottom: 2px solid var(--color-primary-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .database-list-header h3 {
            color: var(--color-primary);
            font-size: 18px;
            margin: 0;
        }

        .database-item {
            padding: 15px 20px;
            border-bottom: 1px solid var(--color-border-lighter);
            display: flex;
            gap: 20px;
            align-items: center;
            transition: all 0.2s ease;
        }

        /* Database Name Section (Left Part) */
        .database-name-section {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            min-width: 0; /* Allow text truncation */
        }

        .expand-indicator {
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--color-text-tertiary);
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .expand-indicator:hover {
            color: var(--color-primary);
            transform: scale(1.1);
        }

        .expand-indicator::before {
            content: '▶';
            font-size: 12px;
            transition: transform 0.2s ease;
        }

        .expand-indicator.expanded::before {
            transform: rotate(90deg);
        }

        .database-icon {
            font-size: 20px;
            color: var(--color-primary);
            flex-shrink: 0;
        }

        .database-main-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-width: 0; /* Allow text truncation */
        }

        .database-name {
            color: var(--color-text-primary);
            font-size: 16px;
            font-weight: 600;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .database-tables {
            color: var(--color-text-tertiary);
            font-size: 12px;
            margin: 0;
        }

        /* Size Indicator Section (Center Part) */
        .database-size-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            flex: 0 0 160px; /* Fixed width for size section */
        }

        .database-size-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            width: 100%;
        }

        .database-size-bar {
            width: 100%;
            max-width: 140px;
            height: 8px;
            border: 1px solid var(--color-border-lighter);
            border-radius: 4px;
            background: var(--color-bg-light);
            position: relative;
            overflow: hidden;
            cursor: help;
        }

        .database-size-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--color-primary-light) 0%, var(--color-primary) 100%);
            border-radius: 3px;
            transition: width 0.3s ease;
        }

        .database-size-fill.large {
            background: linear-gradient(90deg, var(--color-warning-light) 0%, var(--color-danger) 100%);
        }

        .database-size-text {
            font-size: 12px;
            color: var(--color-text-primary);
            font-weight: 500;
            text-align: center;
        }

        /* Buttons Section (Right Part) */
        .database-actions-section {
            display: flex;
            gap: 8px;
            flex: 0 0 auto; /* Don't grow or shrink */
        }

        .database-actions-section button {
            padding: 6px 12px;
            font-size: 12px;
            min-width: auto;
            white-space: nowrap;
        }

        /* Database Table Subsection (Hidden by default) */
        .database-tables-subsection {
            display: none;
            background: var(--color-bg-lighter);
            border-top: 1px solid var(--color-border-lighter);
            padding: 15px 20px 15px 50px; /* Extra left padding for indentation */
            margin-top: 0;
        }

        .database-tables-subsection.expanded {
            display: block;
        }

        .database-tables-subsection h4 {
            color: var(--color-text-secondary);
            font-size: 14px;
            margin: 0 0 10px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .database-tables-subsection h4::before {
            content: '📋';
            font-size: 16px;
        }

        .database-tables-grid {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-top: 8px;
        }

        .database-table-item {
            background: var(--color-bg-white);
            border: 1px solid var(--color-border-light);
            border-radius: 6px;
            padding: 10px 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s ease;
            cursor: pointer;
            width: 100%;
        }

        .database-table-item:hover {
            background: var(--color-bg-hover);
            border-color: var(--color-primary-light);
        }

        .database-table-item .table-icon {
            font-size: 16px;
            color: var(--color-primary);
            flex-shrink: 0;
        }

        .database-table-item .table-name {
            font-size: 14px;
            font-weight: 500;
            color: var(--color-text-primary);
            flex: 1;
            min-width: 0;
        }

        .database-table-item .table-type {
            font-size: 11px;
            color: var(--color-text-tertiary);
            background: var(--color-bg-light);
            padding: 3px 8px;
            border-radius: 12px;
            flex-shrink: 0;
        }

        .database-table-item .table-actions {
            display: flex;
            gap: 6px;
            flex-shrink: 0;
        }

        .database-table-item .table-actions button {
            padding: 4px 8px;
            font-size: 11px;
            border-radius: 4px;
            min-width: 50px;
        }

        .database-item:hover {
            background: var(--color-bg-hover);
        }

        .database-item:last-child {
            border-bottom: none;
        }

        /* Tooltip styles for size bar */
        .database-size-bar::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: var(--color-sapphire-navy);
            color: white;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 11px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
            pointer-events: none;
            margin-bottom: 5px;
        }

        .database-size-bar::before {
            content: '';
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 4px solid transparent;
            border-top-color: var(--color-sapphire-navy);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
            pointer-events: none;
        }

        .database-size-bar:hover::after,
        .database-size-bar:hover::before {
            opacity: 1;
            visibility: visible;
        }

        .table-list {
            background: var(--color-bg-white);
            border: 2px solid var(--color-border-light);
            border-radius: 8px;
            overflow: hidden;
        }

        .table-list-header {
            background: linear-gradient(135deg, var(--color-primary-lightest) 0%, var(--color-primary-pale) 100%);
            padding: 15px 20px;
            border-bottom: 2px solid var(--color-primary-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-list-header h3 {
            color: var(--color-primary);
            font-size: 18px;
            margin: 0;
        }

        .table-item {
            padding: 12px 20px;
            border-bottom: 1px solid var(--color-border-lighter);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s ease;
        }

        .table-item:hover {
            background: var(--color-bg-hover);
        }

        .table-item:last-child {
            border-bottom: none;
        }

        .table-item.selected {
            background: var(--color-bg-active);
            border-left: 4px solid var(--color-primary-light);
        }

        .table-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-icon {
            font-size: 16px;
            color: var(--color-primary);
        }

        .table-details h4 {
            color: var(--color-text-primary);
            font-size: 14px;
            margin-bottom: 2px;
        }

        .table-details p {
            color: var(--color-text-tertiary);
            font-size: 11px;
            margin: 0;
        }

        .table-actions {
            display: flex;
            gap: 6px;
        }

        .table-actions button {
            padding: 4px 8px;
            font-size: 11px;
            min-width: auto;
        }

        .button-loading {
            position: relative;
            color: transparent !important;
        }

        .button-loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 16px;
            height: 16px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* Modal content width override */
        .modal-content {
            max-width: 500px;
        }

        /* === Enhancements: searchbar, badges, focus, sticky header === */
        .searchbar { display:flex; gap:8px; align-items:center; }
        .searchbar input[type="search"],
        .searchbar select {
            height: 36px; padding: 0 12px; border: 1px solid var(--color-border-input);
            border-radius: 8px; background: var(--color-bg-white);
        }
        .database-list-header { position: sticky; top: 0; z-index: 5; }
        .database-item.active { background: var(--color-bg-active); border-left: 4px solid var(--color-primary-light); }
        .database-item:focus { outline: 3px solid var(--color-primary-lightest); outline-offset: -3px; }
        .badge-current { display:inline-block; margin-left:8px; padding:2px 8px; font-size:11px; border-radius:999px; background: var(--color-primary-pale); color: var(--color-primary); border:1px solid var(--color-border-light); }

        /* Page-specific responsive styles */
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-buttons button {
                min-width: auto;
            }

            /* Database list responsive styles */
            .database-item {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
                padding: 15px;
            }

            .database-name-section {
                flex: none;
                order: 1;
            }

            .database-size-section {
                flex: none;
                order: 2;
                align-self: center;
            }

            .database-actions-section {
                flex: none;
                order: 3;
                justify-content: center;
                flex-wrap: wrap;
            }

            .database-actions-section button {
                flex: 1;
                min-width: 80px;
            }
        }

        @media (max-width: 480px) {
            .database-item {
                padding: 12px;
            }

            .database-size-section {
                flex: 0 0 120px;
            }

            .database-actions-section {
                gap: 6px;
            }

            .database-actions-section button {
                padding: 8px 10px;
                font-size: 11px;
            }
        }
    </style>
</head>

<body>
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
    include 'templates/header.php';
    ?>
    <div class="loading active" id="loading">
        <div class="spinner"></div>
        <p>Loading database information...</p>
    </div>

    <div id="dashboardContent" style="display: none;">

        <!-- Top Row: Statistics and Database Operations -->
        <div class="dashboard-grid" style="display: flex; gap: 20px; margin-bottom: 20px;">
            <div class="dashboard-card" style="flex: 0 0 100%;">

                <!-- Header row: title left, buttons right -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px">
                    <h3 style="margin: 0;">
                        <span class="card-icon">📊</span>
                        Database Statistics
                    </h3>

                    <div class="action-buttons" style="display: flex; gap: 8px;">
                        <div class="actions-dropdown" id="statsActions">
                            <button type="button" class="dropdown-toggle">⚙️ Actions</button>
                            <div class="dropdown-menu" role="menu" aria-label="Database actions">
                                <ul>
                                    <li><button id="createDatabaseBtn" class="menu-item">➕ Create DB</button></li>
                                    <li><button id="createTableMenuItem" class="menu-item" disabled>➕ Create Table</button></li>
                                    <li><button id="exportDatabaseBtn" class="menu-item" disabled>📤 Export DB</button></li>
                                    <li><button id="importDatabaseBtn" class="menu-item">📥 Import DB</button></li>
                                    <li><button id="exportAllDatabasesBtn" class="menu-item">📦 Export All</button></li>
                                    <li><a href="view_fixer.php" class="menu-item" style="display: block; text-decoration: none; color: inherit;">🔧 Fix View Definers</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats below -->
                <div class="stats-grid" id="statsGrid">
                    <!-- Stats will be populated here -->
                </div>
            </div>
        </div>

        <!-- Database List -->
        <div class="database-list">
            <div class="database-list-header">
                <h3>🗄️ Available Databases</h3>
                <div class="searchbar">
                    <input type="search" id="dbSearchInput" placeholder="Search databases…" aria-label="Search databases">
                    <select id="dbSortSelect" aria-label="Sort databases">
                        <option value="name_asc">Name (A–Z)</option>
                        <option value="name_desc">Name (Z–A)</option>
                        <option value="size_desc">Size (largest)</option>
                        <option value="size_asc">Size (smallest)</option>
                        <option value="tables_desc">Tables (most)</option>
                        <option value="tables_asc">Tables (least)</option>
                    </select>
                    <!-- <button id="refreshDatabasesBtn" class="btn-secondary" style="padding: 6px 12px; font-size: 12px;" aria-label="Refresh databases">🔄 Refresh</button> -->
                </div>
            </div>
            </div>
            <div id="databaseList">
                <!-- Database list will be populated here -->
            </div>
        </div>

        <!-- Table List -->
        <div class="table-list" id="tableListSection" style="display: none;margin-top:10px;">
            <div class="table-list-header">
                <h3>📋 Tables in <span id="currentDatabaseName"></span></h3>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <button id="refreshTablesBtn" class="btn-secondary" style="padding: 6px 12px; font-size: 12px;">🔄
                        Refresh</button>
                    <button id="createTableBtn" class="btn-success" disabled
                        style="padding: 6px 12px; font-size: 12px;">➕ Create Table</button>
                </div>
            </div>
            <div id="tableList">
                <!-- Table list will be populated here -->
            </div>
        </div>
    </div>

    <div class="empty-state" id="emptyState">
        <div class="empty-state-icon">🗄️</div>
        <h3>No Database Selected</h3>
        <p>Please select a database from the dropdown above to manage tables and view statistics.</p>
    </div>
    </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast"></div>

    <!-- Create Database Modal -->
    <div class="modal" id="createDatabaseModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>➕ Create New Database</h2>
                <button class="modal-close" onclick="closeModal('createDatabaseModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="newDatabaseName">Database Name: <span
                            style="color: var(--color-danger);">*</span></label>
                    <input type="text" id="newDatabaseName" placeholder="e.g., my_new_database" required>
                </div>
                <div class="form-group">
                    <label for="newDatabaseCharset">Character Set:</label>
                    <select id="newDatabaseCharset">
                        <option value="utf8mb4">utf8mb4 (Recommended)</option>
                        <option value="utf8">utf8</option>
                        <option value="latin1">latin1</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="newDatabaseCollation">Collation:</label>
                    <select id="newDatabaseCollation">
                        <option value="utf8mb4_unicode_ci">utf8mb4_unicode_ci (Recommended)</option>
                        <option value="utf8mb4_general_ci">utf8mb4_general_ci</option>
                        <option value="utf8_unicode_ci">utf8_unicode_ci</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeModal('createDatabaseModal')">Cancel</button>
                <button class="btn-success" id="confirmCreateDatabaseBtn">💾 Create Database</button>
            </div>
        </div>
    </div>

    <!-- Create Table Modal -->
    <div class="modal" id="createTableModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>➕ Create New Table</h2>
                <button class="modal-close" onclick="closeModal('createTableModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="newTableName">Table Name: <span style="color: var(--color-danger);">*</span></label>
                    <input type="text" id="newTableName" placeholder="e.g., users" required>
                </div>

                <div class="form-group">
                    <label>Columns:</label>
                    <div id="columnsBuilder">
                        <div class="column-rows"></div>
                        <button type="button" class="btn-secondary" id="addColumnRowBtn" style="margin-top: 8px; padding: 6px 10px; font-size: 12px;">➕ Add Column</button>
                    </div>
                    <!-- Hidden textarea kept for API compatibility; populated on submit -->
                    <textarea id="newTableColumns" style="display:none;"></textarea>
                </div>

                <div class="form-group">
                    <label for="newTableEngine">Storage Engine:</label>
                    <select id="newTableEngine">
                        <option value="InnoDB">InnoDB (Recommended)</option>
                        <option value="MyISAM">MyISAM</option>
                        <option value="MEMORY">MEMORY</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeModal('createTableModal')">Cancel</button>
                <button class="btn-success" id="confirmCreateTableBtn">💾 Create Table</button>
            </div>
        </div>
    </div>

    <!-- Export Database Modal -->
    <div class="modal" id="exportDatabaseModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>📤 Export Database</h2>
                <button class="modal-close" onclick="closeModal('exportDatabaseModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="exportDatabaseName">Database:</label>
                    <input type="text" id="exportDatabaseName" readonly style="background: var(--color-bg-light);">
                </div>
                <div class="form-group">
                    <label for="exportFileName">File Name: <span style="color: var(--color-danger);">*</span></label>
                    <input type="text" id="exportFileName" placeholder="e.g., my_database_export" required>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="exportCreateDatabase" checked> Include CREATE DATABASE statement
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="exportDataOnly"> Export data only (no table structure)
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeModal('exportDatabaseModal')">Cancel</button>
                <button class="btn-warning" id="confirmExportBtn" style="padding: 6px 12px; font-size: 12px;">📤
                    Export</button>
            </div>
        </div>
    </div>

    <!-- Import Database Modal -->
    <div class="modal" id="importDatabaseModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>📥 Import Database</h2>
                <button class="modal-close" onclick="closeModal('importDatabaseModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="importFile">SQL File: <span style="color: var(--color-danger);">*</span></label>
                    <input type="file" id="importFile" accept=".sql" required>
                </div>
                <div class="form-group">
                    <label for="importTargetDatabase">Target Database:</label>
                    <select id="importTargetDatabase">
                        <option value="">-- Select database --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="importDropExisting"> Drop existing tables first
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeModal('importDatabaseModal')">Cancel</button>
                <button class="btn-warning" id="confirmImportBtn" style="padding: 6px 12px; font-size: 12px;">📥
                    Import</button>
            </div>
        </div>
    </div>

    <!-- Export All Databases Modal -->
    <div class="modal" id="exportAllDatabasesModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>📦 Export All Databases</h2>
                <button class="modal-close" onclick="closeModal('exportAllDatabasesModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="exportAllFilename">Filename:</label>
                    <input type="text" id="exportAllFilename" value="all_databases_export"
                        placeholder="Enter filename (without extension)">
                    <div class="help-text">File will be saved as: filename_YYYY-MM-DD_HH-MM-SS.sql</div>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="exportAllIncludeCreateDatabase" checked> Include CREATE DATABASE
                        statements
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="exportAllDataOnly"> Export data only (no table structure)
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeModal('exportAllDatabasesModal')">Cancel</button>
                <button class="btn-success" id="confirmExportAllBtn" style="padding: 6px 12px; font-size: 12px;">📦
                    Export All</button>
            </div>
        </div>
    </div>

    <!-- Confirm Dialog (Reusable) -->
    <div class="modal" id="confirmActionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="confirmActionTitle">Confirm Action</h2>
                <button class="modal-close" onclick="closeModal('confirmActionModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p id="confirmActionMessage" style="margin: 0; font-size: 14px; color: var(--color-text-secondary);">
                </p>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" id="confirmActionCancelBtn">Cancel</button>
                <button class="btn-danger" id="confirmActionConfirmBtn">Delete</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Global state
        let currentDatabase = '';
        let databases = [];
        let tables = [];
        let selectedTable = '';
        let dbSearchQuery = '';
        let dbSortMode = 'name_asc';

        // Initialize
        $(document).ready(function () {
            loadDatabases();

            // Event handlers
            $('#refreshBtn').click(function () {
                loadDatabases();
            });

            $('#refreshTablesBtn').click(function () {
                if (currentDatabase) {
                    loadTables();
                }
            });

            $('#databaseSelect').change(function () {
                currentDatabase = $(this).val();
                selectedTable = ''; // Reset table selection when database changes
                
                // Close any expanded databases when selecting from dropdown
                closeAllExpandedDatabases();
                
                // Update visual state of database items
                $('.database-item').removeClass('active');
                if (currentDatabase) {
                    $(`.database-item[data-database="${currentDatabase}"]`).addClass('active');
                }
                
                if (currentDatabase) {
                    // Update session cache so header shows correct database
                    $.ajax({
                        url: 'api.php',
                        method: 'POST',
                        data: {
                            action: 'setCurrentDatabase',
                            database: currentDatabase
                        },
                        dataType: 'json',
                        success: function (response) {
                            if (response.success) {
                                // Update the database badge in header without reload
                                updateDatabaseBadge(currentDatabase);
                            }
                        }
                    });

                    loadTables();
                    updateButtonStates();
                    // Immediately refresh stats so "Current DB" shows the new selection
                    updateStats();
                } else {
                    hideTableList();
                    updateButtonStates();
                    updateStats();
                }
            });

            $('#createDatabaseBtn').click(function () {
                openModal('createDatabaseModal');
            });

            $('#createTableBtn').click(function () {
                openModal('createTableModal');
            });

            // Create Table from menu item (same action)
            $('#createTableMenuItem').click(function(){
                if (!currentDatabase) return;
                openModal('createTableModal');
            });

            // Removed handlers for deleted buttons (#deleteDatabaseBtn, #deleteTableBtn)

            $('#exportDatabaseBtn').click(function () {
                if (currentDatabase) {
                    openExportModal(currentDatabase);
                }
            });

            $('#importDatabaseBtn').click(function () {
                openModal('importDatabaseModal');
            });

            $('#exportAllDatabasesBtn').click(function () {
                openModal('exportAllDatabasesModal');
            });

            // Actions dropdown open/close
            (function(){
                const $dropdown = $('#statsActions');
                const $menu = $dropdown.find('.dropdown-menu');
                $dropdown.find('.dropdown-toggle').on('click', function(e){
                    e.stopPropagation();
                    $menu.toggleClass('show');
                });
                $(document).on('click', function(){
                    $menu.removeClass('show');
                });
            })();

            $('#confirmCreateDatabaseBtn').click(function () {
                createDatabase();
            });

            $('#confirmCreateTableBtn').click(function () {
                // Build columns DDL from builder rows
                const lines = [];
                $('#columnsBuilder .column-row').each(function(){
                    const name = ($(this).find('.col-name').val() || '').trim().toLowerCase();
                    const type = $(this).find('.col-type').val();
                    const length = $(this).find('.col-length').val().trim();
                    const allowNull = $(this).find('.col-null').is(':checked');
                    const autoInc = $(this).find('.col-ai').is(':checked');
                    const defaultMode = $(this).find('.col-default-mode').val();
                    const defaultVal = $(this).find('.col-default').val();

                    if (!name) return; // skip empty rows

                    let ddl = name + ' ' + type + (length ? '(' + length + ')' : '');
                    if (!allowNull) ddl += ' NOT NULL';

                    // Default handling
                    if (defaultMode === 'value' && defaultVal !== '') {
                        // Quote string-like types
                        const needsQuotes = /^(CHAR|VARCHAR|TEXT|TINYTEXT|MEDIUMTEXT|LONGTEXT)$/i.test(type);
                        ddl += ' DEFAULT ' + (needsQuotes ? `'${defaultVal.replace(/'/g, "''")}'` : defaultVal);
                    } else if (defaultMode === 'current_timestamp') {
                        ddl += ' DEFAULT CURRENT_TIMESTAMP';
                    }

                    if (autoInc) ddl += ' AUTO_INCREMENT';

                    lines.push(ddl);
                });

                // Fallback: if no rows, keep existing textarea value (manual entry)
                if (lines.length > 0) {
                    $('#newTableColumns').val(lines.join('\n'));
                }

                createTable();
            });

            $('#confirmImportBtn').click(function () {
                importDatabase();
            });

            $('#confirmExportBtn').click(function () {
                exportDatabase();
            });

            $('#confirmExportAllBtn').click(function () {
                exportAllDatabases();
            });

            // Search & sort handlers
            const debouncedFilter = debounce(function(){
                dbSearchQuery = ($('#dbSearchInput').val() || '').toLowerCase();
                displayDatabases();
            }, 250);
            $('#dbSearchInput').on('input', debouncedFilter);
            $('#dbSortSelect').on('change', function(){
                dbSortMode = $(this).val();
                displayDatabases();
            });

            // Initialize column builder with one default row
            addColumnRow();
            $('#addColumnRowBtn').on('click', function(){ addColumnRow(); });

            // Drag & drop reordering for column rows
            enableDragAndDrop();

            // Close modal on outside click
            $(document).click(function (e) {
                if ($(e.target).hasClass('modal')) {
                    closeModal($(e.target).attr('id'));
                }
            });
        });

        // Debounce helper
        function debounce(fn, delay = 250){
            let t; return function(...args){ clearTimeout(t); t = setTimeout(() => fn.apply(this, args), delay); };
        }

        function enableDragAndDrop() {
            const container = document.querySelector('#columnsBuilder .column-rows');
            let draggedEl = null;

            container.addEventListener('dragstart', function(e){
                const row = e.target.closest('.column-row');
                if (!row) return;
                draggedEl = row;
                row.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', 'drag');
            });

            container.addEventListener('dragend', function(e){
                if (draggedEl) draggedEl.classList.remove('dragging');
                draggedEl = null;
                renumberColumnBadges();
            });

            container.addEventListener('dragover', function(e){
                e.preventDefault();
                const afterElement = getDragAfterElement(container, e.clientY);
                const dragging = container.querySelector('.dragging');
                if (!dragging) return;
                if (afterElement == null) {
                    container.appendChild(dragging);
                } else {
                    container.insertBefore(dragging, afterElement);
                }
            });

            // Make rows draggable
            new MutationObserver(function(){
                container.querySelectorAll('.column-row').forEach(function(row){
                    row.setAttribute('draggable', 'true');
                });
            }).observe(container, { childList: true, subtree: true });

            // Initialize existing
            container.querySelectorAll('.column-row').forEach(function(row){ row.setAttribute('draggable','true'); });
        }

        function getDragAfterElement(container, y) {
            const draggableElements = [...container.querySelectorAll('.column-row:not(.dragging)')];

            return draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                if (offset < 0 && offset > closest.offset) {
                    return { offset: offset, element: child };
                } else {
                    return closest;
                }
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        }

        function renumberColumnBadges(){
            $('#columnsBuilder .column-row .col-badge').each(function(i){
                const text = $(this).text().replace(/Column\s+\d+/, 'Column ' + (i+1));
                $(this).text(text);
            });
        }

        // Load all databases
        function loadDatabases() {
            $('#loading').addClass('active');

            $.ajax({
                url: 'api.php?action=getDatabases',
                method: 'GET',
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        databases = response.databases;
                        displayDatabases();
                        populateDatabaseSelect();
                        updateStats();
                    }
                    $('#loading').removeClass('active');
                    $('#dashboardContent').show();
                    $('#emptyState').hide();
                },
                error: function (xhr) {
                    showToast('Error loading databases: ' + xhr.responseText, 'error');
                    $('#loading').removeClass('active');
                }
            });
        }

        // Load tables for current database
        function loadTables() {
            if (!currentDatabase) return;

            $.ajax({
                url: 'api.php?action=getTables&database=' + encodeURIComponent(currentDatabase),
                method: 'GET',
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        tables = response.tables;
                        displayTables();
                        $('#tableListSection').show();
                        $('#currentDatabaseName').text(currentDatabase);
                    }
                },
                error: function (xhr) {
                    showToast('Error loading tables: ' + xhr.responseText, 'error');
                }
            });
        }

        // Load tables for a specific database (for expand/collapse functionality)
        function loadTablesForDatabase(databaseName, callback) {
            $.ajax({
                url: 'api.php?action=getTables&database=' + encodeURIComponent(databaseName),
                method: 'GET',
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        if (typeof callback === 'function') {
                            callback(response.tables);
                        }
                    }
                },
                error: function (xhr) {
                    showToast('Error loading tables for ' + databaseName + ': ' + xhr.responseText, 'error');
                }
            });
        }

        // Toggle database tables expand/collapse
        function toggleDatabaseTables(databaseName) {
            const expandIndicator = $(`.expand-indicator[aria-label*="${databaseName}"]`);
            const tablesSubsection = $(`.database-tables-subsection[data-database="${databaseName}"]`);
            
            if (tablesSubsection.hasClass('expanded')) {
                // Collapse this database
                tablesSubsection.removeClass('expanded').slideUp(200);
                expandIndicator.removeClass('expanded');
            } else {
                // Close any other expanded databases first (but not this one)
                closeAllExpandedDatabases(databaseName);
                
                // Expand the selected database
                expandIndicator.addClass('expanded');
                tablesSubsection.addClass('expanded');
                
                // Select this database as the current database (without triggering change event)
                currentDatabase = databaseName;
                $('#databaseSelect').val(databaseName);
                
                // Update visual state manually
                $('.database-item').removeClass('active');
                $(`.database-item[data-database="${databaseName}"]`).addClass('active');
                
                // Update database badges manually
                $('.database-name').each(function() {
                    const $this = $(this);
                    const $badge = $this.find('.badge-current');
                    const itemDbName = $this.closest('.database-item').data('database');
                    if (itemDbName === databaseName && !$badge.length) {
                        $this.append('<span class="badge-current" title="Currently selected">Current</span>');
                    } else if (itemDbName !== databaseName && $badge.length) {
                        $badge.remove();
                    }
                });
                
                // Update session cache
                $.ajax({
                    url: 'api.php',
                    method: 'POST',
                    data: {
                        action: 'setCurrentDatabase',
                        database: databaseName
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            updateDatabaseBadge(databaseName);
                        }
                    }
                });
                
                // Load tables if not already loaded
                const tablesGrid = tablesSubsection.find('.database-tables-grid');
                if (tablesGrid.children().length === 0) {
                    loadTablesForDatabase(databaseName, function(tables) {
                        displayTablesInSubsection(databaseName, tables);
                    });
                } else {
                    tablesSubsection.slideDown(200);
                }
                
                // Update button states and stats
                updateButtonStates();
                updateStats();
            }
        }

        // Close all expanded databases (optionally exclude a specific database)
        function closeAllExpandedDatabases(excludeDatabase = null) {
            $('.database-tables-subsection.expanded').each(function() {
                const databaseName = $(this).data('database');
                
                // Skip the excluded database
                if (excludeDatabase && databaseName === excludeDatabase) {
                    return;
                }
                
                const expandIndicator = $(`.expand-indicator[aria-label*="${databaseName}"]`);
                
                $(this).removeClass('expanded').slideUp(200);
                expandIndicator.removeClass('expanded');
            });
        }

        // Display tables in the subsection
        function displayTablesInSubsection(databaseName, tables) {
            const tablesGrid = $(`.database-tables-subsection[data-database="${databaseName}"] .database-tables-grid`);
            tablesGrid.empty();

            if (tables.length === 0) {
                tablesGrid.append(`
                    <div style="text-align: center; color: var(--color-text-tertiary); padding: 20px; background: var(--color-bg-white); border: 1px solid var(--color-border-light); border-radius: 6px;">
                        No tables found in this database.
                    </div>
                `);
            } else {
                tables.forEach(function (table) {
                    const tableName = typeof table === 'string' ? table : table.name;
                    const tableType = typeof table === 'object' ? table.type : 'BASE TABLE';
                    const isView = tableType === 'VIEW';
                    const tableIcon = isView ? '👁️' : '📋';
                    
                    const tableItem = $(`
                        <div class="database-table-item" data-table="${tableName}">
                            <span class="table-icon">${tableIcon}</span>
                            <span class="table-name">${tableName}</span>
                            ${isView ? '<span class="table-type">View</span>' : '<span class="table-type">Table</span>'}
                            <div class="table-actions">
                                <button class="btn-success" onclick="viewTableFromSubsection('${tableName}', '${databaseName}')" title="View table">View</button>
                                ${!isView ? `<button class="btn-danger" onclick="deleteTableFromSubsection('${tableName}', '${databaseName}')" title="Delete table">Delete</button>` : ''}
                            </div>
                        </div>
                    `);
                    
                    tablesGrid.append(tableItem);
                });
            }
            
            // Show the subsection with animation
            $(`.database-tables-subsection[data-database="${databaseName}"]`).slideDown(200);
        }

        // Display databases list (with search + sort + better progress)
        function displayDatabases() {
            const databaseList = $('#databaseList');
            databaseList.empty();

            let list = getFilteredAndSortedDatabases();

            if (list.length === 0) {
                databaseList.append(`
                    <div class="empty-state" style="padding: 40px 20px;">
                        <div class="empty-state-icon">🗄️</div>
                        <h3>No Databases Found</h3>
                        <p>Adjust your search or create a new database.</p>
                    </div>
                `);
                return;
            }

            const maxSize = Math.max(1, ...list.map(db => (db.size || 0)));

            list.forEach(function (db) {
                const isCurrent = db.name === currentDatabase;
                const sizeBytes = db.size || 0;
                const sizePercent = Math.max(4, Math.round((sizeBytes / maxSize) * 100));
                const displaySize = formatBytes(sizeBytes);
                const isLarge = sizeBytes > 100 * 1024 * 1024; // >100MB

                const databaseItem = $(`
                    <div class="database-item ${isCurrent ? 'active' : ''}" data-database="${db.name}" tabindex="0">
                        <!-- Database Name Section (Left Part) -->
                        <div class="database-name-section">
                            <span class="expand-indicator" title="Click to expand/collapse" aria-label="Expand database ${db.name}"></span>
                            <span class="database-icon" aria-hidden="true">🗄️</span>
                            <div class="database-main-info">
                                <h4 class="database-name">${db.name}${isCurrent ? '<span class="badge-current" title="Currently selected">Current</span>' : ''}</h4>
                                <p class="database-tables">${db.tables || 0} tables</p>
                            </div>
                        </div>
                        
                        <!-- Size Indicator Section (Center Part) -->
                        <div class="database-size-section">
                            <div class="database-size-info">
                                <div class="database-size-bar" data-tooltip="Size: ${displaySize}" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="${sizePercent}" aria-label="Database size of ${db.name}">
                                    <div class="database-size-fill ${isLarge ? 'large' : ''}" style="width: ${sizePercent}%;"></div>
                                </div>
                                <span class="database-size-text">${displaySize}</span>
                            </div>
                        </div>
                        
                        <!-- Buttons Section (Right Part) -->
                        <div class="database-actions-section">
                            <button class="btn-success" aria-label="Select ${db.name}" onclick="selectDatabase('${db.name}')">Select</button>
                            <button class="btn-warning" aria-label="Export ${db.name}" onclick="openExportModal('${db.name}')">Export</button>
                            <button class="btn-danger" aria-label="Delete ${db.name}" onclick="deleteDatabase('${db.name}')">Delete</button>
                        </div>
                    </div>
                    <!-- Database Tables Subsection (Hidden by default) -->
                    <div class="database-tables-subsection" data-database="${db.name}">
                        <h4>Tables in ${db.name}</h4>
                        <div class="database-tables-grid">
                            <!-- Tables will be populated here when expanded -->
                        </div>
                    </div>
                `);

                // Keyboard support: Enter/Space to select
                databaseItem.on('keydown', function(e){
                    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); selectDatabase(db.name); }
                });

                // Expand indicator click handler
                databaseItem.find('.expand-indicator').on('click', function(e){
                    e.stopPropagation(); // Prevent database selection
                    toggleDatabaseTables(db.name);
                });

                databaseList.append(databaseItem);
            });
        }

        function getFilteredAndSortedDatabases(){
            let result = databases.slice();
            if (dbSearchQuery) {
                result = result.filter(db => (db.name || '').toLowerCase().includes(dbSearchQuery));
            }
            const byName = (a,b) => (a.name || '').localeCompare(b.name || '');
            const bySize = (a,b) => (b.size || 0) - (a.size || 0);
            const byTables = (a,b) => (b.tables || 0) - (a.tables || 0);
            switch (dbSortMode) {
                case 'name_desc': result.sort((a,b)=>byName(b,a)); break;
                case 'size_desc': result.sort(bySize); break;
                case 'size_asc': result.sort((a,b)=>-bySize(a,b)); break;
                case 'tables_desc': result.sort(byTables); break;
                case 'tables_asc': result.sort((a,b)=>-byTables(a,b)); break;
                default: result.sort(byName);
            }
            return result;
        }

        // Display tables list
        function displayTables() {
            const tableList = $('#tableList');
            tableList.empty();

            if (tables.length === 0) {
                tableList.append(`
                    <div class="empty-state" style="padding: 40px 20px;">
                        <div class="empty-state-icon">📋</div>
                        <h3>No Tables Found</h3>
                        <p>Create your first table to get started.</p>
                    </div>
                `);
                selectedTable = '';
                updateButtonStates();
                return;
            }

            tables.forEach(function (table) {
                // Handle both old format (string) and new format (object)
                const tableName = typeof table === 'string' ? table : table.name;
                const tableType = typeof table === 'object' ? table.type : 'BASE TABLE';
                const isView = tableType === 'VIEW';
                const isSelected = tableName === selectedTable;
                
                const tableIcon = isView ? '👁️' : '📋';
                const typeLabel = isView ? 'View' : 'Table';
                
                const tableItem = $(`
                    <div class="table-item ${isSelected ? 'selected' : ''}" data-table="${tableName}" style="cursor: pointer;">
                        <div class="table-info">
                            <span class="table-icon">${tableIcon}</span>
                            <div class="table-details">
                                <h4>${tableName}${isView ? ' <span style="font-size: 11px; color: var(--color-warning);">(view)</span>' : ''}</h4>
                                <p>${typeLabel} in ${currentDatabase}</p>
                            </div>
                        </div>
                        <div class="table-actions" style="display: flex; gap: 6px;">
                            <button class="btn-success" onclick="event.stopPropagation(); viewTable('${tableName}')" style="padding: 4px 8px; font-size: 11px;">View</button>
                            ${!isView ? `<button class="btn-danger" onclick="event.stopPropagation(); deleteTable('${tableName}')" style="padding: 4px 8px; font-size: 11px;">Delete</button>` : ''}
                        </div>
                    </div>
                `);

                // Add click handler to select the table
                tableItem.click(function (e) {
                    // Don't trigger if clicking on buttons
                    if ($(e.target).is('button') || $(e.target).closest('button').length) {
                        return;
                    }

                    selectTable(tableName);
                });

                tableList.append(tableItem);
            });
        }

        // Add a column row to the builder
        function addColumnRow() {
            const commonTypes = [
                { v: 'INT', label: 'INT' },
                { v: 'BIGINT', label: 'BIGINT' },
                { v: 'VARCHAR', label: 'VARCHAR' },
                { v: 'TEXT', label: 'TEXT' },
                { v: 'DATE', label: 'DATE' },
                { v: 'DATETIME', label: 'DATETIME' },
                { v: 'TIMESTAMP', label: 'TIMESTAMP' },
                { v: 'BOOLEAN', label: 'BOOLEAN' }
            ];

            const index = $('#columnsBuilder .column-row').length + 1;
            const row = $(`
                <div class="column-row">
                    <div class="row-line" style="justify-content: space-between;">
                        <span class="col-badge"><span class="drag-handle" title="Drag to reorder">↕</span> Column ${index}</span>
                        <button type="button" class="btn-danger remove-col" style="padding:4px 8px; font-size:11px;">✖</button>
                    </div>
                    <div class="row-line" style="margin-top:6px;">
                        <input type="text" class="col-name" placeholder="column_name" style="flex:1 1 240px; min-width:140px; padding:6px 8px; border:1px solid var(--color-border-input); border-radius:8px;">
                        <select class="col-type" style="flex:0 0 120px; min-width:80px; padding:6px 8px; border:1px solid var(--color-border-input); border-radius:8px;">
                            ${commonTypes.map(t => `<option value="${t.v}">${t.label}</option>`).join('')}
                        </select>
                        <input type="text" class="col-length" placeholder="len" style="flex:0 0 80px; min-width:70px; padding:6px 8px; border:1px solid var(--color-border-input); border-radius:8px;">
                    </div>
                    <div class="row-line" style="margin-top:6px;">
                        <select class="col-default-mode" style="flex:0 0 200px; padding:6px 8px; border:1px solid var(--color-border-input); border-radius:8px;">
                            <option value="none">Default: None</option>
                            <option value="value">Default: Value…</option>
                            <option value="current_timestamp">Default: CURRENT_TIMESTAMP</option>
                        </select>
                        <input type="text" class="col-default" placeholder="default value" style="flex:1 1 200px; padding:6px 8px; border:1px solid var(--color-border-input); border-radius:8px; display:none;">
                        <label style="display:flex; align-items:center; gap:6px;">
                            <input type="checkbox" class="col-null"> NULL
                        </label>
                        <label style="display:flex; align-items:center; gap:6px;">
                            <input type="checkbox" class="col-ai"> AI
                        </label>
                    </div>
                </div>
            `);

            // Toggle default value input visibility
            row.find('.col-default-mode').on('change', function(){
                const mode = $(this).val();
                row.find('.col-default').toggle(mode === 'value');
            });

            // Force lowercase column names as user types
            row.find('.col-name').on('input', function(){
                this.value = this.value.toLowerCase();
            });

            row.find('.remove-col').on('click', function(){ row.remove(); });

            $('#columnsBuilder .column-rows').append(row);
        }

        // Populate database select dropdown
        function populateDatabaseSelect() {
            const select = $('#databaseSelect');
            select.empty();
            select.append('<option value="">-- Select a database --</option>');

            databases.forEach(function (db) {
                const selected = db.name === currentDatabase ? 'selected' : '';
                select.append(`<option value="${db.name}" ${selected}>${db.name}</option>`);
            });
        }

        // Update statistics
        function updateStats() {
            const statsGrid = $('#statsGrid');
            const totalDatabases = databases.length;
            const totalTables = databases.reduce((sum, db) => sum + (db.tables || 0), 0);
            const totalSize = databases.reduce((sum, db) => sum + (db.size || 0), 0);

            statsGrid.html(`
                <div class="stat-item">
                    <div class="stat-value">${totalDatabases}</div>
                    <div class="stat-label">Databases</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${totalTables}</div>
                    <div class="stat-label">Tables</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${formatBytes(totalSize)}</div>
                    <div class="stat-label">Total Size</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${currentDatabase || 'None'}</div>
                    <div class="stat-label">Current DB</div>
                </div>
            `);
        }

        // Update button states based on current selection
        function updateButtonStates() {
            const hasDatabase = !!currentDatabase;
            const hasTables = tables.length > 0;
            const hasSelectedTable = !!selectedTable;

            $('#createTableBtn').prop('disabled', !hasDatabase);
            $('#createTableMenuItem').prop('disabled', !hasDatabase);

            $('#exportDatabaseBtn').prop('disabled', !hasDatabase);

            $('#importDatabaseBtn').prop('disabled', false); // Can always import
        }

        // Hide table list
        function hideTableList() {
            $('#tableListSection').hide();
        }

        // Select database
        function selectDatabase(databaseName) {
            currentDatabase = databaseName;
            $('#databaseSelect').val(databaseName).trigger('change');
            
            // Update visual state of database items
            $('.database-item').removeClass('active');
            $(`.database-item[data-database="${databaseName}"]`).addClass('active');
            
            // Update database badges
            $('.database-name').each(function() {
                const $this = $(this);
                const $badge = $this.find('.badge-current');
                const itemDbName = $this.closest('.database-item').data('database');
                if (itemDbName === databaseName && !$badge.length) {
                    $this.append('<span class="badge-current" title="Currently selected">Current</span>');
                } else if (itemDbName !== databaseName && $badge.length) {
                    $badge.remove();
                }
            });
        }

        // Select table
        function selectTable(tableName) {
            selectedTable = tableName;

            // Update visual selection
            $('.table-item').removeClass('selected');
            $(`.table-item[data-table="${tableName}"]`).addClass('selected');

            updateButtonStates();
        }

        // Update database badge in header
        function updateDatabaseBadge(databaseName, tableName = '') {
            // Find the database badge in the header and update it
            const databaseBadge = document.querySelector('.control-group span span');
            if (databaseBadge) {
                let displayText = '🗄️ ' + databaseName;
                if (tableName) {
                    displayText += ' -  ' + tableName;
                }
                databaseBadge.textContent = displayText;
            }
        }

        // View table (navigate to table structure page)
        function viewTable(tableName, databaseName = null) {
            const dbName = databaseName || currentDatabase;
            // Update the database badge to show database.table before navigating
            updateDatabaseBadge(dbName, tableName);
            window.location.href = `table_structure.php?table=${encodeURIComponent(tableName)}&database=${encodeURIComponent(dbName)}`;
        }

        // Legacy wrapper for subsection calls
        function viewTableFromSubsection(tableName, databaseName) {
            viewTable(tableName, databaseName);
        }

        // Delete table (consolidated function)
        function deleteTable(tableName, databaseName = null, fromSubsection = false) {
            const dbName = databaseName || currentDatabase;
            
            showConfirmDialog({
                title: 'Delete Table',
                message: `Are you sure you want to delete the table "${tableName}" from database "${dbName}"? This action cannot be undone!`,
                confirmText: 'Delete',
                confirmClass: 'btn-danger'
            }, function onConfirm() {
                $.ajax({
                    url: 'api.php',
                    method: 'POST',
                    data: {
                        action: 'deleteTable',
                        database: dbName,
                        name: tableName
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            showToast('Table deleted successfully!', 'success');
                            
                            if (fromSubsection) {
                                // Remove the table from the subsection
                                $(`.database-table-item[data-table="${tableName}"]`).remove();
                                // Update the table count in the database item
                                updateDatabaseTableCount(dbName);
                            } else {
                                // Clear selection if the deleted table was selected
                                if (selectedTable === tableName) {
                                    selectedTable = '';
                                }
                                loadTables();
                            }
                            
                            // Refresh the main database list stats
                            loadDatabases();
                        } else {
                            showToast('Error: ' + response.error, 'error');
                        }
                    },
                    error: function (xhr) {
                        const response = JSON.parse(xhr.responseText);
                        showToast('Error: ' + (response.error || 'Unknown error'), 'error');
                    }
                });
            });
        }

        // Legacy wrapper for subsection calls
        function deleteTableFromSubsection(tableName, databaseName) {
            deleteTable(tableName, databaseName, true);
        }

        // Update table count in database item after table deletion
        function updateDatabaseTableCount(databaseName) {
            const databaseItem = $(`.database-item[data-database="${databaseName}"]`);
            const tablesCount = $(`.database-table-item[data-database="${databaseName}"]`).length;
            databaseItem.find('.database-tables').text(tablesCount + ' tables');
        }

        // Export all databases
        function exportAllDatabases() {
            const filename = $('#exportAllFilename').val().trim();
            const includeCreateDatabase = $('#exportAllIncludeCreateDatabase').is(':checked');
            const dataOnly = $('#exportAllDataOnly').is(':checked');

            if (!filename) {
                showToast('Please enter a filename', 'error');
                return;
            }

            // Show loading state
            $('#confirmExportAllBtn').prop('disabled', true).text('📦 Exporting...');

            // Close modal immediately
            closeModal('exportAllDatabasesModal');

            // Create a form to submit the request
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'api.php';
            // Remove target='_blank' to stay in same window

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'exportAllDatabases';

            const filenameInput = document.createElement('input');
            filenameInput.type = 'hidden';
            filenameInput.name = 'filename';
            filenameInput.value = filename;

            const includeCreateInput = document.createElement('input');
            includeCreateInput.type = 'hidden';
            includeCreateInput.name = 'includeCreateDatabase';
            includeCreateInput.value = includeCreateDatabase ? 'true' : 'false';

            const dataOnlyInput = document.createElement('input');
            dataOnlyInput.type = 'hidden';
            dataOnlyInput.name = 'dataOnly';
            dataOnlyInput.value = dataOnly ? 'true' : 'false';

            form.appendChild(actionInput);
            form.appendChild(filenameInput);
            form.appendChild(includeCreateInput);
            form.appendChild(dataOnlyInput);

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);

            // Reset button after a delay
            setTimeout(() => {
                $('#confirmExportAllBtn').prop('disabled', false).text('📦 Export All');
            }, 2000);
        }

        // Create database
        function createDatabase() {
            const name = $('#newDatabaseName').val().trim();
            const charset = $('#newDatabaseCharset').val();
            const collation = $('#newDatabaseCollation').val();

            if (!name) {
                showToast('Please enter a database name', 'warning');
                return;
            }

            $.ajax({
                url: 'api.php',
                method: 'POST',
                data: {
                    action: 'createDatabase',
                    name: name,
                    charset: charset,
                    collation: collation
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        showToast('Database created successfully!', 'success');
                        closeModal('createDatabaseModal');
                        loadDatabases();
                    } else {
                        showToast('Error: ' + response.error, 'error');
                    }
                },
                error: function (xhr) {
                    const response = JSON.parse(xhr.responseText);
                    showToast('Error: ' + (response.error || 'Unknown error'), 'error');
                }
            });
        }

        // Create table
        function createTable() {
            const name = $('#newTableName').val().trim();
            const columns = $('#newTableColumns').val().trim();
            const engine = $('#newTableEngine').val();

            if (!name || !columns) {
                showToast('Please enter table name and columns', 'warning');
                return;
            }

            $.ajax({
                url: 'api.php',
                method: 'POST',
                data: {
                    action: 'createTable',
                    database: currentDatabase,
                    name: name,
                    columns: columns,
                    engine: engine
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        showToast('Table created successfully!', 'success');
                        closeModal('createTableModal');
                        loadTables();
                        loadDatabases(); // Refresh stats
                    } else {
                        showToast('Error: ' + response.error, 'error');
                    }
                },
                error: function (xhr) {
                    const response = JSON.parse(xhr.responseText);
                    showToast('Error: ' + (response.error || 'Unknown error'), 'error');
                }
            });
        }

        // Delete database (with custom confirm modal)
        function deleteDatabase(databaseName) {
            showConfirmDialog({
                title: 'Delete Database',
                message: `Are you sure you want to delete the database "${databaseName}"? This action cannot be undone!`,
                confirmText: 'Delete',
                confirmClass: 'btn-danger'
            }, function onConfirm() {
                $.ajax({
                    url: 'api.php',
                    method: 'POST',
                    data: {
                        action: 'deleteDatabase',
                        name: databaseName
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            showToast('Database deleted successfully!', 'success');
                            if (currentDatabase === databaseName) {
                                currentDatabase = '';
                                $('#databaseSelect').val('').trigger('change');
                            }
                            loadDatabases();
                        } else {
                            showToast('Error: ' + response.error, 'error');
                        }
                    },
                    error: function (xhr) {
                        const response = JSON.parse(xhr.responseText);
                        showToast('Error: ' + (response.error || 'Unknown error'), 'error');
                    }
                });
            });
        }


        // Reusable confirm dialog helper
        function showConfirmDialog(options, onConfirm) {
            const { title, message, confirmText = 'Confirm', confirmClass = '' } = options || {};
            $('#confirmActionTitle').text(title || 'Confirm Action');
            $('#confirmActionMessage').text(message || 'Are you sure?');
            const $confirmBtn = $('#confirmActionConfirmBtn');
            $confirmBtn.text(confirmText);
            // reset classes
            $confirmBtn.removeClass('btn-success btn-warning btn-danger');
            if (confirmClass) {
                $confirmBtn.addClass(confirmClass);
            }

            // Clean previous handlers
            $confirmBtn.off('click');
            $('#confirmActionCancelBtn').off('click');

            // Bind actions
            $('#confirmActionCancelBtn').on('click', function () {
                closeModal('confirmActionModal');
            });
            $confirmBtn.on('click', function () {
                closeModal('confirmActionModal');
                if (typeof onConfirm === 'function') {
                    onConfirm();
                }
            });

            // Open
            openModal('confirmActionModal');
        }

        // Open export modal
        function openExportModal(databaseName) {
            $('#exportDatabaseName').val(databaseName);
            $('#exportFileName').val(`${databaseName}_export_${new Date().toISOString().split('T')[0]}`);
            $('#exportCreateDatabase').prop('checked', true);
            $('#exportDataOnly').prop('checked', false);
            openModal('exportDatabaseModal');
        }

        // Export database
        function exportDatabase() {
            const databaseName = $('#exportDatabaseName').val();
            const fileName = $('#exportFileName').val().trim();
            const includeCreateDatabase = $('#exportCreateDatabase').is(':checked');
            const dataOnly = $('#exportDataOnly').is(':checked');

            if (!fileName) {
                showToast('Please enter a file name', 'warning');
                return;
            }

            showToast('Exporting database...', 'warning');

            $.ajax({
                url: 'api.php',
                method: 'POST',
                data: {
                    action: 'exportDatabase',
                    name: databaseName,
                    includeCreateDatabase: includeCreateDatabase,
                    dataOnly: dataOnly
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        // Create download link
                        const blob = new Blob([response.sql], { type: 'application/sql' });
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = fileName.endsWith('.sql') ? fileName : `${fileName}.sql`;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);

                        showToast('Database exported successfully!', 'success');
                        closeModal('exportDatabaseModal');
                    } else {
                        showToast('Error: ' + response.error, 'error');
                    }
                },
                error: function (xhr) {
                    const response = JSON.parse(xhr.responseText);
                    showToast('Error: ' + (response.error || 'Unknown error'), 'error');
                }
            });
        }

        // Import database
        function importDatabase() {
            const fileInput = document.getElementById('importFile');
            const file = fileInput.files[0];
            const targetDatabase = $('#importTargetDatabase').val();
            const dropExisting = $('#importDropExisting').is(':checked');

            if (!file) {
                showToast('Please select a SQL file', 'warning');
                return;
            }

            if (!targetDatabase) {
                showToast('Please select a target database', 'warning');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'importDatabase');
            formData.append('file', file);
            formData.append('database', targetDatabase);
            formData.append('dropExisting', dropExisting);

            showToast('Importing database...', 'warning');

            $.ajax({
                url: 'api.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        showToast('Database imported successfully!', 'success');
                        closeModal('importDatabaseModal');
                        loadDatabases();
                    } else {
                        showToast('Error: ' + response.error, 'error');
                    }
                },
                error: function (xhr) {
                    const response = JSON.parse(xhr.responseText);
                    showToast('Error: ' + (response.error || 'Unknown error'), 'error');
                }
            });
        }

        // Modal functions
        function openModal(modalId) {
            $('#' + modalId).addClass('active');

            // Populate import target database dropdown
            if (modalId === 'importDatabaseModal') {
                const select = $('#importTargetDatabase');
                select.empty();
                select.append('<option value="">-- Select database --</option>');
                databases.forEach(function (db) {
                    select.append(`<option value="${db.name}">${db.name}</option>`);
                });
            }
        }

        function closeModal(modalId) {
            $('#' + modalId).removeClass('active');

            // Clear form fields
            if (modalId === 'createDatabaseModal') {
                $('#newDatabaseName').val('');
            } else if (modalId === 'createTableModal') {
                $('#newTableName').val('');
                $('#newTableColumns').val('');
            } else if (modalId === 'exportDatabaseModal') {
                $('#exportDatabaseName').val('');
                $('#exportFileName').val('');
                $('#exportCreateDatabase').prop('checked', true);
                $('#exportDataOnly').prop('checked', false);
            } else if (modalId === 'importDatabaseModal') {
                $('#importFile').val('');
                $('#importTargetDatabase').val('');
                $('#importDropExisting').prop('checked', false);
            }
        }

        // Show toast notification
        function showToast(message, type = 'success') {
            const toast = $('#toast');
            toast.text(message);
            toast.removeClass('success error warning');
            toast.addClass(type);
            toast.addClass('active');

            setTimeout(function () {
                toast.removeClass('active');
            }, 4000);
        }

        // Format bytes to human readable
        function formatBytes(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Smooth page transitions
        $('.nav-link').click(function (e) {
            const href = $(this).attr('href');

            // Don't apply transition if it's the current page
            if ($(this).hasClass('active')) {
                e.preventDefault();
                return;
            }

            e.preventDefault();
            $('body').addClass('page-transitioning');

            // Navigate after fade out
            setTimeout(function () {
                window.location.href = href;
            }, 200);
        });
    </script>

    <?php include 'templates/footer.php'; ?>
</body>

</html>
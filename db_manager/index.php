<?php
/**
 * Database Manager - Database CRUD Manager
 * IP Authorization Check
 */
require_once '../login/auth_check.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Manager - Database CRUD Manager</title>
    <link rel="stylesheet" href="../styles/common.css">
    <link rel="stylesheet" href="db_manager.css">
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
    include '../templates/header.php';
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
                                    <li><a href="../sync_db/" class="menu-item" style="display: block; text-decoration: none; color: inherit;">🔄 Sync Database</a></li>
                                    <li><a href="../view_fixer" class="menu-item" style="display: block; text-decoration: none; color: inherit;">🔧 Fix View Definers</a></li>
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
                </div>
            </div>
            </div>
            <div id="databaseList">
                <!-- Database list will be populated here -->
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

    <!-- Include Modals -->
    <?php include 'modals.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="db_manager.js"></script>

    <?php include '../templates/footer.php'; ?>
</body>

</html>

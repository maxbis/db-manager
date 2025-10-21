<?php
/**
 * SQL Query Builder - Database CRUD Manager
 * IP Authorization Check
 */
require_once '../login/auth_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL Query Builder - Database CRUD Manager</title>
    <link rel="stylesheet" href="../styles/common.css">
    <link rel="stylesheet" href="query_builder.css">
</head>
<body>
    <?php
    $pageConfig = [
        'id' => 'query',
        'title' => 'SQL Query Builder',
        'icon' => '⚡',
        'controls_html' => '
            <div class="control-group">
                <label for="tableSelect">Select Table:</label>
                <select id="tableSelect">
                    <option value="">-- Choose a table --</option>
                </select>
            </div>
        '
    ];
    include '../templates/header.php';
    ?>
    
    <div class="loading active" id="loading">
        <div class="spinner"></div>
        <p>Loading...</p>
    </div>

    <div id="queryInterface" style="display: none;">
        <div class="query-examples" id="queryExamples">
            <button class="close-examples-btn" id="closeExamplesBtn" title="Close examples">&times;</button>
            <h4>💡 Quick Examples:</h4>
            <ul>
                <li>SELECT * FROM table_name LIMIT 10</li>
                <li>SELECT column1, column2 FROM table_name WHERE condition</li>
                <li>SELECT COUNT(*) as total FROM table_name</li>
            </ul>
        </div>

        <div class="query-layout">
            <div class="fields-panel">
                <h3>📋 Table Fields</h3>
                <ul class="field-list" id="fieldList">
                    <!-- Fields will be populated here -->
                </ul>
            </div>

            <div class="query-panel">
                <div class="query-input-wrapper">
                    <textarea 
                        id="queryInput" 
                        class="query-input" 
                        placeholder="Enter your SQL query here...&#10;&#10;Example:&#10;SELECT * FROM your_table LIMIT 10"
                    ></textarea>
                </div>

                <div class="query-actions">
                    <button class="btn-execute" id="executeBtn">▶ Execute Query</button>
                    <button class="btn-clear" id="clearBtn">🗑️ Clear</button>
                    <button class="btn-save-query" id="saveQueryBtn">💾 Save Query</button>
                </div>
            </div>

            <div class="saved-queries-panel">
                <h3>
                    <span>💾 Saved Queries</span>
                    <div style="display: flex; gap: 5px;">
                        <button class="btn-save-query" id="exportQueriesBtn" title="Export queries" style="padding: 4px 8px; font-size: 11px;">⬇️</button>
                        <button class="btn-save-query" id="importQueriesBtn" title="Import queries" style="padding: 4px 8px; font-size: 11px;">⬆️</button>
                        <button class="btn-save-query" id="saveQueryBtn2" title="Save current query">+</button>
                    </div>
                </h3>
                <ul class="saved-query-list" id="savedQueryList">
                    <!-- Saved queries will be populated here -->
                </ul>
            </div>
            <input type="file" id="importFileInput" accept=".json" style="display: none;">
        </div>

        <div class="results-section" id="resultsSection" style="display: none;">
            <div class="results-header">
                <h3>📊 Query Results</h3>
                <span class="results-info" id="resultsInfo"></span>
            </div>
            <div class="results-wrapper">
                <table class="results-table" id="resultsTable">
                    <thead id="resultsHead"></thead>
                    <tbody id="resultsBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="empty-state" id="emptyState">
        <div class="empty-state-icon">🔍</div>
        <h3>No Table Selected</h3>
        <p>Please select a table from the dropdown above to start building SQL queries.</p>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast"></div>

    <!-- Include Modals -->
    <?php include 'modals.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="query_builder.js"></script>

    <?php include '../templates/footer.php'; ?>
</body>
</html>

<?php
/**
 * Table Structure - Database CRUD Manager
 * IP Authorization Check
 */
require_once '../login/auth_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Table Structure - Database CRUD Manager</title>
    <link rel="stylesheet" href="../styles/common.css">
    <link rel="stylesheet" href="table_structure.css">
</head>
<body>
    <?php
    $pageConfig = [
        'id' => 'table_structure',
        'title' => 'Table Structure Viewer/Editor',
        'icon' => '🔍',
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
                        <th style="text-align: right; width: 150px;">
                            <button id="addColumnBtn" class="btn-add-column" style="display: none;">➕ Add Column</button>
                        </th>
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

    <!-- Include Modals -->
    <?php include 'modals.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="table_structure.js"></script>

    <?php include '../templates/footer.php'; ?>
</body>
</html>

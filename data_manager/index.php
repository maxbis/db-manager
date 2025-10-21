<?php
/**
 * Database CRUD Manager - Main Interface
 * IP Authorization Check
 */
require_once '../login/auth_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database CRUD Manager</title>
    <link rel="stylesheet" href="../styles/common.css">
    <link rel="stylesheet" href="data_manager.css">
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
    include '../templates/header.php';
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

    <!-- Toast Notification -->
    <div class="toast" id="toast"></div>

    <!-- Include Modals -->
    <?php include 'modals.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="data_manager.js"></script>

    <?php include '../templates/footer.php'; ?>
</body>
</html>

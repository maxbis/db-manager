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
    <link rel="stylesheet" href="../styles/table_structure.css">
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

            <?php include __DIR__ . '/partials/structure.php'; ?>

            <?php include __DIR__ . '/partials/empty_state.php'; ?>
        </div>
    </div>

    <?php include __DIR__ . '/partials/column_modal.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="./table_structure.js"></script>

    <?php include '../templates/footer.php'; ?>
</body>
</html>

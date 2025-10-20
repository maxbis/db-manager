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
    <style>
        :root {
            /* Sapphire Nightfall Whisper Color Palette */
            --color-sapphire-bright: #0474C4;
            /* Vibrant medium blue */
            --color-sapphire-muted: #5379AE;
            /* Dusty periwinkle blue */
            --color-sapphire-dark: #2C444C;
            /* Dark desaturated teal */
            --color-sapphire-light: #A8C4EC;
            /* Light pastel sky blue */
            --color-sapphire-rich: #06457F;
            /* Deep rich sapphire */
            --color-sapphire-navy: #262B40;
            /* Dark navy blue */

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
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
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

        select,
        button {
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

        select:hover,
        select:focus {
            border-color: var(--color-primary-light);
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

        button:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            transform: none;
            position: relative;
        }

        button:disabled::after {
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
        }

        button:disabled:hover::after {
            opacity: 1;
            visibility: visible;
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--color-danger-lighter) 0%, var(--color-danger-lightest) 100%);
            color: var(--color-danger);
            border: 2px solid var(--color-danger-light);
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, var(--color-danger-light) 0%, var(--color-danger-lighter) 100%);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--color-success-lighter) 0%, var(--color-success-lightest) 100%);
            color: var(--color-success);
            border: 2px solid var(--color-success-light);
        }

        .btn-success:hover {
            background: linear-gradient(135deg, var(--color-success-light) 0%, var(--color-success-lighter) 100%);
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--color-warning-lighter) 0%, var(--color-warning-lightest) 100%);
            color: var(--color-warning);
            border: 2px solid var(--color-warning-light);
        }

        .btn-warning:hover {
            background: linear-gradient(135deg, var(--color-warning-light) 0%, var(--color-warning-lighter) 100%);
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
            transform: translateY(-2px);
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
            display: grid;
            grid-template-columns: auto 1fr auto auto;
            gap: 15px;
            align-items: center;
            transition: all 0.2s ease;
        }

        .database-item:hover {
            background: var(--color-bg-hover);
        }

        .database-item:last-child {
            border-bottom: none;
        }

        .database-icon {
            font-size: 20px;
            color: var(--color-primary);
        }

        .database-main-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .database-name {
            color: var(--color-text-primary);
            font-size: 16px;
            font-weight: 600;
            margin: 0;
        }

        .database-tables {
            color: var(--color-text-tertiary);
            font-size: 12px;
            margin: 0;
        }

        .database-size-column {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            min-width: 140px;
        }

        .database-size-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }

        .database-size-bar {
            width: 120px;
            height: 8px;
            border: 1px solid var(--color-border-lighter);
            border-radius: 4px;
            background: var(--color-bg-light);
            position: relative;
            overflow: hidden;
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
        }

        .database-size-bar {
            position: relative;
            cursor: help;
        }

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

        .database-actions {
            display: flex;
            gap: 8px;
        }

        .database-actions button {
            padding: 6px 12px;
            font-size: 12px;
            min-width: auto;
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
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
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
            max-width: 500px;
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
            font-size: 20px;
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
            font-size: 14px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px 15px;
            font-family: inherit;
            font-size: 14px;
            border: 2px solid var(--color-border-primary);
            border-radius: 6px;
            outline: none;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: var(--color-primary-light);
            box-shadow: 0 0 0 3px var(--overlay-focus);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
            font-family: 'Courier New', monospace;
        }

        .form-group label input[type="checkbox"] {
            width: auto;
            margin-right: 8px;
            display: inline-block;
        }

        .form-group label {
            display: flex;
            align-items: center;
            cursor: pointer;
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

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
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

        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--color-sapphire-dark);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: var(--shadow-xl);
            z-index: 3000;
            animation: slideInRight 0.3s ease;
            display: none;
            max-width: 400px;
        }

        .toast.active {
            display: block;
        }

        .toast.success {
            background: var(--color-success);
        }

        .toast.error {
            background: var(--color-danger);
        }

        .toast.warning {
            background: var(--color-warning);
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

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-buttons button {
                min-width: auto;
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
                        <button id="createDatabaseBtn" class="btn-success" style="padding: 5px 10px; font-size: 10px;">➕
                            Create DB</button>
                        <button id="exportDatabaseBtn" class="btn-warning" disabled
                            style="padding: 5px 10px; font-size: 10px;">📤 Export DB</button>
                        <button id="importDatabaseBtn" class="btn-warning"
                            style="padding: 5px 10px; font-size: 10px;">📥 Import DB</button>
                        <button id="exportAllDatabasesBtn"
                            style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; padding: 5px 10px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 10px;">
                            📦 Export All
                        </button>
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
                <button id="refreshDatabasesBtn" class="btn-secondary" style="padding: 6px 12px; font-size: 12px;">🔄
                    Refresh</button>
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
                    <label for="newTableColumns">Columns (one per line): <span
                            style="color: var(--color-danger);">*</span></label>
                    <textarea id="newTableColumns"
                        placeholder="id INT AUTO_INCREMENT PRIMARY KEY&#10;name VARCHAR(255) NOT NULL&#10;email VARCHAR(255) UNIQUE&#10;created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
                        rows="8"></textarea>
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

        // Initialize
        $(document).ready(function () {
            loadDatabases();

            // Event handlers
            $('#refreshBtn, #refreshDatabasesBtn').click(function () {
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

            $('#deleteDatabaseBtn').click(function () {
                if (currentDatabase) {
                    deleteDatabase(currentDatabase);
                }
            });

            $('#deleteTableBtn').click(function () {
                if (selectedTable) {
                    deleteTable(selectedTable);
                }
            });

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

            $('#confirmCreateDatabaseBtn').click(function () {
                createDatabase();
            });

            $('#confirmCreateTableBtn').click(function () {
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

            // Close modal on outside click
            $(document).click(function (e) {
                if ($(e.target).hasClass('modal')) {
                    closeModal($(e.target).attr('id'));
                }
            });
        });

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

        // Display databases list
        function displayDatabases() {
            const databaseList = $('#databaseList');
            databaseList.empty();

            if (databases.length === 0) {
                databaseList.append(`
                    <div class="empty-state" style="padding: 40px 20px;">
                        <div class="empty-state-icon">🗄️</div>
                        <h3>No Databases Found</h3>
                        <p>Create your first database to get started.</p>
                    </div>
                `);
                return;
            }

            databases.forEach(function (db) {
                const isCurrent = db.name === currentDatabase;
                const sizeInMB = Math.round((db.size || 0) / (1024 * 1024));
                const barWidth = Math.min(sizeInMB, 100); // Cap at 100px for 100MB
                const isLarge = sizeInMB > 100;
                const displaySize = formatBytes(db.size || 0);

                const databaseItem = $(`
                    <div class="database-item ${isCurrent ? 'active' : ''}" data-database="${db.name}">
                        <span class="database-icon">🗄️</span>
                        <div class="database-main-info">
                            <h4 class="database-name">${db.name}</h4>
                            <p class="database-tables">${db.tables || 0} tables</p>
                        </div>
                        <div class="database-size-column">
                            <div class="database-size-info">
                                <div class="database-size-bar" data-tooltip="Size: ${displaySize} (${sizeInMB}MB)${isLarge ? ' - Large database!' : ''}">
                                    <div class="database-size-fill ${isLarge ? 'large' : ''}" style="width: ${barWidth}px;"></div>
                                </div>
                                <span class="database-size-text">${displaySize}</span>
                            </div>
                        </div>
                        <div class="database-actions" style="display: flex; gap: 6px;">
                            <button class="btn-success" onclick="selectDatabase('${db.name}')" style="padding: 4px 8px; font-size: 11px;">Select</button>
                            <button class="btn-warning" onclick="openExportModal('${db.name}')" style="padding: 4px 8px; font-size: 11px;">Export</button>
                            <button class="btn-danger" onclick="deleteDatabase('${db.name}')" style="padding: 4px 8px; font-size: 11px;">Delete</button>
                        </div>
                    </div>
                `);
                databaseList.append(databaseItem);
            });
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
                const isSelected = table === selectedTable;
                const tableItem = $(`
                    <div class="table-item ${isSelected ? 'selected' : ''}" data-table="${table}" style="cursor: pointer;">
                        <div class="table-info">
                            <span class="table-icon">📋</span>
                            <div class="table-details">
                                <h4>${table}</h4>
                                <p>Table in ${currentDatabase}</p>
                            </div>
                        </div>
                        <div class="table-actions" style="display: flex; gap: 6px;">
                            <button class="btn-success" onclick="event.stopPropagation(); viewTable('${table}')" style="padding: 4px 8px; font-size: 11px;">View</button>
                            <button class="btn-danger" onclick="event.stopPropagation(); deleteTable('${table}')" style="padding: 4px 8px; font-size: 11px;">Delete</button>
                        </div>
                    </div>
                `);

                // Add click handler to select the table
                tableItem.click(function (e) {
                    // Don't trigger if clicking on buttons
                    if ($(e.target).is('button') || $(e.target).closest('button').length) {
                        return;
                    }

                    selectTable(table);
                });

                tableList.append(tableItem);
            });
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

            $('#deleteDatabaseBtn').prop('disabled', !hasDatabase)
                .attr('data-tooltip', hasDatabase ? '' : 'Select a database to delete');
            $('#createTableBtn').prop('disabled', !hasDatabase)
                .attr('data-tooltip', hasDatabase ? '' : 'Select a database to create tables');
            $('#deleteTableBtn').prop('disabled', !hasSelectedTable)
                .attr('data-tooltip', hasSelectedTable ? '' : 'Select a table to delete');
            $('#exportDatabaseBtn').prop('disabled', !hasDatabase)
                .attr('data-tooltip', hasDatabase ? '' : 'Select a database to export');
            $('#importDatabaseBtn').prop('disabled', false)
                .attr('data-tooltip', ''); // Can always import
        }

        // Show/hide table list
        function showTableList() {
            $('#tableListSection').show();
        }

        function hideTableList() {
            $('#tableListSection').hide();
        }

        // Select database
        function selectDatabase(databaseName) {
            $('#databaseSelect').val(databaseName).trigger('change');
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
        function viewTable(tableName) {
            // Update the database badge to show database.table before navigating
            updateDatabaseBadge(currentDatabase, tableName);
            window.location.href = `table_structure.php?table=${encodeURIComponent(tableName)}&database=${encodeURIComponent(currentDatabase)}`;
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

        // Delete table (with custom confirm modal)
        function deleteTable(tableName) {
            showConfirmDialog({
                title: 'Delete Table',
                message: `Are you sure you want to delete the table "${tableName}"? This action cannot be undone!`,
                confirmText: 'Delete',
                confirmClass: 'btn-danger'
            }, function onConfirm() {
                $.ajax({
                    url: 'api.php',
                    method: 'POST',
                    data: {
                        action: 'deleteTable',
                        database: currentDatabase,
                        name: tableName
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            showToast('Table deleted successfully!', 'success');
                            // Clear selection if the deleted table was selected
                            if (selectedTable === tableName) {
                                selectedTable = '';
                            }
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
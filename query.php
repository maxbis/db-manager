<?php
/**
 * SQL Query Builder - Database CRUD Manager
 * IP Authorization Check
 */
require_once 'login/auth_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL Query Builder - Database CRUD Manager</title>
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

        button:active {
            transform: translateY(0);
        }

        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--color-primary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 10px 15px;
            border-radius: 6px;
        }

        .back-link:hover {
            background: var(--overlay-primary);
            transform: translateX(-2px);
        }

        .content {
            padding: 30px;
        }

        .query-layout {
            display: grid;
            grid-template-columns: 250px 1fr 300px;
            gap: 20px;
            margin-bottom: 20px;
        }

        .fields-panel {
            background: var(--color-bg-light);
            border: 2px solid var(--color-border-light);
            border-radius: 8px;
            padding: 15px;
            max-height: 500px;
            overflow-y: auto;
        }

        .fields-panel h3 {
            color: var(--color-primary);
            font-size: 16px;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--color-border-primary);
        }

        .field-list {
            list-style: none;
        }

        .field-item {
            padding: 8px 10px;
            margin-bottom: 4px;
            background: var(--color-bg-white);
            border: 1px solid var(--color-border-lighter);
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: var(--color-text-secondary);
        }

        .field-item:hover {
            background: var(--color-bg-hover);
            border-color: var(--color-primary-light);
            transform: translateX(3px);
        }

        .field-item .field-type {
            display: block;
            font-size: 10px;
            color: var(--color-text-muted);
            margin-top: 2px;
        }

        .saved-queries-panel {
            background: var(--color-bg-light);
            border: 2px solid var(--color-border-light);
            border-radius: 8px;
            padding: 15px;
            max-height: 500px;
            overflow-y: auto;
        }

        .saved-queries-panel h3 {
            color: var(--color-primary);
            font-size: 16px;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--color-border-primary);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .saved-query-list {
            list-style: none;
        }

        .saved-query-item {
            padding: 12px;
            margin-bottom: 8px;
            background: var(--color-bg-white);
            border: 1px solid var(--color-border-lighter);
            border-radius: 6px;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .saved-query-item:hover {
            background: var(--color-bg-hover);
            border-color: var(--color-primary-light);
            box-shadow: var(--shadow-sm);
        }

        .saved-query-name {
            font-weight: 600;
            color: var(--color-primary);
            margin-bottom: 4px;
            font-size: 14px;
        }

        .saved-query-preview {
            font-family: 'Courier New', monospace;
            font-size: 11px;
            color: var(--color-text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 6px;
        }

        .saved-query-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            color: var(--color-text-muted);
        }

        .saved-query-actions {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }

        .saved-query-actions button {
            padding: 6px 12px;
            font-size: 12px;
            flex: 1;
        }

        .btn-load {
            background: linear-gradient(135deg, var(--color-primary-light) 0%, var(--color-primary-lighter) 100%);
            color: var(--color-bg-white);
            border: 1px solid var(--color-primary);
        }

        .btn-load:hover {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-light) 100%);
            box-shadow: var(--shadow-sm);
        }

        .btn-delete-saved {
            background: linear-gradient(135deg, var(--color-danger-lighter) 0%, var(--color-danger-lightest) 100%);
            color: var(--color-danger);
            border: 1px solid var(--color-danger-light);
        }

        .btn-delete-saved:hover {
            background: linear-gradient(135deg, var(--color-danger-light) 0%, var(--color-danger-lighter) 100%);
        }

        .btn-save-query {
            padding: 6px 12px;
            font-size: 12px;
            background: linear-gradient(135deg, var(--color-success-lighter) 0%, var(--color-success-lightest) 100%);
            color: var(--color-success);
            border: 1px solid var(--color-success-light);
        }

        .btn-save-query:hover {
            background: linear-gradient(135deg, var(--color-success-light) 0%, var(--color-success-lighter) 100%);
        }

        .query-panel {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .query-input-wrapper {
            position: relative;
        }

        .query-input {
            width: 100%;
            min-height: 300px;
            padding: 15px;
            font-family: 'Courier New', 'Consolas', 'Monaco', monospace;
            font-size: 14px;
            line-height: 1.6;
            background: var(--color-bg-white);
            border: 2px solid var(--color-border-primary);
            border-radius: 8px;
            outline: none;
            transition: all 0.3s ease;
            resize: vertical;
            color: var(--color-text-primary);
        }

        .query-input:focus {
            border-color: var(--color-primary-light);
            box-shadow: 0 0 0 3px var(--overlay-focus);
        }

        .query-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn-execute {
            background: linear-gradient(135deg, var(--color-primary-light) 0%, var(--color-primary-lighter) 100%);
            color: var(--color-bg-white);
            border: 2px solid var(--color-primary);
            font-size: 15px;
            padding: 12px 30px;
        }

        .btn-execute:hover {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-light) 100%);
        }

        .btn-clear {
            background: linear-gradient(135deg, var(--color-gray-300) 0%, var(--color-gray-200) 100%);
            color: var(--color-text-secondary);
            border: 2px solid var(--color-border-gray);
        }

        .btn-clear:hover {
            background: linear-gradient(135deg, var(--color-gray-400) 0%, var(--color-gray-300) 100%);
        }

        .results-section {
            margin-top: 30px;
        }

        .results-header {
            background: var(--color-bg-lighter);
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
            border: 2px solid var(--color-border-light);
            border-bottom: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .results-header h3 {
            color: var(--color-primary);
            font-size: 18px;
            margin: 0;
        }

        .results-info {
            color: var(--color-text-tertiary);
            font-size: 14px;
        }

        .results-wrapper {
            max-height: 500px;
            overflow: auto;
            border: 2px solid var(--color-border-light);
            border-radius: 0 0 8px 8px;
            background: var(--color-bg-white);
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .results-table thead {
            background: linear-gradient(135deg, var(--color-primary-lightest) 0%, var(--color-primary-pale) 100%);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .results-table th {
            padding: 12px 10px;
            text-align: left;
            font-weight: 600;
            color: var(--color-sapphire-navy);
            border-bottom: 2px solid var(--color-primary-light);
            white-space: nowrap;
            font-family: 'Courier New', monospace;
        }

        .results-table td {
            padding: 10px;
            color: var(--color-text-secondary);
            border-bottom: 1px solid var(--color-border-lighter);
            font-family: 'Courier New', monospace;
        }

        .results-table tbody tr:hover {
            background: var(--color-bg-hover);
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

        .query-examples {
            background: var(--color-warning-pale);
            border: 1px solid var(--color-warning-light);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            position: relative;
            transition: all 0.3s ease;
        }

        .query-examples.hidden {
            display: none;
        }

        .query-examples h4 {
            color: var(--color-warning);
            font-size: 14px;
            margin-bottom: 10px;
            padding-right: 30px;
        }

        .query-examples ul {
            list-style: none;
            font-size: 13px;
            color: var(--color-text-secondary);
        }

        .query-examples li {
            padding: 5px 0;
            font-family: 'Courier New', monospace;
        }

        .close-examples-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            color: var(--color-warning);
            font-size: 20px;
            cursor: pointer;
            padding: 5px;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
            line-height: 1;
        }

        .close-examples-btn:hover {
            background: var(--color-warning-light);
            color: var(--color-bg-white);
            transform: rotate(90deg);
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
        .form-group textarea {
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
        .form-group textarea:focus {
            border-color: var(--color-primary-light);
            box-shadow: 0 0 0 3px var(--overlay-focus);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
            font-family: 'Courier New', monospace;
        }

        .modal-footer {
            padding: 20px 25px;
            background: var(--color-bg-light);
            border-top: 1px solid var(--color-border-light);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
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

        /* Responsive */
        @media (max-width: 1200px) {
            .query-layout {
                grid-template-columns: 200px 1fr 250px;
            }
        }

        @media (max-width: 1024px) {
            .query-layout {
                grid-template-columns: 1fr;
            }

            .fields-panel {
                max-height: 200px;
            }

            .saved-queries-panel {
                max-height: 300px;
            }
        }

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

            .query-input {
                min-height: 200px;
            }

            .results-wrapper {
                max-height: 300px;
            }
        }
    </style>
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
    include 'templates/header.php';
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
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast"></div>

    <!-- Save Query Modal -->
    <div class="modal" id="saveQueryModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>💾 Save Query</h2>
                <button class="modal-close" onclick="closeSaveModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="saveQueryName">Query Name: <span style="color: var(--color-danger);">*</span></label>
                    <input type="text" id="saveQueryName" placeholder="e.g., Get All Users" required>
                </div>
                <div class="form-group">
                    <label for="saveQueryDescription">Description (optional):</label>
                    <textarea id="saveQueryDescription" placeholder="Brief description of what this query does..."></textarea>
                </div>
                <div class="form-group">
                    <label for="saveQuerySql">SQL Query:</label>
                    <textarea id="saveQuerySql" readonly style="background: var(--color-bg-light);"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-clear" onclick="closeSaveModal()">Cancel</button>
                <button class="btn-execute" id="confirmSaveBtn">💾 Save</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Global state
        let currentTable = '';
        let tableInfo = null;

        // Initialize
        $(document).ready(function() {
            loadTables();
            loadSavedQueries();
            
            // Check if examples box should be hidden (user previously closed it)
            if (localStorage.getItem('hideExamples') === 'true') {
                $('#queryExamples').hide();
            }
            
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
            
            // Save current query to localStorage before leaving the page
            function saveCurrentQuery() {
                const query = $('#queryInput').val();
                const table = $('#tableSelect').val();
                if (query && table) {
                    const queryState = {
                        query: query,
                        table: table,
                        timestamp: Date.now()
                    };
                    localStorage.setItem('currentQuery', JSON.stringify(queryState));
                }
            }
            
            // Auto-save query when typing (with debounce)
            let autoSaveTimeout;
            $('#queryInput').on('input', function() {
                clearTimeout(autoSaveTimeout);
                autoSaveTimeout = setTimeout(saveCurrentQuery, 500);
            });
            
            // Save query when leaving the page
            $(window).on('beforeunload', function() {
                saveCurrentQuery();
            });
            
            $('#tableSelect').change(function() {
                const previousTable = currentTable;
                currentTable = $(this).val();
                updateNavLinks();
                updateDatabaseBadge();
                
                if (currentTable) {
                    loadTableInfo();
                    
                    // Check if we have a saved query for this table
                    const savedQueryState = localStorage.getItem('currentQuery');
                    if (savedQueryState) {
                        try {
                            const queryState = JSON.parse(savedQueryState);
                            // Restore query if it's for the same table
                            if (queryState.table === currentTable) {
                                $('#queryInput').val(queryState.query);
                            } else {
                                // Different table selected, clear and set default query
                                $('#queryInput').val(`SELECT * FROM ${currentTable} LIMIT 10`);
                            }
                        } catch (e) {
                            $('#queryInput').val(`SELECT * FROM ${currentTable} LIMIT 10`);
                        }
                    } else {
                        $('#queryInput').val(`SELECT * FROM ${currentTable} LIMIT 10`);
                    }
                    
                    loadSavedQueries(currentTable);
                } else {
                    showEmptyState();
                }
            });

            $('#executeBtn').click(function() {
                executeQuery();
            });

            $('#clearBtn').click(function() {
                $('#queryInput').val('');
                $('#resultsSection').hide();
                // Clear saved query state when explicitly clearing
                localStorage.removeItem('currentQuery');
            });

            $('#saveQueryBtn, #saveQueryBtn2').click(function() {
                openSaveModal();
            });

            $('#confirmSaveBtn').click(function() {
                saveQueryToDatabase();
            });

            $('#exportQueriesBtn').click(function() {
                exportQueries();
            });

            $('#importQueriesBtn').click(function() {
                $('#importFileInput').click();
            });

            $('#importFileInput').change(function(e) {
                importQueries(e);
            });

            // Close examples box
            $('#closeExamplesBtn').click(function() {
                $('#queryExamples').fadeOut(300);
                localStorage.setItem('hideExamples', 'true');
            });

            // Click on field to insert into query
            $(document).on('click', '.field-item', function() {
                const fieldName = $(this).data('field');
                insertFieldName(fieldName);
            });

            // Close modal on outside click
            $(document).click(function(e) {
                if ($(e.target).is('#saveQueryModal')) {
                    closeSaveModal();
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
                            // Handle both old format (string) and new format (object)
                            const tableName = typeof table === 'string' ? table : table.name;
                            const tableType = typeof table === 'object' ? table.type : 'BASE TABLE';
                            const label = tableType === 'VIEW' ? `${tableName} 👁️ (view)` : tableName;
                            
                            select.append(`<option value="${tableName}" data-type="${tableType}">${label}</option>`);
                        });
                        
                        // Check for table parameter in URL and select it
                        const urlParams = new URLSearchParams(window.location.search);
                        const tableParam = urlParams.get('table');
                        if (tableParam) {
                            const tableNames = response.tables.map(t => typeof t === 'string' ? t : t.name);
                            if (tableNames.includes(tableParam)) {
                                select.val(tableParam).trigger('change');
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
            
            $.ajax({
                url: 'api.php?action=getTableInfo&table=' + encodeURIComponent(currentTable),
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        tableInfo = response;
                        displayFieldList();
                        $('#queryInterface').show();
                        $('#emptyState').hide();
                        
                        // Show info if it's a view
                        if (tableInfo.isView) {
                            showToast('👁️ Querying a database VIEW', 'warning');
                        }
                    }
                    $('#loading').removeClass('active');
                },
                error: function(xhr) {
                    showToast('Error loading table info', 'error');
                    $('#loading').removeClass('active');
                }
            });
        }

        // Display field list in sidebar
        function displayFieldList() {
            const fieldList = $('#fieldList');
            fieldList.empty();
            
            if (!tableInfo || !tableInfo.columns) {
                return;
            }
            
            tableInfo.columns.forEach(function(col) {
                const fieldItem = $(`
                    <li class="field-item" data-field="${col.name}">
                        <strong>${col.name}</strong>
                        <span class="field-type">${col.type}</span>
                    </li>
                `);
                fieldList.append(fieldItem);
            });
        }

        // Insert field name at cursor position
        function insertFieldName(fieldName) {
            const textarea = document.getElementById('queryInput');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            const before = text.substring(0, start);
            const after = text.substring(end, text.length);
            
            textarea.value = before + fieldName + after;
            textarea.selectionStart = textarea.selectionEnd = start + fieldName.length;
            textarea.focus();
        }

        // Execute SQL query
        function executeQuery() {
            const query = $('#queryInput').val().trim();
            
            if (!query) {
                showToast('Please enter a SQL query', 'warning');
                return;
            }
            
            $('#loading').addClass('active');
            $('#executeBtn').prop('disabled', true);
            
            $.ajax({
                url: 'api.php',
                method: 'POST',
                data: {
                    action: 'executeQuery',
                    query: query
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        displayResults(response);
                        showToast('Query executed successfully', 'success');
                    } else {
                        showToast('Query error: ' + response.error, 'error');
                    }
                    $('#loading').removeClass('active');
                    $('#executeBtn').prop('disabled', false);
                },
                error: function(xhr) {
                    const response = xhr.responseJSON || {};
                    showToast('Error: ' + (response.error || 'Unknown error'), 'error');
                    $('#loading').removeClass('active');
                    $('#executeBtn').prop('disabled', false);
                }
            });
        }

        // Display query results
        function displayResults(response) {
            const resultsSection = $('#resultsSection');
            const resultsHead = $('#resultsHead');
            const resultsBody = $('#resultsBody');
            const resultsInfo = $('#resultsInfo');
            
            resultsHead.empty();
            resultsBody.empty();
            
            if (response.type === 'select') {
                const data = response.data || [];
                const rowCount = data.length;
                const totalRows = response.totalRows || rowCount;
                
                resultsInfo.text(`${rowCount} rows returned${totalRows > 100 ? ' (limited to first 100)' : ''}`);
                
                if (rowCount === 0) {
                    resultsBody.append('<tr><td colspan="100" style="text-align: center; padding: 40px;">No results found</td></tr>');
                } else {
                    // Build header
                    const columns = Object.keys(data[0]);
                    let headerRow = '<tr>';
                    columns.forEach(function(col) {
                        headerRow += `<th>${escapeHtml(col)}</th>`;
                    });
                    headerRow += '</tr>';
                    resultsHead.html(headerRow);
                    
                    // Build rows
                    data.forEach(function(row) {
                        let rowHtml = '<tr>';
                        columns.forEach(function(col) {
                            const value = row[col];
                            if (value === null) {
                                rowHtml += '<td><em style="color: var(--color-text-muted);">NULL</em></td>';
                            } else {
                                rowHtml += `<td>${escapeHtml(String(value))}</td>`;
                            }
                        });
                        rowHtml += '</tr>';
                        resultsBody.append(rowHtml);
                    });
                }
            } else {
                // Non-SELECT query (INSERT, UPDATE, DELETE, etc.)
                resultsInfo.text(response.message || 'Query executed successfully');
                resultsHead.html('<tr><th>Result</th></tr>');
                resultsBody.html(`<tr><td>${response.message || 'Success'}</td></tr>`);
            }
            
            resultsSection.show();
        }

        // Show empty state
        function showEmptyState() {
            $('#queryInterface').hide();
            $('#emptyState').show();
            $('#resultsSection').hide();
        }

        // Show toast notification
        function showToast(message, type = 'success') {
            const toast = $('#toast');
            toast.text(message);
            toast.removeClass('success error warning');
            toast.addClass(type);
            toast.addClass('active');
            
            setTimeout(function() {
                toast.removeClass('active');
            }, 4000);
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

        // Load saved queries from LocalStorage
        function loadSavedQueries(tableName = null) {
            try {
                // Get queries from localStorage
                const queriesJson = localStorage.getItem('savedQueries');
                let queries = queriesJson ? JSON.parse(queriesJson) : [];
                
                // Filter by table if specified
                if (tableName) {
                    queries = queries.filter(q => q.table_name === tableName || !q.table_name);
                }
                
                // Sort by last used (most recent first), then by created date
                queries.sort((a, b) => {
                    const aDate = a.last_used_at || a.created_at;
                    const bDate = b.last_used_at || b.created_at;
                    return new Date(bDate) - new Date(aDate);
                });
                
                displaySavedQueries(queries);
            } catch (e) {
                console.error('Error loading saved queries from localStorage:', e);
                displaySavedQueries([]);
            }
        }

        // Display saved queries list
        function displaySavedQueries(queries) {
            const savedQueryList = $('#savedQueryList');
            savedQueryList.empty();
            
            if (queries.length === 0) {
                savedQueryList.append(`
                    <li style="text-align: center; padding: 20px; color: var(--color-text-muted); font-size: 13px;">
                        No saved queries yet.<br>Save your first query!
                    </li>
                `);
                return;
            }
            
            queries.forEach(function(query) {
                const queryPreview = query.query_sql.substring(0, 50) + (query.query_sql.length > 50 ? '...' : '');
                const useCount = query.use_count || 0;
                const tableBadge = query.table_name ? 
                    `<span style="background: var(--color-primary-pale); color: var(--color-primary); padding: 2px 6px; border-radius: 3px; font-size: 10px;">${query.table_name}</span>` : 
                    '';
                
                const queryItem = $(`
                    <li class="saved-query-item" data-query-id="${query.id}">
                        <div class="saved-query-name">${escapeHtml(query.query_name)}</div>
                        <div class="saved-query-preview">${escapeHtml(queryPreview)}</div>
                        ${query.description ? `<div style="font-size: 11px; color: var(--color-text-muted); margin-bottom: 4px;">${escapeHtml(query.description)}</div>` : ''}
                        <div class="saved-query-meta">
                            <span>${tableBadge} Used: ${useCount}x</span>
                        </div>
                        <div class="saved-query-actions">
                            <button class="btn-load" onclick="loadQuery(${query.id}); event.stopPropagation();">📂 Load</button>
                            <button class="btn-delete-saved" onclick="deleteSavedQueryConfirm(${query.id}, '${escapeHtml(query.query_name)}'); event.stopPropagation();">🗑️</button>
                        </div>
                    </li>
                `);
                
                savedQueryList.append(queryItem);
            });
        }

        // Open save query modal
        function openSaveModal() {
            const query = $('#queryInput').val().trim();
            
            if (!query) {
                showToast('Please enter a query first', 'warning');
                return;
            }
            
            $('#saveQueryName').val('');
            $('#saveQueryDescription').val('');
            $('#saveQuerySql').val(query);
            $('#saveQueryModal').addClass('active');
        }

        // Close save query modal
        function closeSaveModal() {
            $('#saveQueryModal').removeClass('active');
        }

        // Save query to LocalStorage
        function saveQueryToDatabase() {
            const queryName = $('#saveQueryName').val().trim();
            const queryDescription = $('#saveQueryDescription').val().trim();
            const querySql = $('#saveQuerySql').val();
            
            if (!queryName) {
                showToast('Please enter a query name', 'warning');
                return;
            }
            
            try {
                // Get existing queries from localStorage
                const queriesJson = localStorage.getItem('savedQueries');
                let queries = queriesJson ? JSON.parse(queriesJson) : [];
                
                // Create new query object
                const newQuery = {
                    id: Date.now(), // Use timestamp as unique ID
                    query_name: queryName,
                    query_sql: querySql,
                    table_name: currentTable || null,
                    description: queryDescription || null,
                    created_at: new Date().toISOString(),
                    last_used_at: null,
                    use_count: 0
                };
                
                // Add to queries array
                queries.push(newQuery);
                
                // Save back to localStorage
                localStorage.setItem('savedQueries', JSON.stringify(queries));
                
                showToast('Query saved successfully!', 'success');
                closeSaveModal();
                loadSavedQueries(currentTable);
                
            } catch (e) {
                console.error('Error saving query to localStorage:', e);
                showToast('Error: ' + e.message, 'error');
            }
        }

        // Load a saved query from LocalStorage
        function loadQuery(queryId) {
            try {
                // Get queries from localStorage
                const queriesJson = localStorage.getItem('savedQueries');
                let queries = queriesJson ? JSON.parse(queriesJson) : [];
                
                // Find the query
                const queryIndex = queries.findIndex(q => q.id === queryId);
                
                if (queryIndex === -1) {
                    showToast('Query not found', 'error');
                    return;
                }
                
                const query = queries[queryIndex];
                
                // Update usage statistics
                queries[queryIndex].last_used_at = new Date().toISOString();
                queries[queryIndex].use_count = (queries[queryIndex].use_count || 0) + 1;
                
                // Save updated queries back to localStorage
                localStorage.setItem('savedQueries', JSON.stringify(queries));
                
                // Load the query into the editor
                $('#queryInput').val(query.query_sql);
                showToast('Query loaded successfully!', 'success');
                
                // If query has a specific table and it's different from current, change table
                if (query.table_name && query.table_name !== currentTable) {
                    $('#tableSelect').val(query.table_name).trigger('change');
                } else {
                    // Reload the saved queries to show updated usage count
                    loadSavedQueries(currentTable);
                }
                
            } catch (e) {
                console.error('Error loading query from localStorage:', e);
                showToast('Error: ' + e.message, 'error');
            }
        }

        // Delete saved query with confirmation
        function deleteSavedQueryConfirm(queryId, queryName) {
            if (confirm(`Are you sure you want to delete the query "${queryName}"?`)) {
                deleteSavedQuery(queryId);
            }
        }

        // Delete a saved query from LocalStorage
        function deleteSavedQuery(queryId) {
            try {
                // Get queries from localStorage
                const queriesJson = localStorage.getItem('savedQueries');
                let queries = queriesJson ? JSON.parse(queriesJson) : [];
                
                // Filter out the query to delete
                queries = queries.filter(q => q.id !== queryId);
                
                // Save back to localStorage
                localStorage.setItem('savedQueries', JSON.stringify(queries));
                
                showToast('Query deleted successfully!', 'success');
                loadSavedQueries(currentTable);
                
            } catch (e) {
                console.error('Error deleting query from localStorage:', e);
                showToast('Error: ' + e.message, 'error');
            }
        }

        // Export queries to JSON file
        function exportQueries() {
            try {
                // Get queries from localStorage
                const queriesJson = localStorage.getItem('savedQueries');
                const queries = queriesJson ? JSON.parse(queriesJson) : [];
                
                if (queries.length === 0) {
                    showToast('No queries to export', 'warning');
                    return;
                }
                
                // Create export object with metadata
                const exportData = {
                    version: '1.0',
                    exported_at: new Date().toISOString(),
                    query_count: queries.length,
                    queries: queries
                };
                
                // Convert to JSON string with pretty formatting
                const jsonString = JSON.stringify(exportData, null, 2);
                
                // Create blob and download
                const blob = new Blob([jsonString], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `saved-queries-${new Date().toISOString().split('T')[0]}.json`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                
                showToast(`Exported ${queries.length} queries successfully!`, 'success');
                
            } catch (e) {
                console.error('Error exporting queries:', e);
                showToast('Error: ' + e.message, 'error');
            }
        }

        // Import queries from JSON file
        function importQueries(event) {
            const file = event.target.files[0];
            
            if (!file) {
                return;
            }
            
            const reader = new FileReader();
            
            reader.onload = function(e) {
                try {
                    const importData = JSON.parse(e.target.result);
                    
                    // Validate import data
                    if (!importData.queries || !Array.isArray(importData.queries)) {
                        showToast('Invalid import file format', 'error');
                        return;
                    }
                    
                    // Get existing queries
                    const queriesJson = localStorage.getItem('savedQueries');
                    let existingQueries = queriesJson ? JSON.parse(queriesJson) : [];
                    
                    // Merge strategy: add imported queries with new IDs to avoid conflicts
                    let importCount = 0;
                    let duplicateCount = 0;
                    
                    importData.queries.forEach(function(query) {
                        // Check if query with same name and SQL already exists
                        const isDuplicate = existingQueries.some(q => 
                            q.query_name === query.query_name && q.query_sql === query.query_sql
                        );
                        
                        if (!isDuplicate) {
                            // Assign new ID to avoid conflicts
                            query.id = Date.now() + importCount;
                            existingQueries.push(query);
                            importCount++;
                        } else {
                            duplicateCount++;
                        }
                    });
                    
                    // Save merged queries back to localStorage
                    localStorage.setItem('savedQueries', JSON.stringify(existingQueries));
                    
                    // Show results
                    let message = `Imported ${importCount} queries`;
                    if (duplicateCount > 0) {
                        message += ` (${duplicateCount} duplicates skipped)`;
                    }
                    showToast(message, 'success');
                    
                    // Reload display
                    loadSavedQueries(currentTable);
                    
                } catch (e) {
                    console.error('Error importing queries:', e);
                    showToast('Error: ' + e.message, 'error');
                }
            };
            
            reader.onerror = function() {
                showToast('Error reading file', 'error');
            };
            
            reader.readAsText(file);
            
            // Reset file input so same file can be imported again
            event.target.value = '';
        }

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


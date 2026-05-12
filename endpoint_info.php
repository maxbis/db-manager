<?php
/**
 * Shows resolved public URLs for the current PHP request (rewrites vs script path).
 *
 * Requires login. Supports ?format=json for machine-readable output.
 *
 * This page avoids the shared header/footer because those nav links assume a subdirectory URL.
 */

require_once __DIR__ . '/login/auth_check.php';
require_once __DIR__ . '/api/utils/endpoint_helper.php';

if (($_GET['format'] ?? '') === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    echo endpoint_helper_collect_json();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Endpoint Info</title>
    <link rel="stylesheet" href="styles/common.css">
    <style>
        .endpoint-shell {
            max-width: 46rem;
            margin: 0 auto;
            padding: 1.25rem 1.25rem 2rem;
        }
        .endpoint-shell-top {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }
        .endpoint-shell-top h1 {
            margin: 0;
            font-size: 1.35rem;
        }
        .endpoint-shell-links {
            font-size: 0.9rem;
            display: flex;
            gap: 0.85rem;
        }
        .endpoint-helper-page {
            margin-top: 0.5rem;
        }
        .endpoint-helper-meta,
        .endpoint-helper-tip {
            color: var(--text-muted, #6b7280);
            font-size: 0.9rem;
            line-height: 1.45;
        }
        .endpoint-helper-tip code {
            font-size: 0.82rem;
            background: var(--surface-alt, rgba(0, 0, 0, 0.06));
            padding: 0.08rem 0.35rem;
            border-radius: 0.25rem;
        }
        .endpoint-helper-row {
            margin: 1rem 0;
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }
        .endpoint-helper-row label {
            font-weight: 600;
            font-size: 0.92rem;
        }
        .endpoint-helper-url {
            width: 100%;
            padding: 0.5rem 0.65rem;
            border: 1px solid var(--border-color, rgba(0, 0, 0, 0.12));
            border-radius: 0.375rem;
            font-family: ui-monospace, Consolas, monospace;
            font-size: 0.85rem;
        }
        .endpoint-helper-footnote {
            margin-top: 1.25rem;
            font-size: 0.88rem;
        }
        .endpoint-helper-footnote code {
            word-break: break-all;
            font-size: 0.8rem;
        }
        .endpoint-helper-json-link {
            margin-top: 1rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
<div class="endpoint-shell">
    <div class="endpoint-shell-top">
        <h1>🔗 API endpoint detection</h1>
        <div class="endpoint-shell-links">
            <a href="db_manager/">Database Manager</a>
            <a href="settings/">Settings</a>
            <?php if (isset($_SESSION['username'])): ?>
                <a href="login/logout.php">Logout</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="endpoint-helper-page">
        <?php echo endpoint_helper_render_html_fragment(); ?>
        <p class="endpoint-helper-json-link">
            <a href="endpoint_info.php?format=json">JSON</a>
            — same detection, for paste into scripts or ticketing
        </p>
    </div>
</div>
</body>
</html>

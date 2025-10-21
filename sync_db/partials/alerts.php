<!-- Information Alert -->
<div class="alert alert-info">
    <span style="font-size: 20px;">ℹ️</span>
    <div>
        <strong>Database Sync Tool</strong><br>
        This tool allows you to sync a complete database from a remote server to your local server.
        All tables, data, views, stored procedures, functions, and triggers will be copied.
    </div>
</div>

<!-- Error Alert (Hidden by default) -->
<div class="alert alert-error" id="errorAlert" style="display: none;">
    <span style="font-size: 20px;">❌</span>
    <div style="flex: 1;">
        <strong id="errorTitle">Error</strong><br>
        <span id="errorMessage">An error occurred</span>
    </div>
    <button onclick="hideError()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--color-danger); padding: 0; margin-left: 10px;" title="Dismiss">✖</button>
</div>

<div class="sync-card">
    <h2>🔧 Sync Configuration</h2>
    <form id="syncForm">
        <!-- Remote Server Settings Group -->
        <div class="form-section">
            <h3 class="form-section-title">📡 Source (remote)</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label for="remoteUrl">Remote Server URL</label>
                    <div class="remote-url-container">
                        <input type="url" id="remoteUrl" name="remoteUrl" placeholder="https://example.com/sync_db/api.php" required>
                        <button type="button" id="remoteUrlPresetsBtn" class="btn btn-secondary btn-small" title="Choose a saved remote server URL">
                            <span>📋</span>
                            <span>Presets</span>
                        </button>
                    </div>
                    <small>Full URL to the remote sync API endpoint (pick a preset or type your own)</small>
                </div>

                <div class="form-group">
                    <label for="apiKey">API Key</label>
                    <input type="text" id="apiKey" name="apiKey" placeholder="Enter API key" required>
                    <small>The secure API key configured in config.php (saved in cookie)</small>
                </div>

                <div class="form-group">
                    <label for="remoteDbHost">Remote DB Host</label>
                    <input type="text" id="remoteDbHost" name="remoteDbHost" value="localhost" required>
                    <small>Usually "localhost" on remote server</small>
                </div>

                <div class="form-group">
                    <label for="remoteDbUser">Remote DB Username</label>
                    <input type="text" id="remoteDbUser" name="remoteDbUser" placeholder="database_user" required>
                    <small>Database username on remote server</small>
                </div>

                <div class="form-group">
                    <label for="remoteDbPass">Remote DB Password</label>
                    <input type="password" id="remoteDbPass" name="remoteDbPass" placeholder="database_password" required>
                    <small>Database password on remote server</small>
                </div>

                <div class="form-group">
                    <label for="remoteDbName">Remote Database Name</label>
                    <div class="database-select-container">
                        <input type="text" id="remoteDbName" name="remoteDbName" placeholder="remote_database" required>
                        <button type="button" id="loadDatabasesBtn" class="btn btn-secondary btn-small" title="Load available databases">
                            <span>📋</span>
                            <span>Load DBs</span>
                        </button>
                    </div>
                    <small>Name of database on remote server (click "Load DBs" to see available databases)</small>
                </div>
            </div>
        </div>

        <!-- Local Database Settings Group -->
        <div class="form-section">
            <h3 class="form-section-title">💾 Destination (this server)</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label for="localDbName">
                        Destination Database Name
                        <span id="localDbLock" class="local-lock">🔒</span>
                    </label>
                    <input type="text" id="localDbName" name="localDbName" placeholder="Specify remote DB first" readonly class="local-readonly" required>
                    <small id="localDbHelp">
                        Specify a remote database name first. The destination on this server will be dropped and recreated during sync.
                    </small>
                    <small id="destinationServerHint">
                        Destination server:
                        <code><?php echo htmlspecialchars($targetServerLabel ?? 'this server', ENT_QUOTES); ?></code>
                    </small>
                </div>
            </div>

            <details class="sync-advanced" id="syncAdvanced">
                <summary>Advanced options</summary>
                <div class="form-grid sync-advanced-grid">
                    <div class="form-group">
                        <label for="chunkSize">Chunk Size (rows per batch)</label>
                        <input type="number" id="chunkSize" name="chunkSize" value="1000" min="100" max="10000" required>
                        <small>Number of rows to transfer per request (default: 1000)</small>
                    </div>
                </div>
            </details>
        </div>

        <div class="sync-summary" id="syncSummary">
            Fill in source and destination above to preview the sync.
        </div>

        <div class="button-group">
            <button type="submit" class="btn btn-primary" id="syncBtn">
                <span>🔄</span>
                <span>Start Sync</span>
            </button>
            <button type="button" class="btn btn-secondary" id="testConnectionBtn">
                <span>🔌</span>
                <span>Test Connection</span>
            </button>
            <button type="button" class="btn btn-secondary" id="clearFormBtn">
                <span>🗑️</span>
                <span>Clear Form</span>
            </button>
            <a href="check_ip.php" class="btn btn-secondary" style="text-decoration: none;">
                <span>🌐</span>
                <span>Check My IP</span>
            </a>
        </div>
    </form>
</div>

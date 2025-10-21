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
                <label>Columns:</label>
                <div id="columnsBuilder">
                    <div class="column-rows"></div>
                    <button type="button" class="btn-secondary" id="addColumnRowBtn" style="margin-top: 8px; padding: 6px 10px; font-size: 12px;">➕ Add Column</button>
                </div>
                <!-- Hidden textarea kept for API compatibility; populated on submit -->
                <textarea id="newTableColumns" style="display:none;"></textarea>
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


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

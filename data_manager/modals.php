<!-- Edit/Insert Modal -->
<div class="modal" id="recordModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Edit Record</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- Form fields will be generated dynamically -->
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeModal()">Cancel</button>
            <button id="deleteRecordBtn" class="delete-btn" style="display: none;">🗑️ Delete</button>
            <button id="saveRecordBtn">💾 Save</button>
        </div>
    </div>
</div>

<!-- Confirmation Dialog -->
<div class="confirm-dialog" id="confirmDialog">
    <div class="confirm-content">
        <div class="confirm-icon">⚠️</div>
        <h3>Confirm Deletion</h3>
        <p id="confirmMessage">Are you sure you want to delete this record? This action cannot be undone.</p>
        <div class="confirm-buttons">
            <button class="btn-secondary" onclick="closeConfirmDialog()">Cancel</button>
            <button class="delete-btn" id="confirmDeleteBtn">Delete</button>
        </div>
    </div>
</div>


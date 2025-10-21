<!-- Column Edit Modal -->
<div class="modal" id="columnModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Edit Column</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- Form fields will be generated dynamically -->
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeModal()">Cancel</button>
            <button class="btn-danger" id="deleteColumnBtn" style="display: none;">🗑️ Generate Delete SQL</button>
            <button class="btn-primary" id="saveColumnBtn">⚡ Generate SQL</button>
        </div>
    </div>
</div>

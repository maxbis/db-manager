<!-- Generic Reusable Dialog Component -->
<div class="dialog-overlay" id="genericDialog" role="dialog" aria-modal="true" aria-labelledby="dialogTitle">
    <div class="dialog-container">
        <div class="dialog-header">
            <h2 id="dialogTitle" class="dialog-title">Dialog Title</h2>
            <button class="dialog-close" onclick="Dialog.close()" aria-label="Close dialog">&times;</button>
        </div>
        <div class="dialog-body" id="dialogBody">
            <p id="dialogMessage">Dialog message goes here.</p>
        </div>
        <div class="dialog-footer" id="dialogFooter">
            <button class="btn-secondary dialog-btn-cancel" id="dialogCancelBtn">Cancel</button>
            <button class="btn-primary dialog-btn-confirm" id="dialogConfirmBtn">Confirm</button>
        </div>
    </div>
</div>
/**
 * View Fixer Module - JavaScript
 * Handles view definer fixing functionality
 */

let allViews = [];
let currentUser = '';

// Load views on page load
$(document).ready(function() {
    loadViews();
});

// Load all views
function loadViews() {
    $('#loading').show();
    $('#viewsTable').hide();
    $('#emptyState').hide();

    $.ajax({
        url: '',
        method: 'POST',
        data: { action: 'getViews' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                allViews = response.views;
                currentUser = response.currentUser;
                $('#currentUser').text(currentUser);
                displayViews();
                updateStats();
            }
            $('#loading').hide();
        },
        error: function(xhr) {
            showToast('Error loading views: ' + (xhr.responseJSON?.error || xhr.responseText), 'error');
            $('#loading').hide();
        }
    });
}

// Display views in table
function displayViews() {
    const tbody = $('#viewsBody');
    tbody.empty();

    if (allViews.length === 0) {
        $('#emptyState').show();
        $('#fixAllBtn').prop('disabled', true);
        return;
    }

    $('#viewsTable').show();

    allViews.forEach(function(view) {
        const statusBadge = view.definerExists 
            ? '<span class="status-badge status-ok">✓ OK</span>'
            : '<span class="status-badge status-error">✗ Definer Missing</span>';

        const actionBtn = !view.definerExists
            ? `<button class="btn-success" style="padding: 6px 12px; font-size: 12px;" onclick="fixView('${view.database}', '${view.name}')">🔧 Fix</button>`
            : '<span style="color: var(--color-text-muted);">No action needed</span>';

        const row = `
            <tr>
                <td><strong>${view.database}</strong></td>
                <td>👁️ ${view.name}</td>
                <td><code style="background: var(--color-bg-lighter); padding: 2px 6px; border-radius: 4px;">${view.definer}</code></td>
                <td>${statusBadge}</td>
                <td>${view.securityType}</td>
                <td>${view.isUpdatable}</td>
                <td>${actionBtn}</td>
            </tr>
        `;
        tbody.append(row);
    });

    // Enable fix all button if there are problematic views
    const problematicViews = allViews.filter(v => !v.definerExists);
    $('#fixAllBtn').prop('disabled', problematicViews.length === 0);
}

// Update statistics
function updateStats() {
    const totalViews = allViews.length;
    const problematicViews = allViews.filter(v => !v.definerExists).length;
    const okViews = totalViews - problematicViews;
    const databases = [...new Set(allViews.map(v => v.database))].length;

    const html = `
        <div class="stat-card">
            <h3>${totalViews}</h3>
            <p>Total Views</p>
        </div>
        <div class="stat-card">
            <h3>${okViews}</h3>
            <p>Working Views</p>
        </div>
        <div class="stat-card warning">
            <h3>${problematicViews}</h3>
            <p>Problematic Views</p>
        </div>
        <div class="stat-card">
            <h3>${databases}</h3>
            <p>Databases</p>
        </div>
    `;

    $('#statsGrid').html(html);
}

// Fix a single view
function fixView(database, viewName) {
    if (!confirm(`Fix view "${viewName}" in database "${database}"?\n\nThis will recreate the view with your current user (${currentUser}) as the definer.`)) {
        return;
    }

    const btn = event.target;
    btn.disabled = true;
    btn.textContent = '🔄 Fixing...';

    $.ajax({
        url: '',
        method: 'POST',
        data: {
            action: 'fixView',
            database: database,
            viewName: viewName
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showToast(response.message, 'success');
                loadViews(); // Reload to show updated status
            }
        },
        error: function(xhr) {
            showToast('Error fixing view: ' + (xhr.responseJSON?.error || xhr.responseText), 'error');
            btn.disabled = false;
            btn.textContent = '🔧 Fix';
        }
    });
}

// Fix all problematic views
function fixAllViews() {
    const problematicViews = allViews.filter(v => !v.definerExists);
    
    if (problematicViews.length === 0) {
        showToast('No problematic views to fix!', 'warning');
        return;
    }

    const databases = [...new Set(problematicViews.map(v => v.database))];
    
    if (!confirm(`Fix ${problematicViews.length} problematic view(s) across ${databases.length} database(s)?\n\nDatabases: ${databases.join(', ')}\n\nAll views will be recreated with your current user (${currentUser}) as the definer.`)) {
        return;
    }

    const btn = $('#fixAllBtn');
    btn.prop('disabled', true).text('🔄 Fixing All...');

    // Fix views database by database
    let fixed = 0;
    let failed = 0;

    const fixNextDatabase = (index) => {
        if (index >= databases.length) {
            // All done
            showToast(`Fixed ${fixed} view(s)` + (failed > 0 ? `, ${failed} failed` : ''), fixed > 0 ? 'success' : 'warning');
            loadViews(); // Reload to show updated status
            return;
        }

        const database = databases[index];
        
        $.ajax({
            url: '',
            method: 'POST',
            data: {
                action: 'fixAllViews',
                database: database
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    fixed += response.fixed;
                    failed += response.failed.length;
                    
                    if (response.failed.length > 0) {
                        console.error('Failed views in ' + database + ':', response.failed);
                    }
                }
                fixNextDatabase(index + 1);
            },
            error: function(xhr) {
                failed += problematicViews.filter(v => v.database === database).length;
                console.error('Error fixing database ' + database + ':', xhr.responseJSON?.error);
                fixNextDatabase(index + 1);
            }
        });
    };

    fixNextDatabase(0);
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


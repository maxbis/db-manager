/**
 * Settings page – remote server URL preset management
 */
(function() {
    const presetsJsonInput = document.getElementById('presetsJson');
    const presetListEl = document.getElementById('presetList');
    const addPresetBtn = document.getElementById('addPresetBtn');

    if (!presetsJsonInput || !presetListEl || !addPresetBtn) {
        return;
    }

    let presets = [];

    function parsePresets() {
        try {
            const parsed = JSON.parse(presetsJsonInput.value || '[]');
            presets = Array.isArray(parsed) ? parsed : [];
        } catch (error) {
            presets = [];
        }
    }

    function syncHiddenInput() {
        presetsJsonInput.value = JSON.stringify(presets);
    }

    function defaultLabelFromUrl(url) {
        try {
            return new URL(url).hostname;
        } catch (error) {
            return url;
        }
    }

    function isValidUrl(url) {
        try {
            const parsed = new URL(url);
            return parsed.protocol === 'http:' || parsed.protocol === 'https:';
        } catch (error) {
            return false;
        }
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderPresetList() {
        presetListEl.innerHTML = '';

        if (presets.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'preset-list-empty';
            empty.textContent = 'No presets yet. Click + to add one.';
            presetListEl.appendChild(empty);
            return;
        }

        presets.forEach(function(preset, index) {
            const item = document.createElement('div');
            item.className = 'preset-list-item';
            item.setAttribute('role', 'button');
            item.setAttribute('tabindex', '0');
            item.dataset.index = String(index);
            item.innerHTML =
                '<span class="preset-list-label">' + escapeHtml(preset.label || 'Untitled') + '</span>' +
                '<span class="preset-list-url">' + escapeHtml(preset.url || '') + '</span>';
            item.addEventListener('click', function() {
                openPresetDialog(index);
            });
            item.addEventListener('keydown', function(event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openPresetDialog(index);
                }
            });
            presetListEl.appendChild(item);
        });
    }

    function savePresetsToServer(onSuccess) {
        syncHiddenInput();

        const formData = new FormData();
        formData.append('action', 'save_presets');
        formData.append('presets_json', presetsJsonInput.value);

        fetch('index.php', {
            method: 'POST',
            body: formData
        })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to save presets');
                }
                presets = data.presets || presets;
                syncHiddenInput();
                renderPresetList();
                if (onSuccess) {
                    onSuccess();
                }
            })
            .catch(function(error) {
                Dialog.alert({
                    title: 'Save Failed',
                    message: error.message,
                    icon: '❌',
                    confirmClass: 'btn-danger'
                });
            });
    }

    function openPresetDialog(index) {
        const isEdit = index !== null && index >= 0;
        const preset = isEdit ? presets[index] : { label: '', url: '' };

        const bodyHtml =
            '<div class="preset-dialog-form">' +
                '<div class="form-group">' +
                    '<label for="presetDialogLabel">Label</label>' +
                    '<input type="text" id="presetDialogLabel" value="' + escapeHtml(preset.label || '') + '" placeholder="e.g. Production">' +
                '</div>' +
                '<div class="form-group">' +
                    '<label for="presetDialogUrl">Server URL</label>' +
                    '<input type="url" id="presetDialogUrl" value="' + escapeHtml(preset.url || '') + '" placeholder="https://example.com/sync_db/api.php">' +
                '</div>' +
            '</div>';

        const buttons = [
            {
                text: 'Cancel',
                class: 'btn-secondary',
                action: function() {}
            }
        ];

        if (isEdit) {
            buttons.push({
                text: 'Delete',
                class: 'btn-danger',
                action: function() {
                    Dialog.confirm({
                        title: 'Delete Preset',
                        message: 'Delete "' + (preset.label || preset.url) + '"?',
                        icon: '🗑️',
                        confirmText: 'Delete',
                        confirmClass: 'btn-danger',
                        onConfirm: function() {
                            presets.splice(index, 1);
                            savePresetsToServer(function() {
                                Dialog.alert({
                                    title: 'Deleted',
                                    message: 'Preset removed.',
                                    icon: '✅',
                                    confirmClass: 'btn-success'
                                });
                            });
                        }
                    });
                }
            });
        }

        buttons.push({
            text: isEdit ? 'Save' : 'Add',
            class: 'btn-primary',
            action: function() {
                const labelInput = document.getElementById('presetDialogLabel');
                const urlInput = document.getElementById('presetDialogUrl');
                const label = labelInput.value.trim();
                const url = urlInput.value.trim();

                if (!url) {
                    Dialog.alert({
                        title: 'Missing URL',
                        message: 'Please enter a server URL.',
                        icon: '⚠️',
                        confirmClass: 'btn-warning'
                    });
                    return;
                }

                if (!isValidUrl(url)) {
                    Dialog.alert({
                        title: 'Invalid URL',
                        message: 'Please enter a valid http or https URL.',
                        icon: '⚠️',
                        confirmClass: 'btn-warning'
                    });
                    return;
                }

                const entry = {
                    label: label || defaultLabelFromUrl(url),
                    url: url
                };

                if (isEdit) {
                    presets[index] = entry;
                } else {
                    presets.push(entry);
                }

                savePresetsToServer(function() {
                    Dialog.alert({
                        title: isEdit ? 'Saved' : 'Added',
                        message: isEdit ? 'Preset updated.' : 'Preset added.',
                        icon: '✅',
                        confirmClass: 'btn-success'
                    });
                });
            }
        });

        Dialog.custom({
            title: isEdit ? 'Edit Preset' : 'Add Preset',
            body: bodyHtml,
            buttons: buttons,
            width: '520px'
        });

        setTimeout(function() {
            const focusTarget = document.getElementById(isEdit ? 'presetDialogLabel' : 'presetDialogUrl');
            if (focusTarget) {
                focusTarget.focus();
            }
        }, 50);
    }

    parsePresets();
    renderPresetList();

    addPresetBtn.addEventListener('click', function() {
        openPresetDialog(null);
    });
})();

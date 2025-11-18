(function (window, document) {
    'use strict';

    const config = window.APP_SESSION_CONFIG || {};
    if (!config.statusUrl) {
        return;
    }

    const defaults = {
        statusUrl: config.statusUrl,
        loginUrl: config.loginUrl || 'login/login.php',
        autoReloadDelay: 1000
    };

    const SessionManager = {
        state: 'idle',
        overlay: null,
        overlayMessage: null,
        overlaySpinner: null,
        overlayActions: null,
        reconnectBtn: null,
        loginBtn: null,
        pendingRequest: null,

        init() {
            this.ensureOverlay();
            this.waitForjQuery(() => {
                this.bindAjaxHandlers();
            });
            this.patchFetch();
        },

        waitForjQuery(callback) {
            if (window.jQuery) {
                callback();
                return;
            }
            const interval = setInterval(() => {
                if (window.jQuery) {
                    clearInterval(interval);
                    callback();
                }
            }, 100);
        },

        bindAjaxHandlers() {
            if (!window.jQuery) {
                return;
            }

            const $doc = window.jQuery(document);
            $doc.ajaxError((event, jqxhr) => {
                this.inspectResponse(jqxhr);
            });

            $doc.ajaxSuccess((event, jqxhr) => {
                this.inspectResponse(jqxhr);
            });
        },

        patchFetch() {
            if (!window.fetch || window.fetch.__sessionHandlerPatched) {
                return;
            }

            const originalFetch = window.fetch.bind(window);
            const self = this;

            const patchedFetch = function (...args) {
                return originalFetch(...args)
                    .then(response => {
                        self.inspectFetchResponse(response.clone());
                        return response;
                    })
                    .catch(error => {
                        self.handleNetworkError(error);
                        throw error;
                    });
            };

            patchedFetch.__sessionHandlerPatched = true;
            window.fetch = patchedFetch;
        },

        inspectResponse(xhr) {
            if (this.state !== 'idle' || !xhr) {
                return;
            }

            if (this.isAuthRedirect(xhr)) {
                this.startRecoveryFlow();
            }
        },

        inspectFetchResponse(response) {
            if (this.state !== 'idle' || !response) {
                return;
            }

            if (response.status === 401 || response.status === 440) {
                this.startRecoveryFlow();
                return;
            }

            const responseURL = response.url || '';
            if (responseURL.includes('login.php')) {
                this.startRecoveryFlow();
            }
        },

        handleNetworkError(error) {
            if (this.state !== 'idle') {
                return;
            }
            if (!error || !error.message) {
                return;
            }
            if (error.message.toLowerCase().includes('failed to fetch')) {
                // Could be offline; ignore for now
                return;
            }
        },

        isAuthRedirect(xhr) {
            if (xhr.status === 0) {
                return false;
            }

            if (xhr.status === 401 || xhr.status === 403 || xhr.status === 440) {
                return true;
            }

            const responseURL = xhr.responseURL || '';
            if (responseURL.includes('login.php')) {
                return true;
            }

            if (typeof xhr.responseText === 'string' && xhr.responseText.includes('login/login.php')) {
                return true;
            }

            return false;
        },

        startRecoveryFlow() {
            if (this.pendingRequest || this.state === 'expired') {
                return;
            }

            this.state = 'checking';
            this.showOverlay('Your session might have expired. Reconnecting…', {
                showSpinner: true,
                showButtons: false
            });
            this.requestSessionStatus();
        },

        requestSessionStatus() {
            this.pendingRequest = fetch(`${defaults.statusUrl}?action=refresh`, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json'
                }
            })
                .then(response => response.json())
                .then(data => {
                    this.pendingRequest = null;
                    this.handleStatusResponse(data);
                })
                .catch(() => {
                    this.pendingRequest = null;
                    this.handleStatusResponse({ status: 'error' });
                });
        },

        handleStatusResponse(data) {
            const status = data && data.status ? data.status : 'error';

            if (status === 'active') {
                this.state = 'idle';
                this.hideOverlay();
                return;
            }

            if (status === 'reauthenticated') {
                this.showOverlay('Session restored. Reloading…', {
                    showSpinner: true,
                    showButtons: false
                });
                setTimeout(() => {
                    window.location.reload();
                }, defaults.autoReloadDelay);
                return;
            }

            this.state = 'expired';
            this.showOverlay('Your session ended. Please log in again.', {
                showSpinner: false,
                showButtons: true
            });
        },

        ensureOverlay() {
            if (this.overlay) {
                return;
            }

            const overlay = document.createElement('div');
            overlay.className = 'session-overlay';
            overlay.setAttribute('role', 'alertdialog');
            overlay.setAttribute('aria-live', 'assertive');
            overlay.innerHTML = `
                <div class="session-overlay-content">
                    <div class="session-overlay-header">🔐 Session Notice</div>
                    <p class="session-overlay-message"></p>
                    <div class="session-overlay-spinner" hidden></div>
                    <div class="session-overlay-actions" hidden>
                        <button type="button" class="session-overlay-btn session-overlay-btn-primary" data-action="reconnect">Reconnect</button>
                        <button type="button" class="session-overlay-btn" data-action="login">Go to Login</button>
                    </div>
                </div>
            `;

            document.body.appendChild(overlay);

            this.overlay = overlay;
            this.overlayMessage = overlay.querySelector('.session-overlay-message');
            this.overlaySpinner = overlay.querySelector('.session-overlay-spinner');
            this.overlayActions = overlay.querySelector('.session-overlay-actions');
            this.reconnectBtn = overlay.querySelector('[data-action="reconnect"]');
            this.loginBtn = overlay.querySelector('[data-action="login"]');

            this.reconnectBtn.addEventListener('click', () => {
                if (this.state === 'expired') {
                    this.state = 'checking';
                    this.showOverlay('Trying to reconnect…', {
                        showSpinner: true,
                        showButtons: false
                    });
                    this.requestSessionStatus();
                }
            });

            this.loginBtn.addEventListener('click', () => {
                window.location.href = defaults.loginUrl + '?timeout=1';
            });
        },

        showOverlay(message, options = {}) {
            if (!this.overlay) {
                this.ensureOverlay();
            }

            const { showSpinner = false, showButtons = false } = options;

            this.overlay.classList.add('active');
            this.overlayMessage.textContent = message;

            if (showSpinner) {
                this.overlaySpinner.removeAttribute('hidden');
            } else {
                this.overlaySpinner.setAttribute('hidden', 'hidden');
            }

            if (showButtons) {
                this.overlayActions.removeAttribute('hidden');
            } else {
                this.overlayActions.setAttribute('hidden', 'hidden');
            }
        },

        hideOverlay() {
            if (!this.overlay) {
                return;
            }
            this.overlay.classList.remove('active');
            this.overlayMessage.textContent = '';
            this.overlaySpinner.setAttribute('hidden', 'hidden');
            this.overlayActions.setAttribute('hidden', 'hidden');
        }
    };

    SessionManager.init();
    window.SessionManager = SessionManager;
})(window, document);


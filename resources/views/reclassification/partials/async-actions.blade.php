<div id="async-action-indicator"
     class="hidden fixed bottom-4 left-4 z-[80] rounded-xl px-4 py-2 text-sm text-white shadow-xl">
    <span id="async-action-spinner"
          class="hidden mr-2 inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white border-t-transparent align-[-2px]"></span>
    <span id="async-action-message"></span>
</div>

<script>
    (() => {
        const indicator = document.getElementById('async-action-indicator');
        const indicatorMessage = document.getElementById('async-action-message');
        const indicatorSpinner = document.getElementById('async-action-spinner');
        let hideTimer = null;

        if (!indicator || !indicatorMessage || !indicatorSpinner) return;

        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const isTruthyFlag = (value) => ['1', 'true', 'yes', 'on', 'modal'].includes(String(value || '').toLowerCase());
        let confirmModalRefs = null;

        const ensureConfirmModal = () => {
            if (confirmModalRefs) return confirmModalRefs;

            const modal = document.createElement('div');
            modal.id = 'async-confirm-modal';
            modal.className = 'hidden fixed inset-0 z-[120]';
            modal.innerHTML = `
                <div data-confirm-overlay class="absolute inset-0 bg-black/40"></div>
                <div class="relative z-[121] flex min-h-full items-center justify-center p-4">
                    <div class="w-full max-w-lg rounded-2xl border border-gray-200 bg-white shadow-xl">
                        <div class="border-b border-gray-200 px-6 py-4">
                            <h3 data-confirm-title class="text-lg font-semibold text-gray-900">Please confirm</h3>
                        </div>
                        <div class="px-6 py-4">
                            <p data-confirm-message class="text-sm text-gray-700 leading-6"></p>
                        </div>
                        <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-4">
                            <button type="button" data-confirm-cancel class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="button" data-confirm-accept class="rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-100">
                                Confirm
                            </button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            confirmModalRefs = {
                modal,
                overlay: modal.querySelector('[data-confirm-overlay]'),
                title: modal.querySelector('[data-confirm-title]'),
                message: modal.querySelector('[data-confirm-message]'),
                cancel: modal.querySelector('[data-confirm-cancel]'),
                accept: modal.querySelector('[data-confirm-accept]'),
            };

            return confirmModalRefs;
        };

        const openConfirmModal = ({
            title = 'Please confirm',
            message = '',
            confirmText = 'Confirm',
            cancelText = 'Cancel',
            destructive = false,
        } = {}) => {
            const refs = ensureConfirmModal();
            refs.title.textContent = title;
            refs.message.textContent = message;
            refs.cancel.textContent = cancelText;
            refs.accept.textContent = confirmText;

            refs.accept.classList.remove(
                'border-red-200', 'bg-red-50', 'text-red-700', 'hover:bg-red-100',
                'border-bu', 'bg-bu', 'text-white', 'hover:bg-bu-dark'
            );
            if (destructive) {
                refs.accept.classList.add('border-red-200', 'bg-red-50', 'text-red-700', 'hover:bg-red-100');
            } else {
                refs.accept.classList.add('border-bu', 'bg-bu', 'text-white', 'hover:bg-bu-dark');
            }

            refs.modal.classList.remove('hidden');

            return new Promise((resolve) => {
                let resolved = false;
                const close = (result) => {
                    if (resolved) return;
                    resolved = true;
                    refs.modal.classList.add('hidden');
                    refs.cancel.removeEventListener('click', onCancel);
                    refs.accept.removeEventListener('click', onAccept);
                    refs.overlay.removeEventListener('click', onOverlay);
                    document.removeEventListener('keydown', onKeydown);
                    resolve(result);
                };
                const onCancel = () => close(false);
                const onAccept = () => close(true);
                const onOverlay = (event) => {
                    if (event.target === refs.overlay) close(false);
                };
                const onKeydown = (event) => {
                    if (event.key === 'Escape') close(false);
                };

                refs.cancel.addEventListener('click', onCancel);
                refs.accept.addEventListener('click', onAccept);
                refs.overlay.addEventListener('click', onOverlay);
                document.addEventListener('keydown', onKeydown);

                window.requestAnimationFrame(() => refs.accept.focus());
            });
        };

        const setIndicator = (message, tone = 'info', loading = false) => {
            const toneClass = {
                success: 'bg-green-600',
                error: 'bg-red-600',
                loading: 'bg-gray-800',
                info: 'bg-slate-700',
            }[tone] || 'bg-slate-700';

            indicator.classList.remove('hidden', 'bg-green-600', 'bg-red-600', 'bg-gray-800', 'bg-slate-700');
            indicator.classList.add(toneClass);
            indicatorMessage.textContent = message || '';
            indicatorSpinner.classList.toggle('hidden', !loading);

            if (hideTimer) {
                clearTimeout(hideTimer);
                hideTimer = null;
            }

            if (!loading) {
                hideTimer = setTimeout(() => indicator.classList.add('hidden'), 2600);
            }
        };

        const lockForm = (form, locked) => {
            const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            submitButtons.forEach((button) => {
                button.disabled = locked;
                button.classList.toggle('opacity-60', locked);
                button.classList.toggle('cursor-not-allowed', locked);
            });
        };

        const parseStateKeys = (target) => {
            const raw = (target?.dataset?.asyncStateKeys || '').trim();
            if (!raw) return [];
            return raw
                .split(',')
                .map((key) => key.trim())
                .filter(Boolean);
        };

        const captureState = (target) => {
            if (!(target instanceof HTMLElement)) return { scrollTop: 0, data: {} };
            const keys = parseStateKeys(target);
            const data = {};
            const alpineData = target.__x?.$data || null;
            keys.forEach((key) => {
                if (alpineData && Object.prototype.hasOwnProperty.call(alpineData, key)) {
                    data[key] = alpineData[key];
                }
            });
            return {
                scrollTop: target.scrollTop || 0,
                data,
            };
        };

        const restoreState = (target, snapshot) => {
            if (!(target instanceof HTMLElement) || !snapshot) return;
            const alpineData = target.__x?.$data || null;
            if (alpineData && snapshot.data && typeof snapshot.data === 'object') {
                Object.entries(snapshot.data).forEach(([key, value]) => {
                    if (Object.prototype.hasOwnProperty.call(alpineData, key)) {
                        alpineData[key] = value;
                    }
                });
            }
            target.scrollTop = Number(snapshot.scrollTop || 0);
        };

        const setTargetLoading = (target, isLoading) => {
            if (!(target instanceof HTMLElement)) return;
            // Avoid full-page flicker on large containers (feels like hard reload).
            if (target.id === 'reviewer-content' || target.id === 'faculty-reclassification-root') {
                return;
            }
            if (target.hasAttribute('data-ux-panel') && window.BuUx?.panel) {
                window.BuUx.panel.setLoading(target, isLoading, 'Updating...');
                return;
            }
            target.classList.toggle('ux-loading-fade', isLoading);
        };

        const refreshTargets = async (selectors, options = {}) => {
            const uniqueSelectors = [...new Set((selectors || []).map((item) => (item || '').trim()).filter(Boolean))];
            if (!uniqueSelectors.length) return;

            const keepScroll = options.keepScroll !== false;
            const windowScrollY = window.scrollY;
            const snapshots = new Map();
            uniqueSelectors.forEach((selector) => {
                const current = document.querySelector(selector);
                if (!current) return;
                snapshots.set(selector, captureState(current));
                setTargetLoading(current, true);
            });

            const response = await fetch(window.location.href, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-UX-Background': '1',
                },
            });

            if (!response.ok) {
                snapshots.forEach((_, selector) => {
                    const current = document.querySelector(selector);
                    if (current) setTargetLoading(current, false);
                });
                throw new Error(`Refresh failed (HTTP ${response.status}).`);
            }

            const html = await response.text();
            const parsed = new DOMParser().parseFromString(html, 'text/html');

            uniqueSelectors.forEach((selector) => {
                const current = document.querySelector(selector);
                const incoming = parsed.querySelector(selector);
                if (current && incoming) {
                    current.replaceWith(incoming);
                    if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                        window.Alpine.initTree(incoming);
                    }
                    setTargetLoading(incoming, false);
                    return;
                }

                if (current && !incoming) {
                    current.remove();
                }
            });

            await new Promise((resolve) => window.requestAnimationFrame(resolve));
            await new Promise((resolve) => window.requestAnimationFrame(resolve));

            uniqueSelectors.forEach((selector) => {
                const incoming = document.querySelector(selector);
                const snapshot = snapshots.get(selector);
                if (incoming && snapshot) {
                    restoreState(incoming, snapshot);
                    setTargetLoading(incoming, false);
                }
            });

            if (keepScroll) {
                window.scrollTo({ top: windowScrollY, left: window.scrollX, behavior: 'auto' });
            }
        };

        const getErrorMessage = (payload, status) => {
            if (payload?.message) return payload.message;
            if (payload?.errors && typeof payload.errors === 'object') {
                const first = Object.values(payload.errors).flat()[0];
                if (first) return first;
            }
            if (status === 419) return 'Session expired. Refresh and try again.';
            return `Request failed (HTTP ${status}).`;
        };

        const bindAsyncForms = (scope = document) => {
            scope.querySelectorAll('form[data-async-action]').forEach((form) => {
                if (form.dataset.asyncBound === '1') return;
                form.dataset.asyncBound = '1';

                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const submitter = event.submitter instanceof HTMLElement ? event.submitter : null;

                    const confirmText = form.dataset.confirm || '';
                    if (confirmText) {
                        let confirmed = false;
                        if (isTruthyFlag(form.dataset.confirmModal)) {
                            confirmed = await openConfirmModal({
                                title: form.dataset.confirmTitle || 'Please confirm',
                                message: confirmText,
                                confirmText: form.dataset.confirmConfirmText || 'Confirm',
                                cancelText: form.dataset.confirmCancelText || 'Cancel',
                                destructive: isTruthyFlag(form.dataset.confirmDestructive),
                            });
                        } else {
                            confirmed = window.confirm(confirmText);
                        }
                        if (!confirmed) return;
                    }

                    if (form.dataset.asyncBusy === '1') {
                        setIndicator('Please wait. Request is still processing.', 'info', false);
                        return;
                    }

                    const now = Date.now();
                    const last = Number(form.dataset.lastSubmitAt || 0);
                    if (last && (now - last) < 700) {
                        setIndicator('You are clicking too fast. Please wait a moment.', 'info', false);
                        return;
                    }

                    form.dataset.lastSubmitAt = String(now);
                    form.dataset.asyncBusy = '1';
                    lockForm(form, true);
                    if (submitter) {
                        window.BuUx?.setActionButtonLoading?.(
                            submitter,
                            form.dataset.loadingText || submitter.dataset.loadingText || 'Processing...'
                        );
                    }
                    setIndicator(form.dataset.loadingMessage || 'Saving changes...', 'loading', true);

                    try {
                        const response = await fetch(form.action, {
                            method: (form.method || 'POST').toUpperCase(),
                            credentials: 'same-origin',
                            headers: {
                                'X-CSRF-TOKEN': csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'X-UX-Background': '1',
                            },
                            body: new FormData(form),
                        });

                        let payload = {};
                        try {
                            payload = await response.json();
                        } catch (error) {
                            payload = {};
                        }

                        if (!response.ok) {
                            throw new Error(getErrorMessage(payload, response.status));
                        }

                        const refreshTargetsRaw = form.dataset.asyncRefreshTarget || '';
                        const targets = refreshTargetsRaw.split(',').map((item) => item.trim()).filter(Boolean);
                        await refreshTargets(targets, {
                            keepScroll: form.dataset.asyncKeepScroll !== 'false',
                        });
                        bindAsyncForms(document);
                        window.BuUx?.bindActionLoading?.(document);

                        const successMessage = payload?.message || form.dataset.successMessage || 'Saved.';
                        setIndicator(successMessage, 'success', false);
                        window.BuUx?.toast?.(successMessage, 'success', 2200);
                    } catch (error) {
                        const message = error?.message || 'Unable to save changes.';
                        setIndicator(message, 'error', false);
                        window.BuUx?.toast?.(message, 'error', 3200);
                    } finally {
                        form.dataset.asyncBusy = '0';
                        lockForm(form, false);
                        if (submitter) {
                            window.BuUx?.resetActionButton?.(submitter);
                        }
                    }
                });
            });
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => bindAsyncForms(document));
        } else {
            bindAsyncForms(document);
        }

        window.AsyncActions = window.AsyncActions || {};
        window.AsyncActions.refreshTargets = refreshTargets;
        window.AsyncActions.setIndicator = setIndicator;
        window.AsyncActions.bindAsyncForms = bindAsyncForms;
    })();
</script>

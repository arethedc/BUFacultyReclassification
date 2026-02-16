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
                if (!button.dataset.originalText) {
                    button.dataset.originalText = button.tagName === 'INPUT'
                        ? (button.value || '')
                        : (button.textContent || '');
                }
                button.disabled = locked;
                button.classList.toggle('opacity-60', locked);
                button.classList.toggle('cursor-not-allowed', locked);

                const loadingText = form.dataset.loadingText || 'Saving...';
                if (button.tagName === 'INPUT') {
                    button.value = locked ? loadingText : (button.dataset.originalText || button.value);
                } else {
                    button.textContent = locked ? loadingText : (button.dataset.originalText || button.textContent);
                }
            });
        };

        const refreshTargets = async (selectors) => {
            const uniqueSelectors = [...new Set((selectors || []).map((item) => (item || '').trim()).filter(Boolean))];
            if (!uniqueSelectors.length) return;

            const response = await fetch(window.location.href, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
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
                }
            });
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

                    const confirmText = form.dataset.confirm || '';
                    if (confirmText && !window.confirm(confirmText)) {
                        return;
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
                    setIndicator(form.dataset.loadingMessage || 'Saving changes...', 'loading', true);

                    try {
                        const response = await fetch(form.action, {
                            method: (form.method || 'POST').toUpperCase(),
                            credentials: 'same-origin',
                            headers: {
                                'X-CSRF-TOKEN': csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
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
                        await refreshTargets(targets);
                        bindAsyncForms(document);

                        setIndicator(
                            payload?.message || form.dataset.successMessage || 'Saved.',
                            'success',
                            false
                        );
                    } catch (error) {
                        setIndicator(error?.message || 'Unable to save changes.', 'error', false);
                    } finally {
                        form.dataset.asyncBusy = '0';
                        lockForm(form, false);
                    }
                });
            });
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => bindAsyncForms(document));
        } else {
            bindAsyncForms(document);
        }
    })();
</script>

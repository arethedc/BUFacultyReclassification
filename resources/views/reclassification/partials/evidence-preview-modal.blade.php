<div id="evidence-preview-modal"
     class="fixed inset-0 z-[120] hidden items-center justify-center p-4"
     aria-hidden="true">
    <div class="absolute inset-0 bg-black/40" data-evidence-preview-close></div>

    <div class="relative w-full max-w-4xl max-h-[90vh] rounded-2xl border border-gray-200 bg-white shadow-xl flex flex-col overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between gap-3">
            <h3 id="evidence-preview-title"
                class="text-lg font-semibold text-gray-800 flex-1 min-w-0 truncate">
                Evidence View
            </h3>
            <div id="evidence-preview-counter"
                 class="hidden text-xs text-gray-500 shrink-0">
                1 / 1
            </div>
            <button type="button"
                    data-evidence-preview-close
                    class="text-gray-500 hover:text-gray-700 text-sm font-medium">
                Close
            </button>
        </div>

        <div id="evidence-preview-entry-context"
             class="hidden border-b border-gray-200 bg-gray-50 px-6 py-3">
            <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Entry Details</div>
            <div class="mt-1 space-y-1 text-sm text-gray-700">
                <div id="evidence-preview-entry-section" class="hidden"></div>
                <div id="evidence-preview-entry-criterion" class="hidden"></div>
                <div id="evidence-preview-entry-details" class="hidden space-y-1"></div>
                <div id="evidence-preview-entry-points" class="hidden"></div>
            </div>
        </div>

        <div class="min-h-0 flex-1 bg-gray-50 p-6 overflow-auto">
            <img id="evidence-preview-image"
                 alt="Evidence preview"
                 class="hidden max-h-[70vh] mx-auto rounded-lg border border-gray-200 bg-white object-contain" />

            <iframe id="evidence-preview-frame"
                    class="hidden w-full h-[70vh] rounded-lg border border-gray-200 bg-white"
                    title="Evidence preview"></iframe>

            <div id="evidence-preview-fallback"
                 class="hidden h-[70vh] w-full items-center justify-center rounded-xl border border-dashed border-gray-300 bg-white p-6 text-center">
                <div>
                    <div class="text-sm font-semibold text-gray-800">Preview is not available for this file type.</div>
                    <div class="mt-1 text-xs text-gray-500">Use Open in New Tab to review the file.</div>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-gray-200 flex items-center gap-3">
            <p class="text-xs text-gray-500 min-w-0" id="evidence-preview-nav-hint">Use Previous/Next to browse evidence files.</p>
            <div class="ml-auto flex items-center gap-2">
                <button type="button"
                        id="evidence-preview-prev"
                        class="hidden inline-flex items-center rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Previous
                </button>
                <button type="button"
                        id="evidence-preview-next"
                        class="hidden inline-flex items-center rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Next
                </button>
                <a id="evidence-preview-open-tab"
                   href="#"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Open in New Tab
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    (() => {
        if (window.BuEvidencePreview) return;

        const modal = document.getElementById('evidence-preview-modal');
        if (!modal) return;

        const titleEl = document.getElementById('evidence-preview-title');
        const counterEl = document.getElementById('evidence-preview-counter');
        const navHintEl = document.getElementById('evidence-preview-nav-hint');
        const entryContextEl = document.getElementById('evidence-preview-entry-context');
        const entrySectionEl = document.getElementById('evidence-preview-entry-section');
        const entryCriterionEl = document.getElementById('evidence-preview-entry-criterion');
        const entryDetailsEl = document.getElementById('evidence-preview-entry-details');
        const entryPointsEl = document.getElementById('evidence-preview-entry-points');
        const openTabEl = document.getElementById('evidence-preview-open-tab');
        const prevEl = document.getElementById('evidence-preview-prev');
        const nextEl = document.getElementById('evidence-preview-next');
        const imageEl = document.getElementById('evidence-preview-image');
        const frameEl = document.getElementById('evidence-preview-frame');
        const fallbackEl = document.getElementById('evidence-preview-fallback');

        let galleryItems = [];
        let galleryIndex = 0;
        let currentContext = null;

        const normalizeItem = (item = {}) => ({
            url: String(item.url || '').trim(),
            name: String(item.name || 'Evidence View'),
            mime: String(item.mime || ''),
        });

        const hideAll = () => {
            imageEl.classList.add('hidden');
            imageEl.removeAttribute('src');
            frameEl.classList.add('hidden');
            frameEl.removeAttribute('src');
            fallbackEl.classList.add('hidden');
            fallbackEl.classList.remove('flex');
        };

        const clearEntryContext = () => {
            if (!entryContextEl) return;
            entryContextEl.classList.add('hidden');
            if (entrySectionEl) {
                entrySectionEl.classList.add('hidden');
                entrySectionEl.textContent = '';
            }
            if (entryCriterionEl) {
                entryCriterionEl.classList.add('hidden');
                entryCriterionEl.textContent = '';
            }
            if (entryDetailsEl) {
                entryDetailsEl.classList.add('hidden');
                entryDetailsEl.innerHTML = '';
            }
            if (entryPointsEl) {
                entryPointsEl.classList.add('hidden');
                entryPointsEl.textContent = '';
            }
        };

        const setEntryContext = (context = null) => {
            currentContext = context;
            if (!entryContextEl) return;

            const section = String(context?.section || '').trim();
            const criterion = String(context?.criterion || '').trim();
            const fieldLabel = String(context?.field_label || '').trim();
            const fieldValue = String(context?.field_value || '').trim();
            const details = Array.isArray(context?.details) ? context.details : [];
            const points = String(context?.points || '').trim();

            const hasAny = section || criterion || (fieldLabel && fieldValue) || details.length > 0 || points;
            if (!hasAny) {
                clearEntryContext();
                return;
            }

            entryContextEl.classList.remove('hidden');

            if (entrySectionEl) {
                if (section) {
                    entrySectionEl.classList.remove('hidden');
                    entrySectionEl.textContent = section;
                } else {
                    entrySectionEl.classList.add('hidden');
                    entrySectionEl.textContent = '';
                }
            }

            if (entryCriterionEl) {
                if (criterion) {
                    entryCriterionEl.classList.remove('hidden');
                    entryCriterionEl.textContent = criterion;
                } else {
                    entryCriterionEl.classList.add('hidden');
                    entryCriterionEl.textContent = '';
                }
            }

            if (entryDetailsEl) {
                const normalizedDetails = details
                    .map((item) => ({
                        label: String(item?.label || '').trim(),
                        value: String(item?.value || '').trim(),
                    }))
                    .filter((item) => item.label !== '' && item.value !== '');

                if (normalizedDetails.length > 0) {
                    entryDetailsEl.classList.remove('hidden');
                    entryDetailsEl.innerHTML = '';
                    normalizedDetails.forEach((item) => {
                        const line = document.createElement('div');
                        const labelSpan = document.createElement('span');
                        labelSpan.className = 'text-gray-400';
                        labelSpan.textContent = `${item.label}:`;
                        const valueSpan = document.createElement('span');
                        valueSpan.className = 'text-gray-700';
                        valueSpan.textContent = ` ${item.value}`;
                        line.appendChild(labelSpan);
                        line.appendChild(valueSpan);
                        entryDetailsEl.appendChild(line);
                    });
                } else if (fieldLabel && fieldValue) {
                    entryDetailsEl.classList.remove('hidden');
                    entryDetailsEl.innerHTML = '';
                    const line = document.createElement('div');
                    const labelSpan = document.createElement('span');
                    labelSpan.className = 'text-gray-400';
                    labelSpan.textContent = `${fieldLabel}:`;
                    const valueSpan = document.createElement('span');
                    valueSpan.className = 'text-gray-700';
                    valueSpan.textContent = ` ${fieldValue}`;
                    line.appendChild(labelSpan);
                    line.appendChild(valueSpan);
                    entryDetailsEl.appendChild(line);
                } else {
                    entryDetailsEl.classList.add('hidden');
                    entryDetailsEl.innerHTML = '';
                }
            }

            if (entryPointsEl) {
                if (points) {
                    entryPointsEl.classList.remove('hidden');
                    entryPointsEl.textContent = `Points: ${points}`;
                } else {
                    entryPointsEl.classList.add('hidden');
                    entryPointsEl.textContent = '';
                }
            }
        };

        const setNavState = () => {
            const count = galleryItems.length;
            const hasMultiple = count > 1;

            if (counterEl) {
                counterEl.classList.toggle('hidden', !hasMultiple);
                if (hasMultiple) {
                    counterEl.textContent = `${galleryIndex + 1} / ${count}`;
                }
            }

            if (navHintEl) {
                navHintEl.classList.toggle('hidden', !hasMultiple);
            }

            if (prevEl) {
                prevEl.classList.toggle('hidden', !hasMultiple);
            }
            if (nextEl) {
                nextEl.classList.toggle('hidden', !hasMultiple);
            }
        };

        const setOpenTabState = (url) => {
            const link = String(url || '').trim();
            if (!link || link === '#') {
                openTabEl.href = '#';
                openTabEl.classList.add('opacity-50', 'pointer-events-none');
                return;
            }

            openTabEl.href = link;
            openTabEl.classList.remove('opacity-50', 'pointer-events-none');
        };

        const renderCurrent = () => {
            const current = galleryItems[galleryIndex];
            if (!current) return;

            const previewUrl = String(current.url || '').trim();
            const fileName = String(current.name || 'Evidence View');
            const mimeType = String(current.mime || '').toLowerCase();
            const lowerName = fileName.toLowerCase();
            const isImage = mimeType.startsWith('image/')
                || /\.(jpg|jpeg|png|gif|webp|bmp|svg|tif|tiff|heic|heif)$/i.test(lowerName);
            const isPdf = mimeType === 'application/pdf' || lowerName.endsWith('.pdf');

            hideAll();
            titleEl.textContent = fileName;
            setOpenTabState(previewUrl);

            if (previewUrl && isImage) {
                imageEl.src = previewUrl;
                imageEl.classList.remove('hidden');
            } else if (previewUrl && isPdf) {
                frameEl.src = previewUrl;
                frameEl.classList.remove('hidden');
            } else {
                fallbackEl.classList.remove('hidden');
                fallbackEl.classList.add('flex');
            }

            setNavState();
        };

        const close = () => {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('overflow-y-hidden');
            hideAll();
            galleryItems = [];
            galleryIndex = 0;
            currentContext = null;
            setOpenTabState('');
            setNavState();
            clearEntryContext();
        };

        const open = ({ url, name, mime, items, index, context } = {}) => {
            const parsedItems = Array.isArray(items)
                ? items.map((item) => normalizeItem(item)).filter((item) => !!item.url)
                : [];

            if (parsedItems.length > 0) {
                galleryItems = parsedItems;
                const desired = Number(index);
                galleryIndex = Number.isFinite(desired)
                    ? Math.max(0, Math.min(parsedItems.length - 1, Math.floor(desired)))
                    : 0;
            } else {
                const single = normalizeItem({ url, name, mime });
                if (!single.url) return;
                galleryItems = [single];
                galleryIndex = 0;
            }

            setEntryContext(context || null);
            renderCurrent();
            modal.classList.add('flex');
            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-y-hidden');
        };

        const prev = () => {
            if (!Array.isArray(galleryItems) || galleryItems.length < 2) return;
            galleryIndex = galleryIndex <= 0 ? galleryItems.length - 1 : galleryIndex - 1;
            renderCurrent();
        };

        const next = () => {
            if (!Array.isArray(galleryItems) || galleryItems.length < 2) return;
            galleryIndex = galleryIndex >= galleryItems.length - 1 ? 0 : galleryIndex + 1;
            renderCurrent();
        };

        modal.querySelectorAll('[data-evidence-preview-close]').forEach((el) => {
            el.addEventListener('click', close);
        });

        if (prevEl) prevEl.addEventListener('click', prev);
        if (nextEl) nextEl.addEventListener('click', next);

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('.js-evidence-preview-trigger');
            if (!trigger) return;
            event.preventDefault();

            let parsedItems = null;
            const itemsRaw = trigger.getAttribute('data-evidence-items');
            if (itemsRaw) {
                try {
                    parsedItems = JSON.parse(itemsRaw);
                } catch (_) {
                    parsedItems = null;
                }
            }
            let parsedContext = null;
            const contextRaw = trigger.getAttribute('data-entry-context');
            if (contextRaw) {
                try {
                    parsedContext = JSON.parse(contextRaw);
                } catch (_) {
                    parsedContext = null;
                }
            }

            const rawIndex = Number(trigger.getAttribute('data-evidence-index'));
            open({
                url: trigger.getAttribute('data-evidence-url') || '',
                name: trigger.getAttribute('data-evidence-name') || 'Evidence View',
                mime: trigger.getAttribute('data-evidence-mime') || '',
                items: parsedItems,
                index: Number.isFinite(rawIndex) ? rawIndex : 0,
                context: parsedContext,
            });
        });

        document.addEventListener('keydown', (event) => {
            if (modal.classList.contains('hidden')) return;
            if (event.key === 'Escape') {
                close();
                return;
            }
            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                prev();
                return;
            }
            if (event.key === 'ArrowRight') {
                event.preventDefault();
                next();
            }
        });

        window.BuEvidencePreview = { open, close, next, prev };
    })();
</script>

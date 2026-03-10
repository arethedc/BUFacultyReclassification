<div id="evidence-preview-modal"
     class="fixed inset-0 z-[120] hidden"
     aria-hidden="true">
    <div class="absolute inset-0 bg-black/60" data-evidence-preview-close></div>

    <div class="relative mx-auto my-4 h-[calc(100vh-2rem)] w-[min(96vw,1100px)] rounded-2xl border border-gray-200 bg-white shadow-2xl flex flex-col">
        <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
            <div class="min-w-0">
                <div class="truncate text-sm font-semibold text-gray-800" id="evidence-preview-title">Evidence Preview</div>
                <div class="text-xs text-gray-500">Preview file without leaving this page.</div>
            </div>
            <div class="flex items-center gap-2">
                <a id="evidence-preview-open-tab"
                   href="#"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                    Open in New Tab
                </a>
                <button type="button"
                        data-evidence-preview-close
                        class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                    Close
                </button>
            </div>
        </div>

        <div class="min-h-0 flex-1 bg-gray-50 p-3">
            <img id="evidence-preview-image"
                 alt="Evidence preview"
                 class="hidden h-full w-full rounded-xl border border-gray-200 bg-white object-contain" />

            <iframe id="evidence-preview-frame"
                    class="hidden h-full w-full rounded-xl border border-gray-200 bg-white"
                    title="Evidence preview"></iframe>

            <div id="evidence-preview-fallback"
                 class="hidden h-full w-full items-center justify-center rounded-xl border border-dashed border-gray-300 bg-white p-6 text-center">
                <div>
                    <div class="text-sm font-semibold text-gray-800">Preview is not available for this file type.</div>
                    <div class="mt-1 text-xs text-gray-500">Use Open in New Tab to review the file.</div>
                </div>
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
        const openTabEl = document.getElementById('evidence-preview-open-tab');
        const imageEl = document.getElementById('evidence-preview-image');
        const frameEl = document.getElementById('evidence-preview-frame');
        const fallbackEl = document.getElementById('evidence-preview-fallback');

        const hideAll = () => {
            imageEl.classList.add('hidden');
            imageEl.removeAttribute('src');
            frameEl.classList.add('hidden');
            frameEl.removeAttribute('src');
            fallbackEl.classList.add('hidden');
            fallbackEl.classList.remove('flex');
        };

        const close = () => {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('overflow-y-hidden');
            hideAll();
        };

        const open = ({ url, name, mime } = {}) => {
            const previewUrl = String(url || '').trim();
            if (!previewUrl) return;

            const fileName = String(name || 'Evidence Preview');
            const mimeType = String(mime || '').toLowerCase();
            const lowerName = fileName.toLowerCase();
            const isImage = mimeType.startsWith('image/')
                || /\.(jpg|jpeg|png|gif|webp|bmp|svg|tif|tiff|heic|heif)$/i.test(lowerName);
            const isPdf = mimeType === 'application/pdf' || lowerName.endsWith('.pdf');

            hideAll();
            titleEl.textContent = fileName;
            openTabEl.href = previewUrl;

            if (isImage) {
                imageEl.src = previewUrl;
                imageEl.classList.remove('hidden');
            } else if (isPdf) {
                frameEl.src = previewUrl;
                frameEl.classList.remove('hidden');
            } else {
                fallbackEl.classList.remove('hidden');
                fallbackEl.classList.add('flex');
            }

            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-y-hidden');
        };

        modal.querySelectorAll('[data-evidence-preview-close]').forEach((el) => {
            el.addEventListener('click', close);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                close();
            }
        });

        window.BuEvidencePreview = { open, close };
    })();
</script>


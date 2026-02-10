<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- ✅ AlpineJS (load ONCE globally) -->
        <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
        <script defer src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
        <style>[x-cloak]{display:none !important;}</style>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        <script>
            function isFieldFilled(el) {
                if (!el || el.disabled || el.type === 'hidden') return false;
                if (el.type === 'checkbox' || el.type === 'radio') return el.checked;
                if (el.tagName === 'SELECT') {
                    if (el.multiple) return el.selectedOptions.length > 0;
                    return el.value !== '';
                }
                return (el.value || '').trim() !== '';
            }

            function validateFormRows(form) {
                if (!form || form.dataset.viewOnly === 'true') return true;
                const evidenceSelects = form.querySelectorAll('select[name*="[evidence]"]');
                let invalid = false;
                let firstInvalid = null;

                evidenceSelects.forEach((select) => {
                    const row = select.closest('tr')
                        || select.closest('[data-evidence-row]')
                        || select.closest('.evidence-row')
                        || select.closest('div')
                        || form;

                    const fields = Array.from(row.querySelectorAll('input,select,textarea'))
                        .filter(el => el.type !== 'hidden' && !el.disabled);

                    const nonEvidenceFields = fields.filter(el => el !== select);
                    const proxy = row.querySelector('[data-evidence-proxy]');

                    const hiddenEvidenceInputs = row.querySelectorAll('input[type="hidden"][name*="[evidence]"]');
                    const hasHiddenEvidence = hiddenEvidenceInputs.length > 0;
                    const hasSelectEvidence = isFieldFilled(select);
                    const started = nonEvidenceFields.some(isFieldFilled) || hasSelectEvidence || hasHiddenEvidence;
                    if (!started) {
                        select.classList.remove('border-red-500');
                        nonEvidenceFields.forEach(el => el.classList.remove('border-red-500'));
                        if (proxy) proxy.classList.remove('ring-1', 'ring-red-500');
                        return;
                    }

                    let rowInvalid = false;

                    // Require evidence when row is started
                    if (!hasSelectEvidence && !hasHiddenEvidence) {
                        rowInvalid = true;
                        select.classList.add('border-red-500');
                        if (proxy) proxy.classList.add('ring-1', 'ring-red-500', 'rounded-lg');
                    } else {
                        select.classList.remove('border-red-500');
                        if (proxy) proxy.classList.remove('ring-1', 'ring-red-500');
                    }

                    // Require other fields to be filled when row is started
                    nonEvidenceFields.forEach((el) => {
                        if (!isFieldFilled(el)) {
                            rowInvalid = true;
                            el.classList.add('border-red-500');
                        } else {
                            el.classList.remove('border-red-500');
                        }
                    });

                    if (rowInvalid) {
                        invalid = true;
                        if (!firstInvalid) firstInvalid = select;
                    }
                });

                if (invalid) {
                    if (firstInvalid) {
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstInvalid.focus({ preventScroll: true });
                    }
                    alert('Please complete all required fields and evidence for each started row before continuing.');
                }

                return !invalid;
            }

            window.validateFormRows = validateFormRows;

            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('form[data-validate-evidence]').forEach((form) => {
                    form.addEventListener('submit', (event) => {
                        const submitter = event.submitter || document.activeElement;
                        if (submitter) {
                            const skip = submitter.getAttribute('data-skip-validate') === 'true';
                            const isDraft = submitter.name === 'action' && submitter.value === 'draft';
                            if (skip || isDraft) return;
                        }
                        if (!validateFormRows(form)) {
                            event.preventDefault();
                        }
                    });
                });

                document.addEventListener('click', (event) => {
                    if (event.defaultPrevented) return;
                    const navLink = event.target.closest('a[data-section-nav]');
                    if (!navLink) return;

                    const activePane = document.querySelector('[data-section-pane].is-active');
                    const form = activePane ? activePane.querySelector('form[data-validate-evidence]') : null;
                    if (!form) return;

                    if (!validateFormRows(form)) {
                        event.preventDefault();
                    }
                });
            });
        </script>
    </body>
</html>

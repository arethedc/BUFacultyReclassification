<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800">Faculty Reclassification</h2>
                <p class="text-sm text-gray-500">
                    Review and complete your reclassification requirements.
                </p>
            </div>

            {{-- Status Chip --}}
            @php
                $status = $application->status ?? 'draft';
                $statusLabel = match($status) {
                    'draft' => 'Draft',
                    'returned_to_faculty' => 'Returned',
                    'dean_review' => 'Dean Review',
                    'hr_review' => 'HR Review',
                    'vpaa_review' => 'VPAA Review',
                    'president_review' => 'President Review',
                    'finalized' => 'Finalized',
                    default => ucfirst(str_replace('_',' ', $status)),
                };

                $statusClass = match($status) {
                    'draft' => 'bg-gray-100 text-gray-700 border-gray-200',
                    'returned_to_faculty' => 'bg-amber-50 text-amber-700 border-amber-200',
                    'finalized' => 'bg-green-50 text-green-700 border-green-200',
                    default => 'bg-blue-50 text-blue-700 border-blue-200',
                };

                $canEdit = in_array($status, ['draft', 'returned_to_faculty'], true);
            @endphp

            <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border {{ $statusClass }}">
                    {{ $statusLabel }}
                </span>

                {{-- Optional: Submit (works with your route if you already have it) --}}
                @if($canEdit)
                    <form method="POST" action="{{ route('reclassification.submit', $application->id) }}">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 rounded-xl bg-bu text-white text-sm font-semibold
                                   hover:bg-bu-dark shadow-soft focus:ring-2 focus:ring-bu focus:ring-offset-2 transition">
                            Submit
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    @php
        // default active section: 1
        $active = (int) request()->route('number', 1);
        if ($active < 1 || $active > 5) $active = 1;
    @endphp

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
         x-data="reclassificationWizard()"
         x-init="init()">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            {{-- LEFT SIDEBAR (Desktop) --}}
            <aside class="hidden lg:block lg:col-span-3">
                <div class="bg-white border border-gray-200 rounded-2xl shadow-card overflow-hidden">
                    <div class="px-5 py-4 border-b">
                        <h3 class="text-sm font-semibold text-gray-800">Sections</h3>
                        <p class="text-xs text-gray-500">Navigate the form.</p>
                    </div>

                    <div class="p-3 space-y-2">
                        @for($i = 1; $i <= 5; $i++)
                            @php
                                $isLocked = $i === 2; // Section II view-only (Dean)
                            @endphp

                            <a href="{{ route('reclassification.section', $i) }}" data-section-nav
                               @click.prevent="navTo({{ $i }})"
                               class="flex items-center justify-between px-4 py-3 rounded-xl border transition"
                               :class="active === {{ $i }} ? 'border-bu bg-bu/5' : 'border-gray-200 hover:bg-gray-50'">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-xl flex items-center justify-center"
                                        :class="active === {{ $i }} ? 'bg-bu text-white' : 'bg-gray-100 text-gray-700'">
                                        {{ $i }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-gray-800">
                                            Section {{ $i }}
                                            @if($isLocked)
                                                <span class="ml-2 text-[11px] font-semibold text-gray-500">(View-only)</span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $i === 2 ? 'Dean inputs & review' : 'Faculty inputs' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span class="text-[11px] px-2 py-0.5 rounded-full border"
                                          :class="scoreChipClass({{ $i }})"
                                          x-text="scoreChip({{ $i }})"></span>
                                    <span class="text-gray-400">&rsaquo;</span>
                                </div>
                            </a>
                        @endfor

                        <a href="{{ route('reclassification.review') }}" data-section-nav
                           @click.prevent="navTo('review')"
                           class="flex items-center justify-between px-4 py-3 rounded-xl border border-gray-200 hover:bg-gray-50 transition">
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 rounded-xl flex items-center justify-center bg-gray-100 text-gray-700">
                                    ?
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-gray-800">Review</div>
                                    <div class="text-xs text-gray-500">Summary & checklist</div>
                                </div>
                            </div>
                            <span class="text-gray-400">&rsaquo;</span>
                        </a>
                    </div>
                </div>
            </aside>

            {{-- MAIN CONTENT --}}
            <main class="lg:col-span-9 space-y-4">

                {{-- MOBILE SECTION NAV --}}
                <div class="lg:hidden bg-white border border-gray-200 rounded-2xl shadow-card">
                    <div class="px-5 py-4 flex items-center justify-between">
                        <div>
                            <div class="text-sm font-semibold text-gray-800">Go to a section</div>
                            <div class="text-xs text-gray-500">Tap to navigate.</div>
                        </div>

                        <button @click="openMobileNav = !openMobileNav"
                                class="px-3 py-2 rounded-xl border border-gray-200 text-sm text-gray-700 hover:bg-gray-50 transition">
                            Menu
                        </button>
                    </div>

                    <div x-show="openMobileNav" x-transition class="px-5 pb-4 space-y-2">
                        @for($i = 1; $i <= 5; $i++)
                            <a href="{{ route('reclassification.section', $i) }}" data-section-nav
                               @click.prevent="navTo({{ $i }})"
                               class="block px-4 py-3 rounded-xl border"
                               :class="active === {{ $i }} ? 'border-bu bg-bu/5' : 'border-gray-200 hover:bg-gray-50'">
                                Section {{ $i }} @if($i === 2) <span class="text-xs text-gray-500">(View-only)</span> @endif
                            </a>
                        @endfor
                        <a href="{{ route('reclassification.review') }}" data-section-nav
                           @click.prevent="navTo('review')"
                           class="block px-4 py-3 rounded-xl border border-gray-200 hover:bg-gray-50">
                            Review
                        </a>
                    </div>
                </div>

                {{-- SECTION CARD --}}
                <div class="bg-white border border-gray-200 rounded-2xl shadow-card overflow-hidden">
                    <div class="px-6 py-4 border-b flex items-start justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">
                                Section <span x-text="active"></span>
                                <template x-if="active === 2">
                                    <span class="ml-2 text-sm font-medium text-gray-500">(View-only)</span>
                                </template>
                            </h3>

                            <p class="text-sm text-gray-500">
                                <template x-if="active === 2">
                                    <span>This section is completed by the Dean. Faculty can view only.</span>
                                </template>
                                <template x-if="active !== 2">
                                    <span>Fill out the required information and attach evidences.</span>
                                </template>
                            </p>
                        </div>

                        {{-- Editability badge --}}
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border {{ $canEdit ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-50 text-gray-700 border-gray-200' }}">
                            {{ $canEdit ? 'Editable' : 'Read-only (Submitted)' }}
                        </span>
                    </div>

                    <div class="p-6">
                        @for ($i = 1; $i <= 5; $i++)
                            <section data-section-pane :class="active === {{ $i }} ? 'block is-active' : 'hidden'">
                                @if($i === 2)
                                    <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 text-amber-700 px-4 py-3 text-sm">
                                        Section 2 is for Dean's evaluation and is view-only for faculty.
                                    </div>
                                    <div class="opacity-70 pointer-events-none">
                                        @include("reclassification.section{$i}", [
                                            'application' => $application,
                                            'section' => $application->sections->firstWhere('section_code', '2'),
                                            'sectionData' => [],
                                        ])
                                    </div>
                                @else
                                    @include("reclassification.section{$i}", [
                                        'application' => $application,
                                        'section' => $application->sections->firstWhere('section_code', (string) $i),
                                        'sectionData' => $i === 1 ? ($sectionData ?? []) : [],
                                    ])
                                @endif
                            </section>
                        @endfor
                    </div>
                </div>

                {{-- Bottom navigation --}}
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-500">
                        Tip: Use the sidebar to jump between sections.
                    </div>

                    <div class="flex gap-2">
                        <button type="button"
                                x-show="active > 1"
                                @click="navTo(active - 1)"
                                class="px-4 py-2 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 transition">
                            &larr; Previous
                        </button>

                        <button type="button"
                                @click="confirmSection(active)"
                                class="px-4 py-2 rounded-xl border border-green-200 bg-green-50 text-green-700 hover:bg-green-100 transition">
                            Mark Section Complete
                        </button>

                        <button type="button"
                                x-show="active < 5"
                                @click="navTo(active + 1)"
                                class="px-4 py-2 rounded-xl bg-bu text-white hover:bg-bu-dark transition shadow-soft">
                            Next &rarr;
                        </button>

                        <button type="button"
                                x-show="active === 5"
                                @click="navTo('review')"
                                class="px-4 py-2 rounded-xl bg-bu text-white hover:bg-bu-dark transition shadow-soft">
                            Go to Review &rarr;
                        </button>
                    </div>
                </div>

            </main>
        </div>
    </div>

    @php
        $initialSections = $application->sections->keyBy('section_code')->map(function ($s) {
            return [
                'confirmed' => (bool) $s->is_complete,
                'points' => (float) $s->points_total,
                'max' => $s->section_code === '1'
                    ? 140
                    : ($s->section_code === '2'
                        ? 120
                        : ($s->section_code === '3'
                            ? 70
                            : ($s->section_code === '4' ? 40 : 30))),
            ];
        });
    @endphp

    <script>
        function reclassificationWizard() {
            const initial = @json($initialSections);

            return {
                openMobileNav: false,
                active: {{ $active }},
                sections: initial,

                init() {
                    if (!this.sections['1']) {
                        this.sections = {
                            '1': { confirmed: false, points: 0, max: 140 },
                            '2': { confirmed: false, points: 0, max: 120 },
                            '3': { confirmed: false, points: 0, max: 70 },
                            '4': { confirmed: false, points: 0, max: 40 },
                            '5': { confirmed: false, points: 0, max: 30 },
                        };
                    }
                },

                navTo(target) {
                    const form = document.querySelector('[data-section-pane].is-active form[data-validate-evidence]');
                    if (window.validateFormRows && form) {
                        if (!window.validateFormRows(form)) {
                            return;
                        }
                    }

                    if (target === 'review') {
                        window.location.href = "{{ route('reclassification.review') }}";
                        return;
                    }

                    this.active = Number(target);
                },

                confirmSection(sectionId) {
                    if (this.sections[String(sectionId)]) {
                        this.sections[String(sectionId)].confirmed = true;
                    }
                },

                scoreChip(sectionId) {
                    const s = this.sections[String(sectionId)];
                    if (!s) return '--/--';
                    return `${Number(s.points).toFixed(0)}/${s.max}`;
                },

                scoreChipClass(sectionId) {
                    const s = this.sections[String(sectionId)];
                    if (!s) return 'border-gray-200 text-gray-500';
                    return s.confirmed
                        ? 'border-green-200 bg-green-50 text-green-700'
                        : 'border-gray-200 text-gray-500';
                },
            };
        }
    </script>
</x-app-layout>






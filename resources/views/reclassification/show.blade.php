<x-app-layout>
    @php
        $activeParam = request()->get('tab');
        $active = $activeParam === 'review'
            ? 'review'
            : (int) request()->route('number', 1);
        if ($active !== 'review' && ($active < 1 || $active > 5)) $active = 1;
    @endphp

    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800">Faculty Reclassification</h2>
                <p class="text-sm text-gray-500">
                    Review and complete your reclassification requirements.
                </p>
            </div>

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

                @if($canEdit)
                    <button type="button"
                            onclick="window.saveDraftAll && window.saveDraftAll()"
                            class="inline-flex items-center px-3 py-2 rounded-xl border border-gray-200 text-sm font-semibold
                                   text-gray-700 hover:bg-gray-50 transition">
                        Save Draft
                    </button>

                    @if($active !== 2)
                        <form method="POST"
                              action="{{ route('reclassification.section.reset', $active) }}"
                              onsubmit="return confirm('Reset this section? This clears all entries and detaches evidence from this section.');">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center px-3 py-2 rounded-xl border border-amber-200 bg-amber-50 text-sm font-semibold
                                           text-amber-700 hover:bg-amber-100 transition">
                                Reset Section
                            </button>
                        </form>
                    @endif

                    <form method="POST"
                          action="{{ route('reclassification.reset') }}"
                          onsubmit="return confirm('Reset the entire reclassification? This clears all sections and detaches all evidence. Uploaded files remain in your library.');">
                        @csrf
                        <input type="hidden" name="active" value="{{ $active }}">
                        <button type="submit"
                                class="inline-flex items-center px-3 py-2 rounded-xl border border-red-200 bg-red-50 text-sm font-semibold
                                       text-red-700 hover:bg-red-100 transition">
                            Reset Reclassification
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    @php
        $initialSections = $application->sections->keyBy('section_code')->map(function ($s) {
            return [
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

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
         x-data="reclassificationWizard()"
         x-init="init()"
         @review-nav.window="navTo($event.detail.target)">

        @if (session('success'))
            <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="sticky top-20 z-30">
            <div class="bg-white/95 backdrop-blur border border-gray-200 rounded-2xl shadow-card">
                <div class="px-4 py-3 flex items-center justify-between gap-4">
                    <div>
                        <div class="text-sm font-semibold text-gray-800">Sections</div>
                        <div class="text-xs text-gray-500">Navigate and monitor scores.</div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="text-xs text-gray-500">
                            Total: <span class="font-semibold text-gray-800" x-text="totalPoints().toFixed(0)"></span>
                        </div>
                        <button type="button"
                                @click="showScores = !showScores"
                                class="px-3 py-1.5 rounded-lg border text-xs font-medium text-gray-700 hover:bg-gray-50">
                            <span x-text="showScores ? 'Hide scores' : 'Show scores'"></span>
                        </button>
                    </div>
                </div>

                <div class="px-4 pb-3">
                    <div class="flex flex-wrap gap-2">
                        @for($i = 1; $i <= 5; $i++)
                            @php $isLocked = $i === 2; @endphp
                            <a href="{{ route('reclassification.section', $i) }}" data-section-nav
                               @click.prevent="navTo({{ $i }})"
                               class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border text-sm transition"
                               :class="active === {{ $i }} ? 'border-bu bg-bu/5 text-gray-800' : 'border-gray-200 hover:bg-gray-50 text-gray-700'">
                                <span class="font-semibold">Section {{ $i }}</span>
                                @if($isLocked)
                                    <span class="text-[11px] text-gray-500">(View-only)</span>
                                @endif
                                <span x-show="showScores"
                                      class="text-[11px] px-2 py-0.5 rounded-full border"
                                      :class="scoreChipClass({{ $i }})"
                                      x-text="scoreChip({{ $i }})"></span>
                            </a>
                        @endfor

                        <a href="{{ route('reclassification.show', ['tab' => 'review']) }}" data-section-nav
                           @click.prevent="navTo('review')"
                           class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-gray-200 hover:bg-gray-50 text-sm text-gray-700">
                            <span class="font-semibold">Review</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 bg-white border border-gray-200 rounded-2xl shadow-card overflow-hidden">
            <div class="px-6 py-4 border-b flex items-start justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">
                        <template x-if="active !== 'review'">
                            <span>Section <span x-text="active"></span></span>
                        </template>
                        <template x-if="active === 'review'">
                            <span>Review Summary</span>
                        </template>
                        <template x-if="active === 2">
                            <span class="ml-2 text-sm font-medium text-gray-500">(View-only)</span>
                        </template>
                    </h3>
                    <p class="text-sm text-gray-500">
                        <template x-if="active === 'review'">
                            <span>Read-only summary and final submit.</span>
                        </template>
                        <template x-if="active === 2">
                            <span>This section is completed by the Dean. Faculty can view only.</span>
                        </template>
                        <template x-if="active !== 2 && active !== 'review'">
                            <span>Fill out the required information and attach evidences.</span>
                        </template>
                    </p>
                </div>

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
                                    'sectionData' => $sectionsData['2'] ?? [],
                                    'globalEvidence' => $globalEvidence ?? [],
                                    'readOnly' => true,
                                    'embedded' => true,
                                ])
                            </div>
                        @else
                            @include("reclassification.section{$i}", [
                                'application' => $application,
                                'section' => $application->sections->firstWhere('section_code', (string) $i),
                                'sectionData' => $sectionsData[(string) $i] ?? [],
                                'globalEvidence' => $globalEvidence ?? [],
                            ])
                        @endif
                    </section>
                @endfor

                @php
                    $sectionsByCode = $application->sections->keyBy('section_code');
                    $sectionTotals = [
                        '1' => (float) optional($sectionsByCode->get('1'))->points_total,
                        '2' => (float) optional($sectionsByCode->get('2'))->points_total,
                        '3' => (float) optional($sectionsByCode->get('3'))->points_total,
                        '4' => (float) optional($sectionsByCode->get('4'))->points_total,
                        '5' => (float) optional($sectionsByCode->get('5'))->points_total,
                    ];
                    $currentRank = $currentRankLabel ?? ($eligibility['currentRank'] ?? 'Instructor');
                    $trackKey = match (strtolower(trim($currentRank))) {
                        'full professor', 'full' => 'full',
                        'associate professor', 'associate' => 'associate',
                        'assistant professor', 'assistant' => 'assistant',
                        default => 'instructor',
                    };
                @endphp

                <section id="review-summary" data-section-pane :class="active === 'review' ? 'block' : 'hidden'">
                    <div class="space-y-6">
                        <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                            <div class="px-6 py-4 border-b">
                                <h3 class="text-lg font-semibold text-gray-800">My Information</h3>
                            </div>
                            <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
                                <div class="space-y-3">
                                    <div>
                                        <div class="text-xs text-gray-500">Name</div>
                                        <div class="text-sm font-semibold text-gray-800">{{ auth()->user()->name ?? 'Faculty' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500">Date of Original Appointment</div>
                                        <div class="text-sm font-semibold text-gray-800">{{ $appointmentDate ?? 'â€”' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500">Total Years of Service (BU)</div>
                                        <div class="text-sm font-semibold text-gray-800">
                                            {{ $yearsService !== null ? $yearsService . ' years' : 'â€”' }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500">Employment Type</div>
                                        <div class="text-sm font-semibold text-gray-800">
                                            {{ $profile?->employment_type === 'part_time' ? 'Part-time' : 'Full-time' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div>
                                        <div class="text-xs text-gray-500">Current Teaching Rank</div>
                                        <div class="text-sm font-semibold text-gray-800">{{ $currentRank }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500">Rank Based on Points</div>
                                        <div class="text-sm font-semibold text-gray-800" x-text="pointsRankLabel() || 'â€”'"></div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500">Allowed Rank (Rules Applied)</div>
                                        <div class="text-sm font-semibold text-gray-800" x-text="allowedRankLabel() || 'Not eligible'"></div>
                                    </div>
                                </div>

                                                                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-900 space-y-2">
                                    <div class="font-semibold text-amber-800">Eligibility checklist</div>
                                    <template x-for="item in eligibilityChecklist()" :key="item.label">
                                        <div class="flex items-start gap-2">
                                            <span class="mt-0.5 h-4 w-4 rounded-full flex items-center justify-center text-[10px]"
                                                  :class="item.ok ? 'bg-green-100 text-green-700' : (item.optional ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700')"
                                                  x-text="item.ok ? 'Y' : (item.optional ? '!' : 'X')"></span>
                                            <span x-text="item.label"></span>
                                        </div>
                                    </template>
                                    <div class="text-[11px] text-amber-700">Only one rank step per cycle.</div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                            <div class="px-6 py-4 border-b flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800">Total Points Summary</h3>
                                    <p class="text-sm text-gray-500">Counted totals per section (caps already applied).</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500">TOTAL</p>
                                    <p class="text-lg font-semibold text-gray-900" x-text="totalPoints().toFixed(2)"></p>
                                </div>
                            </div>

                            <div class="p-6">
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm border rounded-lg overflow-hidden">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-3 text-left">Section</th>
                                                <th class="px-4 py-3 text-right">Counted Points</th>
                                                <th class="px-4 py-3 text-right">Quick Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y">
                                            <tr>
                                                <td class="px-4 py-3 font-medium">Section I â€“ Academic Preparation & Professional Development</td>
                                                <td class="px-4 py-3 text-right font-semibold text-gray-800" x-text="sectionPoints(1).toFixed(2)"></td>
                                                <td class="px-4 py-3 text-right">
                                                    <button type="button" @click="$dispatch('review-nav', { target: 1 })" class="text-bu text-xs font-medium hover:underline">View Section I</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="px-4 py-3 font-medium">Section II â€“ Instructional Competence</td>
                                                <td class="px-4 py-3 text-right font-semibold text-gray-800" x-text="sectionPoints(2).toFixed(2)"></td>
                                                <td class="px-4 py-3 text-right">
                                                    <button type="button" @click="$dispatch('review-nav', { target: 2 })" class="text-bu text-xs font-medium hover:underline">View Section II</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="px-4 py-3 font-medium">Section III â€“ Research Competence & Productivity</td>
                                                <td class="px-4 py-3 text-right font-semibold text-gray-800" x-text="sectionPoints(3).toFixed(2)"></td>
                                                <td class="px-4 py-3 text-right">
                                                    <button type="button" @click="$dispatch('review-nav', { target: 3 })" class="text-bu text-xs font-medium hover:underline">View Section III</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="px-4 py-3 font-medium">Section IV â€“ Teaching / Professional / Administrative Experience</td>
                                                <td class="px-4 py-3 text-right font-semibold text-gray-800" x-text="sectionPoints(4).toFixed(2)"></td>
                                                <td class="px-4 py-3 text-right">
                                                    <button type="button" @click="$dispatch('review-nav', { target: 4 })" class="text-bu text-xs font-medium hover:underline">View Section IV</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="px-4 py-3 font-medium">Section V â€“ Professional & Community Leadership Service</td>
                                                <td class="px-4 py-3 text-right font-semibold text-gray-800" x-text="sectionPoints(5).toFixed(2)"></td>
                                                <td class="px-4 py-3 text-right">
                                                    <button type="button" @click="$dispatch('review-nav', { target: 5 })" class="text-bu text-xs font-medium hover:underline">View Section V</button>
                                                </td>
                                            </tr>
                                            <tr class="bg-gray-50">
                                                <td class="px-4 py-3 font-semibold">TOTAL POINTS</td>
                                                <td class="px-4 py-3 text-right font-semibold text-gray-900" x-text="totalPoints().toFixed(2)"></td>
                                                <td class="px-4 py-3"></td>
                                            </tr>
                                            <tr>
                                                <td class="px-4 py-3 font-semibold">EQUIVALENT PERCENTAGE (Total Ã· 4)</td>
                                                <td class="px-4 py-3 text-right font-semibold text-gray-900" x-text="eqPercent().toFixed(2)"></td>
                                                <td class="px-4 py-3"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                            <div class="px-6 py-4 border-b">
                                <h3 class="text-lg font-semibold text-gray-800">Notes to the Rater</h3>
                            </div>
                            <div class="p-6 text-sm text-gray-700 space-y-2">
                                <p>No faculty member can be promoted to the rank of Full Professor who has not earned a doctorate degree in his field of teaching assignment or allied field of discipline and has produced at least one accepted research output; or recognition of outstanding accomplishments in arts and sciences; attainment of higher responsible position in government service, business and industry.</p>
                                <p>No faculty member can be promoted to more than one rank (not step) during any one reclassification term.</p>
                                <p>Normally, a new faculty member starts as a probationary instructor, but he may be appointed to a higher rank depending on his credentials.</p>
                                <p>A faculty member cannot be ranked if he does not have a masterâ€™s degree.</p>
                                <p>A faculty member cannot be ranked without any research or its equivalent.</p>
                                <p>A faculty member who has just earned his/her masterâ€™s degree can be classified even if it is not within the reclassification term in the University.</p>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <form method="POST" action="{{ route('reclassification.submit', $application->id) }}">
                                @csrf
                                <button type="submit"
                                        class="px-6 py-2.5 rounded-xl bg-bu text-white font-semibold"
                                        :disabled="!canFinalSubmit()"
                                        :class="!canFinalSubmit() ? 'opacity-60 cursor-not-allowed' : ''">
                                    Final Submit
                                </button>
                            </form>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        {{-- GLOBAL EVIDENCE LIBRARY --}}
        <div id="global-evidence-library" class="mt-6 bg-white border border-gray-200 rounded-2xl shadow-card">
            <div class="px-6 py-4 border-b flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Evidence Library</h3>
                    <p class="text-sm text-gray-500">Upload once, then attach per row using Select Evidence.</p>
                </div>
            </div>

            <div class="p-6 space-y-5">
                <div class="rounded-2xl border border-dashed border-gray-300 bg-bu-muted/30 p-6">
                    <div class="flex flex-col items-center text-center gap-2">
                        <div class="text-sm font-semibold text-gray-800">Upload Evidence</div>
                        <div class="text-xs text-gray-500">
                            Add multiple files. They will appear below and can be attached from any section.
                        </div>

                        <label class="mt-2 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-bu text-white text-sm font-semibold shadow-soft hover:bg-bu-dark cursor-pointer">
                            <span>Select files</span>
                            <input type="file" multiple class="sr-only" @change="addUploadFiles($event)">
                        </label>

                        <div class="text-xs text-gray-500">
                            Uploaded: <span class="font-semibold text-gray-800" x-text="evidenceCount()"></span>
                            <span class="mx-2 text-gray-300"></span>
                            Pending: <span class="font-semibold text-gray-800" x-text="pendingUploads.length"></span>
                        </div>
                    </div>

                    <template x-if="pendingUploads.length">
                        <div class="mt-4 space-y-2">
                            <div class="text-xs font-semibold text-gray-700">Pending uploads</div>
                            <div class="space-y-2">
                                <template x-for="(item, idx) in pendingItems()" :key="item.id">
                                    <div class="flex items-center justify-between rounded-xl border bg-white px-3 py-2 text-xs">
                                        <div class="min-w-0">
                                            <div class="font-medium text-gray-800 truncate" x-text="item.name"></div>
                                            <div class="text-gray-500" x-text="item.typeLabel"></div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <button type="button" @click="openLibraryPreview(item)" class="text-bu hover:underline">
                                                Preview
                                            </button>
                                            <button type="button" @click="removePendingUpload(idx)" class="text-red-600 hover:underline">
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <button type="button"
                                    @click="uploadPendingEvidence()"
                                    :disabled="uploading || pendingUploads.length === 0"
                                    class="mt-2 px-4 py-2 rounded-xl bg-bu text-white text-sm font-semibold shadow-soft hover:bg-bu-dark disabled:opacity-50 disabled:cursor-not-allowed">
                                <span x-text="uploading ? 'Uploadingâ€¦' : 'Upload selected'"></span>
                            </button>
                        </div>
                    </template>
                </div>

                <div>
                    <div class="text-sm font-semibold text-gray-800">Uploaded Files</div>
                    <div class="text-xs text-gray-500">Preview, detach, or remove files (unattached only).</div>
                </div>

                <template x-if="normalizedEvidence().length === 0">
                    <div class="rounded-2xl border border-dashed p-6 text-center text-sm text-gray-500">
                        No uploaded evidence yet.
                    </div>
                </template>

                <template x-if="normalizedEvidence().length > 0">
                    <div class="overflow-hidden rounded-2xl border">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-left">
                                <tr>
                                    <th class="px-4 py-2">File</th>
                                    <th class="px-4 py-2">Used</th>
                                    <th class="px-4 py-2 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <template x-for="item in normalizedEvidence()" :key="item.id">
                                    <tr>
                                        <td class="px-4 py-2">
                                            <div class="font-medium text-gray-800 truncate" x-text="item.name"></div>
                                            <div class="text-xs text-gray-500 flex items-center gap-2">
                                                <span x-text="item.typeLabel"></span>
                                                <span class="text-gray-300"></span>
                                                <span x-text="item.uploaded_at || '-'"></span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] border"
                                                  :class="item.entry_count > 0 ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-50 text-gray-600 border-gray-200'">
                                                <span x-text="item.entry_count ? `${item.entry_count} linked` : 'Not used'"></span>
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-right space-x-3">
                                            <button type="button" @click="openLibraryPreview(item)" class="text-xs text-bu hover:underline">
                                                Preview
                                            </button>
                                            <button type="button"
                                                    x-show="item.entry_id"
                                                    @click="detachLibraryEvidence(item)"
                                                    class="text-xs text-red-600 hover:underline">
                                                Detach
                                            </button>
                                            <button type="button"
                                                    x-show="!item.entry_id"
                                                    @click="deleteLibraryEvidence(item)"
                                                    class="text-xs text-gray-600 hover:underline">
                                                Remove
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-between">
            <div class="text-sm text-gray-500">Use the top bar to jump between sections.</div>
            <div class="flex gap-2">
                <button type="button"
                        x-show="active !== 'review' && active > 1"
                        @click="navTo(active - 1)"
                        class="px-4 py-2 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 transition">
                    &larr; Previous
                </button>

                <button type="button"
                        x-show="active !== 'review' && active < 5"
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

                <button type="button"
                        x-show="active === 'review'"
                        @click="navTo(5)"
                        class="px-4 py-2 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 transition">
                    Back to Section V
                </button>
            </div>
        </div>

    <script>
        function reclassificationWizard() {
            const initial = @json($initialSections);
            const sectionUrls = @json(collect(range(1, 5))->mapWithKeys(fn ($i) => [$i => route('reclassification.section', $i)]));
            const reviewUrl = @json(route('reclassification.show', ['tab' => 'review']));
            const globalEvidence = @json($globalEvidence ?? []);
            const detachBase = @json(url('/reclassification/evidences'));
            const deleteBase = @json(url('/reclassification/evidences'));
            const uploadUrl = @json(route('reclassification.evidence.upload'));
            const rankTrack = @json($trackKey ?? 'instructor');
            const rankTrackLabel = @json($currentRank ?? 'Instructor');
            const eligibility = {
                hasMasters: @json(($eligibility['hasMasters'] ?? false)),
                hasDoctorate: @json(($eligibility['hasDoctorate'] ?? false)),
                hasResearchEquivalent: @json(($eligibility['hasResearchEquivalent'] ?? false)),
                hasAcceptedResearchOutput: @json(($eligibility['hasAcceptedResearchOutput'] ?? false)),
            };

            return {
                active: @json($active),
                sections: initial,
                 showScores: true,
                 sectionUrls,
                 reviewUrl,
                 globalEvidence,
                 libraryPreviewOpen: false,
                 libraryPreviewItem: null,
                 libraryToast: { show: false, message: '' },
                pendingUploads: [],
                savingDraft: false,
                uploading: false,
                track: rankTrack,
                trackLabel: rankTrackLabel,
                hasMasters: eligibility.hasMasters,
                hasDoctorate: eligibility.hasDoctorate,
                hasResearchEquivalent: eligibility.hasResearchEquivalent,
                hasAcceptedResearchOutput: eligibility.hasAcceptedResearchOutput,

                init() {
                    if (!this.sections['1']) {
                        this.sections = {
                            '1': { points: 0, max: 140 },
                            '2': { points: 0, max: 120 },
                            '3': { points: 0, max: 70 },
                            '4': { points: 0, max: 40 },
                            '5': { points: 0, max: 30 },
                        };
                    }

                    document.addEventListener('section-score', (event) => {
                        const detail = event.detail || {};
                        const key = String(detail.section || '');
                        if (this.sections[key]) {
                            this.sections[key].points = Number(detail.points || 0);
                        }
                    });

                    window.saveDraftAll = this.saveDraftAll.bind(this);
                },

                navTo(target) {
                    const form = document.querySelector('[data-section-pane].is-active form[data-validate-evidence]');
                    if (window.validateFormRows && form) {
                        if (!window.validateFormRows(form)) {
                            return;
                        }
                    }

                    if (target === 'review') {
                        this.active = 'review';
                        if (this.reviewUrl) {
                            window.history.replaceState({}, '', this.reviewUrl);
                        }
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        return;
                    }

                    this.active = Number(target);
                    if (this.sectionUrls && this.sectionUrls[this.active]) {
                        window.history.replaceState({}, '', this.sectionUrls[this.active]);
                    }
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                },

                totalPoints() {
                    return Object.values(this.sections).reduce((sum, s) => sum + Number(s.points || 0), 0);
                },

                sectionPoints(id) {
                    const s = this.sections[String(id)];
                    return Number(s?.points || 0);
                },

                eqPercent() {
                    return this.totalPoints() / 4;
                },

                pointsRank() {
                    const p = this.eqPercent();
                    const ranges = {
                        full: [
                            { letter: 'A', min: 95.87, max: 100.0 },
                            { letter: 'B', min: 91.5, max: 95.86 },
                            { letter: 'C', min: 87.53, max: 91.49 },
                        ],
                        associate: [
                            { letter: 'A', min: 83.34, max: 87.52 },
                            { letter: 'B', min: 79.19, max: 83.33 },
                            { letter: 'C', min: 75.02, max: 79.18 },
                        ],
                        assistant: [
                            { letter: 'A', min: 70.85, max: 75.01 },
                            { letter: 'B', min: 66.68, max: 70.84 },
                            { letter: 'C', min: 62.51, max: 66.67 },
                        ],
                        instructor: [
                            { letter: 'A', min: 58.34, max: 62.5 },
                            { letter: 'B', min: 54.14, max: 58.33 },
                            { letter: 'C', min: 50.0, max: 54.16 },
                        ],
                    };
                    const order = ['full', 'associate', 'assistant', 'instructor'];
                    for (const key of order) {
                        const list = ranges[key];
                        const hit = list.find((r) => p >= r.min && p <= r.max);
                        if (hit) return { track: key, letter: hit.letter };
                    }
                    return null;
                },

                pointsRankLabel() {
                    const hit = this.pointsRank();
                    if (!hit) return '';
                    const labels = {
                        full: 'Full Professor',
                        associate: 'Associate Professor',
                        assistant: 'Assistant Professor',
                        instructor: 'Instructor',
                    };
                    return `${labels[hit.track]} - ${hit.letter}`;
                },

                allowedRankLabel() {
                    if (!this.hasMasters || !this.hasResearchEquivalent) return '';
                    const labels = {
                        full: 'Full Professor',
                        associate: 'Associate Professor',
                        assistant: 'Assistant Professor',
                        instructor: 'Instructor',
                    };
                    const order = { instructor: 1, assistant: 2, associate: 3, full: 4 };
                    const nextRank = (key) => {
                        const target = order[key] + 1;
                        const hit = Object.keys(order).find((k) => order[k] === target);
                        return hit || key;
                    };

                    let desired = this.pointsRank()?.track || this.track;
                    const maxAllowed = (this.hasDoctorate && this.hasAcceptedResearchOutput) ? 'full' : 'associate';
                    if (order[desired] > order[maxAllowed]) desired = maxAllowed;

                    const oneStep = nextRank(this.track);
                    if (order[desired] > order[oneStep]) desired = oneStep;

                    return labels[desired] || '';
                },

                eligibilityChecklist() {
                    const needsDoctorate = (this.pointsRank()?.track || this.track) === 'full';
                    return [
                        { label: 'Masters degree required', ok: this.hasMasters },
                        { label: 'At least one research output/equivalent', ok: this.hasResearchEquivalent },
                        {
                            label: 'Doctorate + accepted research output for Full Professor',
                            ok: !needsDoctorate || (this.hasDoctorate && this.hasAcceptedResearchOutput),
                            optional: !needsDoctorate,
                        },
                    ];
                },

                canFinalSubmit() {
                    if (!this.hasMasters) return false;
                    if (!this.hasResearchEquivalent) return false;
                    return true;
                },

                evidenceCount() {
                    return this.normalizedEvidence().length;
                },

                fileTypeLabel(name, mime) {
                    if (mime) {
                        const parts = mime.split('/');
                        return (parts[1] || parts[0]).toUpperCase();
                    }
                    const ext = (name || '').split('.').pop();
                    return ext ? ext.toUpperCase() : 'FILE';
                },

                normalizedEvidence() {
                    return (this.globalEvidence || []).map((ev) => {
                        const typeLabel = this.fileTypeLabel(ev.name, ev.mime_type || '');
                        const isImage = (ev.mime_type || '').startsWith('image/') || /\.(png|jpe?g|gif|webp)$/i.test(ev.name || '');
                        const isPdf = (ev.mime_type || '') === 'application/pdf' || /\.pdf$/i.test(ev.name || '');
                        return {
                            ...ev,
                            entry_count: Number(ev.entry_count || 0),
                            typeLabel,
                            isImage,
                            isPdf,
                        };
                    });
                },

                addUploadFiles(event) {
                    const files = Array.from(event.target.files || []);
                    if (!files.length) return;
                    const existing = this.pendingUploads || [];
                    const signature = (file) => `${file.name}|${file.size}|${file.lastModified}`;
                    const map = new Set(existing.map(signature));
                    files.forEach((file) => {
                        const sig = signature(file);
                        if (!map.has(sig)) {
                            map.add(sig);
                            existing.push(file);
                        }
                    });
                    this.pendingUploads = [...existing];
                    event.target.value = '';
                },

                pendingItems() {
                    return (this.pendingUploads || []).map((file, idx) => {
                        const mime = file.type || '';
                        const typeLabel = this.fileTypeLabel(file.name, mime);
                        const isImage = mime.startsWith('image/') || /\.(png|jpe?g|gif|webp)$/i.test(file.name || '');
                        const isPdf = mime === 'application/pdf' || /\.pdf$/i.test(file.name || '');
                        return {
                            id: `pending-${idx}`,
                            name: file.name,
                            mime_type: mime,
                            typeLabel,
                            isImage,
                            isPdf,
                            file,
                            url: null,
                        };
                    });
                },

                removePendingUpload(index) {
                    this.pendingUploads = (this.pendingUploads || []).filter((_, idx) => idx !== index);
                },

                uploadPendingEvidence() {
                    if (!this.pendingUploads.length || this.uploading) return;
                    this.uploading = true;
                    const formData = new FormData();
                    this.pendingUploads.forEach((file) => formData.append('evidence_files[]', file));
                    fetch(uploadUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content'),
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: formData,
                    })
                        .then((res) => {
                            return res.json()
                                .catch(() => ({}))
                                .then((data) => ({ ok: res.ok, status: res.status, data }));
                        })
                        .then(({ ok, status, data }) => {
                            if (!ok) {
                                const message = data?.message
                                    || (data?.errors ? Object.values(data.errors).flat()[0] : null)
                                    || (status === 419 ? 'Session expired. Refresh the page and try again.' : null)
                                    || `Upload failed (HTTP ${status}).`;
                                throw new Error(message);
                            }
                            if (Array.isArray(data?.evidence)) {
                                this.globalEvidence = data.evidence;
                                window.dispatchEvent(new CustomEvent('evidence-updated', { detail: { evidence: this.globalEvidence } }));
                            }
                            this.pendingUploads = [];
                            this.libraryToast = { show: true, message: 'Uploads saved.' };
                            setTimeout(() => { this.libraryToast.show = false; }, 2500);
                        })
                        .catch((err) => {
                            this.libraryToast = { show: true, message: err?.message || 'Upload failed.' };
                            setTimeout(() => { this.libraryToast.show = false; }, 3500);
                        })
                        .finally(() => { this.uploading = false; });
                },

                openLibraryPreview(item) {
                    if (!item) return;
                    const previewUrl = item.url || (item.file ? URL.createObjectURL(item.file) : null);
                    this.libraryPreviewItem = { ...item, previewUrl };
                    this.libraryPreviewOpen = true;
                },

                closeLibraryPreview() {
                    this.libraryPreviewOpen = false;
                    this.libraryPreviewItem = null;
                },

                detachLibraryEvidence(item) {
                    if (!item || !item.entry_id) return;
                    if (!confirm('Detach evidence from this criterion? The file will remain in your uploaded files.')) return;
                    fetch(`${detachBase}/${item.id}/detach`, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content'),
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    })
                        .then((res) => {
                            if (!res.ok) throw new Error('Failed');
                            this.globalEvidence = (this.globalEvidence || []).map((ev) => {
                                if (ev.id !== item.id) return ev;
                                return { ...ev, entry_id: null, section_code: null };
                            });
                            this.libraryToast = { show: true, message: 'Evidence detached.' };
                            setTimeout(() => { this.libraryToast.show = false; }, 2000);
                            window.dispatchEvent(new CustomEvent('evidence-detached', { detail: { id: item.id } }));
                            window.dispatchEvent(new CustomEvent('evidence-updated', { detail: { evidence: this.globalEvidence } }));
                        })
                        .catch(() => {
                            this.libraryToast = { show: true, message: 'Detach failed.' };
                            setTimeout(() => { this.libraryToast.show = false; }, 2000);
                        });
                },

                deleteLibraryEvidence(item) {
                    if (!item || item.entry_id) return;
                    if (!confirm('Remove this evidence file? This cannot be undone.')) return;
                    fetch(`${deleteBase}/${item.id}`, {
                        method: 'DELETE',
                        credentials: 'same-origin',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content'),
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    })
                        .then((res) => {
                            if (!res.ok) throw new Error('Failed');
                            this.globalEvidence = (this.globalEvidence || []).filter((ev) => ev.id !== item.id);
                            this.libraryToast = { show: true, message: 'Evidence removed.' };
                            setTimeout(() => { this.libraryToast.show = false; }, 2000);
                            window.dispatchEvent(new CustomEvent('evidence-updated', { detail: { evidence: this.globalEvidence } }));
                            window.dispatchEvent(new CustomEvent('evidence-detached', { detail: { id: item.id } }));
                        })
                        .catch(() => {
                            this.libraryToast = { show: true, message: 'Remove failed.' };
                            setTimeout(() => { this.libraryToast.show = false; }, 2000);
                        });
                },

                scoreChip(sectionId) {
                    const s = this.sections[String(sectionId)];
                    if (!s) return '--/--';
                    return `${Number(s.points).toFixed(0)}/${s.max}`;
                },

                scoreChipClass(sectionId) {
                    const s = this.sections[String(sectionId)];
                    if (!s) return 'border-gray-200 text-gray-500';
                    return Number(s.points || 0) > 0
                        ? 'border-green-200 bg-green-50 text-green-700'
                        : 'border-gray-200 bg-gray-50 text-gray-600';
                },

                saveDraftAll() {
                    if (this.savingDraft) return;
                    const forms = Array.from(document.querySelectorAll('form[data-validate-evidence]'))
                        .filter((form) => form.dataset.viewOnly !== 'true');
                    if (!forms.length) {
                        this.libraryToast = { show: true, message: 'Nothing to save.' };
                        setTimeout(() => { this.libraryToast.show = false; }, 2000);
                        return;
                    }

                    this.savingDraft = true;
                    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    const requests = forms.map((form) => {
                        const formData = new FormData(form);
                        formData.set('action', 'draft');
                        return fetch(form.action, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'X-CSRF-TOKEN': csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            body: formData,
                        });
                    });

                    Promise.all(requests)
                        .then((responses) => {
                            const failed = responses.find((res) => !res.ok);
                            if (failed) throw new Error(`Save failed (HTTP ${failed.status}).`);
                            this.libraryToast = { show: true, message: 'Draft saved.' };
                            setTimeout(() => { this.libraryToast.show = false; }, 2500);
                        })
                        .catch((err) => {
                            this.libraryToast = { show: true, message: err?.message || 'Draft save failed.' };
                            setTimeout(() => { this.libraryToast.show = false; }, 3000);
                        })
                        .finally(() => {
                            this.savingDraft = false;
                        });
                },
            };
        }
    </script>
    <script>
        function reviewSummary(init) {
            return {
                s1: Number(init.s1 || 0),
                s2: Number(init.s2 || 0),
                s3: Number(init.s3 || 0),
                s4: Number(init.s4 || 0),
                s5: Number(init.s5 || 0),

                track: init.track || 'instructor',
                trackLabel: init.trackLabel || 'Instructor',

                hasMasters: !!init.hasMasters,
                hasDoctorate: !!init.hasDoctorate,
                hasResearchEquivalent: !!init.hasResearchEquivalent,
                hasAcceptedResearchOutput: !!init.hasAcceptedResearchOutput,

                totalPoints() {
                    return Number(this.s1 + this.s2 + this.s3 + this.s4 + this.s5);
                },

                eqPercent() {
                    return this.totalPoints() / 4;
                },

                recommendedRank() {
                    const p = this.eqPercent();
                    const ranges = {
                        full: [
                            { letter:'A', min:95.87, max:100.00 },
                            { letter:'B', min:91.50, max:95.86 },
                            { letter:'C', min:87.53, max:91.49 },
                        ],
                        associate: [
                            { letter:'A', min:83.34, max:87.52 },
                            { letter:'B', min:79.19, max:83.33 },
                            { letter:'C', min:75.02, max:79.18 },
                        ],
                        assistant: [
                            { letter:'A', min:70.85, max:75.01 },
                            { letter:'B', min:66.68, max:70.84 },
                            { letter:'C', min:62.51, max:66.67 },
                        ],
                        instructor: [
                            { letter:'A', min:58.34, max:62.50 },
                            { letter:'B', min:54.14, max:58.33 },
                            { letter:'C', min:50.00, max:54.16 },
                        ],
                    };
                    const list = ranges[this.track] || [];
                    const hit = list.find(r => p >= r.min && p <= r.max);
                    if (!hit) return '';

                    const trackLabel = {
                        full:'Full Professor',
                        associate:'Associate Professor',
                        assistant:'Assistant Professor',
                        instructor:'Instructor',
                    }[this.track] || this.trackLabel;

                    return `${trackLabel} â€“ ${hit.letter}`;
                },

                pointsRank() {
                    const p = this.eqPercent();
                    const ranges = {
                        full: [
                            { letter:'A', min:95.87, max:100.00 },
                            { letter:'B', min:91.50, max:95.86 },
                            { letter:'C', min:87.53, max:91.49 },
                        ],
                        associate: [
                            { letter:'A', min:83.34, max:87.52 },
                            { letter:'B', min:79.19, max:83.33 },
                            { letter:'C', min:75.02, max:79.18 },
                        ],
                        assistant: [
                            { letter:'A', min:70.85, max:75.01 },
                            { letter:'B', min:66.68, max:70.84 },
                            { letter:'C', min:62.51, max:66.67 },
                        ],
                        instructor: [
                            { letter:'A', min:58.34, max:62.50 },
                            { letter:'B', min:54.14, max:58.33 },
                            { letter:'C', min:50.00, max:54.16 },
                        ],
                    };
                    const order = ['full','associate','assistant','instructor'];
                    for (const key of order) {
                        const list = ranges[key];
                        const hit = list.find(r => p >= r.min && p <= r.max);
                        if (hit) return { track: key, letter: hit.letter };
                    }
                    return null;
                },

                pointsRankLabel() {
                    const hit = this.pointsRank();
                    if (!hit) return '';
                    const labels = {
                        full: 'Full Professor',
                        associate: 'Associate Professor',
                        assistant: 'Assistant Professor',
                        instructor: 'Instructor',
                    };
                    return `${labels[hit.track]} â€“ ${hit.letter}`;
                },

                allowedRankLabel() {
                    if (!this.hasMasters || !this.hasResearchEquivalent) return '';
                    const labels = {
                        full: 'Full Professor',
                        associate: 'Associate Professor',
                        assistant: 'Assistant Professor',
                        instructor: 'Instructor',
                    };
                    const order = { instructor: 1, assistant: 2, associate: 3, full: 4 };
                    const nextRank = (key) => {
                        const target = order[key] + 1;
                        const hit = Object.keys(order).find((k) => order[k] === target);
                        return hit || key;
                    };

                    let desired = this.pointsRank()?.track || this.track;
                    const maxAllowed = (this.hasDoctorate && this.hasAcceptedResearchOutput) ? 'full' : 'associate';
                    if (order[desired] > order[maxAllowed]) desired = maxAllowed;

                    const oneStep = nextRank(this.track);
                    if (order[desired] > order[oneStep]) desired = oneStep;

                    return labels[desired] || '';
                },

                canFinalSubmit() {
                    if (!this.hasMasters) return false;
                    if (!this.hasResearchEquivalent) return false;
                    return true;
                },
            }
        }
    </script>
    <script>
        window.validateFormRows = function (form) {
            if (!form) return true;
            const action = form.getAttribute('action') || '';
            if (!/reclassification\\/section\\/4/.test(action)) {
                return true;
            }

            const getNumber = (name) => {
                const field = form.querySelector(`[name="${name}"]`);
                if (!field) return 0;
                const value = parseFloat(field.value || '0');
                return Number.isNaN(value) ? 0 : value;
            };

            const getEvidence = (name) => {
                const fields = Array.from(form.querySelectorAll(`[name="${name}"]`));
                if (!fields.length) return [];
                const values = [];
                fields.forEach((field) => {
                    if (field instanceof HTMLSelectElement) {
                        values.push(...Array.from(field.selectedOptions).map((opt) => opt.value));
                        return;
                    }
                    if (field.value) {
                        values.push(field.value);
                    }
                });
                return values.map((v) => String(v).trim()).filter(Boolean);
            };

            const a1Years = getNumber('section4[a][a1_years]');
            const a2Years = getNumber('section4[a][a2_years]');
            const bYears = getNumber('section4[b][years]');
            const bUnlocked = a1Years >= 5 || a2Years >= 3;

            const missing = [];
            if (a1Years > 0 && getEvidence('section4[a][a1_evidence][]').length === 0) missing.push('a1');
            if (a2Years > 0 && getEvidence('section4[a][a2_evidence][]').length === 0) missing.push('a2');
            if (bYears > 0 && bUnlocked && getEvidence('section4[b][evidence][]').length === 0) missing.push('b');

            if (!missing.length) return true;

            const first = missing[0];
            const target = form.querySelector(`[data-evidence-block="${first}"]`);
            if (target) {
                target.classList.add('ring-2', 'ring-red-400', 'rounded-xl');
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(() => target.classList.remove('ring-2', 'ring-red-400', 'rounded-xl'), 2000);
            }
            alert('Please attach evidence for Section IV before going next.');
            return false;
        };
    </script>

    {{-- Library Preview --}}
    <div x-cloak x-show="libraryPreviewOpen" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="closeLibraryPreview()"></div>
        <div class="relative bg-white w-full max-w-4xl mx-4 rounded-2xl shadow-xl border">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800" x-text="libraryPreviewItem?.name || 'Preview'"></h3>
                <button type="button" @click="closeLibraryPreview()" class="text-gray-500 hover:text-gray-700">Close</button>
            </div>
            <div class="p-6">
                <template x-if="libraryPreviewItem && libraryPreviewItem.isImage">
                    <img :src="libraryPreviewItem.previewUrl || libraryPreviewItem.url" alt="Preview" class="max-h-[70vh] mx-auto rounded-lg border" />
                </template>
                <template x-if="libraryPreviewItem && libraryPreviewItem.isPdf">
                    <iframe :src="libraryPreviewItem.previewUrl || libraryPreviewItem.url" class="w-full h-[70vh] rounded-lg border"></iframe>
                </template>
                <template x-if="libraryPreviewItem && !libraryPreviewItem.isImage && !libraryPreviewItem.isPdf">
                    <div class="text-sm text-gray-600 space-y-3">
                        <p>Preview is not available for this file type.</p>
                        <template x-if="libraryPreviewItem.url">
                            <a :href="libraryPreviewItem.url" target="_blank" class="text-bu hover:underline">Open in new tab</a>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <div x-cloak x-show="libraryToast.show" class="fixed bottom-6 right-6 z-50">
        <div class="px-4 py-2 rounded-lg shadow-lg text-sm text-white bg-gray-800">
            <span x-text="libraryToast.message"></span>
        </div>
    </div>
    </div>
</x-app-layout>



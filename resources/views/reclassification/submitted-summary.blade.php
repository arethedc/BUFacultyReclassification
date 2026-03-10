<x-app-layout>
    @php
        $summaryMode = $summaryMode ?? 'submitted';
        $isDraftHistoryMode = $summaryMode === 'draft_history';
        $canRequestReturn = !$isDraftHistoryMode && in_array((string) ($application->status ?? ''), [
            'dean_review',
            'hr_review',
            'vpaa_review',
            'vpaa_approved',
        ], true);
        $hasPendingReturnRequest = !is_null($application->faculty_return_requested_at ?? null);
    @endphp
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800">
                    {{ $isDraftHistoryMode ? 'Draft Reclassification Paper' : 'Submitted Reclassification Paper' }}
                </h2>
                <p class="text-sm text-gray-500">
                    {{ $isDraftHistoryMode ? 'Read-only summary of your historical draft.' : 'Read-only summary of your submitted form.' }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                @if($canRequestReturn)
                    @php
                        $requestReturnModalName = 'request-return-summary-' . $application->id;
                    @endphp
                    <button type="button"
                            x-data=""
                            x-on:click.prevent="$dispatch('open-modal', '{{ $requestReturnModalName }}')"
                            @disabled($hasPendingReturnRequest)
                            class="px-4 py-2 rounded-xl border text-sm font-semibold {{ $hasPendingReturnRequest ? 'border-amber-200 bg-amber-50 text-amber-700 cursor-not-allowed' : 'border-amber-300 text-amber-700 hover:bg-amber-50' }}">
                        {{ $hasPendingReturnRequest ? 'Return Requested' : 'Request Return' }}
                    </button>

                    <x-modal name="{{ $requestReturnModalName }}" :show="$errors->has('return_request_reason')" focusable>
                        <form method="POST" action="{{ route('reclassification.request-return', $application) }}" class="p-6">
                            @csrf
                            <h2 class="text-lg font-semibold text-gray-900">
                                Are you sure you want to request return?
                            </h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Add your reason so the reviewer can process your request.
                            </p>

                            <div class="mt-4">
                                <label for="return_request_reason_summary_{{ $application->id }}" class="block text-sm font-medium text-gray-700">
                                    Reason / Comment
                                </label>
                                <textarea id="return_request_reason_summary_{{ $application->id }}"
                                          name="return_request_reason"
                                          rows="4"
                                          maxlength="1000"
                                          required
                                          class="mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-bu focus:ring-bu"
                                          placeholder="Enter your reason for requesting return...">{{ old('return_request_reason', (string) ($application->faculty_return_request_reason ?? '')) }}</textarea>
                                <x-input-error :messages="$errors->get('return_request_reason')" class="mt-2" />
                            </div>

                            <div class="mt-6 flex justify-end gap-2">
                                <button type="button"
                                        x-on:click="$dispatch('close')"
                                        class="px-4 py-2 rounded-xl border border-gray-300 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                    Cancel
                                </button>
                                <button type="submit"
                                        class="px-4 py-2 rounded-xl border border-amber-300 bg-amber-50 text-sm font-semibold text-amber-700 hover:bg-amber-100">
                                    Confirm Request Return
                                </button>
                            </div>
                        </form>
                    </x-modal>
                @endif
            </div>
        </div>
    </x-slot>

    @php
        $sections = $application->sections->sortBy('section_code');
        $sectionsByCode = $sections->keyBy('section_code');
        $sectionTotals = [
            '1' => (float) optional($sectionsByCode->get('1'))->points_total,
            '2' => (float) optional($sectionsByCode->get('2'))->points_total,
            '3' => (float) optional($sectionsByCode->get('3'))->points_total,
            '4' => (float) optional($sectionsByCode->get('4'))->points_total,
            '5' => (float) optional($sectionsByCode->get('5'))->points_total,
        ];
        $currentRank = $currentRankLabel ?? 'Instructor';
        $trackKey = match (strtolower(trim($currentRank))) {
            'full professor', 'full' => 'full',
            'associate professor', 'associate' => 'associate',
            'assistant professor', 'assistant' => 'assistant',
            default => 'instructor',
        };
        $returnedFrom = strtolower(trim((string) ($application->returned_from ?? '')));
        $returnedFromLabel = match($returnedFrom) {
            'dean' => 'Dean',
            'hr' => 'HR',
            'vpaa' => 'VPAA',
            'president' => 'President',
            default => 'Reviewer',
        };
        $statusLabel = match($application->status) {
            'draft' => 'Draft',
            'returned_to_faculty' => "Returned by {$returnedFromLabel}",
            'dean_review' => 'Dean',
            'hr_review' => 'HR',
            'vpaa_review' => 'VPAA',
            'vpaa_approved' => 'VPAA Approved',
            'president_review' => 'President',
            'finalized' => 'Finalized',
            'rejected_final' => 'Rejected',
            default => ucfirst(str_replace('_',' ', $application->status)),
        };
        $approvedRankLabel = trim((string) ($application->approved_rank_label ?? ''));
        $criterionLabels = [
            '1' => [
                'a1' => "A1. Bachelor's Degree (Latin honors)",
                'a2' => "A2. Additional Bachelor's Degree",
                'a3' => "A3. Master's Degree",
                'a4' => "A4. Master's Degree Units",
                'a5' => "A5. Additional Master's Degree",
                'a6' => 'A6. Doctoral Units',
                'a7' => "A7. Doctor's Degree",
                'a8' => 'A8. Qualifying Government Examinations',
                'a9' => 'A9. International/National Certifications',
                'b' => 'B. Advanced/Specialized Training',
                'c' => 'C. Short-term Workshops/Seminars',
                'b_prev' => 'B. Previous Reclassification (1/3)',
                'c_prev' => 'C. Previous Reclassification (1/3)',
            ],
            '2' => [
                'ratings' => 'Instructional Competence Ratings',
                'previous_points' => 'Previous Reclassification (1/3)',
            ],
            '3' => [
                'c1' => 'C1. Book Authorship',
                'c2' => 'C2. Workbook/Module',
                'c3' => 'C3. Instructional Materials',
                'c4' => 'C4. Refereed Articles',
                'c5' => 'C5. Research Papers',
                'c6' => 'C6. Research Inventions/Patents',
                'c7' => 'C7. Artistic Works',
                'c8' => 'C8. Editorial Work',
                'c9' => 'C9. Professional Output',
                'previous_points' => 'Previous Reclassification (1/3)',
            ],
            '4' => [
                'a1' => 'A1. Actual Services Outside BU',
                'a2' => 'A2. Actual Services at BU',
                'b' => 'B. Industrial/Professional Experience',
            ],
            '5' => [
                'a' => 'A. Membership/Leadership',
                'b' => 'B. Awards/Recognition',
                'c1' => 'C1. Curriculum Development',
                'c2' => 'C2. Extension/Outreach',
                'c3' => 'C3. University Activities',
                'd' => 'D. Community Involvement',
                'b_prev' => 'B. Previous Reclassification (1/3)',
                'c_prev' => 'C. Previous Reclassification (1/3)',
                'd_prev' => 'D. Previous Reclassification (1/3)',
                'previous_points' => 'Previous Reclassification (1/3)',
            ],
        ];

        $summaryTotalPoints = array_sum($sectionTotals);
        $summaryEqPercent = $summaryTotalPoints / 4;
        $summaryRankLabels = [
            'full' => 'Full Professor',
            'associate' => 'Associate Professor',
            'assistant' => 'Assistant Professor',
            'instructor' => 'Instructor',
        ];
        $summaryRanges = [
            'full' => [
                ['letter' => 'A', 'min' => 95.87, 'max' => 100.00],
                ['letter' => 'B', 'min' => 91.50, 'max' => 95.86],
                ['letter' => 'C', 'min' => 87.53, 'max' => 91.49],
            ],
            'associate' => [
                ['letter' => 'A', 'min' => 83.34, 'max' => 87.52],
                ['letter' => 'B', 'min' => 79.19, 'max' => 83.33],
                ['letter' => 'C', 'min' => 75.02, 'max' => 79.18],
            ],
            'assistant' => [
                ['letter' => 'A', 'min' => 70.85, 'max' => 75.01],
                ['letter' => 'B', 'min' => 66.68, 'max' => 70.84],
                ['letter' => 'C', 'min' => 62.51, 'max' => 66.67],
            ],
            'instructor' => [
                ['letter' => 'A', 'min' => 58.34, 'max' => 62.50],
                ['letter' => 'B', 'min' => 54.14, 'max' => 58.33],
                ['letter' => 'C', 'min' => 50.00, 'max' => 54.16],
            ],
        ];
        $summaryPointsTrack = null;
        $summaryPointsLetter = null;
        foreach (['full', 'associate', 'assistant', 'instructor'] as $rank) {
            foreach ($summaryRanges[$rank] as $band) {
                if ($summaryEqPercent >= $band['min'] && $summaryEqPercent <= $band['max']) {
                    $summaryPointsTrack = $rank;
                    $summaryPointsLetter = $band['letter'];
                    break 2;
                }
            }
        }
        $summaryPointsRankLabel = $summaryPointsTrack
            ? ($summaryRankLabels[$summaryPointsTrack] . ' - ' . $summaryPointsLetter)
            : '-';

        $summaryHasMasters = (bool) ($eligibility['hasMasters'] ?? false);
        $summaryHasDoctorate = (bool) ($eligibility['hasDoctorate'] ?? false);
        $summaryHasResearchEquivalent = (bool) ($eligibility['hasResearchEquivalent'] ?? false);
        $summaryHasAcceptedResearchOutput = (bool) ($eligibility['hasAcceptedResearchOutput'] ?? false);

        $summaryAllowedRankLabel = 'Not eligible';
        if ($summaryHasMasters && $summaryHasResearchEquivalent) {
            $summaryOrder = ['instructor' => 1, 'assistant' => 2, 'associate' => 3, 'full' => 4];
            $summaryDesired = $summaryPointsTrack ?: $trackKey;
            $summaryMaxAllowed = ($summaryHasDoctorate && $summaryHasAcceptedResearchOutput) ? 'full' : 'associate';
            if (($summaryOrder[$summaryDesired] ?? 0) > ($summaryOrder[$summaryMaxAllowed] ?? 0)) {
                $summaryDesired = $summaryMaxAllowed;
            }
            $summaryOneStepOrder = ($summaryOrder[$trackKey] ?? 1) + 1;
            $summaryOneStep = array_search($summaryOneStepOrder, $summaryOrder, true) ?: $trackKey;
            if (($summaryOrder[$summaryDesired] ?? 0) > ($summaryOrder[$summaryOneStep] ?? 0)) {
                $summaryDesired = $summaryOneStep;
            }
            $summaryAllowedLetter = $summaryPointsLetter;
            if ($summaryPointsTrack && $summaryPointsTrack !== $summaryDesired) {
                // If capped down from a higher points rank, use highest letter in the allowed rank.
                $summaryAllowedLetter = 'A';
            }
            $summaryAllowedRankLabel = ($summaryRankLabels[$summaryDesired] ?? 'Not eligible')
                . ($summaryAllowedLetter ? (' - ' . $summaryAllowedLetter) : '');
        }
    @endphp

    <div class="py-10 bg-bu-muted min-h-screen">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6 flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500">Current Stage</div>
                    <div class="text-lg font-semibold text-gray-800">{{ $statusLabel }}</div>
                    @if($hasPendingReturnRequest)
                        <div class="mt-1 text-xs text-amber-700">
                            Return request sent on {{ optional($application->faculty_return_requested_at)->format('M d, Y h:i A') }}.
                            @if(!empty($application->faculty_return_request_reason))
                                <span class="block mt-1">Reason: {{ $application->faculty_return_request_reason }}</span>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="text-sm text-gray-500">
                    {{ $isDraftHistoryMode ? 'Saved at:' : 'Submitted at:' }}
                    <span class="font-medium text-gray-700">
                        {{ optional($isDraftHistoryMode ? $application->updated_at : $application->submitted_at)->format('M d, Y') ?? 'Not set' }}
                    </span>
                </div>
            </div>

            {{-- MY INFORMATION --}}
            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6"
                 x-data="submittedSummary({
                    s1: {{ $sectionTotals['1'] }},
                    s2: {{ $sectionTotals['2'] }},
                    s3: {{ $sectionTotals['3'] }},
                    s4: {{ $sectionTotals['4'] }},
                    s5: {{ $sectionTotals['5'] }},
                    track: '{{ $trackKey }}',
                    trackLabel: @js($currentRank),
                    hasMasters: {{ ($eligibility['hasMasters'] ?? false) ? 'true' : 'false' }},
                    hasDoctorate: {{ ($eligibility['hasDoctorate'] ?? false) ? 'true' : 'false' }},
                    hasResearchEquivalent: {{ ($eligibility['hasResearchEquivalent'] ?? false) ? 'true' : 'false' }},
                    hasAcceptedResearchOutput: {{ ($eligibility['hasAcceptedResearchOutput'] ?? false) ? 'true' : 'false' }},
                 })">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">My Information</h3>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="space-y-3">
                        <div>
                            <div class="text-xs text-gray-500">Name</div>
                            <div class="text-sm font-semibold text-gray-800">{{ $application->faculty?->name ?? 'Faculty' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Date of Original Appointment</div>
                            <div class="text-sm font-semibold text-gray-800">{{ $appointmentDate ?? '"' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Total Years of Service (BU)</div>
                            <div class="text-sm font-semibold text-gray-800">
                                {{ $yearsService !== null ? (int) $yearsService . ' years' : '"' }}
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
                            <div class="text-sm font-semibold text-gray-800">{{ $currentRankLabel ?? 'Instructor' }}</div>
                        </div>
                        @if($approvedRankLabel !== '')
                            <div>
                                <div class="text-xs text-gray-500">Approved Rank</div>
                                <div class="text-sm font-semibold text-green-700">{{ $approvedRankLabel }}</div>
                            </div>
                        @endif
                        <div class="space-y-2 text-sm text-gray-700">
                            <div>
                                <div class="text-xs text-gray-500">Rank Based on Points</div>
                                <div class="font-semibold text-gray-800">{{ $summaryPointsRankLabel }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500">Allowed Rank (Rules Applied)</div>
                                <div class="font-semibold text-gray-800">{{ $summaryAllowedRankLabel }}</div>
                            </div>
                            <div class="text-xs text-gray-500">
                                Total points: <span class="font-semibold text-gray-800">{{ number_format((float) $summaryTotalPoints, 2) }}</span>
                                <span class="mx-2 text-gray-300">•</span>
                                Equivalent %: <span class="font-semibold text-gray-800">{{ number_format((float) $summaryEqPercent, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-xs text-gray-700 space-y-2">
                        <div class="font-semibold text-gray-800">Reminder</div>
                        <div>This summary is read-only.</div>
                        <div>If revisions are requested, you will be notified.</div>
                        <div class="text-gray-500">Section II points are not included yet.</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">Ranks and Equivalent Percentages</h3>
                    <p class="text-sm text-gray-500">Reference table used to determine A/B/C rank letter.</p>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm border rounded-lg overflow-hidden">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left">Track</th>
                                    <th class="px-4 py-3 text-left">A</th>
                                    <th class="px-4 py-3 text-left">B</th>
                                    <th class="px-4 py-3 text-left">C</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr>
                                    <td class="px-4 py-3 font-medium">Full Professor</td>
                                    <td class="px-4 py-3">95.87 - 100.00</td>
                                    <td class="px-4 py-3">91.50 - 95.86</td>
                                    <td class="px-4 py-3">87.53 - 91.49</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 font-medium">Associate Professor</td>
                                    <td class="px-4 py-3">83.34 - 87.52</td>
                                    <td class="px-4 py-3">79.19 - 83.33</td>
                                    <td class="px-4 py-3">75.02 - 79.18</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 font-medium">Assistant Professor</td>
                                    <td class="px-4 py-3">70.85 - 75.01</td>
                                    <td class="px-4 py-3">66.68 - 70.84</td>
                                    <td class="px-4 py-3">62.51 - 66.67</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 font-medium">Instructor</td>
                                    <td class="px-4 py-3">58.34 - 62.50</td>
                                    <td class="px-4 py-3">54.14 - 58.33</td>
                                    <td class="px-4 py-3">50.00 - 54.16</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @forelse($sections as $section)
                @if($section->section_code === '2')
                    @continue
                @endif
                @php
                    $sectionCode = (string) ($section->section_code ?? '');
                    $order = collect(['a1', 'a2', 'a3', 'a4', 'a5', 'a6', 'a7', 'a8', 'a9', 'b', 'c', 'b_prev', 'c_prev', 'd_prev', 'previous_points']);
                    $orderIndex = $order->flip();
                    $entries = $section->entries->groupBy('criterion_key');
                    $entryIsRemoved = function ($entry): bool {
                        $data = is_array($entry?->data) ? $entry->data : [];
                        return in_array(strtolower((string) ($data['is_removed'] ?? '')), ['1', 'true', 'yes', 'on'], true);
                    };
                    $activeEntriesFlat = collect($section->entries ?? [])->filter(fn ($entry) => !$entryIsRemoved($entry));
                    $scoresByKey = $activeEntriesFlat
                        ->groupBy('criterion_key')
                        ->map(fn ($rows) => (float) collect($rows)->sum(fn ($entry) => (float) ($entry->points ?? 0)));
                    $scoreFor = fn (string $key): float => (float) ($scoresByKey->get($key, 0));
                    $inputValueFor = function (string $key) use ($section): float {
                        $entry = collect($section->entries ?? [])->first(fn ($row) => (string) ($row->criterion_key ?? '') === $key);
                        if (!$entry) return 0;
                        $data = is_array($entry->data) ? $entry->data : [];
                        $raw = $data['value'] ?? null;
                        if (is_numeric($raw)) return (float) $raw;
                        if (in_array($key, ['b_prev', 'c_prev', 'd_prev', 'previous_points'], true)) {
                            return ((float) ($entry->points ?? 0)) * 3;
                        }
                        return is_numeric($entry->points ?? null) ? (float) $entry->points : 0;
                    };
                    $sectionMax = match($sectionCode) {
                        '1' => 140,
                        '2' => 120,
                        '3' => 70,
                        '4' => 40,
                        '5' => 30,
                        default => null,
                    };
                    $sectionPoints = (float) ($section->points_total ?? 0);

                    $s1RawA8 = $scoreFor('a8');
                    $s1RawA9 = $scoreFor('a9');
                    $s1RawA = $scoreFor('a1') + $scoreFor('a2') + $scoreFor('a3') + $scoreFor('a4') + $scoreFor('a5') + $scoreFor('a6') + $scoreFor('a7') + min($s1RawA8, 15) + min($s1RawA9, 10);
                    $s1BPrevThird = $inputValueFor('b_prev') / 3;
                    $s1CPrevThird = $inputValueFor('c_prev') / 3;
                    $s1RawB = $scoreFor('b') + $s1BPrevThird;
                    $s1RawC = $scoreFor('c') + $s1CPrevThird;
                    $s1CountedA = min($s1RawA, 140);
                    $s1CountedB = min($s1RawB, 20);
                    $s1CountedC = min($s1RawC, 20);
                    $s1RawTotal = $s1RawA + $s1RawB + $s1RawC;
                    $s1CountedTotal = min($s1CountedA + $s1CountedB + $s1CountedC, 140);

                    $s3CriteriaKeys = collect(['c1', 'c2', 'c3', 'c4', 'c5', 'c6', 'c7', 'c8', 'c9']);
                    $s3Subtotal = (float) $s3CriteriaKeys->sum(fn ($key) => $scoreFor($key));
                    $s3PrevThird = $inputValueFor('previous_points') / 3;
                    $s3RawTotal = $s3Subtotal + $s3PrevThird;
                    $s3Counted = min($s3RawTotal, 70);
                    $s3CriteriaMet = (int) $s3CriteriaKeys->filter(fn ($key) => $scoreFor($key) > 0)->count();

                    $s4A1 = $scoreFor('a1');
                    $s4A2 = $scoreFor('a2');
                    $s4Teaching = min($s4A1 + $s4A2, 40);
                    $s4Industry = min($scoreFor('b'), 20);
                    $s4TrackIsA = $s4Teaching >= $s4Industry;
                    $s4TrackLabel = $s4TrackIsA ? 'A. Teaching Experience' : 'B. Industry/Admin Experience';
                    $s4IsPartTime = (($application->faculty?->facultyProfile?->employment_type ?? $application->faculty?->employment_type ?? 'full_time') === 'part_time');
                    $s4ModeLabel = $s4IsPartTime ? 'Part-time (50%)' : 'Full-time (100%)';
                    $s4RawCounted = max($s4Teaching, $s4Industry);
                    $s4Final = min($s4RawCounted * ($s4IsPartTime ? 0.5 : 1), 40);

                    $s5ARaw = $scoreFor('a');
                    $s5ACapped = min($s5ARaw, 5);
                    $s5PrevBThird = $inputValueFor('b_prev') / 3;
                    $s5PrevCThird = $inputValueFor('c_prev') / 3;
                    $s5PrevDThird = $inputValueFor('d_prev') / 3;
                    $s5PrevThird = $inputValueFor('previous_points') / 3;
                    $s5BRaw = $scoreFor('b') + $s5PrevBThird;
                    $s5BCapped = min($s5BRaw, 10);
                    $s5C1Raw = $scoreFor('c1');
                    $s5C2Raw = $scoreFor('c2');
                    $s5C3Raw = $scoreFor('c3');
                    $s5C1Capped = min($s5C1Raw, 10);
                    $s5C2Capped = min($s5C2Raw, 5);
                    $s5C3Capped = min($s5C3Raw, 10);
                    $s5CRaw = $s5C1Raw + $s5C2Raw + $s5C3Raw + $s5PrevCThird;
                    $s5CCapped = min($s5C1Capped + $s5C2Capped + $s5C3Capped + $s5PrevCThird, 15);
                    $s5DRaw = $scoreFor('d') + $s5PrevDThird;
                    $s5DCapped = min($s5DRaw, 10);
                    $s5Subtotal = $s5ACapped + $s5BCapped + $s5CCapped + $s5DCapped;
                    $s5RawTotal = $s5Subtotal + $s5PrevThird;
                    $s5Counted = min($s5RawTotal, 30);

                    $summaryRaw = $sectionPoints;
                    $summaryCounted = $sectionPoints;
                    $summaryLimit = $sectionMax;
                    $summaryWithinLimit = is_null($sectionMax) ? true : ($sectionPoints <= $sectionMax);
                    if ($sectionCode === '1') {
                        $summaryRaw = $s1RawTotal;
                        $summaryCounted = $s1CountedTotal;
                        $summaryLimit = 140;
                        $summaryWithinLimit = $s1RawTotal <= 140;
                    } elseif ($sectionCode === '3') {
                        $summaryRaw = $s3RawTotal;
                        $summaryCounted = $s3Counted;
                        $summaryLimit = 70;
                        $summaryWithinLimit = $s3RawTotal <= 70;
                    } elseif ($sectionCode === '4') {
                        $summaryRaw = $s4Final;
                        $summaryCounted = $s4Final;
                        $summaryLimit = 40;
                        $summaryWithinLimit = $s4Final <= 40;
                    } elseif ($sectionCode === '5') {
                        $summaryRaw = $s5RawTotal;
                        $summaryCounted = $s5Counted;
                        $summaryLimit = 30;
                        $summaryWithinLimit = $s5RawTotal <= 30;
                    }
                @endphp

                <div class="bg-white rounded-2xl shadow-card border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">
                                Section {{ $section->section_code }}
                            </h3>
                            <p class="text-sm text-gray-500">{{ $section->title ?? '' }}</p>
                        </div>
                        <div class="text-sm font-semibold text-gray-700">
                            Score: {{ number_format((float) $section->points_total, 2) }}
                        </div>
                    </div>

                    <div class="px-6 py-4 border-b bg-slate-50/70 space-y-3">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <div class="text-sm font-semibold text-slate-800">Section {{ $sectionCode }} Score Summary</div>
                                <div class="mt-1 text-xs text-slate-600">
                                    @if($sectionCode === '4')
                                        Final: <span class="font-semibold text-slate-800">{{ number_format($summaryCounted, 2) }}</span>
                                        <span class="text-slate-400">/ {{ number_format((float) $summaryLimit, 2) }}</span>
                                        <span class="mx-1 text-slate-300">&middot;</span>
                                        Track: <span class="font-semibold text-slate-800">{{ $s4TrackLabel }}</span>
                                    @else
                                        Raw: <span class="font-semibold text-slate-800">{{ number_format($summaryRaw, 2) }}</span>
                                        <span class="text-slate-400">/ {{ number_format((float) $summaryLimit, 2) }}</span>
                                        <span class="mx-1 text-slate-300">&middot;</span>
                                        Counted: <span class="font-semibold text-slate-800">{{ number_format($summaryCounted, 2) }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-medium {{ $summaryWithinLimit ? 'border-green-200 bg-green-50 text-green-700' : 'border-red-200 bg-red-50 text-red-700' }}">
                                    {{ $summaryWithinLimit ? 'Within limit' : 'Over limit' }}
                                </span>
                                @if($sectionCode === '3')
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-medium {{ $s3CriteriaMet >= 1 ? 'border-green-200 bg-green-50 text-green-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">
                                        {{ $s3CriteriaMet >= 1 ? 'Minimum criteria met (1/1)' : 'Need at least 1 criterion' }}
                                    </span>
                                @endif
                                @if($sectionCode === '4')
                                    <span class="inline-flex items-center rounded-full border border-gray-200 bg-white px-2.5 py-1 text-[11px] font-medium text-gray-700">
                                        Counted track: <span class="ml-1 font-semibold">{{ $s4TrackLabel }}</span>
                                    </span>
                                    <span class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-[11px] font-medium text-blue-700">
                                        Scoring mode: <span class="ml-1 font-semibold">{{ $s4ModeLabel }}</span>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="rounded-xl border border-slate-200 bg-white px-3 py-2.5">
                                <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Section Score</div>
                                <div class="mt-1 text-sm font-semibold text-slate-900">{{ number_format($sectionPoints, 2) }}</div>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-white px-3 py-2.5">
                                <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Section Max</div>
                                <div class="mt-1 text-sm font-semibold text-slate-900">{{ $sectionMax !== null ? number_format((float) $sectionMax, 2) : '-' }}</div>
                            </div>
                        </div>

                        @if($sectionCode === '1')
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="text-xs text-gray-500">A. Academic Degree Earned</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ number_format($s1RawA, 2) }} <span class="text-sm text-gray-400">/ 140</span></div>
                                    <div class="text-xs text-gray-500">Counted: <span class="font-medium text-gray-700">{{ number_format($s1CountedA, 2) }}</span></div>
                                    <div class="mt-2 space-y-1 text-xs text-gray-500">
                                        <div class="flex items-center justify-between">
                                            <span>A8 Exams cap</span>
                                            <span><span class="font-medium text-gray-700">{{ number_format($s1RawA8, 2) }}</span> <span class="text-gray-400">/ 15</span></span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span>A9 Certifications cap</span>
                                            <span><span class="font-medium text-gray-700">{{ number_format($s1RawA9, 2) }}</span> <span class="text-gray-400">/ 10</span></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="flex items-center justify-between">
                                        <div class="text-xs text-gray-500">B. Specialized Training</div>
                                        <span class="text-[11px] px-2 py-0.5 rounded-full border bg-gray-50 text-gray-600">Max 20</span>
                                    </div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ number_format($s1RawB, 2) }} <span class="text-sm text-gray-400">/ 20</span></div>
                                    <div class="text-xs text-gray-500">Counted: <span class="font-medium text-gray-700">{{ number_format($s1CountedB, 2) }}</span></div>
                                    <div class="mt-1 text-xs text-gray-500">Previous (1/3): {{ number_format($s1BPrevThird, 2) }}</div>
                                </div>
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="flex items-center justify-between">
                                        <div class="text-xs text-gray-500">C. Seminars / Workshops</div>
                                        <span class="text-[11px] px-2 py-0.5 rounded-full border bg-gray-50 text-gray-600">Max 20</span>
                                    </div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ number_format($s1RawC, 2) }} <span class="text-sm text-gray-400">/ 20</span></div>
                                    <div class="text-xs text-gray-500">Counted: <span class="font-medium text-gray-700">{{ number_format($s1CountedC, 2) }}</span></div>
                                    <div class="mt-1 text-xs text-gray-500">Previous (1/3): {{ number_format($s1CPrevThird, 2) }}</div>
                                </div>
                            </div>
                            <div class="text-xs text-slate-600">
                                Raw total: <span class="font-semibold text-slate-800">{{ number_format($s1RawTotal, 2) }}</span>
                                <span class="mx-2 text-slate-300">&middot;</span>
                                Counted total: <span class="font-semibold text-slate-800">{{ number_format($s1CountedTotal, 2) }}</span>
                            </div>
                        @elseif($sectionCode === '3')
                            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="text-xs text-gray-500">Criteria Met</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ $s3CriteriaMet }} <span class="text-sm text-gray-400">/ 9</span></div>
                                    <div class="text-xs text-gray-500">Minimum required: {{ $s3CriteriaMet >= 1 ? '1/1 met' : '0/1' }}</div>
                                </div>
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="text-xs text-gray-500">Total (No Previous)</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ number_format($s3Subtotal, 2) }}</div>
                                </div>
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="text-xs text-gray-500">Previous Reclass (1/3)</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ number_format($s3PrevThird, 2) }}</div>
                                    <div class="text-xs text-gray-500">Input: {{ number_format($inputValueFor('previous_points'), 2) }}</div>
                                </div>
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="text-xs text-gray-500">Final</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ number_format($s3RawTotal, 2) }} <span class="text-sm text-gray-400">/ 70</span></div>
                                    <div class="text-xs text-gray-500">Counted: <span class="font-medium text-gray-700">{{ number_format($s3Counted, 2) }}</span></div>
                                </div>
                            </div>
                        @elseif($sectionCode === '4')
                            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="text-xs text-gray-500">A1 (Before BU)</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ number_format($s4A1, 2) }} <span class="text-sm text-gray-400">/ 20</span></div>
                                    <div class="text-xs text-gray-500">2 pts/year (capped)</div>
                                </div>
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="text-xs text-gray-500">A2 (After BU)</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ number_format($s4A2, 2) }} <span class="text-sm text-gray-400">/ 40</span></div>
                                    <div class="text-xs text-gray-500">3 pts/year (capped)</div>
                                </div>
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="text-xs text-gray-500">Teaching Total (A)</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ number_format($s4Teaching, 2) }} <span class="text-sm text-gray-400">/ 40</span></div>
                                    <div class="text-xs text-gray-500">A1 + A2, capped at 40</div>
                                </div>
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="text-xs text-gray-500">Industry/Admin (B)</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ number_format($s4Industry, 2) }} <span class="text-sm text-gray-400">/ 20</span></div>
                                    <div class="text-xs text-gray-500">2 pts/year (capped)</div>
                                </div>
                            </div>
                            <div class="text-xs text-slate-600">
                                Raw counted track: <span class="font-semibold text-slate-800">{{ number_format($s4RawCounted, 2) }}</span>
                                <span class="mx-2 text-slate-300">&middot;</span>
                                Deduction rate: <span class="font-semibold text-slate-800">{{ $s4IsPartTime ? '50%' : '100%' }}</span>
                                <span class="mx-2 text-slate-300">&middot;</span>
                                Final counted score: <span class="font-semibold text-slate-800">{{ number_format($s4Final, 2) }}</span>
                                <span class="text-slate-400">/ 40</span>
                            </div>
                        @elseif($sectionCode === '5')
                            <div class="grid grid-cols-1 sm:grid-cols-5 gap-3">
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="text-xs text-gray-500">A (cap 5)</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ number_format($s5ACapped, 2) }}</div>
                                    <div class="text-xs text-gray-500">Raw: {{ number_format($s5ARaw, 2) }}</div>
                                </div>
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="text-xs text-gray-500">B (cap 10)</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ number_format($s5BCapped, 2) }}</div>
                                    <div class="text-xs text-gray-500">Raw: {{ number_format($s5BRaw, 2) }} &middot; Prev 1/3: {{ number_format($s5PrevBThird, 2) }}</div>
                                </div>
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="text-xs text-gray-500">C (cap 15)</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ number_format($s5CCapped, 2) }}</div>
                                    <div class="text-xs text-gray-500">Raw: {{ number_format($s5CRaw, 2) }} &middot; Prev 1/3: {{ number_format($s5PrevCThird, 2) }}</div>
                                    <div class="mt-1 text-xs text-gray-500">C1: {{ number_format($s5C1Raw, 2) }} (cap 10) &middot; C2: {{ number_format($s5C2Raw, 2) }} (cap 5) &middot; C3: {{ number_format($s5C3Raw, 2) }} (cap 10)</div>
                                </div>
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="text-xs text-gray-500">D (cap 10)</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ number_format($s5DCapped, 2) }}</div>
                                    <div class="text-xs text-gray-500">Raw: {{ number_format($s5DRaw, 2) }} &middot; Prev 1/3: {{ number_format($s5PrevDThird, 2) }}</div>
                                </div>
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="text-xs text-gray-500">Section 5 Previous (1/3)</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ number_format($s5PrevThird, 2) }}</div>
                                </div>
                            </div>
                            <div class="text-xs text-slate-600">
                                Raw total: <span class="font-semibold text-slate-800">{{ number_format($s5RawTotal, 2) }}</span>
                                <span class="mx-2 text-slate-300">&middot;</span>
                                Counted total: <span class="font-semibold text-slate-800">{{ number_format($s5Counted, 2) }}</span>
                            </div>
                        @endif

                    </div>

                    <div class="p-6 space-y-6">
                        @if($section->entries->isEmpty())
                            <p class="text-sm text-gray-500">No entries submitted for this section.</p>
                        @else
                            @foreach($entries as $criterionKey => $rows)
                                @php
                                    $label = $criterionLabels[$section->section_code][$criterionKey]
                                        ?? ($rows->first()?->title ?? strtoupper($criterionKey));
                                    $rowsPoints = $rows->sum('points');
                                @endphp
                                @if($section->section_code === '4' && $criterionKey === 'b' && $rowsPoints <= 0)
                                    @continue
                                @endif
                                <div class="space-y-2">
                                    <div class="text-sm font-semibold text-gray-800">
                                        {{ $label }}
                                    </div>

                                    <div class="overflow-x-auto border rounded-xl">
                                        <table class="min-w-full text-sm">
                                            <thead class="bg-gray-50 text-gray-600">
                                                <tr>
                                                    <th class="px-4 py-2 text-left">Entry</th>
                                                    <th class="px-4 py-2 text-left">Details</th>
                                                    <th class="px-4 py-2 text-left">Evidence</th>
                                                    <th class="px-4 py-2 text-right">Points</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y">
                                                @foreach($rows as $entry)
                                                    @php
                                                        $data = is_array($entry->data) ? $entry->data : [];
                                                        $title = $entry->title ?: ($data['text'] ?? $data['title'] ?? 'Entry');
                                                        $evidences = $entry->evidences ?? collect();
                                                        $ignoredDetailKeys = [
                                                            'id',
                                                            'evidence',
                                                            'comments',
                                                            'is_removed',
                                                            'points',
                                                            'counted',
                                                            'removed_points_backup',
                                                            'removed_points_raw_backup',
                                                            'removed_at',
                                                            'removed_by',
                                                            'removed_source',
                                                            'removed_by_user_id',
                                                        ];
                                                        $detailRows = [];
                                                        foreach ($data as $key => $value) {
                                                            $keyString = (string) $key;
                                                            if (in_array($keyString, $ignoredDetailKeys, true)) {
                                                                continue;
                                                            }
                                                            if (str_ends_with($keyString, '_id')) {
                                                                continue;
                                                            }
                                                            if (is_array($value) || is_object($value)) {
                                                                continue;
                                                            }

                                                            $raw = is_null($value) ? '' : trim((string) $value);
                                                            if ($raw === '') {
                                                                continue;
                                                            }

                                                            $label = ucwords(str_replace(['_', '-'], ' ', $keyString));
                                                            $label = preg_replace('/\bBu\b/', 'BU', $label);

                                                            $display = $raw;
                                                            if (in_array(strtolower($raw), ['true', 'false'], true)) {
                                                                $display = strtolower($raw) === 'true' ? 'Yes' : 'No';
                                                            } elseif (str_contains($raw, '_') || str_contains($raw, '-')) {
                                                                $display = ucwords(str_replace(['_', '-'], ' ', $raw));
                                                            } elseif (ctype_lower($raw) && strlen($raw) <= 40 && !str_contains($raw, ' ')) {
                                                                $display = ucfirst($raw);
                                                            }

                                                            $detailRows[] = [
                                                                'label' => $label,
                                                                'value' => $display,
                                                            ];
                                                        }
                                                    @endphp
                                                    <tr>
                                                        <td class="px-4 py-2 font-medium text-gray-800">{{ $title }}</td>
                                                        <td class="px-4 py-2 text-gray-600">
                                                            @if(empty($detailRows))
                                                                <span class="text-gray-400">No details</span>
                                                            @else
                                                                <div class="space-y-1">
                                                                    @foreach($detailRows as $detail)
                                                                        <div>
                                                                            <span class="text-gray-400">{{ $detail['label'] }}:</span>
                                                                            <span class="text-gray-700">{{ $detail['value'] }}</span>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-2">
                                                            @if($evidences->isEmpty())
                                                                <span class="text-gray-400">None</span>
                                                            @else
                                                                <div class="space-y-2">
                                                                    @foreach($evidences as $ev)
                                                                        @php
                                                                            $url = $ev->disk ? \Illuminate\Support\Facades\Storage::disk($ev->disk)->url($ev->path) : null;
                                                                            $mime = strtolower((string) ($ev->mime_type ?? ''));
                                                                            $fileName = strtolower((string) ($ev->original_name ?? ''));
                                                                            $isImage = str_starts_with($mime, 'image/')
                                                                                || preg_match('/\.(jpg|jpeg|png|gif|webp|bmp|svg|tif|tiff|heic|heif)$/i', $fileName);
                                                                            $isPdf = $mime === 'application/pdf' || str_ends_with($fileName, '.pdf');
                                                                        @endphp
                                                                        <div class="rounded-lg border p-3">
                                                                            <div class="flex items-center justify-between gap-3">
                                                                                <div class="min-w-0 flex items-center gap-2">
                                                                                    <div class="shrink-0 h-8 w-8 rounded-md border border-gray-200 bg-gray-50 flex items-center justify-center overflow-hidden">
                                                                                        @if($isImage && $url)
                                                                                            <img src="{{ $url }}" alt="Evidence preview" class="h-full w-full object-cover">
                                                                                        @elseif($isPdf)
                                                                                            <span class="text-[10px] font-bold text-red-600">PDF</span>
                                                                                        @else
                                                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                                                                <path d="M4 3.5A1.5 1.5 0 015.5 2h6.879a1.5 1.5 0 011.06.44l2.121 2.12a1.5 1.5 0 01.44 1.061V16.5A1.5 1.5 0 0114.5 18h-9A1.5 1.5 0 014 16.5v-13z" />
                                                                                            </svg>
                                                                                        @endif
                                                                                    </div>
                                                                                    <div class="truncate font-medium text-gray-800">
                                                                                        {{ $ev->original_name ?? 'Evidence file' }}
                                                                                    </div>
                                                                                </div>
                                                                                <div class="shrink-0">
                                                                                    @if($url)
                                                                                        <button type="button"
                                                                                                class="js-evidence-preview-trigger inline-flex items-center rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                                                                                data-evidence-url="{{ $url }}"
                                                                                                data-evidence-name="{{ $ev->original_name ?? 'Evidence file' }}"
                                                                                                data-evidence-mime="{{ $ev->mime_type ?? '' }}">
                                                                                            Preview
                                                                                        </button>
                                                                                    @else
                                                                                        <span class="text-xs text-gray-400">Unavailable</span>
                                                                                    @endif
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-2 text-right font-semibold text-gray-800">
                                                            {{ number_format((float) $entry->points, 2) }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>

                @if($section->section_code === '1' && !empty($section2Review))
                    @php
                        $ratings = $section2Review['ratings'] ?? [];
                        $points = $section2Review['points'] ?? [];
                        $rDe = $ratings['dean'] ?? [];
                        $rCh = $ratings['chair'] ?? [];
                        $rSt = $ratings['student'] ?? [];
                    @endphp
                    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                        <div class="px-6 py-4 border-b">
                            <h3 class="text-lg font-semibold text-gray-800">Section II " Instructional Competence</h3>
                            <p class="text-sm text-gray-500">Ratings from Dean, Chair, and Students (read-only).</p>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                                <div class="rounded-xl border p-4">
                                    <div class="text-sm font-semibold text-gray-800">Dean Ratings</div>
                                    <div class="mt-2 space-y-1 text-sm text-gray-700">
                                        <div>Item 1: {{ $rDe['i1'] ?? '"' }}</div>
                                        <div>Item 2: {{ $rDe['i2'] ?? '"' }}</div>
                                        <div>Item 3: {{ $rDe['i3'] ?? '"' }}</div>
                                        <div>Item 4: {{ $rDe['i4'] ?? '"' }}</div>
                                    </div>
                                    <div class="mt-2 text-xs text-gray-500">Points: {{ number_format((float) ($points['dean'] ?? 0), 2) }}</div>
                                </div>

                                <div class="rounded-xl border p-4">
                                    <div class="text-sm font-semibold text-gray-800">Chair Ratings</div>
                                    <div class="mt-2 space-y-1 text-sm text-gray-700">
                                        <div>Item 1: {{ $rCh['i1'] ?? '"' }}</div>
                                        <div>Item 2: {{ $rCh['i2'] ?? '"' }}</div>
                                        <div>Item 3: {{ $rCh['i3'] ?? '"' }}</div>
                                        <div>Item 4: {{ $rCh['i4'] ?? '"' }}</div>
                                    </div>
                                    <div class="mt-2 text-xs text-gray-500">Points: {{ number_format((float) ($points['chair'] ?? 0), 2) }}</div>
                                </div>

                                <div class="rounded-xl border p-4">
                                    <div class="text-sm font-semibold text-gray-800">Student Ratings</div>
                                    <div class="mt-2 space-y-1 text-sm text-gray-700">
                                        <div>Item 1: {{ $rSt['i1'] ?? '"' }}</div>
                                        <div>Item 2: {{ $rSt['i2'] ?? '"' }}</div>
                                        <div>Item 3: {{ $rSt['i3'] ?? '"' }}</div>
                                        <div>Item 4: {{ $rSt['i4'] ?? '"' }}</div>
                                    </div>
                                    <div class="mt-2 text-xs text-gray-500">Points: {{ number_format((float) ($points['student'] ?? 0), 2) }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                                <div class="rounded-xl border p-4">
                                    <div class="text-xs text-gray-500">Weighted Total</div>
                                    <div class="text-lg font-semibold text-gray-800">{{ number_format((float) ($points['weighted'] ?? 0), 2) }}</div>
                                </div>
                                <div class="rounded-xl border p-4">
                                    <div class="text-xs text-gray-500">Previous Reclass (1/3)</div>
                                    <div class="text-lg font-semibold text-gray-800">{{ number_format((float) (($points['previous'] ?? 0) / 3), 2) }}</div>
                                </div>
                                <div class="rounded-xl border p-4">
                                    <div class="text-xs text-gray-500">Section II Total (Capped)</div>
                                    <div class="text-lg font-semibold text-gray-800">{{ number_format((float) ($points['total'] ?? 0), 2) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @empty
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                    <p class="text-sm text-gray-500">No sections found for this application.</p>
                </div>
            @endforelse

        </div>
    </div>

    @include('reclassification.partials.evidence-preview-modal')

    <script>
        function submittedSummary(init) {
            return {
                showRanks: false,
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
            };
        }
    </script>
</x-app-layout>

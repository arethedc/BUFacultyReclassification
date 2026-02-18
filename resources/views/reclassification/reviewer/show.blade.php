<x-app-layout>
    @php
        $sections = $application->sections->sortBy('section_code');
        $statusLabel = match($application->status) {
            'dean_review' => 'Dean Review',
            'hr_review' => 'HR Review',
            'vpaa_review' => 'VPAA Review',
            'vpaa_approved' => 'VPAA Approved',
            'president_review' => 'President Review',
            'returned_to_faculty' => 'Returned',
            'finalized' => 'Finalized',
            'rejected_final' => 'Rejected',
            default => ucfirst(str_replace('_',' ', $application->status)),
        };
        $sectionsByCode = $sections->keyBy('section_code');
        $sectionTotals = [
            '1' => (float) optional($sectionsByCode->get('1'))->points_total,
            '2' => (float) optional($sectionsByCode->get('2'))->points_total,
            '3' => (float) optional($sectionsByCode->get('3'))->points_total,
            '4' => (float) optional($sectionsByCode->get('4'))->points_total,
            '5' => (float) optional($sectionsByCode->get('5'))->points_total,
        ];
        $totalPoints = array_sum($sectionTotals);
        $eqPercent = $totalPoints / 4;
        $trackKey = match (strtolower(trim((string) ($currentRankLabel ?? 'Instructor')))) {
            'full professor', 'full' => 'full',
            'associate professor', 'associate' => 'associate',
            'assistant professor', 'assistant' => 'assistant',
            default => 'instructor',
        };
        $rankLabels = [
            'full' => 'Full Professor',
            'associate' => 'Associate Professor',
            'assistant' => 'Assistant Professor',
            'instructor' => 'Instructor',
        ];
        $ranges = [
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
        $pointsRankTrack = null;
        $pointsRankLetter = null;
        foreach (['full', 'associate', 'assistant', 'instructor'] as $rank) {
            foreach ($ranges[$rank] as $band) {
                if ($eqPercent >= $band['min'] && $eqPercent <= $band['max']) {
                    $pointsRankTrack = $rank;
                    $pointsRankLetter = $band['letter'];
                    break 2;
                }
            }
        }
        $pointsRankLabel = $pointsRankTrack
            ? ($rankLabels[$pointsRankTrack] . ' - ' . $pointsRankLetter)
            : '-';

        $hasMasters = (bool) ($eligibility['hasMasters'] ?? false);
        $hasDoctorate = (bool) ($eligibility['hasDoctorate'] ?? false);
        $hasResearchEquivalent = (bool) ($eligibility['hasResearchEquivalent'] ?? false);
        $hasAcceptedResearchOutput = (bool) ($eligibility['hasAcceptedResearchOutput'] ?? false);

        $allowedRankLabel = 'Not eligible';
        if ($hasMasters && $hasResearchEquivalent) {
            $order = ['instructor' => 1, 'assistant' => 2, 'associate' => 3, 'full' => 4];
            $desired = $pointsRankTrack ?: $trackKey;
            $maxAllowed = ($hasDoctorate && $hasAcceptedResearchOutput) ? 'full' : 'associate';
            if (($order[$desired] ?? 0) > ($order[$maxAllowed] ?? 0)) {
                $desired = $maxAllowed;
            }
            $oneStepOrder = ($order[$trackKey] ?? 1) + 1;
            $oneStep = array_search($oneStepOrder, $order, true) ?: $trackKey;
            if (($order[$desired] ?? 0) > ($order[$oneStep] ?? 0)) {
                $desired = $oneStep;
            }
            $allowedLetter = $pointsRankLetter;
            if ($pointsRankTrack && $pointsRankTrack !== $desired) {
                // If capped down from a higher points rank, use highest letter in the allowed rank.
                $allowedLetter = 'A';
            }
            $allowedRankLabel = ($rankLabels[$desired] ?? 'Not eligible')
                . ($allowedLetter ? (' - ' . $allowedLetter) : '');
        }
        $criterionLabels = [
            '1' => [
                'a1' => 'A1. Bachelor’s Degree (Latin honors)',
                'a2' => 'A2. Additional Bachelor’s Degree',
                'a3' => 'A3. Master’s Degree',
                'a4' => 'A4. Master’s Degree Units',
                'a5' => 'A5. Additional Master’s Degree',
                'a6' => 'A6. Doctoral Units',
                'a7' => 'A7. Doctor’s Degree',
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
        $section1Ranges = [
            'speaker' => [
                'international' => [13, 15],
                'national' => [11, 12],
                'regional' => [9, 10],
                'provincial' => [7, 8],
                'municipal' => [4, 6],
                'school' => [1, 3],
            ],
            'resource' => [
                'international' => [11, 12],
                'national' => [9, 10],
                'regional' => [7, 8],
                'provincial' => [5, 6],
                'municipal' => [3, 4],
                'school' => [1, 2],
            ],
            'participant' => [
                'international' => [9, 10],
                'national' => [7, 8],
                'regional' => [5, 6],
                'provincial' => [3, 4],
                'municipal' => [2, 2],
                'school' => [1, 1],
            ],
        ];
        $reviewerRole = strtolower((string) (auth()->user()->role ?? ''));
        $canCreateMoveRequest = in_array($reviewerRole, ['dean', 'hr', 'vpaa'], true);
        $moveTargetOptions = collect($criterionLabels)
            ->except(['2'])
            ->map(function ($criteria, $sectionCode) {
                return collect($criteria)
                    ->reject(fn ($label, $criterionKey) => str_ends_with((string) $criterionKey, '_prev') || $criterionKey === 'previous_points')
                    ->map(fn ($label, $criterionKey) => [
                        'section_code' => (string) $sectionCode,
                        'criterion_key' => (string) $criterionKey,
                        'label' => "Section {$sectionCode} - {$label}",
                    ])
                    ->values();
            })
            ->flatten(1)
            ->values();
    @endphp

    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800">Reclassification Review</h2>
                <p class="text-sm text-gray-500">
                    {{ $application->faculty?->name ?? 'Faculty' }} • {{ $statusLabel }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('reclassification.review.queue') }}"
                   class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50">
                    Back to Queue
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-10 bg-bu-muted min-h-screen">
        <div id="reviewer-content" class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div>
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6 flex items-center justify-between">
                    <div>
                        <div class="text-sm text-gray-500">Status</div>
                        <div class="text-lg font-semibold text-gray-800">{{ $statusLabel }}</div>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        @php
                            $currentRole = strtolower((string) (auth()->user()->role ?? ''));
                            $nextLabel = match($application->status) {
                                'dean_review' => 'Forward to HR',
                                'hr_review' => 'Forward to VPAA',
                                'vpaa_review' => 'Approve to VPAA List',
                                'president_review' => 'Use Approved List',
                                default => 'Forward',
                            };
                            $canForwardPerPaper = in_array($application->status, ['dean_review','hr_review','vpaa_review'], true);
                            $section2 = $application->sections->firstWhere('section_code', '2');
                            $section2Complete = (bool) ($section2?->is_complete);
                            $section2Blocked = $currentRole === 'dean'
                                && (string) $application->status === 'dean_review'
                                && !$section2Complete;
                            $openRequiredCommentEntries = $application->sections
                                ->flatMap(function ($sec) {
                                    return ($sec->entries ?? collect())
                                        ->map(function ($entry) use ($sec) {
                                            $openRequiredCount = ($entry->rowComments ?? collect())
                                                ->filter(function ($comment) {
                                                    if ((string) ($comment->visibility ?? '') !== 'faculty_visible') {
                                                        return false;
                                                    }
                                                    if (!is_null($comment->parent_id ?? null)) {
                                                        return false;
                                                    }
                                                    if ((string) ($comment->status ?? 'open') !== 'open') {
                                                        return false;
                                                    }
                                                    return (string) ($comment->action_type ?? 'requires_action') === 'requires_action';
                                                })
                                                ->count();

                                            if ($openRequiredCount < 1) {
                                                return null;
                                            }

                                            return [
                                                'entry_id' => (int) $entry->id,
                                                'section_code' => (string) ($sec->section_code ?? ''),
                                                'criterion_key' => strtoupper((string) ($entry->criterion_key ?? '')),
                                                'count' => $openRequiredCount,
                                            ];
                                        })
                                        ->filter()
                                        ->values();
                                })
                                ->values();
                            $openRequiredCommentsCount = (int) $openRequiredCommentEntries->sum('count');
                            $pendingActionMoveRequestsCount = collect($application->moveRequests ?? [])
                                ->where('status', 'pending')
                                ->count();
                            $returnBlocked = $canForwardPerPaper
                                && (($openRequiredCommentsCount + $pendingActionMoveRequestsCount) < 1);
                            $forwardBlocked = $canForwardPerPaper && ($openRequiredCommentsCount > 0 || $section2Blocked);
                        @endphp
                        @if($canForwardPerPaper)
                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('reclassification.return', $application) }}">
                                    @csrf
                                    <button type="submit"
                                            @disabled($returnBlocked)
                                            class="px-4 py-2 rounded-xl border border-amber-200 bg-amber-50 text-amber-700 text-sm font-semibold {{ $returnBlocked ? 'opacity-60 cursor-not-allowed' : '' }}">
                                        Return to Faculty
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('reclassification.forward', $application) }}">
                                    @csrf
                                    <button type="submit"
                                            @disabled($forwardBlocked)
                                            class="px-4 py-2 rounded-xl bg-bu text-white text-sm font-semibold shadow-soft {{ $forwardBlocked ? 'opacity-60 cursor-not-allowed' : '' }}">
                                        {{ $nextLabel }}
                                    </button>
                                </form>
                            </div>
                            @if($forwardBlocked)
                                <div class="w-full text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                                    @if($section2Blocked)
                                        <div>Forward blocked: Section II (Dean Input) is not yet completed.</div>
                                        <div class="mt-1">
                                            <a href="#section-2-dean-input"
                                               class="inline-flex items-center px-2 py-0.5 rounded border border-amber-300 bg-white hover:bg-amber-100">
                                                Go to Section II
                                            </a>
                                        </div>
                                    @endif
                                    @if($openRequiredCommentsCount > 0)
                                        <div class="{{ $section2Blocked ? 'mt-2' : '' }}">
                                            {{ $openRequiredCommentsCount === 1
                                                ? 'Forward blocked: 1 action-required comment is still open.'
                                                : "Forward blocked: {$openRequiredCommentsCount} action-required comments are still open." }}
                                            Use Return to Faculty.
                                        </div>
                                    @endif
                                    @if($openRequiredCommentEntries->isNotEmpty())
                                        <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                            <span class="font-medium">Jump to:</span>
                                            @foreach($openRequiredCommentEntries->take(6) as $target)
                                                <a href="#entry-comments-{{ $target['entry_id'] }}"
                                                   class="inline-flex items-center px-2 py-0.5 rounded border border-amber-300 bg-white hover:bg-amber-100">
                                                    S{{ $target['section_code'] }} / {{ $target['criterion_key'] }} ({{ $target['count'] }})
                                                </a>
                                            @endforeach
                                            @if($openRequiredCommentEntries->count() > 6)
                                                <span class="text-amber-700">+{{ $openRequiredCommentEntries->count() - 6 }} more</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endif
                            @if($returnBlocked)
                                <div class="w-full text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                                    Return blocked: add at least one action-required comment or move request first.
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            @php
                $activeMoveRequests = ($application->moveRequests ?? collect())
                    ->whereIn('status', ['pending', 'addressed'])
                    ->values();
            @endphp
            @if($activeMoveRequests->isNotEmpty())
                <div class="bg-white rounded-2xl shadow-card border border-indigo-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Move Requests</h3>
                    <div class="space-y-2">
                        @foreach($activeMoveRequests as $move)
                            @php
                                $mvStatus = (string) ($move->status ?? 'pending');
                                $mvStatusClass = $mvStatus === 'addressed'
                                    ? 'bg-blue-50 text-blue-700 border-blue-200'
                                    : 'bg-amber-50 text-amber-700 border-amber-200';
                                $mvStatusLabel = $mvStatus === 'addressed' ? 'Addressed by faculty' : 'Pending';
                            @endphp
                            <div class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 flex flex-wrap items-center justify-between gap-3">
                                <div class="text-sm text-indigo-900">
                                    Section {{ $move->source_section_code }} / {{ strtoupper($move->source_criterion_key) }}
                                    &rarr; Section {{ $move->target_section_code }} / {{ strtoupper($move->target_criterion_key) }}
                                    @if($move->note)
                                        <div class="text-xs text-indigo-800 mt-1">{{ $move->note }}</div>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[11px] {{ $mvStatusClass }}">
                                        {{ $mvStatusLabel }}
                                    </span>
                                    @if($mvStatus !== 'resolved')
                                        <form method="POST"
                                              action="{{ route('reclassification.move-requests.destroy', $move) }}"
                                              data-async-action
                                              data-async-refresh-target="#reviewer-content"
                                              data-loading-text="Removing..."
                                              data-loading-message="Removing move request..."
                                              data-confirm="Remove this move request?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="px-3 py-1.5 rounded-lg border border-red-200 bg-red-50 text-xs font-semibold text-red-700 hover:bg-red-100">
                                                Remove
                                            </button>
                                        </form>
                                    @endif
                                    @if($mvStatus === 'addressed')
                                        <form method="POST"
                                              action="{{ route('reclassification.move-requests.resolve', $move) }}"
                                              data-async-action
                                              data-async-refresh-target="#reviewer-content"
                                              data-loading-text="Saving..."
                                              data-loading-message="Resolving move request...">
                                            @csrf
                                            <button type="submit"
                                                    class="px-3 py-1.5 rounded-lg border border-green-200 bg-green-50 text-xs font-semibold text-green-700 hover:bg-green-100">
                                                Mark Resolved
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Faculty Information</h3>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="space-y-3">
                        <div>
                            <div class="text-xs text-gray-500">Name</div>
                            <div class="text-sm font-semibold text-gray-800">{{ $application->faculty?->name ?? 'Faculty' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Department</div>
                            <div class="text-sm font-semibold text-gray-800">{{ $application->faculty?->department?->name ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Date of Original Appointment</div>
                            <div class="text-sm font-semibold text-gray-800">{{ $appointmentDate ?? '—' }}</div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <div class="text-xs text-gray-500">Total Years of Service (BU)</div>
                            <div class="text-sm font-semibold text-gray-800">
                                {{ $yearsService !== null ? (int) $yearsService . ' years' : '-' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Current Teaching Rank</div>
                            <div class="text-sm font-semibold text-gray-800">{{ $currentRankLabel ?? 'Instructor' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Rank Based on Points</div>
                            <div class="text-sm font-semibold text-gray-800">{{ $pointsRankLabel }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Allowed Rank (Rules Applied)</div>
                            <div class="text-sm font-semibold text-gray-800">{{ $allowedRankLabel }}</div>
                        </div>
                        <div class="text-xs text-gray-500">
                            Total points: <span class="font-semibold text-gray-800">{{ number_format((float) $totalPoints, 2) }}</span>
                            <span class="mx-2 text-gray-300">&middot;</span>
                            Equivalent %: <span class="font-semibold text-gray-800">{{ number_format((float) $eqPercent, 2) }}</span>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-xs text-gray-700 space-y-2">
                        <div class="font-semibold text-gray-800">Reviewer</div>
                        <div>Name: {{ auth()->user()->name ?? 'Reviewer' }}</div>
                        <div>Role: {{ $reviewerRole ?? 'Reviewer' }}</div>
                        <div>Department: {{ $reviewerDept ?? '—' }}</div>
                    </div>
                </div>
            </div>

            @foreach($sections as $section)
                @if($section->section_code === '2')
                    @continue
                @endif
                @php
                    $entries = $section->entries->groupBy('criterion_key');
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

                    <div class="p-6 space-y-6">
                        @if($section->entries->isEmpty())
                            <p class="text-sm text-gray-500">No entries submitted for this section.</p>
                        @else
                            @foreach($entries as $criterionKey => $rows)
                                @php
                                    $label = $criterionLabels[$section->section_code][$criterionKey]
                                        ?? ($rows->first()?->title ?? strtoupper($criterionKey));
                                @endphp
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
                                                        $isRemoved = in_array(strtolower((string) ($data['is_removed'] ?? '')), ['1', 'true', 'yes', 'on'], true);
                                                        $title = $entry->title ?: ($data['text'] ?? $data['title'] ?? 'Entry');
                                                        $evidences = $entry->evidences ?? collect();
                                                        $rowComments = $entry->rowComments ?? collect();
                                                    @endphp
                                                    <tr class="{{ $isRemoved ? 'bg-gray-100/70' : '' }}">
                                                        <td class="px-4 py-2 font-medium {{ $isRemoved ? 'text-gray-500' : 'text-gray-800' }}">
                                                            <div class="flex items-center gap-2">
                                                                <span>{{ $title }}</span>
                                                                @if($isRemoved)
                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full border border-gray-300 bg-gray-200 text-[10px] uppercase tracking-wide text-gray-700">
                                                                        Removed by faculty
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-2 text-gray-600">
                                                            <div class="space-y-1">
                                                                @foreach($data as $key => $value)
                                                                    @if(in_array((string) $key, ['evidence', 'id', 'is_removed', 'points', 'counted', 'comments'], true))
                                                                        @continue
                                                                    @endif
                                                                    <div>
                                                                        <span class="text-gray-400">{{ ucfirst(str_replace('_',' ', $key)) }}:</span>
                                                                        <span class="text-gray-700">{{ is_array($value) ? json_encode($value) : $value }}</span>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-2">
                                                            @if($evidences->isEmpty())
                                                                <span class="text-gray-400">None</span>
                                                            @else
                                                                <div class="space-y-2">
                                                                    @foreach($evidences as $ev)
                                                                        @php
                                                                            $url = $ev->disk ? \Illuminate\Support\Facades\Storage::disk($ev->disk)->url($ev->path) : null;
                                                                        @endphp
                                                                        <div class="rounded-lg border p-3">
                                                                            <div class="flex items-center justify-between gap-3">
                                                                                <div class="min-w-0">
                                                                                    <div class="truncate font-medium text-gray-800">
                                                                                        {{ $ev->original_name ?? 'Evidence file' }}
                                                                                    </div>
                                                                                    <div class="text-xs text-gray-500">ID #{{ $ev->id }}</div>
                                                                                </div>
                                                                                <div class="shrink-0">
                                                                                    @if($url)
                                                                                        <a href="{{ $url }}"
                                                                                           target="_blank"
                                                                                           class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                                                                            View
                                                                                        </a>
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
                                                        <td class="px-4 py-2 text-right">
                                                            @if($isRemoved)
                                                                <div class="font-semibold text-gray-500">0.00</div>
                                                            @elseif($section->section_code === '1' && $criterionKey === 'c')
                                                                @php
                                                                    $roleKey = $data['role'] ?? null;
                                                                    $levelKey = $data['level'] ?? null;
                                                                    $range = $section1Ranges[$roleKey][$levelKey] ?? null;
                                                                @endphp
                                                                @if($canEditSection1C && $range)
                                                                    <form method="POST"
                                                                          action="{{ route($section1cUpdateRoute, [$application, $entry]) }}"
                                                                          class="flex items-center justify-end gap-2">
                                                                        @csrf
                                                                        <input type="number"
                                                                               name="points"
                                                                               min="{{ $range[0] }}"
                                                                               max="{{ $range[1] }}"
                                                                               step="1"
                                                                               value="{{ (float) $entry->points }}"
                                                                               class="w-20 rounded border-gray-300 text-right text-sm">
                                                                        <button type="submit"
                                                                                class="px-2 py-1 rounded-lg border text-xs text-gray-700 hover:bg-gray-50">
                                                                            Update
                                                                        </button>
                                                                    </form>
                                                                    <div class="mt-1 text-[11px] text-gray-500 text-right">
                                                                        Range: {{ $range[0] }}-{{ $range[1] }}
                                                                    </div>
                                                                @else
                                                                    <div class="font-semibold text-gray-800">
                                                                        {{ number_format((float) $entry->points, 2) }}
                                                                    </div>
                                                                    @if($range)
                                                                        <div class="mt-1 text-[11px] text-gray-500 text-right">
                                                                            Range: {{ $range[0] }}-{{ $range[1] }}
                                                                        </div>
                                                                    @endif
                                                                @endif
                                                            @else
                                                                <div class="font-semibold text-gray-800">
                                                                    {{ number_format((float) $entry->points, 2) }}
                                                                </div>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @php
                                                        $rowMoveRequests = ($application->moveRequests ?? collect())
                                                            ->whereIn('status', ['pending', 'addressed'])
                                                            ->where('source_section_code', (string) $section->section_code)
                                                            ->where('source_criterion_key', (string) $criterionKey);
                                                    @endphp
                                                    <tr class="bg-gray-50/50">
                                                        <td colspan="4" class="px-4 py-3">
                                                            <div id="entry-comments-{{ $entry->id }}" x-data="{ actionType: '' }" class="space-y-3 scroll-mt-28">
                                                                <div>
                                                                    <div class="text-xs font-semibold text-gray-700">Reviewer Comments</div>
                                                                    @php
                                                                        $rootComments = $rowComments
                                                                            ->whereNull('parent_id')
                                                                            ->sortBy('created_at')
                                                                            ->values();
                                                                    @endphp
                                                                    @if($rootComments->isEmpty())
                                                                        <div class="text-xs text-gray-500 mt-1">No comments yet.</div>
                                                                    @else
                                                                        <div class="mt-2 space-y-2">
                                                                            @foreach($rootComments as $comment)
                                                                                @php
                                                                                    $visibilityClass = $comment->visibility === 'faculty_visible'
                                                                                        ? 'bg-green-50 text-green-700 border-green-200'
                                                                                        : 'bg-gray-50 text-gray-600 border-gray-200';
                                                                                    $visibilityLabel = $comment->visibility === 'faculty_visible'
                                                                                        ? 'Visible to faculty'
                                                                                        : 'Internal';
                                                                                    $commentActionType = (string) ($comment->action_type ?? 'requires_action');
                                                                                    $commentActionClass = $commentActionType === 'info'
                                                                                        ? 'bg-slate-50 text-slate-700 border-slate-200'
                                                                                        : 'bg-amber-50 text-amber-700 border-amber-200';
                                                                                    $commentActionLabel = $commentActionType === 'info'
                                                                                        ? 'No action required'
                                                                                        : 'Action required';
                                                                                    $status = $comment->status ?? 'open';
                                                                                    if ($commentActionType === 'info') {
                                                                                        $statusClass = 'bg-slate-50 text-slate-700 border-slate-200';
                                                                                        $statusLabel = 'FYI';
                                                                                    } else {
                                                                                        $statusClass = match($status) {
                                                                                            'resolved' => 'bg-green-50 text-green-700 border-green-200',
                                                                                            'addressed' => 'bg-blue-50 text-blue-700 border-blue-200',
                                                                                            default => 'bg-amber-50 text-amber-700 border-amber-200',
                                                                                        };
                                                                                        $statusLabel = match($status) {
                                                                                            'resolved' => 'Resolved',
                                                                                            'addressed' => 'Addressed',
                                                                                            default => 'Open',
                                                                                        };
                                                                                    }
                                                                                    $replies = $rowComments
                                                                                        ->where('parent_id', $comment->id)
                                                                                        ->sortBy('created_at')
                                                                                        ->values();
                                                                                @endphp
                                                                                <div class="rounded-lg border bg-white px-3 py-2 text-xs">
                                                                                    <div class="flex items-center justify-between gap-2">
                                                                                        <div class="font-medium text-gray-800">
                                                                                            {{ $comment->author?->name ?? 'Reviewer' }}
                                                                                        </div>
                                                                                        <div class="flex items-center gap-2">
                                                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] border {{ $visibilityClass }}">
                                                                                                {{ $visibilityLabel }}
                                                                                            </span>
                                                                                            @if($comment->visibility === 'faculty_visible')
                                                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] border {{ $commentActionClass }}">
                                                                                                    {{ $commentActionLabel }}
                                                                                                </span>
                                                                                            @endif
                                                                                            @if(($comment->status ?? 'open') !== 'resolved')
                                                                                                <form method="POST"
                                                                                                      action="{{ route('reclassification.row-comments.destroy', $comment) }}"
                                                                                                      data-async-action
                                                                                                      data-async-refresh-target="#reviewer-content"
                                                                                                      data-loading-text="Removing..."
                                                                                                      data-loading-message="Removing comment..."
                                                                                                      data-confirm="Remove this comment thread?">
                                                                                                    @csrf
                                                                                                    @method('DELETE')
                                                                                                    <button type="submit"
                                                                                                            class="px-2 py-0.5 rounded border border-red-200 bg-red-50 text-[10px] font-semibold text-red-700 hover:bg-red-100">
                                                                                                        Remove
                                                                                                    </button>
                                                                                                </form>
                                                                                            @endif
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="mt-1 flex items-center gap-2">
                                                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] border {{ $statusClass }}">
                                                                                            {{ $statusLabel }}
                                                                                        </span>
                                                                                        @if($comment->resolved_at)
                                                                                            <span class="text-[10px] text-gray-400">
                                                                                                {{ optional($comment->resolved_at)->format('M d, Y g:i A') }}
                                                                                            </span>
                                                                                        @endif
                                                                                    </div>
                                                                                    <div class="mt-1 text-gray-700">{{ $comment->body }}</div>
                                                                                    <div class="mt-1 text-[10px] text-gray-400">
                                                                                        {{ optional($comment->created_at)->format('M d, Y g:i A') }}
                                                                                    </div>

                                                                                    @if($replies->isNotEmpty())
                                                                                        <div class="mt-2 rounded-md border border-gray-200 bg-gray-50 p-2 space-y-1">
                                                                                            @foreach($replies as $reply)
                                                                                                <div class="text-[11px] text-gray-700">
                                                                                                    <span class="font-semibold">{{ $reply->author?->name ?? 'Faculty' }}:</span>
                                                                                                    {{ $reply->body }}
                                                                                                </div>
                                                                                            @endforeach
                                                                                        </div>
                                                                                    @endif

                                                                                    @if(
                                                                                        $comment->visibility === 'faculty_visible'
                                                                                        && ($comment->action_type ?? 'requires_action') === 'requires_action'
                                                                                        && $comment->status === 'addressed'
                                                                                    )
                                                                                        <div class="mt-2 flex justify-end">
                                                                                            <form method="POST"
                                                                                                  action="{{ route('reclassification.row-comments.resolve', $comment) }}"
                                                                                                  data-async-action
                                                                                                  data-async-refresh-target="#reviewer-content"
                                                                                                  data-loading-text="Saving..."
                                                                                                  data-loading-message="Resolving comment...">
                                                                                                @csrf
                                                                                                <button type="submit"
                                                                                                        class="px-2.5 py-1 rounded-lg border border-green-200 bg-green-50 text-[11px] font-semibold text-green-700 hover:bg-green-100">
                                                                                                    Mark Resolved
                                                                                                </button>
                                                                                            </form>
                                                                                        </div>
                                                                                    @endif
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                    @endif
                                                                </div>

                                                                @if($isRemoved)
                                                                    <div class="rounded-lg border border-gray-300 bg-gray-100 px-3 py-2 text-xs text-gray-700">
                                                                        This entry was removed by faculty. New comments and move requests are disabled.
                                                                    </div>
                                                                @else
                                                                    <div class="rounded-lg border border-gray-200 bg-white px-3 py-2">
                                                                        <label class="text-xs font-semibold text-gray-700">Add action</label>
                                                                        <select x-model="actionType" class="mt-1 w-full rounded-lg border-gray-300 text-xs">
                                                                            <option value="">Select action</option>
                                                                            <option value="comment">Comment</option>
                                                                            @if($canCreateMoveRequest)
                                                                                <option value="move">Move request</option>
                                                                            @endif
                                                                        </select>
                                                                    </div>
                                                                @endif

                                                                <form x-show="!{{ $isRemoved ? 'true' : 'false' }} && actionType === 'comment'"
                                                                      x-cloak
                                                                      method="POST"
                                                                      action="{{ route('reclassification.row-comments.store', [$application, $entry]) }}"
                                                                      x-data="{ visibility: '', commentType: '' }"
                                                                      data-async-action
                                                                      data-async-refresh-target="#reviewer-content"
                                                                      data-loading-text="Saving..."
                                                                      data-loading-message="Saving comment..."
                                                                      class="grid grid-cols-1 md:grid-cols-7 gap-3">
                                                                    @csrf
                                                                    <div class="md:col-span-4">
                                                                        <label class="text-xs text-gray-600">Add comment</label>
                                                                        <textarea name="body" rows="2" required
                                                                                  class="mt-1 w-full rounded-lg border-gray-300 text-xs"
                                                                                  placeholder="Leave a note for the faculty..."></textarea>
                                                                    </div>
                                                                    <div>
                                                                        <label class="text-xs text-gray-600">Visibility</label>
                                                                        <select name="visibility"
                                                                                x-model="visibility"
                                                                                required
                                                                                class="mt-1 w-full rounded-lg border-gray-300 text-xs">
                                                                            <option value="">Select visibility</option>
                                                                            <option value="faculty_visible">Visible to faculty</option>
                                                                            <option value="internal">Internal</option>
                                                                        </select>
                                                                    </div>
                                                                    <template x-if="visibility === 'faculty_visible'">
                                                                        <div>
                                                                            <label class="text-xs text-gray-600">Type</label>
                                                                            <select name="action_type"
                                                                                    x-model="commentType"
                                                                                    required
                                                                                    class="mt-1 w-full rounded-lg border-gray-300 text-xs">
                                                                                <option value="">Select type</option>
                                                                                <option value="requires_action">Action required</option>
                                                                                <option value="info">No action required (FYI)</option>
                                                                            </select>
                                                                        </div>
                                                                    </template>
                                                                    <template x-if="visibility === 'internal'">
                                                                        <input type="hidden" name="action_type" value="info">
                                                                    </template>
                                                                    <div class="flex items-end">
                                                                        <button type="submit"
                                                                                class="w-full px-3 py-2 rounded-lg bg-bu text-white text-xs font-semibold">
                                                                            Save Comment
                                                                        </button>
                                                                    </div>
                                                                </form>

                                                                @if($canCreateMoveRequest)
                                                                    <div class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-3 space-y-3">
                                                                        <div>
                                                                            <div class="text-xs font-semibold text-indigo-800">Request move to another criterion</div>
                                                                            <div class="text-xs text-indigo-700 mt-1">
                                                                                Use when this entry's evidence fits a different criterion. Faculty will revise after return.
                                                                            </div>
                                                                        </div>

                                                                        @if($rowMoveRequests->isNotEmpty())
                                                                            <div class="space-y-1">
                                                                                @foreach($rowMoveRequests as $move)
                                                                                    @php
                                                                                        $mvStatus = (string) ($move->status ?? 'pending');
                                                                                        $mvStatusClass = $mvStatus === 'addressed'
                                                                                            ? 'bg-blue-50 text-blue-700 border-blue-200'
                                                                                            : 'bg-amber-50 text-amber-700 border-amber-200';
                                                                                        $mvStatusLabel = $mvStatus === 'addressed' ? 'Addressed' : 'Pending';
                                                                                    @endphp
                                                                                    <div class="rounded border border-indigo-200 bg-white px-2 py-1.5 text-[11px] text-indigo-900">
                                                                                        <div class="flex items-center justify-between gap-2">
                                                                                            <div>
                                                                                                Move: Section {{ $move->source_section_code }} / {{ strtoupper($move->source_criterion_key) }}
                                                                                                &rarr; Section {{ $move->target_section_code }} / {{ strtoupper($move->target_criterion_key) }}
                                                                                            </div>
                                                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full border {{ $mvStatusClass }}">
                                                                                                {{ $mvStatusLabel }}
                                                                                            </span>
                                                                                        </div>
                                                                                        @if($move->note)
                                                                                            <div class="mt-1">{{ $move->note }}</div>
                                                                                        @endif
                                                                                        @if($move->status !== 'resolved')
                                                                                            <div class="mt-2 flex justify-end gap-2">
                                                                                                <form method="POST"
                                                                                                      action="{{ route('reclassification.move-requests.destroy', $move) }}"
                                                                                                      data-async-action
                                                                                                      data-async-refresh-target="#reviewer-content"
                                                                                                      data-loading-text="Removing..."
                                                                                                      data-loading-message="Removing move request..."
                                                                                                      data-confirm="Remove this move request?">
                                                                                                    @csrf
                                                                                                    @method('DELETE')
                                                                                                    <button type="submit"
                                                                                                            class="px-2.5 py-1 rounded border border-red-200 bg-red-50 text-red-700 font-semibold hover:bg-red-100">
                                                                                                        Remove
                                                                                                    </button>
                                                                                                </form>
                                                                                                @if($move->status === 'addressed')
                                                                                                <form method="POST"
                                                                                                      action="{{ route('reclassification.move-requests.resolve', $move) }}"
                                                                                                      data-async-action
                                                                                                      data-async-refresh-target="#reviewer-content"
                                                                                                      data-loading-text="Saving..."
                                                                                                      data-loading-message="Resolving move request...">
                                                                                                    @csrf
                                                                                                    <button type="submit"
                                                                                                            class="px-2.5 py-1 rounded border border-green-200 bg-green-50 text-green-700 font-semibold hover:bg-green-100">
                                                                                                        Mark Resolved
                                                                                                    </button>
                                                                                                </form>
                                                                                                @endif
                                                                                            </div>
                                                                                        @endif
                                                                                    </div>
                                                                                @endforeach
                                                                            </div>
                                                                        @endif

                                                                        <form x-show="!{{ $isRemoved ? 'true' : 'false' }} && actionType === 'move'"
                                                                              x-cloak
                                                                              method="POST"
                                                                              action="{{ route('reclassification.move-requests.store', [$application, $entry]) }}"
                                                                              data-async-action
                                                                              data-async-refresh-target="#reviewer-content"
                                                                              data-loading-text="Saving..."
                                                                              data-loading-message="Saving move request..."
                                                                              class="grid grid-cols-1 md:grid-cols-6 gap-3">
                                                                            @csrf
                                                                            <div class="md:col-span-4">
                                                                                <label class="text-xs text-gray-700">Target Criterion</label>
                                                                                <select name="target" class="mt-1 w-full rounded-lg border-gray-300 text-xs" required>
                                                                                    <option value="">Select target criterion</option>
                                                                                    @foreach($moveTargetOptions as $opt)
                                                                                        <option value="{{ $opt['section_code'] }}|{{ $opt['criterion_key'] }}">
                                                                                            {{ $opt['label'] }}
                                                                                        </option>
                                                                                    @endforeach
                                                                                </select>
                                                                            </div>
                                                                            <div class="md:col-span-2">
                                                                                <label class="text-xs text-gray-700">Reason</label>
                                                                                <input type="text"
                                                                                       name="note"
                                                                                       maxlength="2000"
                                                                                       class="mt-1 w-full rounded-lg border-gray-300 text-xs"
                                                                                       placeholder="Why this should be moved">
                                                                            </div>
                                                                            <div class="md:col-span-6 flex justify-end">
                                                                                <button type="submit"
                                                                                        class="px-3 py-2 rounded-lg bg-indigo-600 text-white text-xs font-semibold">
                                                                                    Save Move Request
                                                                                </button>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                @endif
                                                            </div>
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

                @if($section->section_code === '1')
                    <div id="section-2-dean-input" class="bg-white rounded-2xl shadow-card border border-gray-200 p-6 scroll-mt-28">
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Section II (Dean Input)</h3>
                        @if(auth()->user()->role === 'dean')
                            @include('reclassification.section2', [
                                'sectionData' => $section2Data ?? [],
                                'actionRoute' => route('reclassification.review.section2.save', $application),
                                'readOnly' => false,
                                'asyncRefreshTarget' => '#reviewer-content',
                            ])
                        @else
                            @php
                                $ratings = $section2Review['ratings'] ?? [];
                                $points = $section2Review['points'] ?? [];
                                $rDe = $ratings['dean'] ?? [];
                                $rCh = $ratings['chair'] ?? [];
                                $rSt = $ratings['student'] ?? [];
                            @endphp
                            <div class="space-y-4">
                                <p class="text-sm text-gray-500">Read-only summary (filled by the Dean).</p>
                                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                                    <div class="rounded-xl border p-4">
                                        <div class="text-sm font-semibold text-gray-800">Dean Ratings</div>
                                        <div class="mt-2 space-y-1 text-sm text-gray-700">
                                            <div>Item 1: {{ $rDe['i1'] ?? '—' }}</div>
                                            <div>Item 2: {{ $rDe['i2'] ?? '—' }}</div>
                                            <div>Item 3: {{ $rDe['i3'] ?? '—' }}</div>
                                            <div>Item 4: {{ $rDe['i4'] ?? '—' }}</div>
                                        </div>
                                        <div class="mt-2 text-xs text-gray-500">Points: {{ number_format((float) ($points['dean'] ?? 0), 2) }}</div>
                                    </div>

                                    <div class="rounded-xl border p-4">
                                        <div class="text-sm font-semibold text-gray-800">Chair Ratings</div>
                                        <div class="mt-2 space-y-1 text-sm text-gray-700">
                                            <div>Item 1: {{ $rCh['i1'] ?? '—' }}</div>
                                            <div>Item 2: {{ $rCh['i2'] ?? '—' }}</div>
                                            <div>Item 3: {{ $rCh['i3'] ?? '—' }}</div>
                                            <div>Item 4: {{ $rCh['i4'] ?? '—' }}</div>
                                        </div>
                                        <div class="mt-2 text-xs text-gray-500">Points: {{ number_format((float) ($points['chair'] ?? 0), 2) }}</div>
                                    </div>

                                    <div class="rounded-xl border p-4">
                                        <div class="text-sm font-semibold text-gray-800">Student Ratings</div>
                                        <div class="mt-2 space-y-1 text-sm text-gray-700">
                                            <div>Item 1: {{ $rSt['i1'] ?? '—' }}</div>
                                            <div>Item 2: {{ $rSt['i2'] ?? '—' }}</div>
                                            <div>Item 3: {{ $rSt['i3'] ?? '—' }}</div>
                                            <div>Item 4: {{ $rSt['i4'] ?? '—' }}</div>
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
                        @endif
                    </div>
                @endif
            @endforeach

            @if($canForwardPerPaper)
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <div class="text-sm font-semibold text-gray-800">Review Actions</div>
                            <p class="text-xs text-gray-500">Quick access to return or forward at the bottom of the page.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <form method="POST" action="{{ route('reclassification.return', $application) }}">
                                @csrf
                                <button type="submit"
                                        @disabled($returnBlocked)
                                        class="px-4 py-2 rounded-xl border border-amber-200 bg-amber-50 text-amber-700 text-sm font-semibold {{ $returnBlocked ? 'opacity-60 cursor-not-allowed' : '' }}">
                                    Return to Faculty
                                </button>
                            </form>
                            <form method="POST" action="{{ route('reclassification.forward', $application) }}">
                                @csrf
                                <button type="submit"
                                        @disabled($forwardBlocked)
                                        class="px-4 py-2 rounded-xl bg-bu text-white text-sm font-semibold shadow-soft {{ $forwardBlocked ? 'opacity-60 cursor-not-allowed' : '' }}">
                                    {{ $nextLabel }}
                                </button>
                            </form>
                        </div>
                    </div>
                    @if($forwardBlocked)
                        <div class="mt-3 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                            @if($section2Blocked)
                                <div>Forward blocked: Section II (Dean Input) is not yet completed.</div>
                            @endif
                            @if($openRequiredCommentsCount > 0)
                                <div class="{{ $section2Blocked ? 'mt-1' : '' }}">
                                    {{ $openRequiredCommentsCount === 1
                                        ? 'Forward blocked: 1 action-required comment is still open.'
                                        : "Forward blocked: {$openRequiredCommentsCount} action-required comments are still open." }}
                                </div>
                            @endif
                        </div>
                    @endif
                    @if($returnBlocked)
                        <div class="mt-3 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                            Return blocked: add at least one action-required comment or move request first.
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <button type="button"
            onclick="window.scrollTo({ top: 0, behavior: 'smooth' })"
            class="fixed bottom-6 right-6 z-50 inline-flex items-center gap-2 px-3 py-2 rounded-full bg-bu text-white text-sm font-semibold shadow-soft hover:bg-bu-dark transition">
        <span aria-hidden="true">&uarr;</span>
        <span>Top</span>
    </button>

    @include('reclassification.partials.async-actions')
</x-app-layout>

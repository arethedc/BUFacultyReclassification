<x-app-layout>
    @php
        $sections = $application->sections->sortBy('section_code');
        $statusLabel = match($application->status) {
            'dean_review' => 'Dean Review',
            'hr_review' => 'HR Review',
            'vpaa_review' => 'VPAA Review',
            'president_review' => 'President Review',
            'returned_to_faculty' => 'Returned',
            'finalized' => 'Finalized',
            default => ucfirst(str_replace('_',' ', $application->status)),
        };
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
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
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

            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6 flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500">Status</div>
                    <div class="text-lg font-semibold text-gray-800">{{ $statusLabel }}</div>
                </div>
                <div class="flex items-center gap-2">
                    @php
                        $nextLabel = match($application->status) {
                            'dean_review' => 'Forward to HR',
                            'hr_review' => 'Forward to VPAA',
                            'vpaa_review' => 'Forward to President',
                            'president_review' => 'Finalize',
                            default => 'Forward',
                        };
                    @endphp
                    <form method="POST" action="{{ route('reclassification.return', $application) }}">
                        @csrf
                        <button type="submit"
                                class="px-4 py-2 rounded-xl border border-amber-200 bg-amber-50 text-amber-700 text-sm font-semibold">
                            Return to Faculty
                        </button>
                    </form>
                    @if(in_array($application->status, ['dean_review','hr_review','vpaa_review','president_review'], true))
                        <form method="POST" action="{{ route('reclassification.forward', $application) }}">
                            @csrf
                            <button type="submit"
                                    class="px-4 py-2 rounded-xl bg-bu text-white text-sm font-semibold shadow-soft">
                                {{ $nextLabel }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>

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
                                {{ $yearsService !== null ? $yearsService . ' years' : '—' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Current Teaching Rank</div>
                            <div class="text-sm font-semibold text-gray-800">{{ $currentRankLabel ?? 'Instructor' }}</div>
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
                                                        $title = $entry->title ?: ($data['text'] ?? $data['title'] ?? 'Entry');
                                                        $evidences = $entry->evidences ?? collect();
                                                        $rowComments = $entry->rowComments ?? collect();
                                                    @endphp
                                                    <tr>
                                                        <td class="px-4 py-2 font-medium text-gray-800">{{ $title }}</td>
                                                        <td class="px-4 py-2 text-gray-600">
                                                            <div class="space-y-1">
                                                                @foreach($data as $key => $value)
                                                                    @if($key === 'evidence')
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
                                                                            $status = $ev->status ?? 'pending';
                                                                        @endphp
                                                                        <div class="rounded-lg border p-3 space-y-2">
                                                                            <div class="flex items-center justify-between">
                                                                                <div class="min-w-0">
                                                                                    @if($url)
                                                                                        <a href="{{ $url }}" target="_blank" class="text-bu hover:underline">
                                                                                            {{ $ev->original_name ?? 'Evidence file' }}
                                                                                        </a>
                                                                                    @else
                                                                                        <span class="text-gray-600">{{ $ev->original_name ?? $ev->path }}</span>
                                                                                    @endif
                                                                                    <div class="text-xs text-gray-500">ID #{{ $ev->id }}</div>
                                                                                </div>
                                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] border
                                                                                    {{ $status === 'accepted' ? 'bg-green-50 text-green-700 border-green-200' : ($status === 'rejected' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-gray-50 text-gray-600 border-gray-200') }}">
                                                                                    {{ ucfirst($status) }}
                                                                                </span>
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-2 text-right">
                                                            @if($section->section_code === '1' && $criterionKey === 'c')
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
                                                    <tr class="bg-gray-50/50">
                                                        <td colspan="4" class="px-4 py-3">
                                                            <div class="space-y-3">
                                                                <div>
                                                                    <div class="text-xs font-semibold text-gray-700">Reviewer Comments</div>
                                                                    @if($rowComments->isEmpty())
                                                                        <div class="text-xs text-gray-500 mt-1">No comments yet.</div>
                                                                    @else
                                                                        <div class="mt-2 space-y-2">
                                                                            @foreach($rowComments as $comment)
                                                                                <div class="rounded-lg border bg-white px-3 py-2 text-xs">
                                                                                    <div class="flex items-center justify-between gap-2">
                                                                                        <div class="font-medium text-gray-800">
                                                                                            {{ $comment->author?->name ?? 'Reviewer' }}
                                                                                        </div>
                                                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] border
                                                                                            {{ $comment->visibility === 'faculty_visible' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-50 text-gray-600 border-gray-200' }}">
                                                                                            {{ $comment->visibility === 'faculty_visible' ? 'Visible to faculty' : 'Internal' }}
                                                                                        </span>
                                                                                    </div>
                                                                                    <div class="mt-1 text-gray-700">{{ $comment->body }}</div>
                                                                                    <div class="mt-1 text-[10px] text-gray-400">
                                                                                        {{ optional($comment->created_at)->format('M d, Y g:i A') }}
                                                                                    </div>
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                    @endif
                                                                </div>

                                                                <form method="POST" action="{{ route('reclassification.row-comments.store', [$application, $entry]) }}" class="grid grid-cols-1 md:grid-cols-6 gap-3">
                                                                    @csrf
                                                                    <div class="md:col-span-4">
                                                                        <label class="text-xs text-gray-600">Add comment</label>
                                                                        <textarea name="body" rows="2" required
                                                                                  class="mt-1 w-full rounded-lg border-gray-300 text-xs"
                                                                                  placeholder="Leave a note for the faculty..."></textarea>
                                                                    </div>
                                                                    <div>
                                                                        <label class="text-xs text-gray-600">Visibility</label>
                                                                        <select name="visibility" class="mt-1 w-full rounded-lg border-gray-300 text-xs">
                                                                            <option value="faculty_visible">Visible to faculty</option>
                                                                            <option value="internal">Internal</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="flex items-end">
                                                                        <button type="submit"
                                                                                class="w-full px-3 py-2 rounded-lg bg-bu text-white text-xs font-semibold">
                                                                            Save Comment
                                                                        </button>
                                                                    </div>
                                                                </form>
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
                    <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Section II (Dean Input)</h3>
                        @if(auth()->user()->role === 'dean')
                            @include('reclassification.section2', [
                                'sectionData' => $section2Data ?? [],
                                'actionRoute' => route('reclassification.review.section2.save', $application),
                                'readOnly' => false,
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
        </div>
    </div>
</x-app-layout>

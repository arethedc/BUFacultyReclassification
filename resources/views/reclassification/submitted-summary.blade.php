<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800">Submitted Reclassification Paper</h2>
                <p class="text-sm text-gray-500">Read-only summary of your submitted form.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('reclassification.submitted') }}"
                   class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50">
                    Back
                </a>
                <a href="{{ route('faculty.dashboard') }}"
                   class="px-4 py-2 rounded-xl bg-bu text-white shadow">
                    Faculty Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $sections = $application->sections->sortBy('section_code');
        $statusLabel = match($application->status) {
            'draft' => 'Draft',
            'returned_to_faculty' => 'Returned',
            'dean_review' => 'Dean Review',
            'hr_review' => 'HR Review',
            'vpaa_review' => 'VPAA Review',
            'president_review' => 'President Review',
            'finalized' => 'Finalized',
            default => ucfirst(str_replace('_',' ', $application->status)),
        };
    @endphp

    <div class="py-10 bg-bu-muted min-h-screen">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6 flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500">Current Status</div>
                    <div class="text-lg font-semibold text-gray-800">{{ $statusLabel }}</div>
                </div>
                <div class="text-sm text-gray-500">
                    Submitted at:
                    <span class="font-medium text-gray-700">
                        {{ optional($application->submitted_at)->format('M d, Y') ?? 'Not set' }}
                    </span>
                </div>
            </div>

            @forelse($sections as $section)
                @php
                    $entries = $section->entries->groupBy('criterion_key');
                    $evidencesByEntry = $section->evidences->groupBy('reclassification_section_entry_id');
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
                                <div class="space-y-2">
                                    <div class="text-sm font-semibold text-gray-800">
                                        Criterion: {{ strtoupper($criterionKey) }}
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
                                                        $evidences = $evidencesByEntry[$entry->id] ?? collect();
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
                                                                <div class="space-y-1">
                                                                    @foreach($evidences as $ev)
                                                                        @php
                                                                            $url = $ev->disk ? \Illuminate\Support\Facades\Storage::disk($ev->disk)->url($ev->path) : null;
                                                                        @endphp
                                                                        <div>
                                                                            @if($url)
                                                                                <a href="{{ $url }}" target="_blank" class="text-bu hover:underline">
                                                                                    {{ $ev->original_name ?? 'Evidence file' }}
                                                                                </a>
                                                                            @else
                                                                                <span class="text-gray-600">{{ $ev->original_name ?? $ev->path }}</span>
                                                                            @endif
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
            @empty
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                    <p class="text-sm text-gray-500">No sections found for this application.</p>
                </div>
            @endforelse

        </div>
    </div>
</x-app-layout>

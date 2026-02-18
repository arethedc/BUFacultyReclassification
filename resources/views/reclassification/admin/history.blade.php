<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800">{{ $title }}</h2>
                <p class="text-sm text-gray-500">{{ $subtitle }}</p>
            </div>
            <a href="{{ $backRoute }}"
               class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50">
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-10 bg-bu-muted min-h-screen">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <form method="GET" action="{{ $indexRoute }}"
                  class="bg-white rounded-2xl shadow-card border border-gray-200 p-6 grid grid-cols-1 md:grid-cols-5 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600">Search Period</label>
                    <input type="text"
                           name="q"
                           value="{{ $q }}"
                           placeholder="Search by period name or cycle"
                           class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu">
                </div>
                @if($showDepartmentFilter)
                    <div>
                        <label class="block text-xs font-semibold text-gray-600">Department</label>
                        <select name="department_id"
                                class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu">
                            <option value="">All Departments</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" @selected((string) $filterDepartmentId === (string) $dept->id)>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <label class="block text-xs font-semibold text-gray-600">Rank</label>
                    <select name="rank_level_id"
                            class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu">
                        <option value="">All Ranks</option>
                        @foreach($rankLevels as $level)
                            <option value="{{ $level->id }}" @selected((string) $rankLevelId === (string) $level->id)>
                                {{ $level->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end justify-end gap-2">
                    <a href="{{ $indexRoute }}"
                       class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50">
                        Reset
                    </a>
                    <button type="submit"
                            class="px-4 py-2 rounded-xl bg-bu text-white shadow-soft">
                        Search
                    </button>
                </div>
            </form>

            @php
                $totalPeriods = (int) $periods->count();
                $totalApproved = (int) $periods->sum('approved_count');
                $endedCount = (int) $periods->count();
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-5">
                    <div class="text-xs text-gray-500">Total Periods</div>
                    <div class="text-2xl font-semibold text-gray-800">{{ $totalPeriods }}</div>
                </div>
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-5">
                    <div class="text-xs text-gray-500">Total Approved Promotions</div>
                    <div class="text-2xl font-semibold text-gray-800">{{ $totalApproved }}</div>
                </div>
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-5">
                    <div class="text-xs text-gray-500">Ended Periods</div>
                    <div class="text-2xl font-semibold text-gray-800">{{ $endedCount }}</div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-card border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Period History</h3>
                    <p class="text-sm text-gray-500">Only ended periods are listed here.</p>
                </div>

                @if($periods->isEmpty())
                    <div class="p-6 text-sm text-gray-500">No periods found.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-left">
                                <tr>
                                    <th class="px-4 py-2">Name</th>
                                    <th class="px-4 py-2">Cycle</th>
                                    <th class="px-4 py-2">Status</th>
                                    <th class="px-4 py-2">Submissions</th>
                                    <th class="px-4 py-2">Approved</th>
                                    <th class="px-4 py-2">Created</th>
                                    <th class="px-4 py-2 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @foreach($periods as $period)
                                    @php
                                        $periodStatus = (string) ($period->status ?? ($period->is_open ? 'active' : 'ended'));
                                        $periodStatusClass = $periodStatus === 'active'
                                            ? 'bg-green-50 text-green-700 border-green-200'
                                            : ($periodStatus === 'draft'
                                                ? 'bg-blue-50 text-blue-700 border-blue-200'
                                                : 'bg-gray-50 text-gray-600 border-gray-200');
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-2 font-medium text-gray-800">{{ $period->name }}</td>
                                        <td class="px-4 py-2 text-gray-600">{{ $period->cycle_year ?? '-' }}</td>
                                        <td class="px-4 py-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] border {{ $periodStatusClass }}">
                                                {{ ucfirst($periodStatus) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-gray-600">{{ (int) ($period->submission_count ?? 0) }}</td>
                                        <td class="px-4 py-2 text-gray-700 font-semibold">{{ (int) ($period->approved_count ?? 0) }}</td>
                                        <td class="px-4 py-2 text-gray-600">{{ optional($period->created_at)->format('M d, Y') ?? '-' }}</td>
                                        <td class="px-4 py-2 text-right">
                                            <a href="{{ route('reclassification.history.period', array_filter([
                                                'period' => $period,
                                                'department_id' => $showDepartmentFilter ? $filterDepartmentId : null,
                                                'rank_level_id' => $rankLevelId,
                                            ], fn ($value) => !is_null($value) && $value !== '')) }}"
                                               class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold border border-bu/20 text-bu hover:bg-bu/5">
                                                View Approved List
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

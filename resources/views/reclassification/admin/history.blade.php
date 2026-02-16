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
                    <label class="block text-xs font-semibold text-gray-600">Search</label>
                    <input type="text"
                           name="q"
                           value="{{ $q }}"
                           placeholder="Faculty name or email"
                           class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu">
                </div>
                @if($showDepartmentFilter)
                    <div>
                        <label class="block text-xs font-semibold text-gray-600">Department</label>
                        <select name="department_id"
                                class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu">
                            <option value="">All Departments</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" @selected((string) $departmentId === (string) $dept->id)>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <label class="block text-xs font-semibold text-gray-600">Cycle Year</label>
                    <select name="cycle_year"
                            class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu">
                        <option value="">All Cycles</option>
                        @foreach($cycleYears as $year)
                            <option value="{{ $year }}" @selected((string) $cycleYear === (string) $year)>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-5 flex items-center justify-end gap-2">
                    <a href="{{ $indexRoute }}"
                       class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50">
                        Reset
                    </a>
                    <button type="submit"
                            class="px-4 py-2 rounded-xl bg-bu text-white shadow-soft">
                        Apply Filters
                    </button>
                </div>
            </form>

            @php
                $totalPromoted = (int) $applications->count();
                $totalCycles = (int) $cycleSummaries->count();
                $latestCycle = $cycleSummaries->first()?->cycle_year;
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-5">
                    <div class="text-xs text-gray-500">Total Cycles</div>
                    <div class="text-2xl font-semibold text-gray-800">{{ $totalCycles }}</div>
                </div>
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-5">
                    <div class="text-xs text-gray-500">Total Approved Promotions</div>
                    <div class="text-2xl font-semibold text-gray-800">{{ $totalPromoted }}</div>
                </div>
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-5">
                    <div class="text-xs text-gray-500">Latest Cycle</div>
                    <div class="text-2xl font-semibold text-gray-800">{{ $latestCycle ?: '-' }}</div>
                </div>
            </div>

            @if($applicationsByCycle->isEmpty())
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6 text-sm text-gray-500">
                    No finalized records found.
                </div>
            @else
                <div class="space-y-4">
                    @foreach($applicationsByCycle as $cycle => $cycleApps)
                        <div x-data="{ open: false }" class="bg-white rounded-2xl shadow-card border border-gray-200 overflow-hidden">
                            <button type="button"
                                    @click="open = !open"
                                    class="w-full px-6 py-4 border-b border-gray-200 flex items-center justify-between text-left">
                                <div>
                                    <div class="text-xs text-gray-500">Cycle</div>
                                    <div class="text-lg font-semibold text-gray-800">{{ $cycle }}</div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border bg-green-50 text-green-700 border-green-200">
                                        {{ $cycleApps->count() }} approved
                                    </span>
                                    <span class="text-sm text-gray-600" x-text="open ? 'Hide List' : 'Show List'"></span>
                                </div>
                            </button>

                            <div x-show="open" x-collapse class="p-4">
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead class="bg-gray-50 text-left">
                                            <tr>
                                                <th class="px-4 py-2">Faculty</th>
                                                <th class="px-4 py-2">Department</th>
                                                <th class="px-4 py-2">From Rank</th>
                                                <th class="px-4 py-2">Approved Rank</th>
                                                <th class="px-4 py-2">Approved By</th>
                                                <th class="px-4 py-2">Approved At</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y">
                                            @foreach($cycleApps as $app)
                                                <tr>
                                                    <td class="px-4 py-2 font-medium text-gray-800">{{ $app->faculty?->name ?? 'Faculty' }}</td>
                                                    <td class="px-4 py-2 text-gray-600">{{ $app->faculty?->department?->name ?? '-' }}</td>
                                                    <td class="px-4 py-2 text-gray-700">{{ $app->current_rank_label_at_approval ?? '-' }}</td>
                                                    <td class="px-4 py-2 text-gray-700 font-semibold">{{ $app->approved_rank_label ?? '-' }}</td>
                                                    <td class="px-4 py-2 text-gray-600">{{ $app->approvedBy?->name ?? '-' }}</td>
                                                    <td class="px-4 py-2 text-gray-600">{{ optional($app->approved_at ?? $app->finalized_at)->format('M d, Y g:i A') ?? '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

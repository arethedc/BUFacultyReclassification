<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800">Approved Reclassifications</h2>
                <p class="text-sm text-gray-500">Applications finalized at the VPAA stage.</p>
            </div>
            <a href="{{ route('hr.dashboard') }}"
               class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50">
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-10 bg-bu-muted min-h-screen">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <form method="GET" action="{{ route('reclassification.admin.approved') }}"
                  class="bg-white rounded-2xl shadow-card border border-gray-200 p-6 grid grid-cols-1 md:grid-cols-5 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600">Search</label>
                    <input type="text"
                           name="q"
                           value="{{ $q }}"
                           placeholder="Faculty name or email"
                           class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu">
                </div>
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
                    <a href="{{ route('reclassification.admin.approved') }}"
                       class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50">
                        Reset
                    </a>
                    <button type="submit"
                            class="px-4 py-2 rounded-xl bg-bu text-white shadow-soft">
                        Apply Filters
                    </button>
                </div>
            </form>

            <div class="bg-white rounded-2xl shadow-card border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">Approved List</h3>
                </div>

                @if($applications->isEmpty())
                    <div class="p-6 text-sm text-gray-500">No approved records found.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-left">
                                <tr>
                                    <th class="px-4 py-2">Faculty</th>
                                    <th class="px-4 py-2">Department</th>
                                    <th class="px-4 py-2">Cycle</th>
                                    <th class="px-4 py-2">Current Rank</th>
                                    <th class="px-4 py-2">Approved Rank</th>
                                    <th class="px-4 py-2">Approved By</th>
                                    <th class="px-4 py-2">Approved At</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @foreach($applications as $app)
                                    <tr>
                                        <td class="px-4 py-2">
                                            <div class="font-medium text-gray-800">{{ $app->faculty?->name ?? 'Faculty' }}</div>
                                            <div class="text-xs text-gray-500">ID #{{ $app->faculty_user_id }}</div>
                                        </td>
                                        <td class="px-4 py-2 text-gray-600">{{ $app->faculty?->department?->name ?? '-' }}</td>
                                        <td class="px-4 py-2 text-gray-600">{{ $app->cycle_year ?? '-' }}</td>
                                        <td class="px-4 py-2 text-gray-700 font-medium">
                                            {{ $app->current_rank_label_at_approval ?? '-' }}
                                        </td>
                                        <td class="px-4 py-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] border bg-green-50 text-green-700 border-green-200">
                                                {{ $app->approved_rank_label ?? ($app->current_rank_label_at_approval ?? '-') }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-gray-600">{{ $app->approvedBy?->name ?? '-' }}</td>
                                        <td class="px-4 py-2 text-gray-600">
                                            {{ optional($app->approved_at ?? $app->finalized_at)->format('M d, Y g:i A') ?? '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div>
                {{ $applications->links() }}
            </div>
        </div>
    </div>
</x-app-layout>

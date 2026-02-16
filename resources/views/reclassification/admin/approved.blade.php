<x-app-layout>
    @php
        $title = $title ?? 'Approved Reclassifications';
        $subtitle = $subtitle ?? 'Applications finalized after final approval.';
        $indexRoute = $indexRoute ?? route('reclassification.admin.approved');
        $backRoute = $backRoute ?? route('hr.dashboard');
        $showDepartmentFilter = $showDepartmentFilter ?? true;
        $showCycleFilter = $showCycleFilter ?? true;
        $showVpaaActions = $showVpaaActions ?? false;
        $showPresidentActions = $showPresidentActions ?? false;
        $batchReadyCount = (int) ($batchReadyCount ?? 0);
        $batchBlockingCount = (int) ($batchBlockingCount ?? 0);
        $activePeriod = $activePeriod ?? null;
    @endphp
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
            @if (session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

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
                @if($showCycleFilter)
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
                @endif
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

            @if($showVpaaActions || $showPresidentActions)
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6 space-y-3">
                    <div class="text-sm text-gray-700">
                        <span class="font-semibold">Active period:</span>
                        @if($activePeriod)
                            {{ $activePeriod->name }} ({{ $activePeriod->cycle_year ?? 'No cycle' }})
                        @else
                            No open period
                        @endif
                    </div>
                    <div class="text-sm text-gray-700">
                        <span class="font-semibold">Ready for batch action:</span>
                        {{ $batchReadyCount }}
                        <span class="mx-2 text-gray-300">-</span>
                        <span class="font-semibold">Blocking submissions:</span>
                        {{ $batchBlockingCount }}
                    </div>

                    @if($errors->has('approved_list'))
                        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">
                            {{ $errors->first('approved_list') }}
                        </div>
                    @endif

                    <div class="flex flex-wrap items-center gap-3">
                        @if($showVpaaActions)
                            <form method="POST" action="{{ route('reclassification.review.approved.forward') }}">
                                @csrf
                                <button type="submit"
                                        @disabled(!$activePeriod || $batchReadyCount === 0 || $batchBlockingCount > 0)
                                        class="px-4 py-2 rounded-xl bg-bu text-white shadow-soft disabled:opacity-60 disabled:cursor-not-allowed">
                                    Forward Approved List to President
                                </button>
                            </form>
                        @endif

                        @if($showPresidentActions)
                            <form method="POST" action="{{ route('reclassification.review.approved.finalize') }}">
                                @csrf
                                <button type="submit"
                                        @disabled(!$activePeriod || $batchReadyCount === 0 || $batchBlockingCount > 0)
                                        class="px-4 py-2 rounded-xl bg-green-600 text-white shadow-soft disabled:opacity-60 disabled:cursor-not-allowed">
                                    Approve and Finalize Cycle List
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

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
                                    <th class="px-4 py-2">Status</th>
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
                                        <td class="px-4 py-2 text-gray-600">
                                            {{ ucfirst(str_replace('_',' ', $app->status)) }}
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

            @if(method_exists($applications, 'links'))
                <div>
                    {{ $applications->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

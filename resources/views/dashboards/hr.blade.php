<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-gray-800">HR Dashboard</h2>
            <p class="text-sm text-gray-500">Overview, submissions, and staff management.</p>
        </div>
    </x-slot>

    <div class="py-12 bg-bu-muted min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <div class="text-sm text-gray-500">Current Submission Period</div>
                        <div class="text-lg font-semibold text-gray-800">
                            {{ $openPeriod?->name ?? 'No open period' }}
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            {{ $openPeriod?->is_open ? 'Open for submissions' : 'Closed' }}
                            @if($openPeriod?->start_at || $openPeriod?->end_at)
                                • {{ optional($openPeriod?->start_at)->format('M d, Y') ?? '—' }}
                                to {{ optional($openPeriod?->end_at)->format('M d, Y') ?? '—' }}
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('reclassification.review.queue') }}"
                           class="px-4 py-2 rounded-xl bg-bu text-white text-sm font-semibold shadow-soft">
                            Review Queue
                        </a>
                        <a href="{{ route('reclassification.admin.submissions') }}"
                           class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50">
                            All Submissions
                        </a>
                        <a href="{{ route('reclassification.periods') }}"
                           class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50">
                            Manage Periods
                        </a>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                    <div class="text-xs text-gray-500">Pending HR Review</div>
                    <div class="text-2xl font-semibold text-gray-800">
                        {{ $statusCounts['hr_review'] ?? 0 }}
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                    <div class="text-xs text-gray-500">Returned to Faculty</div>
                    <div class="text-2xl font-semibold text-gray-800">
                        {{ $statusCounts['returned_to_faculty'] ?? 0 }}
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                    <div class="text-xs text-gray-500">Finalized</div>
                    <div class="text-2xl font-semibold text-gray-800">
                        {{ $statusCounts['finalized'] ?? 0 }}
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                    <div class="text-xs text-gray-500">Faculty Accounts</div>
                    <div class="text-2xl font-semibold text-gray-800">
                        {{ $facultyCount ?? 0 }}
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6 lg:col-span-2">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Recent Submissions</h3>
                        <a href="{{ route('reclassification.admin.submissions') }}"
                           class="text-sm font-semibold text-bu hover:underline">
                            View all
                        </a>
                    </div>

                    @if($recentApplications->isEmpty())
                        <div class="text-sm text-gray-500">No submissions yet.</div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="text-left text-gray-500 border-b">
                                    <tr>
                                        <th class="py-2">Faculty</th>
                                        <th class="py-2">Department</th>
                                        <th class="py-2">Status</th>
                                        <th class="py-2">Submitted</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach($recentApplications as $app)
                                        <tr>
                                            <td class="py-2 font-medium text-gray-800">
                                                {{ $app->faculty?->name ?? 'Faculty' }}
                                            </td>
                                            <td class="py-2 text-gray-600">
                                                {{ $app->faculty?->department?->name ?? '—' }}
                                            </td>
                                            <td class="py-2 text-gray-600">
                                                {{ ucfirst(str_replace('_',' ', $app->status)) }}
                                            </td>
                                            <td class="py-2 text-gray-600">
                                                {{ optional($app->submitted_at)->format('M d, Y') ?? '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="lg:col-span-1 flex flex-col gap-6">
                    <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-800">Faculty Management</h3>
                        <p class="text-sm text-gray-600 mt-2">
                            Maintain faculty records and profile accuracy.
                        </p>
                        <div class="mt-5 grid grid-cols-1 gap-3">
                            <a href="{{ route('faculty.index') }}"
                               class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl
                                      bg-bu text-white text-sm font-semibold shadow-soft">
                                Faculty Records
                            </a>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-800">User Management</h3>
                        <p class="text-sm text-gray-600 mt-2">
                            Create accounts, manage roles, and keep faculty records accurate.
                        </p>
                        <div class="mt-5 grid grid-cols-1 gap-3">
                            <a href="{{ route('users.create') }}"
                               class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl
                                      bg-bu text-white text-sm font-semibold shadow-soft">
                                Create User
                            </a>
                            <a href="{{ route('users.index') }}"
                               class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl
                                      border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50">
                                Manage Users
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-gray-800">President Dashboard</h2>
            <p class="text-sm text-gray-500">Final approval and signing of reclassification submissions.</p>
        </div>
    </x-slot>

    <div class="py-12 bg-bu-muted min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <div class="text-sm text-gray-500">Reclassification Workflow</div>
                        <div class="text-lg font-semibold text-gray-800">
                            President Final Review
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            Approve final submissions and complete the workflow.
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('reclassification.review.submissions') }}"
                           class="px-4 py-2 rounded-xl bg-bu text-white text-sm font-semibold shadow-soft">
                            All Submissions
                        </a>
                        <a href="{{ route('reclassification.review.approved') }}"
                           class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50">
                            Approved List
                        </a>
                        <a href="{{ route('reclassification.history') }}"
                           class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50">
                            Reclassification History
                        </a>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                    <div class="text-xs text-gray-500">Pending President Review</div>
                    <div class="text-2xl font-semibold text-gray-800">
                        {{ $statusCounts['president_review'] ?? 0 }}
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
                    <div class="text-xs text-gray-500">Total Submissions</div>
                    <div class="text-2xl font-semibold text-gray-800">
                        {{ $totalSubmissions ?? 0 }}
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Recent Submissions</h3>
                    <a href="{{ route('reclassification.review.submissions') }}"
                       class="text-sm font-semibold text-bu hover:underline">
                        View all
                    </a>
                </div>

                    @if($recentApplications->isEmpty())
                    <div class="text-sm text-gray-500">
                        {{ !empty($hasActivePeriod) ? 'No submissions yet.' : 'No active period. No recent submissions.' }}
                    </div>
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
                                            {{ $app->faculty?->department?->name ?? '--' }}
                                        </td>
                                        <td class="py-2 text-gray-600">
                                            {{ ucfirst(str_replace('_',' ', $app->status)) }}
                                        </td>
                                        <td class="py-2 text-gray-600">
                                            {{ optional($app->submitted_at)->format('M d, Y') ?? '--' }}
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

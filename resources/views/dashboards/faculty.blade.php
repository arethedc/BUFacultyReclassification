<x-app-layout>
    {{-- HEADER --}}
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-gray-800">
                Faculty Dashboard
            </h2>
            <p class="text-sm text-gray-500">
                Manage your reclassification applications.
            </p>
        </div>
    </x-slot>

    <div class="py-12 bg-bu-muted min-h-screen">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- PROFILE SUMMARY --}}
            @php
                $profile = $user->facultyProfile;
                $departmentName = $user->department?->name ?? 'Not set';
                $employeeNo = $profile?->employee_no ?? 'Not set';
                $employmentType = $profile?->employment_type
                    ? ucwords(str_replace('_', ' ', $profile->employment_type))
                    : 'Not set';
                $rank = $profile?->teaching_rank ?? '';
                $rankStep = $profile?->rank_step ?? '';
                $currentRank = trim($rank . ($rankStep !== '' ? ' - ' . $rankStep : ''));
                $currentRank = $currentRank !== '' ? $currentRank : 'Not set';
            @endphp

            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    Faculty Profile
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">Employee No.</span>
                        <div class="font-medium text-gray-800">
                            {{ $employeeNo }}
                        </div>
                    </div>

                    <div>
                        <span class="text-gray-500">Department</span>
                        <div class="font-medium text-gray-800">
                            {{ $departmentName }}
                        </div>
                    </div>

                    <div>
                        <span class="text-gray-500">Current Rank</span>
                        <div class="font-medium text-gray-800">
                            {{ $currentRank }}
                        </div>
                    </div>

                    <div>
                        <span class="text-gray-500">Employment Type</span>
                        <div class="font-medium text-gray-800">
                            {{ $employmentType }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- ACTION NAV --}}
            @php
                $latest = $applications->first();
                $isEditable = $latest && in_array($latest->status, ['draft', 'returned_to_faculty'], true);
                $actionLabel = 'Start Reclassification';
                $actionRoute = route('reclassification.show');

                if ($latest) {
                    if ($isEditable) {
                        $actionLabel = 'Continue Reclassification';
                        $actionRoute = route('reclassification.show');
                    } else {
                        $actionLabel = 'View Submitted';
                        $actionRoute = route('reclassification.submitted');
                    }
                }

                $submittedApp = $applications->firstWhere('status', 'finalized')
                    ?? $applications->first(fn ($app) => !in_array($app->status, ['draft','returned_to_faculty'], true));
            @endphp

            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-4">
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ $actionRoute }}"
                       class="px-5 py-2.5 rounded-xl bg-bu text-white text-sm font-semibold shadow-soft">
                        {{ $actionLabel }}
                    </a>

                    @if($submittedApp)
                        <a href="{{ route('reclassification.submitted-summary.show', $submittedApp) }}"
                           class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50">
                            View Submitted Paper
                        </a>
                    @endif

                    <a href="{{ route('profile.edit') }}"
                       class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50">
                        My Profile / Reset Password
                    </a>
                </div>
            </div>

            {{-- APPLICATION HISTORY --}}
            <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">
                        Reclassification History
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-6 py-3 text-left">Term</th>
                                <th class="px-6 py-3 text-left">Current Rank</th>
                                <th class="px-6 py-3 text-left">Status</th>
                                <th class="px-6 py-3 text-right">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y">
                            @php
                                $statusMap = [
                                    'draft' => ['Draft', 'bg-gray-100 text-gray-700'],
                                    'returned_to_faculty' => ['Returned', 'bg-amber-50 text-amber-700'],
                                    'dean_review' => ['Dean Review', 'bg-blue-50 text-blue-700'],
                                    'hr_review' => ['HR Review', 'bg-blue-50 text-blue-700'],
                                    'vpaa_review' => ['VPAA Review', 'bg-blue-50 text-blue-700'],
                                    'president_review' => ['President Review', 'bg-blue-50 text-blue-700'],
                                    'finalized' => ['Finalized', 'bg-green-50 text-green-700'],
                                ];
                            @endphp

                            @forelse($applications as $app)
                                @php
                                    $term = $app->cycle_year ?: 'Not set';
                                    $statusInfo = $statusMap[$app->status] ?? [ucfirst(str_replace('_', ' ', $app->status)), 'bg-gray-100 text-gray-700'];
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">{{ $term }}</td>
                                    <td class="px-6 py-4">{{ $currentRank }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded-full {{ $statusInfo[1] }}">
                                            {{ $statusInfo[0] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        @if(in_array($app->status, ['draft','returned_to_faculty'], true))
                                            <a href="{{ route('reclassification.show') }}"
                                               class="text-bu hover:underline font-medium">
                                                Continue
                                            </a>
                                        @else
                                            <a href="{{ route('reclassification.submitted-summary.show', $app) }}"
                                               class="text-bu hover:underline font-medium">
                                                View
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-6 py-6 text-center text-gray-500" colspan="4">
                                        No reclassification applications yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

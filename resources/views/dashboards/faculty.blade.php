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
            @if (session('success'))
                <div class="bg-blue-50 border border-blue-200 rounded-2xl shadow-card p-6">
                    <div class="text-sm font-semibold text-blue-800">
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            @php
                $latestFinalized = $applications->firstWhere('status', 'finalized');
            @endphp
            @if(!empty($promotionNotification) || $latestFinalized)
                @php
                    $payload = $promotionNotification?->data ?? [];
                    $fromRank = $payload['from_rank'] ?? ($latestFinalized->current_rank_label_at_approval ?? null);
                    $toRank = $payload['to_rank'] ?? ($latestFinalized->approved_rank_label ?? null);
                    $cycle = $payload['cycle_year'] ?? ($latestFinalized->cycle_year ?? null);
                    $congratsKeySeed = $payload['application_id'] ?? ($latestFinalized->id ?? 'latest');
                    $congratsDismissKey = 'faculty_congrats_dismissed_' . $congratsKeySeed;
                @endphp
                <div
                    x-data="{ hidden: localStorage.getItem(@js($congratsDismissKey)) === '1' }"
                    x-show="!hidden"
                    x-cloak
                    class="bg-green-50 border border-green-200 rounded-2xl shadow-card p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="text-sm font-semibold text-green-800">
                            {{ $payload['title'] ?? 'Congratulations! Your reclassification has been approved.' }}
                        </div>
                        <button type="button"
                                @click="hidden = true; localStorage.setItem(@js($congratsDismissKey), '1')"
                                class="inline-flex items-center justify-center h-7 w-7 rounded-md border border-green-300 text-green-700 hover:bg-green-100"
                                aria-label="Dismiss notification">
                            ×
                        </button>
                    </div>
                    <div class="mt-1 text-sm text-green-700">
                        {{ $payload['message'] ?? 'Your promotion has been finalized.' }}
                        @if($fromRank && $toRank)
                            <span class="font-semibold"> {{ $fromRank }} to {{ $toRank }}.</span>
                        @endif
                        @if($cycle)
                            <span> (Cycle {{ $cycle }})</span>
                        @endif
                    </div>
                </div>
            @endif

            @php
                $bannerPeriod = $activePeriod ?? null;
                $isOpen = !empty($openPeriod);
                $startAt = $bannerPeriod?->start_at;
                $endAt = $bannerPeriod?->end_at;
                $periodRange = null;
                if ($startAt || $endAt) {
                    $periodRange = (optional($startAt)->format('M d, Y') ?? 'TBD') . ' to ' . (optional($endAt)->format('M d, Y') ?? 'TBD');
                }

                $bannerClass = 'bg-gray-50 border-gray-200 text-gray-800';
                $bannerTitle = 'No active reclassification submission';
                $bannerMessage = 'There is currently no ongoing reclassification period. Please wait for HR announcement.';

                if ($bannerPeriod && $isOpen) {
                    $bannerClass = 'bg-green-50 border-green-200 text-green-900';
                    $bannerTitle = 'Reclassification is open';
                    if ($endAt) {
                        $daysLeft = now()->startOfDay()->diffInDays($endAt->copy()->startOfDay(), false);
                        if ($daysLeft > 1) {
                            $bannerMessage = $daysLeft . ' days left to submit.';
                        } elseif ($daysLeft === 1) {
                            $bannerMessage = '1 day left to submit.';
                        } elseif ($daysLeft === 0) {
                            $bannerMessage = 'Last day to submit.';
                        } else {
                            $bannerClass = 'bg-amber-50 border-amber-200 text-amber-900';
                            $bannerMessage = 'Submission deadline has passed. Please contact HR.';
                        }
                    } else {
                        $bannerMessage = 'Submission period is open. No closing date set yet.';
                    }
                } elseif ($bannerPeriod) {
                    $bannerClass = 'bg-amber-50 border-amber-200 text-amber-900';
                    $bannerTitle = 'Reclassification is closed';
                    if ($startAt && now()->lt($startAt)) {
                        $daysToOpen = now()->startOfDay()->diffInDays($startAt->copy()->startOfDay(), false);
                        if ($daysToOpen > 1) {
                            $bannerMessage = 'Opens in ' . $daysToOpen . ' days.';
                        } elseif ($daysToOpen === 1) {
                            $bannerMessage = 'Opens tomorrow.';
                        } else {
                            $bannerMessage = 'Opens today.';
                        }
                    } else {
                        $bannerMessage = 'No ongoing reclassification submission.';
                    }
                }
            @endphp

            <div class="rounded-2xl border p-5 {{ $bannerClass }}">
                <div class="flex flex-col gap-1">
                    <div class="text-sm font-semibold">{{ $bannerTitle }}</div>
                    <div class="text-sm">{{ $bannerMessage }}</div>
                    @if($bannerPeriod)
                        <div class="text-xs opacity-80 mt-1">
                            Period: {{ $bannerPeriod->name ?? ('AY ' . ($bannerPeriod->cycle_year ?? 'N/A')) }}
                            @if($periodRange)
                                • {{ $periodRange }}
                            @endif
                        </div>
                    @endif
                </div>
            </div>

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
                $hasContinuableSubmission = $applications->contains(function ($app) {
                    return in_array($app->status, ['draft', 'returned_to_faculty'], true);
                });
                $actionLabel = $hasContinuableSubmission
                    ? 'Continue Reclassification'
                    : 'Start Reclassification';
                $actionRoute = route('reclassification.show');
            @endphp

            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-4">
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ $actionRoute }}"
                       class="px-5 py-2.5 rounded-xl bg-bu text-white text-sm font-semibold shadow-soft">
                        {{ $actionLabel }}
                    </a>

                    <a href="{{ route('profile.edit') }}"
                       class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50">
                        My Profile / Reset Password
                    </a>
                </div>
            </div>

            @php
                $statusMap = [
                    'draft' => ['Draft', 'bg-gray-100 text-gray-700'],
                    'returned_to_faculty' => ['Returned', 'bg-amber-50 text-amber-700'],
                    'dean_review' => ['Dean Review', 'bg-blue-50 text-blue-700'],
                    'hr_review' => ['HR Review', 'bg-blue-50 text-blue-700'],
                    'vpaa_review' => ['VPAA Review', 'bg-blue-50 text-blue-700'],
                    'vpaa_approved' => ['VPAA Approved', 'bg-indigo-50 text-indigo-700'],
                    'president_review' => ['President Review', 'bg-blue-50 text-blue-700'],
                    'finalized' => ['Finalized', 'bg-green-50 text-green-700'],
                ];

                $currentSubmission = $applications->first(function ($app) {
                    return $app->status !== 'finalized';
                });
                $historyApplications = $applications->reject(function ($app) use ($currentSubmission) {
                    return $currentSubmission
                        && $currentSubmission->status === 'draft'
                        && (int) $app->id === (int) $currentSubmission->id;
                });
            @endphp

            {{-- CURRENT SUBMISSION --}}
            <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">
                        Current Submission
                    </h3>
                </div>

                <div class="p-6">
                    @if($currentSubmission)
                        @php
                            $statusInfo = $statusMap[$currentSubmission->status] ?? [ucfirst(str_replace('_', ' ', $currentSubmission->status)), 'bg-gray-100 text-gray-700'];
                            $term = $currentSubmission->cycle_year ?: 'Not set';
                            $isEditable = in_array($currentSubmission->status, ['draft', 'returned_to_faculty'], true);
                        @endphp
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div class="space-y-2">
                                <div class="text-sm text-gray-500">
                                    Term: <span class="font-medium text-gray-800">{{ $term }}</span>
                                </div>
                                <span class="inline-flex px-2 py-1 text-xs rounded-full {{ $statusInfo[1] }}">
                                    {{ $statusInfo[0] }}
                                </span>
                                <div class="text-xs text-gray-500">
                                    Last updated {{ optional($currentSubmission->updated_at)->format('M d, Y h:i A') }}
                                </div>
                            </div>

                            <div>
                                @if($isEditable)
                                    <a href="{{ route('reclassification.show') }}"
                                       class="px-4 py-2 rounded-xl bg-bu text-white text-sm font-semibold shadow-soft">
                                        Continue Draft
                                    </a>
                                @else
                                    <a href="{{ route('reclassification.submitted-summary.show', $currentSubmission) }}"
                                       class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50">
                                        View Submission
                                    </a>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="text-sm text-gray-500">
                            No active draft or in-review submission.
                        </div>
                    @endif
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
                            @forelse($historyApplications as $app)
                                @php
                                    $term = $app->cycle_year ?: 'Not set';
                                    $statusInfo = $statusMap[$app->status] ?? [ucfirst(str_replace('_', ' ', $app->status)), 'bg-gray-100 text-gray-700'];
                                    $historicalCurrentRank = trim((string) ($app->current_rank_label_at_approval ?? ''));
                                    if ($historicalCurrentRank === '') {
                                        $historicalCurrentRank = $currentRank;
                                    }
                                    $historicalApprovedRank = trim((string) ($app->approved_rank_label ?? ''));
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">{{ $term }}</td>
                                    <td class="px-6 py-4">
                                        <div>{{ $historicalCurrentRank }}</div>
                                        @if($historicalApprovedRank !== '' && $historicalApprovedRank !== $historicalCurrentRank)
                                            <div class="text-xs text-green-700">Promoted to {{ $historicalApprovedRank }}</div>
                                        @endif
                                    </td>
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

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800">Reclassification Form</h2>
                <p class="text-sm text-gray-500">
                    Your submission is now under review.
                </p>
            </div>

            @php
                $status = $application->status ?? 'submitted';
                $statusLabel = match($status) {
                    'draft' => 'Draft',
                    'returned_to_faculty' => 'Returned',
                    'dean_review' => 'Dean Review',
                    'hr_review' => 'HR Review',
                    'vpaa_review' => 'VPAA Review',
                    'president_review' => 'President Review',
                    'finalized' => 'Finalized',
                    default => ucfirst(str_replace('_',' ', $status)),
                };

                $statusClass = match($status) {
                    'draft' => 'bg-gray-100 text-gray-700 border-gray-200',
                    'returned_to_faculty' => 'bg-amber-50 text-amber-700 border-amber-200',
                    'finalized' => 'bg-green-50 text-green-700 border-green-200',
                    default => 'bg-blue-50 text-blue-700 border-blue-200',
                };
            @endphp

            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border {{ $statusClass }}">
                {{ $statusLabel }}
            </span>
        </div>
    </x-slot>

    <div class="py-12 bg-bu-muted min-h-screen">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-8">
                <div class="flex items-start gap-4">
                    <div class="h-12 w-12 rounded-full bg-green-50 text-green-700 flex items-center justify-center text-xl font-semibold">
                        ✓
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">
                            Already submitted. Your paper is under review.
                        </h3>
                        <p class="text-sm text-gray-500 mt-1">
                            You can view a read-only summary of your submitted reclassification form.
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('reclassification.submitted-summary') }}"
                       class="px-5 py-2.5 rounded-xl bg-bu text-white shadow">
                        View Submitted Paper
                    </a>

                    <a href="{{ route('faculty.dashboard') }}"
                       class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50">
                        Back to Dashboard
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                <h4 class="text-sm font-semibold text-gray-800 mb-2">What happens next?</h4>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>• Your application is routed to the Dean for initial review.</li>
                    <li>• You’ll see status updates here as it moves through HR, VPAA, and the President.</li>
                    <li>• If it’s returned for revisions, you’ll be notified and can edit again.</li>
                </ul>
            </div>

        </div>
    </div>
</x-app-layout>

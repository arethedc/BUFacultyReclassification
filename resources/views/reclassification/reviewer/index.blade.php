<x-app-layout>
    @php
        $role = auth()->user()->role ?? 'dean';
        $roleLabel = match($role) {
            'dean' => 'Dean',
            'hr' => 'HR',
            'vpaa' => 'VPAA',
            'president' => 'President',
            default => 'Reviewer',
        };
        $dashboardRoute = match($role) {
            'dean' => route('dean.dashboard'),
            'hr' => route('hr.dashboard'),
            'vpaa' => route('vpaa.dashboard'),
            'president' => route('president.dashboard'),
            default => route('dashboard'),
        };
    @endphp
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800">{{ $roleLabel }} Review Queue</h2>
                <p class="text-sm text-gray-500">Applications awaiting {{ strtolower($roleLabel) }} evaluation.</p>
            </div>
            <a href="{{ $dashboardRoute }}"
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

            <div class="bg-white rounded-2xl shadow-card border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">Pending Applications</h3>
                </div>

                @if($applications->isEmpty())
                    <div class="p-6 text-sm text-gray-500">No applications in {{ strtolower($roleLabel) }} review.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-left">
                                <tr>
                                    <th class="px-4 py-2">Faculty</th>
                                    <th class="px-4 py-2">Submitted</th>
                                    <th class="px-4 py-2">Cycle</th>
                                    <th class="px-4 py-2">Status</th>
                                    <th class="px-4 py-2 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @foreach($applications as $app)
                                    <tr>
                                        <td class="px-4 py-2">
                                            <div class="font-medium text-gray-800">{{ $app->faculty?->name ?? 'Faculty' }}</div>
                                            <div class="text-xs text-gray-500">ID #{{ $app->faculty_user_id }}</div>
                                        </td>
                                        <td class="px-4 py-2 text-gray-600">
                                            {{ optional($app->submitted_at)->format('M d, Y') ?? '—' }}
                                        </td>
                                        <td class="px-4 py-2 text-gray-600">{{ $app->cycle_year }}</td>
                                        <td class="px-4 py-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] border bg-blue-50 text-blue-700 border-blue-200">
                                                {{ ucfirst(str_replace('_',' ', $app->status)) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-right">
                                            <a href="{{ route('reclassification.review.show', $app) }}"
                                               class="text-bu hover:underline font-semibold">
                                                Review
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

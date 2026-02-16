<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800">Department Submissions</h2>
                <p class="text-sm text-gray-500">
                    Active-period submissions in your department.
                </p>
            </div>
            <a href="{{ route('dashboard') }}"
               class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50">
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-10 bg-bu-muted min-h-screen">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if(!$hasActivePeriod)
                <div class="bg-amber-50 border border-amber-200 rounded-2xl shadow-card p-6">
                    <div class="text-sm font-semibold text-amber-900">No active period</div>
                    <div class="mt-1 text-sm text-amber-800">
                        Submissions are only shown for the active period. Past submissions are available in Reclassification History.
                    </div>
                </div>
            @endif

            <form method="GET" action="{{ route('dean.submissions') }}"
                  class="bg-white rounded-2xl shadow-card border border-gray-200 p-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600">Search</label>
                    <input type="text"
                           name="q"
                           value="{{ $q }}"
                           placeholder="Faculty name, email, or employee no."
                           class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600">Status</label>
                    <select name="status"
                            class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu">
                        <option value="all" @selected($status === 'all')>All</option>
                        <option value="submitted" @selected($status === 'submitted')>Submitted (In Review)</option>
                        @foreach(['dean_review','hr_review','vpaa_review','vpaa_approved','president_review','returned_to_faculty','finalized'] as $st)
                            <option value="{{ $st }}" @selected($status === $st)>
                                {{ ucfirst(str_replace('_',' ', $st)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
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
                <div class="md:col-span-4 flex items-center justify-end gap-2">
                    <a href="{{ route('dean.submissions') }}"
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
                    <h3 class="text-lg font-semibold text-gray-800">Submissions</h3>
                </div>

                @if($applications->isEmpty())
                    <div class="p-6 text-sm text-gray-500">
                        {{ $hasActivePeriod ? 'No submissions match your filters.' : 'No submissions to display because there is no active period.' }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-left">
                                <tr>
                                    <th class="px-4 py-2">Faculty</th>
                                    <th class="px-4 py-2">Rank</th>
                                    <th class="px-4 py-2">Cycle</th>
                                    <th class="px-4 py-2">Status</th>
                                    <th class="px-4 py-2">Submitted</th>
                                    <th class="px-4 py-2 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @foreach($applications as $app)
                                    @php
                                        $profile = $app->faculty?->facultyProfile;
                                        $rankLabel = $profile?->rankLevel?->title ?: ($profile?->teaching_rank ?? '--');
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-2">
                                            <div class="font-medium text-gray-800">{{ $app->faculty?->name ?? 'Faculty' }}</div>
                                            <div class="text-xs text-gray-500">ID #{{ $app->faculty_user_id }}</div>
                                        </td>
                                        <td class="px-4 py-2 text-gray-600">{{ $rankLabel }}</td>
                                        <td class="px-4 py-2 text-gray-600">{{ $app->cycle_year ?? '--' }}</td>
                                        <td class="px-4 py-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] border bg-blue-50 text-blue-700 border-blue-200">
                                                {{ ucfirst(str_replace('_',' ', $app->status)) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-gray-600">
                                            {{ optional($app->submitted_at)->format('M d, Y') ?? '--' }}
                                        </td>
                                        <td class="px-4 py-2 text-right">
                                            @if($app->status === 'dean_review')
                                                <a href="{{ route('reclassification.review.show', $app) }}"
                                                   class="text-bu hover:underline font-semibold">
                                                    Review
                                                </a>
                                            @else
                                                <span class="text-xs text-gray-400">Not in Dean stage</span>
                                            @endif
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

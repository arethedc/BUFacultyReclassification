<x-app-layout>
    {{-- HEADER --}}
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-gray-800">Faculty Records</h2>
            <p class="text-sm text-gray-500">
                HR-managed faculty profiles used for reclassification and evaluation.
            </p>
        </div>
    </x-slot>

    @php
        $q = request('q');
        $status = request('status', 'active'); // active | inactive | all
    @endphp

    {{-- ✅ Outside click clears selection --}}
    <div class="py-12 bg-bu-muted min-h-screen"
         x-data="{
            selectedUserUrl: null,
            selectedFacultyUrl: null,
            selectedRowId: null,

            select(rowId, userUrl, facultyUrl) {
                this.selectedRowId = rowId;
                this.selectedUserUrl = userUrl;
                this.selectedFacultyUrl = facultyUrl;
            },
            clear() {
                this.selectedRowId = null;
                this.selectedUserUrl = null;
                this.selectedFacultyUrl = null;
            }
         }"
         @click="clear()">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- SEARCH + FILTER + ACTION BAR --}}
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

                <div class="flex flex-col sm:flex-row sm:items-center gap-3 w-full lg:w-auto">

                    {{-- Search --}}
                    <form method="GET"
                          action="{{ route('faculty.index') }}"
                          class="flex items-center gap-2 w-full sm:w-auto">
                        <input type="text"
                               name="q"
                               value="{{ $q }}"
                               placeholder="Search name, email, or employee no."
                               class="h-11 w-full sm:w-80 rounded-xl
                                      border border-gray-300 bg-white px-4 text-sm
                                      focus:border-bu focus:ring-bu">

                        {{-- keep status when searching --}}
                        <input type="hidden" name="status" value="{{ $status }}">

                        <button type="submit"
                                class="h-11 px-5 rounded-xl
                                       bg-bu text-white text-sm font-medium
                                       hover:bg-bu-dark shadow-soft transition
                                       flex items-center justify-center">
                            Search
                        </button>

                        @if(request()->filled('q'))
                            <a href="{{ route('faculty.index', ['status' => $status]) }}"
                               class="h-11 px-5 rounded-xl
                                      border border-gray-300 text-gray-700 text-sm
                                      hover:bg-gray-100 transition
                                      flex items-center justify-center">
                                Clear
                            </a>
                        @endif
                    </form>

                    {{-- Status filter pills --}}

                </div>

     {{-- Contextual Actions --}}
<div class="flex items-center gap-2">

    {{-- DEFAULT STATE: no row selected --}}
    <template x-if="!selectedUserUrl">
        <div class="flex items-center gap-2">

            {{-- Create Faculty --}}
            <a href="{{ route('users.create', ['context' => 'faculty']) }}"
               class="h-11 px-6 rounded-xl bg-bu text-white text-sm font-semibold
                      hover:bg-bu-dark shadow-soft transition
                      flex items-center justify-center whitespace-nowrap">
                + Create Faculty
            </a>

            {{-- More Options (Status Filter) --}}
            <div class="relative" x-data="{ open: false }">
                <button @click.stop="open = !open"
                        class="h-11 w-11 rounded-xl border border-gray-300
                               text-gray-600 hover:bg-gray-100
                               flex items-center justify-center transition">
                    ⋮
                </button>

                <div x-show="open"
                     @click.outside="open = false"
                     x-transition
                     class="absolute right-0 mt-2 w-44
                            bg-white rounded-xl shadow-lg border border-gray-200 z-20">

                    <a href="{{ route('faculty.index', ['q' => $q, 'status' => 'active']) }}"
                       class="block px-4 py-2 text-sm hover:bg-gray-50
                              {{ $status === 'active' ? 'font-semibold text-bu' : 'text-gray-700' }}">
                        Show Active
                    </a>

                    <a href="{{ route('faculty.index', ['q' => $q, 'status' => 'inactive']) }}"
                       class="block px-4 py-2 text-sm hover:bg-gray-50
                              {{ $status === 'inactive' ? 'font-semibold text-bu' : 'text-gray-700' }}">
                        Show Inactive
                    </a>

                    <a href="{{ route('faculty.index', ['q' => $q, 'status' => 'all']) }}"
                       class="block px-4 py-2 text-sm hover:bg-gray-50
                              {{ $status === 'all' ? 'font-semibold text-bu' : 'text-gray-700' }}">
                        Show All
                    </a>
                </div>
            </div>
        </div>
    </template>

    {{-- SELECTED STATE --}}
    <template x-if="selectedUserUrl">
        <div class="flex items-center gap-2">
            <a :href="selectedUserUrl"
               class="h-11 px-5 rounded-xl
                      border border-gray-300 text-gray-700 text-sm
                      hover:bg-gray-100 transition
                      flex items-center justify-center whitespace-nowrap">
                View User
            </a>

            <a x-show="selectedFacultyUrl"
               :href="selectedFacultyUrl"
               class="h-11 px-5 rounded-xl
                      bg-bu text-white text-sm
                      hover:bg-bu-dark transition
                      flex items-center justify-center whitespace-nowrap">
                View Faculty
            </a>
        </div>
    </template>

</div>

            </div>

            {{-- TABLE --}}
            <div class="bg-white rounded-2xl shadow-card border border-gray-200 overflow-x-auto"
                 @click.stop>
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-6 py-3 text-left">Employee No.</th>
                            <th class="px-6 py-3 text-left">Name</th>
                            <th class="px-6 py-3 text-left">Department</th>
                            <th class="px-6 py-3 text-left">Rank</th>
                            <th class="px-6 py-3 text-left">Employment</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        @forelse($faculty as $f)
                            <tr class="hover:bg-gray-50 cursor-pointer"
                                :class="selectedRowId === {{ $f->id }} ? 'bg-bu-muted' : ''"
                                @click="select(
                                    {{ $f->id }},
                                    '{{ route('users.edit', $f) }}',
                                    '{{ route('faculty-profiles.edit', $f) }}'
                                )">

                                <td class="px-6 py-4">
                                    {{ $f->facultyProfile?->employee_no ?? '—' }}
                                </td>

                                <td class="px-6 py-4 font-medium text-gray-800">
                                    <div class="flex items-center gap-2">
                                        <span>{{ $f->name }}</span>

                                        {{-- Status badge (visible only when status=all) --}}
                                        @if($status === 'all')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs
                                                {{ $f->status === 'active'
                                                    ? 'bg-green-100 text-green-700'
                                                    : 'bg-gray-200 text-gray-600' }}">
                                                {{ ucfirst($f->status) }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="text-xs text-gray-500">
                                        {{ $f->email }}
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    {{ $f->department?->name ?? '—' }}
                                </td>

                                <td class="px-6 py-4">
                                    {{ $f->facultyProfile?->teaching_rank ?? '—' }}
                                    @if($f->facultyProfile?->rank_step)
                                        – {{ $f->facultyProfile->rank_step }}
                                    @endif
                                </td>

                                <td class="px-6 py-4">
                                    {{ ucfirst(str_replace('_',' ', $f->facultyProfile?->employment_type ?? '—')) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-6 text-center text-gray-500">
                                    No faculty records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            <div>
                {{ $faculty->links() }}
            </div>

        </div>
    </div>
</x-app-layout>

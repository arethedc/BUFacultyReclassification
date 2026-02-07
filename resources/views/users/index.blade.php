<x-app-layout>
    {{-- HEADER --}}
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-gray-800">System Users</h2>
            <p class="text-sm text-gray-500">
                Manage system user records.
            </p>
        </div>
    </x-slot>

    @php
        $q = request('q');
        $status = request('status', 'active'); // active | inactive | all
    @endphp

    {{-- Outside click clears selection --}}
    <div class="py-12 bg-bu-muted min-h-screen"
         x-data="{
            selectedRowId: null,
            selectedUserUrl: null,
            selectedFacultyUrl: null,

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

            {{-- SEARCH + ACTION BAR --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

                {{-- Search --}}
                <form method="GET"
                      action="{{ route('users.index') }}"
                      class="flex items-center gap-2 w-full sm:w-auto">
                    <input type="text"
                           name="q"
                           value="{{ $q }}"
                           placeholder="Search name, email, role, or employee no."
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
                        <a href="{{ route('users.index', ['status' => $status]) }}"
                           class="h-11 px-5 rounded-xl
                                  border border-gray-300 text-gray-700 text-sm
                                  hover:bg-gray-100 transition
                                  flex items-center justify-center">
                            Clear
                        </a>
                    @endif
                </form>

                {{-- Contextual Actions --}}
                <div class="flex items-center gap-2">

                    {{-- DEFAULT (no selection): Create + More --}}
                    <template x-if="!selectedUserUrl">
                        <div class="flex items-center gap-2">

                            <a href="{{ route('users.create') }}"
                               class="h-11 px-6 rounded-xl
                                      bg-bu text-white text-sm font-semibold
                                      hover:bg-bu-dark shadow-soft transition
                                      flex items-center justify-center whitespace-nowrap">
                                + Create User
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

                                    <a href="{{ route('users.index', ['q' => $q, 'status' => 'active']) }}"
                                       class="block px-4 py-2 text-sm hover:bg-gray-50
                                              {{ $status === 'active' ? 'font-semibold text-bu' : 'text-gray-700' }}">
                                        Show Active
                                    </a>

                                    <a href="{{ route('users.index', ['q' => $q, 'status' => 'inactive']) }}"
                                       class="block px-4 py-2 text-sm hover:bg-gray-50
                                              {{ $status === 'inactive' ? 'font-semibold text-bu' : 'text-gray-700' }}">
                                        Show Inactive
                                    </a>

                                    <a href="{{ route('users.index', ['q' => $q, 'status' => 'all']) }}"
                                       class="block px-4 py-2 text-sm hover:bg-gray-50
                                              {{ $status === 'all' ? 'font-semibold text-bu' : 'text-gray-700' }}">
                                        Show All
                                    </a>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- SELECTED: View actions only --}}
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
                            <th class="px-6 py-3 text-left">Name</th>
                            <th class="px-6 py-3 text-left">Email</th>
                            <th class="px-6 py-3 text-left">Role</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        @forelse($users as $user)
                            <tr class="hover:bg-gray-50 cursor-pointer"
                                :class="selectedRowId === {{ $user->id }} ? 'bg-bu-muted' : ''"
                                @click="select(
                                    {{ $user->id }},
                                    '{{ route('users.edit', $user) }}',
                                    {{ $user->role === 'faculty'
                                        ? "'" . route('faculty-profiles.edit', $user) . "'"
                                        : 'null'
                                    }}
                                )">

                                <td class="px-6 py-4 font-medium text-gray-800">
                                    <div class="flex items-center gap-2">
                                        <span>{{ $user->name }}</span>

                                        {{-- Status badge only when viewing All --}}
                                        @if($status === 'all')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs
                                                {{ $user->status === 'active'
                                                    ? 'bg-green-100 text-green-700'
                                                    : 'bg-gray-200 text-gray-600' }}">
                                                {{ ucfirst($user->status) }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-gray-700">
                                    {{ $user->email }}
                                </td>

                                <td class="px-6 py-4">
                                    {{ ucfirst(str_replace('_',' ', $user->role)) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-6 text-center text-gray-500">
                                    No users found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            <div>
                {{ $users->links() }}
            </div>

        </div>
    </div>
</x-app-layout>

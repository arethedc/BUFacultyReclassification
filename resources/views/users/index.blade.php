<x-app-layout>
    {{-- HEADER --}}
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-gray-800">
                Faculty & System Users
            </h2>
            <p class="text-sm text-gray-500">
                Manage system users and faculty records.
            </p>
        </div>
    </x-slot>

    <div class="py-12 bg-bu-muted min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- ACTION BAR --}}
            <div class="flex items-center justify-between">
                <div class="flex gap-3">
                    <input type="text"
                           placeholder="Search name or employee no."
                           class="w-72 rounded-xl border border-gray-300
                                  bg-white focus:border-bu focus:ring-bu">
                </div>

                <a href="{{ route('users.create') }}"
                   class="px-5 py-2.5 rounded-xl bg-bu text-white
                          hover:bg-bu-dark shadow-soft transition">
                    + Create User
                </a>
            </div>

            {{-- USER TABLE CARD --}}
            <div class="bg-white rounded-2xl shadow-card border border-gray-200">

                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">
                        User List
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-6 py-3 text-left">Employee No.</th>
                                <th class="px-6 py-3 text-left">Name</th>
                                <th class="px-6 py-3 text-left">Role</th>
                                <th class="px-6 py-3 text-left">Department</th>
                                <th class="px-6 py-3 text-left">Rank</th>
                                <th class="px-6 py-3 text-left">Status</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y">
                            {{-- SAMPLE ROW (STATIC FOR NOW) --}}
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">BU-2021-0045</td>
                                <td class="px-6 py-4 font-medium text-gray-800">
                                    Dela Cruz, Juan M.
                                </td>
                                <td class="px-6 py-4">Faculty</td>
                                <td class="px-6 py-4">College of Education</td>
                                <td class="px-6 py-4">
                                    Assistant Professor – B
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-full
                                                 bg-green-100 text-green-700">
                                        Active
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                   <a href="{{ route('users.edit', 1) }}">View / Edit</a>

                                </td>
                            </tr>

                            {{-- more rows later --}}
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>

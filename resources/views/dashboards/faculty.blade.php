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
            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    Faculty Profile
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">Employee No.</span>
                        <div class="font-medium text-gray-800">
                            BU-2021-0045
                        </div>
                    </div>

                    <div>
                        <span class="text-gray-500">Department</span>
                        <div class="font-medium text-gray-800">
                            College of Education
                        </div>
                    </div>

                    <div>
                        <span class="text-gray-500">Current Rank</span>
                        <div class="font-medium text-gray-800">
                            Assistant Professor – B
                        </div>
                    </div>

                    <div>
                        <span class="text-gray-500">Employment Type</span>
                        <div class="font-medium text-gray-800">
                            Full-time
                        </div>
                    </div>
                </div>
            </div>

            {{-- QUICK ACTION --}}
            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">
                    Reclassification
                </h3>

                <p class="text-sm text-gray-500 mb-4">
                    Submit a reclassification application for the current term.
                </p>
<a href="{{ route('faculty.reclassification') }}"
   class="px-6 py-3 rounded-xl bg-bu text-white shadow">
    Open Reclassification
</a>
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
                                <th class="px-6 py-3 text-left">Applied Rank</th>
                                <th class="px-6 py-3 text-left">Status</th>
                                <th class="px-6 py-3 text-right">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y">
                            {{-- SAMPLE ROW --}}
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">2023 – 2026</td>
                                <td class="px-6 py-4">Associate Professor</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-full
                                                 bg-yellow-100 text-yellow-700">
                                        Under Review
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="#"
                                       class="text-bu hover:underline font-medium">
                                        View
                                    </a>
                                </td>
                            </tr>

                            {{-- empty state later --}}
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

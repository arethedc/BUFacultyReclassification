<x-app-layout>
    {{-- PAGE HEADER --}}
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-gray-800">
                HR Dashboard
            </h2>
            <p class="text-sm text-gray-500">
                Manage faculty records and verification workflows
            </p>
        </div>
    </x-slot>

    {{-- PAGE BODY --}}
    <div class="py-12 bg-bu-muted min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- DASHBOARD GRID --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                {{-- FACULTY MANAGEMENT CARD --}}
                <div class="bg-white rounded-2xl shadow-card
                            border border-gray-200
                            hover:shadow-md transition">

                    <div class="p-7 flex flex-col h-full justify-between">

                        <div>
                            <div class="inline-flex items-center justify-center
                                        h-12 w-12 rounded-xl
                                        bg-bu-light text-bu mb-4">
                                👤
                            </div>

                            <h3 class="text-lg font-semibold text-gray-800">
                                Faculty Management
                            </h3>

                            <p class="text-sm text-gray-600 mt-2 leading-relaxed">
                                Create, update, and manage faculty accounts used
                                for reclassification and evaluation processes.
                            </p>
                        </div>

                        <div class="pt-8">
                            <a href="{{ route('users.create') }}"
                               class="inline-flex items-center justify-center
                                      px-7 py-3.5 rounded-xl
                                      bg-bu text-white
                                      text-sm font-semibold
                                      shadow-soft
                                      hover:bg-bu-dark
                                      focus:outline-none
                                      focus:ring-2 focus:ring-bu focus:ring-offset-2
                                      transition">
                                Create Faculty User
                            </a>

                                        <div class="pt-8">
                            <a href="{{ route('users.index') }}"
                               class="inline-flex items-center justify-center
                                      px-7 py-3.5 rounded-xl
                                      bg-bu text-white
                                      text-sm font-semibold
                                      shadow-soft
                                      hover:bg-bu-dark
                                      focus:outline-none
                                      focus:ring-2 focus:ring-bu focus:ring-offset-2
                                      transition">
                                View List
                            </a>
                        </div>

                                        <div class="pt-8">
                            <a href="{{ route('users.edit', 1) }}"
                               class="inline-flex items-center justify-center
                                      px-7 py-3.5 rounded-xl
                                      bg-bu text-white
                                      text-sm font-semibold
                                      shadow-soft
                                      hover:bg-bu-dark
                                      focus:outline-none
                                      focus:ring-2 focus:ring-bu focus:ring-offset-2
                                      transition">
                                Edit
                            </a>
                        </div>

                        </div>

                    </div>
                </div>

                {{-- DOCUMENT VERIFICATION CARD --}}
                <div class="bg-white rounded-2xl shadow-card
                            border border-gray-200
                            hover:shadow-md transition">

                    <div class="p-7 flex flex-col h-full justify-between">

                        <div>
                            <div class="inline-flex items-center justify-center
                                        h-12 w-12 rounded-xl
                                        bg-bu-light text-bu mb-4">
                                📄
                            </div>

                            <h3 class="text-lg font-semibold text-gray-800">
                                Document Verification
                            </h3>

                            <p class="text-sm text-gray-600 mt-2 leading-relaxed">
                                Review, validate, and approve submitted faculty
                                documents prior to academic evaluation.
                            </p>
                        </div>

                        <div class="pt-8">
                            <a href="#"
                               class="inline-flex items-center justify-center
                                      px-6 py-3 rounded-xl
                                      border border-bu
                                      text-bu text-sm font-semibold
                                      hover:bg-bu-light
                                      transition">
                                View Submissions
                            </a>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>

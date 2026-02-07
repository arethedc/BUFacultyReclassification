<x-app-layout>
    {{-- PAGE HEADER --}}
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-gray-800">HR Dashboard</h2>
            <p class="text-sm text-gray-500">
                Manage faculty records, accounts, and verification workflows.
            </p>
        </div>
    </x-slot>

    {{-- PAGE BODY --}}
    <div class="py-12 bg-bu-muted min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- DASHBOARD GRID --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                {{-- =====================================================
                    FACULTY MANAGEMENT
                ====================================================== --}}
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 hover:shadow-md transition">
                    <div class="p-7 flex flex-col h-full justify-between">

                        {{-- Card top --}}
                        <div>
                            <div class="inline-flex items-center justify-center h-12 w-12 rounded-xl bg-bu-light text-bu mb-4">
                                👤
                            </div>

                            <h3 class="text-lg font-semibold text-gray-800">Faculty Management</h3>
                            <p class="text-sm text-gray-600 mt-2 leading-relaxed">
                                Create and manage faculty user accounts and HR-controlled faculty profile details
                                used for reclassification and evaluation.
                            </p>

                            {{-- Quick actions label --}}
                            <div class="mt-5 text-xs font-semibold uppercase tracking-wide text-gray-400">
                                Quick Actions
                            </div>
                        </div>

                        {{-- Card actions --}}
                        <div class="pt-6 grid grid-cols-1 sm:grid-cols-2 gap-3">
                            {{-- Primary --}}
                            <a href="{{ route('users.create') }}"
                               class="inline-flex items-center justify-center px-5 py-3 rounded-xl
                                      bg-bu text-white text-sm font-semibold shadow-soft
                                      hover:bg-bu-dark focus:outline-none focus:ring-2 focus:ring-bu focus:ring-offset-2 transition">
                                + Create User
                            </a>

                            {{-- Secondary --}}
                            <a href="{{ route('users.index') }}"
                               class="inline-flex items-center justify-center px-5 py-3 rounded-xl
                                      border border-gray-300 text-gray-700 text-sm font-semibold
                                      hover:bg-gray-50 transition">
                                View Users
                            </a>

                            {{-- Optional: Faculty records list (only if you created faculty.index) --}}
                  
                        </div>
                    </div>
                </div>

                {{-- =====================================================
                    DOCUMENT VERIFICATION
                ====================================================== --}}
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 hover:shadow-md transition">
                    <div class="p-7 flex flex-col h-full justify-between">

                        {{-- Card top --}}
                        <div>
                            <div class="inline-flex items-center justify-center h-12 w-12 rounded-xl bg-bu-light text-bu mb-4">
                                📄
                            </div>

                            <h3 class="text-lg font-semibold text-gray-800">Document Verification</h3>
                            <p class="text-sm text-gray-600 mt-2 leading-relaxed">
                                Review, validate, and approve submitted evidence documents prior to academic evaluation.
                                Keep decisions audit-friendly and traceable.
                            </p>

                            <div class="mt-5 text-xs font-semibold uppercase tracking-wide text-gray-400">
                                Workflow
                            </div>
                        </div>

                        {{-- Card actions --}}
                        <div class="pt-6 grid grid-cols-1 sm:grid-cols-2 gap-3">
                            {{-- Primary --}}
                            <a href="#"
                               class="inline-flex items-center justify-center px-5 py-3 rounded-xl
                                      bg-bu text-white text-sm font-semibold shadow-soft
                                      hover:bg-bu-dark focus:outline-none focus:ring-2 focus:ring-bu focus:ring-offset-2 transition">
                                View Submissions
                            </a>

                            {{-- Secondary --}}
                            <a href="#"
                               class="inline-flex items-center justify-center px-5 py-3 rounded-xl
                                      border border-gray-300 text-gray-700 text-sm font-semibold
                                      hover:bg-gray-50 transition">
                                Verification Log
                            </a>
                        </div>

                        {{-- Optional hint row --}}
                        <div class="mt-6 text-xs text-gray-500">
                            Tip: Require evidence per row to support scoring and avoid blind approvals.
                        </div>

                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>

<x-app-layout>
    {{-- HEADER --}}
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-gray-800">
                Edit User
            </h2>
            <p class="text-sm text-gray-500">
                Update user information and system profile.
            </p>
        </div>
    </x-slot>

    <div class="py-12 bg-bu-muted min-h-screen">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            <form x-data="{ role: 'faculty' }" class="space-y-8 pb-12">

                {{-- =========================
                     STEP 1: ROLE (LOCKED)
                ========================== --}}
                <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                    <div class="px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">
                            User Role
                        </h3>
                        <p class="text-sm text-gray-500">
                            Role cannot be changed.
                        </p>
                    </div>

                    <div class="p-6">
                        <select disabled
                                class="w-full md:w-1/2 rounded-xl
                                       border border-gray-300 bg-gray-100
                                       text-gray-600 cursor-not-allowed">
                            <option>Faculty</option>
                        </select>
                    </div>
                </div>

                {{-- =========================
                     STEP 2: ACCOUNT (LIMITED)
                ========================== --}}
                <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                    <div class="px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Account Information
                        </h3>
                        <p class="text-sm text-gray-500">
                            Login credentials.
                        </p>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Email Address
                            </label>
                            <input type="email" disabled
                                   value="juan.delacruz@baliuagu.edu.ph"
                                   class="mt-1 w-full rounded-xl
                                          border border-gray-300 bg-gray-100
                                          text-gray-600 cursor-not-allowed">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Reset Password
                            </label>
                            <button type="button"
                                    class="mt-2 px-4 py-2 rounded-xl
                                           border border-gray-300
                                           text-gray-700 hover:bg-gray-100 transition">
                                Send Password Reset
                            </button>
                        </div>

                    </div>
                </div>

                {{-- =========================
                     STEP 3: PERSONAL DETAILS
                ========================== --}}
                <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                    <div class="px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Personal & System Identity
                        </h3>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Employee Number
                            </label>
                            <input type="text" disabled
                                   value="BU-2021-0045"
                                   class="mt-1 w-full rounded-xl
                                          border border-gray-300 bg-gray-100
                                          text-gray-600 cursor-not-allowed">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Department
                            </label>
                            <select class="mt-1 w-full rounded-xl
                                           border border-gray-300 bg-white
                                           focus:border-bu focus:ring-bu">
                                <option>College of Education</option>
                                <option>College of Engineering</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                First Name
                            </label>
                            <input type="text"
                                   value="Juan"
                                   class="mt-1 w-full rounded-xl
                                          border border-gray-300 bg-white
                                          focus:border-bu focus:ring-bu">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Last Name
                            </label>
                            <input type="text"
                                   value="Dela Cruz"
                                   class="mt-1 w-full rounded-xl
                                          border border-gray-300 bg-white
                                          focus:border-bu focus:ring-bu">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Middle Name
                            </label>
                            <input type="text"
                                   value="M."
                                   class="mt-1 w-full rounded-xl
                                          border border-gray-300 bg-white
                                          focus:border-bu focus:ring-bu">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Suffix
                            </label>
                            <input type="text"
                                   class="mt-1 w-full rounded-xl
                                          border border-gray-300 bg-white
                                          focus:border-bu focus:ring-bu">
                        </div>

                    </div>
                </div>

                {{-- =========================
                     STEP 4: FACULTY ONLY
                ========================== --}}
                <div x-show="role === 'faculty'" x-transition
                     class="bg-white rounded-2xl shadow-card border border-gray-200">

                    <div class="px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Faculty Status
                        </h3>
                        <p class="text-sm text-gray-500">
                            Updated after approved reclassification.
                        </p>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Employment Type
                            </label>
                            <select class="mt-1 w-full rounded-xl
                                           border border-gray-300 bg-white
                                           focus:border-bu focus:ring-bu">
                                <option>Full-time</option>
                                <option>Part-time</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Academic Rank
                            </label>
                            <select class="mt-1 w-full rounded-xl
                                           border border-gray-300 bg-white
                                           focus:border-bu focus:ring-bu">
                                <option>Assistant Professor</option>
                                <option>Associate Professor</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Rank Step
                            </label>
                            <select class="mt-1 w-full rounded-xl
                                           border border-gray-300 bg-white
                                           focus:border-bu focus:ring-bu">
                                <option>A</option>
                                <option>B</option>
                                <option>C</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Date of Original Appointment
                            </label>
                            <input type="date" disabled
                                   class="mt-1 w-full rounded-xl
                                          border border-gray-300 bg-gray-100
                                          text-gray-600 cursor-not-allowed">
                        </div>

                    </div>
                </div>

                {{-- =========================
                     ACTIONS
                ========================== --}}
                <div class="pt-6 border-t flex justify-end gap-4">
                    <a href="{{ route('users.index') }}"
                       class="px-6 py-2.5 rounded-xl border border-gray-300
                              text-gray-700 hover:bg-gray-100 transition">
                        Cancel
                    </a>

                    <button type="submit"
                            class="px-6 py-2.5 rounded-xl bg-bu text-white
                                   hover:bg-bu-dark shadow-soft transition">
                        Save Changes
                    </button>
                </div>

            </form>
        </div>
    </div>
</x-app-layout>

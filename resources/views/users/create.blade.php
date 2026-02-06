<x-app-layout>
    {{-- HEADER --}}
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-gray-800">
                Create User
            </h2>
            <p class="text-sm text-gray-500">
                Create system users based on role and responsibility.
            </p>
        </div>
    </x-slot>

    <div class="py-12 bg-bu-muted min-h-screen">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            <form x-data="{ role: '' }" class="space-y-8 pb-12">

                {{-- =========================
                     STEP 1: ROLE SELECTION
                ========================== --}}
                <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                    <div class="px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">
                            User Role
                        </h3>
                        <p class="text-sm text-gray-500">
                            Determines required information.
                        </p>
                    </div>

                    <div class="p-6">
                        <label class="block text-sm font-medium text-gray-700">Role</label>
                        <select x-model="role"
                                class="mt-1 w-full md:w-1/2 rounded-xl
                                       border border-gray-300 bg-white
                                       focus:border-bu focus:ring-bu">
                            <option value="">Select Role</option>
                            <option value="faculty">Faculty</option>
                            <option value="dean">Dean</option>
                            <option value="hr">HR</option>
                            <option value="vpaa">VPAA</option>
                            <option value="president">President</option>
                        </select>
                    </div>
                </div>

                {{-- =========================
                     STEP 2: ACCOUNT CREDENTIALS
                ========================== --}}
                <div x-show="role !== ''" x-transition
                     class="bg-white rounded-2xl shadow-card border border-gray-200">

                    <div class="px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Account Credentials
                        </h3>
                        <p class="text-sm text-gray-500">
                            Used for system login.
                        </p>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Email Address
                            </label>
                            <input type="email"
                                   class="mt-1 w-full rounded-xl
                                          border border-gray-300 bg-white
                                          focus:border-bu focus:ring-bu">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Password
                            </label>
                            <input type="password"
                                   class="mt-1 w-full rounded-xl
                                          border border-gray-300 bg-white
                                          focus:border-bu focus:ring-bu">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Confirm Password
                            </label>
                            <input type="password"
                                   class="mt-1 w-full rounded-xl
                                          border border-gray-300 bg-white
                                          focus:border-bu focus:ring-bu">
                        </div>

                    </div>
                </div>

                {{-- =========================
                     STEP 3: PERSONAL DETAILS
                ========================== --}}
                <div x-show="role !== ''" x-transition
                     class="bg-white rounded-2xl shadow-card border border-gray-200">

                    <div class="px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Personal & System Identity
                        </h3>
                        <p class="text-sm text-gray-500">
                            Required for all users.
                        </p>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Employee Number / Staff ID
                            </label>
                            <input type="text"
                                   class="mt-1 w-full rounded-xl
                                          border border-gray-300 bg-white
                                          focus:border-bu focus:ring-bu">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Department
                            </label>
                            <select
                                class="mt-1 w-full rounded-xl
                                       border border-gray-300 bg-white
                                       focus:border-bu focus:ring-bu">
                                <option>Select Department</option>
                                <option>College of Education</option>
                                <option>College of Engineering</option>
                                <option>College of Business</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                First Name
                            </label>
                            <input type="text"
                                   class="mt-1 w-full rounded-xl
                                          border border-gray-300 bg-white
                                          focus:border-bu focus:ring-bu">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Last Name
                            </label>
                            <input type="text"
                                   class="mt-1 w-full rounded-xl
                                          border border-gray-300 bg-white
                                          focus:border-bu focus:ring-bu">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Middle Name (Optional)
                            </label>
                            <input type="text"
                                   class="mt-1 w-full rounded-xl
                                          border border-gray-300 bg-white
                                          focus:border-bu focus:ring-bu">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Suffix (Optional)
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
                            Faculty Reclassification Information
                        </h3>
                        <p class="text-sm text-gray-500">
                            Required for faculty members.
                        </p>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Employment Type
                            </label>
                            <div class="mt-3 flex gap-8">
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="radio" name="employment_type"
                                           class="text-bu focus:ring-bu">
                                    Full-time
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="radio" name="employment_type"
                                           class="text-bu focus:ring-bu">
                                    Part-time
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Academic Rank
                            </label>
                            <select
                                class="mt-1 w-full rounded-xl
                                       border border-gray-300 bg-white
                                       focus:border-bu focus:ring-bu">
                                <option>Instructor</option>
                                <option>Assistant Professor</option>
                                <option>Associate Professor</option>
                                <option>Full Professor</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Rank Step
                            </label>
                            <select
                                class="mt-1 w-full rounded-xl
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
                            <input type="date"
                                   class="mt-1 w-full rounded-xl
                                          border border-gray-300 bg-white
                                          focus:border-bu focus:ring-bu">
                        </div>

                    </div>
                </div>

                {{-- =========================
                     ACTIONS
                ========================== --}}
                <div class="pt-6 border-t flex justify-end gap-4">
                    <button type="button"
                            class="px-6 py-2.5 rounded-xl border border-gray-300
                                   text-gray-700 hover:bg-gray-100 transition">
                        Cancel
                    </button>

                    <button type="submit"
                            class="px-6 py-2.5 rounded-xl bg-bu text-white
                                   hover:bg-bu-dark shadow-soft
                                   focus:ring-2 focus:ring-bu focus:ring-offset-2
                                   transition">
                        Create User
                    </button>
                </div>

            </form>
        </div>
    </div>
</x-app-layout>

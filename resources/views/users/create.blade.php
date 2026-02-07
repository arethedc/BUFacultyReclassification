<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-gray-800">Create User</h2>
            <p class="text-sm text-gray-500">
                Create system users based on role and responsibility.
            </p>
        </div>
    </x-slot>

    <div class="py-12 bg-bu-muted min-h-screen">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            <form
                x-data="{
                    role: '{{ old('role', ($context ?? null) === 'faculty' ? 'faculty' : '') }}'
                }"
                method="POST"
                action="{{ route('users.store') }}"
                class="space-y-8 pb-12"
            >
                @csrf

                {{-- ✅ TOP ERROR SUMMARY --}}
                @if ($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-800 rounded-2xl p-5">
                        <div class="font-semibold mb-2">Please fix the errors below.</div>
                        <ul class="list-disc ml-5 text-sm space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- =========================
                    STEP 1: ROLE SELECTION
                ========================== --}}
                @if(($context ?? null) !== 'faculty')
                    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                        <div class="px-6 py-4 border-b">
                            <h3 class="text-lg font-semibold text-gray-800">User Role</h3>
                            <p class="text-sm text-gray-500">Determines required information.</p>
                        </div>

                        <div class="p-6">
                            <label class="block text-sm font-medium text-gray-700">Role</label>

                            <select
                                x-model="role"
                                name="role"
                                required
                                class="mt-1 w-full md:w-1/2 rounded-xl border bg-white
                                {{ $errors->has('role') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-bu focus:ring-bu' }}"
                            >
                                <option value="">Select Role</option>
                                <option value="faculty" {{ old('role')==='faculty' ? 'selected' : '' }}>Faculty</option>
                                <option value="dean" {{ old('role')==='dean' ? 'selected' : '' }}>Dean</option>
                                <option value="hr" {{ old('role')==='hr' ? 'selected' : '' }}>HR</option>
                                <option value="vpaa" {{ old('role')==='vpaa' ? 'selected' : '' }}>VPAA</option>
                                <option value="president" {{ old('role')==='president' ? 'selected' : '' }}>President</option>
                            </select>

                            @error('role')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                @else
                    {{-- FACULTY CONTEXT: enforce role faculty --}}
                    <input type="hidden" name="role" value="faculty">
                @endif

                {{-- =========================
                    STEP 2: ACCOUNT CREDENTIALS
                ========================== --}}
                <div x-show="role !== ''" x-transition
                     class="bg-white rounded-2xl shadow-card border border-gray-200">

                    <div class="px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">Account Credentials</h3>
                        <p class="text-sm text-gray-500">Used for system login.</p>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Email Address</label>
                            <input
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                class="mt-1 w-full rounded-xl border bg-white
                                {{ $errors->has('email') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-bu focus:ring-bu' }}"
                            >
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Password</label>
                            <input
                                type="password"
                                name="password"
                                required
                                class="mt-1 w-full rounded-xl border bg-white
                                {{ $errors->has('password') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-bu focus:ring-bu' }}"
                            >
                            @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                            <input
                                type="password"
                                name="password_confirmation"
                                required
                                class="mt-1 w-full rounded-xl border bg-white
                                {{ $errors->has('password_confirmation') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-bu focus:ring-bu' }}"
                            >
                            @error('password_confirmation')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- =========================
                    STEP 3: PERSONAL DETAILS
                ========================== --}}
                <div x-show="role !== ''" x-transition
                     class="bg-white rounded-2xl shadow-card border border-gray-200">

                    <div class="px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">Personal & System Identity</h3>
                        <p class="text-sm text-gray-500">Required for all users.</p>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">

                        <div>
                            <label class="block text-sm font-medium text-gray-700">First Name</label>
                            <input
                                type="text"
                                name="first_name"
                                value="{{ old('first_name') }}"
                                required
                                class="mt-1 w-full rounded-xl border bg-white
                                {{ $errors->has('first_name') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-bu focus:ring-bu' }}"
                            >
                            @error('first_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Last Name</label>
                            <input
                                type="text"
                                name="last_name"
                                value="{{ old('last_name') }}"
                                required
                                class="mt-1 w-full rounded-xl border bg-white
                                {{ $errors->has('last_name') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-bu focus:ring-bu' }}"
                            >
                            @error('last_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Middle Name (Optional)</label>
                            <input
                                type="text"
                                name="middle_name"
                                value="{{ old('middle_name') }}"
                                class="mt-1 w-full rounded-xl border bg-white
                                {{ $errors->has('middle_name') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-bu focus:ring-bu' }}"
                            >
                            @error('middle_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Suffix (Optional)</label>
                            <input
                                type="text"
                                name="suffix"
                                value="{{ old('suffix') }}"
                                class="mt-1 w-full rounded-xl border bg-white
                                {{ $errors->has('suffix') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-bu focus:ring-bu' }}"
                            >
                            @error('suffix')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- ✅ Department required for faculty/dean (matches controller rule) --}}
                        <div x-show="role === 'faculty' || role === 'dean'" x-transition class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Department</label>

                            <select
                                name="department_id"
                                class="mt-1 w-full md:w-1/2 rounded-xl border bg-white
                                {{ $errors->has('department_id') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-bu focus:ring-bu' }}"
                            >
                                <option value="">Select Department</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('department_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror

                            <p class="mt-1 text-xs text-gray-500">
                                Required for Faculty and Dean.
                            </p>
                        </div>

                    </div>
                </div>

                {{-- =========================
                    STEP 4: FACULTY ONLY
                ========================== --}}
                <div x-show="role === 'faculty'" x-transition
                     class="bg-white rounded-2xl shadow-card border border-gray-200">

                    <div class="px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">Faculty Reclassification Information</h3>
                        <p class="text-sm text-gray-500">Required for faculty members.</p>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Employee Number / Staff ID
                            </label>

                            <input
                                type="text"
                                name="employee_no"
                                value="{{ old('employee_no') }}"
                                class="mt-1 w-full rounded-xl border bg-white
                                {{ $errors->has('employee_no') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-bu focus:ring-bu' }}"
                            >

                            @error('employee_no')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror

                            <p class="mt-1 text-xs text-gray-500">
                                Required when Role = Faculty (per validation rules).
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Employment Type</label>

                            @php $emp = old('employment_type', 'full_time'); @endphp

                            <div class="mt-3 flex gap-8">
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="radio" name="employment_type" value="full_time"
                                           class="text-bu focus:ring-bu"
                                           {{ $emp === 'full_time' ? 'checked' : '' }}>
                                    Full-time
                                </label>

                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="radio" name="employment_type" value="part_time"
                                           class="text-bu focus:ring-bu"
                                           {{ $emp === 'part_time' ? 'checked' : '' }}>
                                    Part-time
                                </label>
                            </div>

                            @error('employment_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Academic Rank</label>

                            <select
                                name="teaching_rank"
                                class="mt-1 w-full rounded-xl border bg-white
                                {{ $errors->has('teaching_rank') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-bu focus:ring-bu' }}"
                            >
                                <option value="">Select Teaching Rank</option>
                                @foreach(['Instructor','Assistant Professor','Associate Professor','Full Professor'] as $r)
                                    <option value="{{ $r }}" {{ old('teaching_rank') === $r ? 'selected' : '' }}>
                                        {{ $r }}
                                    </option>
                                @endforeach
                            </select>

                            @error('teaching_rank')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Rank Step</label>

                            <select
                                name="rank_step"
                                class="mt-1 w-full rounded-xl border bg-white
                                {{ $errors->has('rank_step') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-bu focus:ring-bu' }}"
                            >
                                <option value="">Select Rank Step</option>
                                @foreach(['A','B','C'] as $s)
                                    <option value="{{ $s }}" {{ old('rank_step') === $s ? 'selected' : '' }}>
                                        {{ $s }}
                                    </option>
                                @endforeach
                            </select>

                            @error('rank_step')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date of Original Appointment</label>

                            <input
                                type="date"
                                name="original_appointment_date"
                                value="{{ old('original_appointment_date') }}"
                                class="mt-1 w-full rounded-xl border bg-white
                                {{ $errors->has('original_appointment_date') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-bu focus:ring-bu' }}"
                            >

                            @error('original_appointment_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- =========================
                    ACTION BUTTONS
                ========================== --}}
                <div class="pt-6 border-t flex justify-end gap-4">
                    <a href="{{ route('users.index') }}"
                       class="px-6 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-100 transition">
                        Cancel
                    </a>

                    <button type="submit"
                            class="px-6 py-2.5 rounded-xl bg-bu text-white hover:bg-bu-dark shadow-soft
                                   focus:ring-2 focus:ring-bu focus:ring-offset-2 transition">
                        Create User
                    </button>
                </div>

            </form>
        </div>
    </div>
</x-app-layout>

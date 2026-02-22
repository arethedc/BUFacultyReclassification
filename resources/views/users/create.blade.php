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
                    role: '{{ old('role', ($forceRole ?? null) ? $forceRole : (($context ?? null) === 'faculty' ? 'faculty' : '')) }}',
                    manualPassword: {{ old('manual_password') ? 'true' : 'false' }},
                    password: '',
                    passwordConfirmation: '',
                    showManualPassword: false,
                    showManualPasswordConfirmation: false,
                    formatEmployeeNo(value) {
                        const digits = String(value || '').replace(/\D/g, '').slice(0, 7);
                        return digits.length > 4
                            ? digits.slice(0, 4) + '-' + digits.slice(4)
                            : digits;
                    },
                    hasPasswordValues() {
                        return this.password.length > 0 || this.passwordConfirmation.length > 0;
                    },
                    passwordsMatch() {
                        return this.password !== '' && this.password === this.passwordConfirmation;
                    },
                    passwordsMismatch() {
                        return this.passwordConfirmation !== '' && this.password !== this.passwordConfirmation;
                    }
                }"
                method="POST"
                action="{{ $actionRoute ?? route('users.store') }}"
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
                @if(($forceRole ?? null) === 'faculty')
                    <input type="hidden" name="role" value="faculty">
                @elseif(($context ?? null) !== 'faculty')
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
                            <label class="inline-flex items-center gap-3 text-sm text-gray-700">
                                <input
                                    type="checkbox"
                                    name="manual_password"
                                    value="1"
                                    x-model="manualPassword"
                                    class="rounded border-gray-300 text-bu focus:ring-bu"
                                >
                                Set password manually
                            </label>
                            <p class="mt-1 text-xs text-gray-500" x-show="!manualPassword">
                                If unchecked, the system generates a temporary password and sends a password setup email.
                            </p>
                            <p class="mt-1 text-xs text-gray-500" x-show="manualPassword">
                                If checked, enter password and confirmation below.
                            </p>
                        </div>

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

                        <div x-show="manualPassword" x-transition>
                            <label class="block text-sm font-medium text-gray-700">Password</label>
                            <div class="relative mt-1">
                                <input
                                    x-bind:type="showManualPassword ? 'text' : 'password'"
                                    name="password"
                                    :required="manualPassword"
                                    x-model="password"
                                    class="w-full rounded-xl border bg-white pr-11
                                    {{ $errors->has('password') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-bu focus:ring-bu' }}"
                                >
                                <button
                                    type="button"
                                    @click="showManualPassword = !showManualPassword"
                                    class="absolute inset-y-0 right-0 px-3 text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-bu focus:ring-offset-1 rounded-md"
                                    :aria-label="showManualPassword ? 'Hide password' : 'Show password'"
                                >
                                    <svg x-show="!showManualPassword" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    <svg x-show="showManualPassword" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.73 5.08A10.45 10.45 0 0 1 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639a10.477 10.477 0 0 1-1.61 3.04M6.228 6.228A10.451 10.451 0 0 0 2.037 11.68a1.012 1.012 0 0 0 0 .639C3.423 16.49 7.36 19.5 12 19.5a10.45 10.45 0 0 0 5.772-1.728M9.88 9.88a3 3 0 1 0 4.243 4.243" />
                                    </svg>
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div x-show="manualPassword" x-transition>
                            <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                            <div class="relative mt-1">
                                <input
                                    x-bind:type="showManualPasswordConfirmation ? 'text' : 'password'"
                                    name="password_confirmation"
                                    :required="manualPassword"
                                    x-model="passwordConfirmation"
                                    class="w-full rounded-xl border bg-white pr-11
                                    {{ $errors->has('password_confirmation') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-bu focus:ring-bu' }}"
                                >
                                <button
                                    type="button"
                                    @click="showManualPasswordConfirmation = !showManualPasswordConfirmation"
                                    class="absolute inset-y-0 right-0 px-3 text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-bu focus:ring-offset-1 rounded-md"
                                    :aria-label="showManualPasswordConfirmation ? 'Hide confirm password' : 'Show confirm password'"
                                >
                                    <svg x-show="!showManualPasswordConfirmation" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    <svg x-show="showManualPasswordConfirmation" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.73 5.08A10.45 10.45 0 0 1 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639a10.477 10.477 0 0 1-1.61 3.04M6.228 6.228A10.451 10.451 0 0 0 2.037 11.68a1.012 1.012 0 0 0 0 .639C3.423 16.49 7.36 19.5 12 19.5a10.45 10.45 0 0 0 5.772-1.728M9.88 9.88a3 3 0 1 0 4.243 4.243" />
                                    </svg>
                                </button>
                            </div>
                            <p x-show="manualPassword && hasPasswordValues() && passwordsMatch()"
                               class="mt-1 text-sm text-green-600">
                                Passwords match.
                            </p>
                            <p x-show="manualPassword && passwordsMismatch()"
                               class="mt-1 text-sm text-red-600">
                                Passwords do not match.
                            </p>
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

                            @if(!empty($lockDepartment) && !empty($defaultDepartmentId))
                                <div class="mt-1 w-full md:w-1/2 rounded-xl border border-gray-200 bg-gray-50 px-4 py-2 text-sm text-gray-700">
                                    {{ optional($departments->first())->name ?? 'Department' }}
                                </div>
                                <input type="hidden" name="department_id" value="{{ $defaultDepartmentId }}">
                            @else
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
                            @endif

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
                                placeholder="1234-567"
                                value="{{ old('employee_no') }}"
                                @input="$event.target.value = formatEmployeeNo($event.target.value)"
                                class="mt-1 w-full rounded-xl border bg-white
                                {{ $errors->has('employee_no') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-bu focus:ring-bu' }}"
                            >

                            @error('employee_no')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror

                            <p class="mt-1 text-xs text-gray-500">
                                Required when Role = Faculty. Format: 4 digits, dash, 3 digits.
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
                            <label class="block text-sm font-medium text-gray-700">Academic Rank Level</label>

                            <select
                                name="rank_level_id"
                                class="mt-1 w-full rounded-xl border bg-white
                                {{ $errors->has('rank_level_id') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-bu focus:ring-bu' }}"
                            >
                                <option value="">Select Rank Level</option>
                                @foreach($rankLevels as $level)
                                    <option value="{{ $level->id }}" {{ old('rank_level_id') == $level->id ? 'selected' : '' }}>
                                        {{ $level->title }}
                                    </option>
                                @endforeach
                            </select>

                            @error('rank_level_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Highest Degree Earned</label>
                            <select
                                name="highest_degree"
                                class="mt-1 w-full rounded-xl border bg-white
                                {{ $errors->has('highest_degree') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-bu focus:ring-bu' }}"
                            >
                                <option value="">Select degree</option>
                                <option value="bachelors" {{ old('highest_degree') === 'bachelors' ? 'selected' : '' }}>Bachelor’s</option>
                                <option value="masters" {{ old('highest_degree') === 'masters' ? 'selected' : '' }}>Master’s</option>
                                <option value="doctorate" {{ old('highest_degree') === 'doctorate' ? 'selected' : '' }}>Doctorate</option>
                            </select>
                            @error('highest_degree')
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
                            :disabled="manualPassword && passwordsMismatch()"
                            :class="manualPassword && passwordsMismatch() ? 'opacity-60 cursor-not-allowed' : ''"
                            class="px-6 py-2.5 rounded-xl bg-bu text-white hover:bg-bu-dark shadow-soft
                                   focus:ring-2 focus:ring-bu focus:ring-offset-2 transition">
                        Create User
                    </button>
                </div>

            </form>
        </div>
    </div>
</x-app-layout>

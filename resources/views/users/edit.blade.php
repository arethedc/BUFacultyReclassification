<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-gray-800">User Details</h2>
            <p class="text-sm text-gray-500">
                View account information. Click Edit to modify and Save to apply changes.
            </p>
        </div>
    </x-slot>

    <div class="py-12 bg-bu-muted min-h-screen">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Alerts --}}
            @if(session('success'))
                <div class="p-4 rounded-xl bg-green-50 border border-green-200 text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700">
                    <p class="font-semibold mb-2">Please fix the following:</p>
                    <ul class="list-disc ml-5 text-sm space-y-1">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @php
                $showDepartment = in_array($user->role, ['faculty', 'dean']);
            @endphp

            <form method="POST"
                  action="{{ route('users.update', $user) }}"
                  class="space-y-6"
                  x-data="{
                    editMode: {{ $errors->any() ? 'true' : 'false' }},
                    original: {},
                    formatEmployeeNo(value) {
                      const digits = String(value || '').replace(/\D/g, '').slice(0, 7);
                      return digits.length > 4
                        ? digits.slice(0, 4) + '-' + digits.slice(4)
                        : digits;
                    },
                    init() {
                      this.original = {
                        first_name: $refs.first_name?.value ?? '',
                        middle_name: $refs.middle_name?.value ?? '',
                        last_name: $refs.last_name?.value ?? '',
                        suffix: $refs.suffix?.value ?? '',
                        email: $refs.email?.value ?? '',
                        department_id: $refs.department_id?.value ?? '',
                        status: $refs.status?.value ?? '',
                      };
                    },
                    enableEdit() {
                      this.editMode = true;
                    },
                    discard() {
                      if ($refs.first_name) $refs.first_name.value = this.original.first_name;
                      if ($refs.middle_name) $refs.middle_name.value = this.original.middle_name;
                      if ($refs.last_name) $refs.last_name.value = this.original.last_name;
                      if ($refs.suffix) $refs.suffix.value = this.original.suffix;
                      if ($refs.email) $refs.email.value = this.original.email;
                      if ($refs.department_id) $refs.department_id.value = this.original.department_id;
                      if ($refs.status) $refs.status.value = this.original.status;
                      this.editMode = false;
                    }
                  }">
                @csrf
                @method('PUT')

                {{-- TOP ACTION BAR --}}
                <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                    <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <a href="{{ $back ?? route('users.index') }}"
                               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-100 transition">
                                ← Back
                            </a>

                            <div class="text-sm text-gray-500">
                                Role:
                                <span class="font-semibold text-gray-800">
                                    {{ ucfirst(str_replace('_',' ', $user->role)) }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button"
                                    x-show="!editMode"
                                    @click="enableEdit()"
                                    class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-100 transition">
                                Edit
                            </button>

                            <button type="button"
                                    x-show="editMode"
                                    @click="discard()"
                                    class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-100 transition">
                                Discard Changes
                            </button>

                            <button type="submit"
                                    x-show="editMode"
                                    class="px-5 py-2.5 rounded-xl bg-bu text-white hover:bg-bu-dark shadow-soft transition">
                                Save Changes
                            </button>
                        </div>
                    </div>

                    <div class="px-6 pb-4">
                        <div x-show="!editMode" class="text-xs text-gray-500">
                            Fields are locked for safety. Click <span class="font-semibold">Edit</span> to unlock.
                        </div>
                        <div x-show="editMode" class="text-xs text-gray-500">
                            Edit mode enabled. Save to apply changes or Discard to cancel.
                        </div>
                    </div>
                </div>

                {{-- ACCOUNT --}}
                <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                    <div class="px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">Account</h3>
                        <p class="text-sm text-gray-500">Login email and identity.</p>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input x-ref="email"
                                   :disabled="!editMode"
                                   type="email"
                                   name="email"
                                   value="{{ old('email', $user->email) }}"
                                   class="mt-1 w-full rounded-xl border border-gray-300 bg-white
                                          focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600"
                                   required>
                        </div>
                    </div>
                </div>

                {{-- USER INFO --}}
                <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                    <div class="px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">User Information</h3>
                        <p class="text-sm text-gray-500">Stored in users table.</p>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">First Name</label>
                            <input x-ref="first_name"
                                   :disabled="!editMode"
                                   type="text"
                                   name="first_name"
                                   value="{{ old('first_name', $nameParts['first_name'] ?? '') }}"
                                   class="mt-1 w-full rounded-xl border border-gray-300 bg-white
                                          focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600"
                                   required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Last Name</label>
                            <input x-ref="last_name"
                                   :disabled="!editMode"
                                   type="text"
                                   name="last_name"
                                   value="{{ old('last_name', $nameParts['last_name'] ?? '') }}"
                                   class="mt-1 w-full rounded-xl border border-gray-300 bg-white
                                          focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600"
                                   required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Middle Name</label>
                            <input x-ref="middle_name"
                                   :disabled="!editMode"
                                   type="text"
                                   name="middle_name"
                                   value="{{ old('middle_name', $nameParts['middle_name'] ?? '') }}"
                                   class="mt-1 w-full rounded-xl border border-gray-300 bg-white
                                          focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Suffix</label>
                            <input x-ref="suffix"
                                   :disabled="!editMode"
                                   type="text"
                                   name="suffix"
                                   value="{{ old('suffix', $nameParts['suffix'] ?? '') }}"
                                   class="mt-1 w-full rounded-xl border border-gray-300 bg-white
                                          focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600">
                        </div>

                        @if($showDepartment)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Department</label>
                                <select x-ref="department_id"
                                        :disabled="!editMode"
                                        name="department_id"
                                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white
                                               focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600"
                                        required>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}"
                                            @selected(old('department_id', $user->department_id) == $dept->id)>
                                            {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                </div>

                @if($user->role === 'faculty')
                    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                        <div class="px-6 py-4 border-b">
                            <h3 class="text-lg font-semibold text-gray-800">Faculty Profile</h3>
                            <p class="text-sm text-gray-500">Employee number format: 4 digits, dash, 3 digits.</p>
                        </div>

                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Employee Number</label>
                                <input x-ref="employee_no"
                                       :disabled="!editMode"
                                       type="text"
                                       name="employee_no"
                                       placeholder="1234-567"
                                       value="{{ old('employee_no', $user->facultyProfile?->employee_no) }}"
                                       @input="$event.target.value = formatEmployeeNo($event.target.value)"
                                       class="mt-1 w-full rounded-xl border border-gray-300 bg-white
                                              focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600"
                                       required>
                                @error('employee_no')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ACCOUNT STATUS --}}
                <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                    <div class="px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">Account Status</h3>
                        <p class="text-sm text-gray-500">
                            Controls system access for this user.
                        </p>
                    </div>

                    <div class="p-6">
                        <label class="block text-sm font-medium text-gray-700">Status</label>

                        <select x-ref="status"
                                :disabled="!editMode"
                                name="status"
                                class="mt-1 w-full md:w-1/2 rounded-xl border border-gray-300 bg-white
                                       focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600"
                                required>
                            <option value="active" @selected(old('status', $user->status) === 'active')>
                                Active
                            </option>
                            <option value="inactive" @selected(old('status', $user->status) === 'inactive')>
                                Inactive
                            </option>
                        </select>

                        <p x-show="!editMode" class="text-xs text-gray-500 mt-2">
                            Click Edit to change account status.
                        </p>

                        <p class="text-xs text-gray-500 mt-2">
                            Inactive users cannot log in to the system.
                        </p>
                    </div>
                </div>

                {{-- BOTTOM ACTIONS --}}
                <div class="flex justify-end gap-2">
                    <button type="button"
                            x-show="editMode"
                            @click="discard()"
                            class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-100 transition">
                        Discard Changes
                    </button>
                    <button type="submit"
                            x-show="editMode"
                            class="px-5 py-2.5 rounded-xl bg-bu text-white hover:bg-bu-dark shadow-soft transition">
                        Save Changes
                    </button>
                </div>

            </form>
        </div>
    </div>
</x-app-layout>

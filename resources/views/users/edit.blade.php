<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex flex-col gap-1">
                <h2 class="text-2xl font-semibold text-gray-800">Person Profile</h2>
                <p class="text-sm text-gray-500">
                    Unified profile for account and faculty data management.
                </p>
            </div>
            <a href="{{ $backRoute ?? route('users.index') }}"
               class="h-11 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50 inline-flex items-center justify-center whitespace-nowrap self-start sm:self-auto">
                {{ $backLabel ?? 'Back to Manage Users' }}
            </a>
        </div>
    </x-slot>

    @php
        $showDepartment = in_array($user->role, ['faculty', 'dean'], true);
        $showFacultyDetails = $user->role === 'faculty';
        $profile = $user->facultyProfile;
        $profileErrorFields = [
            'first_name',
            'last_name',
            'middle_name',
            'suffix',
            'status',
            'department_id',
            'employee_no',
            'employment_type',
            'rank_level_id',
            'teaching_rank',
            'rank_step',
            'highest_degree',
            'original_appointment_date',
        ];
        $hasProfileErrors = collect($profileErrorFields)->contains(fn ($field) => $errors->has($field));
        $hasEmailError = $errors->has('email');
    @endphp

    <div class="py-12 bg-bu-muted min-h-screen">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6"
             x-data="{
                 suppressEditBeforeUnload: false,
                 hasEditUnsavedChanges() {
                     const emailForm = document.querySelector('[data-email-edit-form]');
                     const profileForm = document.querySelector('[data-profile-edit-form]');
                     const emailEditMode = emailForm?.dataset.emailEditMode === '1';
                     const profileEditMode = profileForm?.dataset.profileEditMode === '1';
                     const emailDirty = emailForm?.dataset.emailDirty === '1';
                     const profileDirty = profileForm?.dataset.profileDirty === '1';
                     return emailEditMode || profileEditMode || emailDirty || profileDirty;
                 },
                 init() {
                     if (window.__editProfileBeforeUnloadGuard) {
                         window.removeEventListener('beforeunload', window.__editProfileBeforeUnloadGuard);
                     }
                     window.__editProfileBeforeUnloadGuard = (event) => {
                         if (!this.suppressEditBeforeUnload && this.hasEditUnsavedChanges()) {
                             event.preventDefault();
                             event.returnValue = '';
                         }
                     };
                     window.addEventListener('beforeunload', window.__editProfileBeforeUnloadGuard);
                 }
             }"
             @edit-form-submitting.window="suppressEditBeforeUnload = true">
            @if(session('success'))
                <div class="p-4 rounded-xl bg-green-50 border border-green-200 text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('post_create_notice'))
                @php $notice = session('post_create_notice'); @endphp
                <div class="p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-sm">
                    {{ $notice['message'] ?? '' }}
                    @if(!empty($notice['link_url']) && !empty($notice['link_label']))
                        <span>{{ $notice['link_prefix'] ?? '' }}</span>
                        <a href="{{ $notice['link_url'] }}" class="font-semibold underline hover:no-underline">
                            {{ $notice['link_label'] }}
                        </a>.
                    @endif
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

            <div class="bg-white rounded-2xl shadow-card border border-gray-200"
                 x-data="{ editMode: {{ $hasProfileErrors ? 'true' : 'false' }}, saveLocked: true }"
                 @profile-toolbar-state.window="editMode = !!($event.detail?.editMode); saveLocked = !!($event.detail?.saveLocked)">
                <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="text-sm text-gray-500">
                            Current role:
                            <span class="font-semibold text-gray-800">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button"
                                x-show="!editMode"
                                @click="$dispatch('profile-toolbar-edit')"
                                class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-100 transition">
                            Edit Profile
                        </button>
                        <button type="button"
                                x-show="editMode"
                                @click="$dispatch('profile-toolbar-discard')"
                                class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-100 transition">
                            Discard Changes
                        </button>
                        <button type="submit"
                                form="profile-edit-form"
                                x-show="editMode"
                                :disabled="saveLocked"
                                :class="saveLocked ? 'opacity-60 cursor-not-allowed' : ''"
                                class="px-5 py-2.5 rounded-xl bg-bu text-white hover:bg-bu-dark shadow-soft transition">
                            Save Changes
                        </button>
                    </div>
                </div>

                <div class="px-6 pb-4 text-xs text-gray-500">
                    <span x-show="!editMode">Fields are locked. Click <span class="font-semibold">Edit Profile</span> to unlock.</span>
                    <span x-show="editMode">Edit mode enabled. Save Changes is available only when you modify fields.</span>
                </div>
            </div>

            <form method="POST"
                  action="{{ route('users.update', ['user' => $user, 'context' => ($editContext ?? 'users')]) }}"
                  class="space-y-6"
                  data-email-edit-form
                  x-bind:data-email-edit-mode="emailEditMode ? '1' : '0'"
                  x-bind:data-email-dirty="(emailEditMode && emailHasChanged()) ? '1' : '0'"
                  @input="emailChangeTick++"
                  @change="emailChangeTick++"
                  @submit="if (isEmailSaveLocked()) { $event.preventDefault(); return; } $dispatch('edit-form-submitting')"
                  x-data="{
                      emailEditMode: {{ $hasEmailError ? 'true' : 'false' }},
                      isEmailVerified: @js(!empty($user->email_verified_at)),
                      originalEmail: @js((string) ($user->email ?? '')),
                      emailChangeTick: 0,
                      emailHasChanged() {
                          const _tick = this.emailChangeTick;
                          return String(this.$refs.email?.value ?? '').trim() !== String(this.originalEmail).trim();
                      },
                      isEmailSaveLocked() {
                          if (this.isEmailVerified) return true;
                          if (!this.emailEditMode) return true;
                          return !this.emailHasChanged();
                      },
                      enableEmailEdit() {
                          if (this.isEmailVerified) return;
                          this.emailEditMode = true;
                          this.$nextTick(() => this.$refs.email?.focus());
                      },
                      discardEmail() {
                          if (this.$refs.email) this.$refs.email.value = this.originalEmail;
                          this.emailEditMode = false;
                          this.emailChangeTick++;
                      }
                  }">
                @csrf
                @method('PUT')
                <input type="hidden" name="profile_action" value="email">

                <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                    <div class="px-6 py-4 border-b flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Account Information</h3>
                            <p class="text-sm text-gray-500">Email and login identity.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button"
                                    x-show="!isEmailVerified && !emailEditMode"
                                    @click="enableEmailEdit()"
                                    class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-100 transition">
                                Edit Email
                            </button>
                            <button type="button"
                                    x-show="!isEmailVerified && emailEditMode"
                                    @click="discardEmail()"
                                    class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-100 transition">
                                Cancel Email Edit
                            </button>
                            <button type="submit"
                                    x-show="!isEmailVerified && emailEditMode"
                                    :disabled="isEmailSaveLocked()"
                                    :class="isEmailSaveLocked() ? 'opacity-60 cursor-not-allowed' : ''"
                                    class="px-5 py-2.5 rounded-xl bg-bu text-white hover:bg-bu-dark shadow-soft transition">
                                Save Email & Send Verification
                            </button>
                        </div>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input x-ref="email"
                                   type="email"
                                   name="email"
                                   value="{{ old('email', $user->email) }}"
                                   :disabled="isEmailVerified || !emailEditMode"
                                   class="mt-1 w-full rounded-xl border border-gray-300 bg-white text-gray-700 focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600">
                            @error('email')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                            <p class="mt-2 text-xs text-gray-500">
                                <span x-show="isEmailVerified">
                                    Email is locked once verified.
                                </span>
                                <span x-show="!isEmailVerified">
                                    Updating email will send a new verification link to the new address.
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </form>

            <form method="POST"
                  id="profile-edit-form"
                  action="{{ route('users.update', ['user' => $user, 'context' => ($editContext ?? 'users')]) }}"
                  class="space-y-6"
                  data-profile-edit-form
                  x-bind:data-profile-edit-mode="editMode ? '1' : '0'"
                  x-bind:data-profile-dirty="(editMode && hasProfileChanges()) ? '1' : '0'"
                  x-bind:data-profile-save-locked="isProfileSaveLocked() ? '1' : '0'"
                  x-ref="profile_form"
                  @input="profileChangeTick++"
                  @change="profileChangeTick++"
                  @profile-toolbar-edit.window="enableEdit()"
                  @profile-toolbar-discard.window="discard()"
                  @submit="if (isProfileSaveLocked()) { $event.preventDefault(); return; } $dispatch('edit-form-submitting')"
                  x-data="{
                      editMode: {{ $hasProfileErrors ? 'true' : 'false' }},
                      usesRankLevels: @js($showFacultyDetails && $rankLevels->isNotEmpty()),
                      profileChangeTick: 0,
                      employeeNoAvailabilityUrl: @js(route('users.employee-no-availability', $user)),
                      employeeCheckState: 'idle',
                      employeeCheckMessage: '',
                      employeeCheckTimer: null,
                      hasEmployeeNoServerError: @js($errors->has('employee_no')),
                      original: {
                          first_name: @js((string) ($nameParts['first_name'] ?? '')),
                          middle_name: @js((string) ($nameParts['middle_name'] ?? '')),
                          last_name: @js((string) ($nameParts['last_name'] ?? '')),
                          suffix: @js((string) ($nameParts['suffix'] ?? '')),
                          department_id: @js((string) ($user->department_id ?? '')),
                          status: @js((string) ($user->status ?? '')),
                          employee_no: @js((string) ($profile?->employee_no ?? '')),
                          employment_type: @js((string) ($profile?->employment_type ?? '')),
                          rank_level_id: @js(($showFacultyDetails && $rankLevels->isNotEmpty()) ? (string) ($profile?->rank_level_id ?? '') : ''),
                          teaching_rank: @js(($showFacultyDetails && !$rankLevels->isNotEmpty()) ? (string) ($profile?->teaching_rank ?? '') : ''),
                          highest_degree: @js((string) ($highestDegree?->highest_degree ?? '')),
                          original_appointment_date: @js((string) optional($profile?->original_appointment_date)->format('Y-m-d')),
                      },
                      parseAppointmentDateFromEmployeeNo(value) {
                          const raw = String(value || '').trim();
                          if (!/^\d{2}(0[1-9]|1[0-2])-\d{3}$/.test(raw)) return null;

                          const yearPart = Number(raw.slice(0, 2));
                          const monthPart = Number(raw.slice(2, 4));
                          if (!Number.isInteger(monthPart) || monthPart < 1 || monthPart > 12) return null;

                          const currentYearTwoDigits = new Date().getFullYear() % 100;
                          const fullYear = yearPart <= currentYearTwoDigits
                              ? 2000 + yearPart
                              : 1900 + yearPart;
                          return `${String(fullYear).padStart(4, '0')}-${String(monthPart).padStart(2, '0')}-01`;
                      },
                      formatEmployeeNoInput() {
                          if (!this.$refs.employee_no) return '';
                          const digits = String(this.$refs.employee_no.value || '').replace(/\D/g, '').slice(0, 7);
                          const formatted = digits.length > 4
                              ? `${digits.slice(0, 4)}-${digits.slice(4)}`
                              : digits;
                          this.$refs.employee_no.value = formatted;
                          return formatted;
                      },
                      autoSetOriginalAppointmentDateFromEmployeeNo() {
                          if (!this.$refs.employee_no || !this.$refs.original_appointment_date) return;
                          const derivedDate = this.parseAppointmentDateFromEmployeeNo(this.$refs.employee_no.value);
                          this.$refs.original_appointment_date.value = derivedDate || '';
                      },
                      currentSnapshot() {
                          const refs = this.$refs;
                          return {
                              first_name: refs.first_name?.value ?? '',
                              middle_name: refs.middle_name?.value ?? '',
                              last_name: refs.last_name?.value ?? '',
                              suffix: refs.suffix?.value ?? '',
                              department_id: refs.department_id?.value ?? '',
                              status: refs.status?.value ?? '',
                              employee_no: refs.employee_no?.value ?? '',
                              employment_type: refs.employment_type?.value ?? '',
                              rank_level_id: this.usesRankLevels
                                  ? (refs.rank_level_id?.value ?? '')
                                  : '',
                              teaching_rank: this.usesRankLevels
                                  ? ''
                                  : (refs.teaching_rank?.value ?? ''),
                              highest_degree: refs.highest_degree?.value ?? '',
                              original_appointment_date: refs.original_appointment_date?.value ?? '',
                          };
                      },
                      hasProfileChanges() {
                          const _tick = this.profileChangeTick;
                          const current = this.currentSnapshot();
                          return Object.keys(current).some((key) => String(current[key] ?? '') !== String(this.original[key] ?? ''));
                      },
                      syncToolbarState() {
                          this.$dispatch('profile-toolbar-state', {
                              editMode: this.editMode,
                              saveLocked: this.isProfileSaveLocked(),
                          });
                      },
                      init() {
                          this.formatEmployeeNoInput();
                          if (this.editMode && this.$refs.employee_no && !this.hasEmployeeNoServerError) {
                              this.checkEmployeeNoAvailability(true);
                          }
                          this.$nextTick(() => this.syncToolbarState());
                          this.$watch('editMode', () => this.syncToolbarState());
                          this.$watch('profileChangeTick', () => this.syncToolbarState());
                          this.$watch('employeeCheckState', () => this.syncToolbarState());
                          this.$watch('hasEmployeeNoServerError', () => this.syncToolbarState());
                      },
                      enableEdit() {
                          this.editMode = true;
                          this.profileChangeTick++;
                          this.$nextTick(() => {
                              this.hasEmployeeNoServerError = false;
                              this.checkEmployeeNoAvailability(true);
                          });
                      },
                      async checkEmployeeNoAvailability(immediate = false) {
                          if (!this.$refs.employee_no) return this.employeeCheckState;
                          this.hasEmployeeNoServerError = false;

                          const employeeNo = String(this.$refs.employee_no.value || '').trim();
                          const currentEmployeeNo = String(this.original.employee_no || '').trim();
                          const pattern = /^\d{2}(0[1-9]|1[0-2])-\d{3}$/;

                          if (this.employeeCheckTimer) {
                              clearTimeout(this.employeeCheckTimer);
                              this.employeeCheckTimer = null;
                          }

                          if (!this.editMode) {
                              this.employeeCheckState = 'idle';
                              this.employeeCheckMessage = '';
                              return this.employeeCheckState;
                          }

                          if (!employeeNo) {
                              this.employeeCheckState = 'invalid';
                              this.employeeCheckMessage = 'Employee number is required.';
                              return this.employeeCheckState;
                          }

                          if (employeeNo === currentEmployeeNo) {
                              this.employeeCheckState = 'idle';
                              this.employeeCheckMessage = '';
                              return this.employeeCheckState;
                          }

                          if (!pattern.test(employeeNo)) {
                              this.employeeCheckState = 'invalid';
                              this.employeeCheckMessage = 'Employee number must follow YYMM-XXX format.';
                              return this.employeeCheckState;
                          }

                          const runCheck = async () => {
                              this.employeeCheckState = 'checking';
                              this.employeeCheckMessage = 'Checking employee number...';
                              try {
                                  const url = `${this.employeeNoAvailabilityUrl}?employee_no=${encodeURIComponent(employeeNo)}`;
                                  const response = await fetch(url, {
                                      method: 'GET',
                                      credentials: 'same-origin',
                                      headers: {
                                          'X-Requested-With': 'XMLHttpRequest',
                                      },
                                  });
                                  if (!response.ok) throw new Error('Employee number check failed');
                                  const payload = await response.json();
                                  if (String(this.$refs.employee_no.value || '').trim() !== employeeNo) {
                                      return;
                                  }
                                  if (payload.available) {
                                      this.employeeCheckState = 'valid';
                                      this.employeeCheckMessage = payload.message || 'Employee number is available.';
                                  } else {
                                      this.employeeCheckState = 'unavailable';
                                      this.employeeCheckMessage = payload.message || 'Employee number already exists.';
                                  }
                              } catch (error) {
                                  this.employeeCheckState = 'error';
                                  this.employeeCheckMessage = 'Unable to verify employee number right now. Try again.';
                              }
                          };

                          if (immediate) {
                              await runCheck();
                          } else {
                              this.employeeCheckTimer = setTimeout(() => {
                                  runCheck();
                              }, 350);
                          }

                          return this.employeeCheckState;
                      },
                      isProfileSaveLocked() {
                          const _tick = this.profileChangeTick;
                          if (!this.editMode) return true;
                          if (!this.hasProfileChanges()) return true;
                          if (!this.$refs.employee_no) return false;
                          if (this.hasEmployeeNoServerError) return true;
                          return ['invalid', 'unavailable', 'checking', 'error'].includes(this.employeeCheckState);
                      },
                      discard() {
                          if ($refs.first_name) $refs.first_name.value = this.original.first_name;
                          if ($refs.middle_name) $refs.middle_name.value = this.original.middle_name;
                          if ($refs.last_name) $refs.last_name.value = this.original.last_name;
                          if ($refs.suffix) $refs.suffix.value = this.original.suffix;
                          if ($refs.department_id) $refs.department_id.value = this.original.department_id;
                          if ($refs.status) $refs.status.value = this.original.status;
                          if ($refs.employee_no) $refs.employee_no.value = this.original.employee_no;
                          if ($refs.employment_type) $refs.employment_type.value = this.original.employment_type;
                          if ($refs.rank_level_id) $refs.rank_level_id.value = this.original.rank_level_id;
                          if ($refs.teaching_rank) $refs.teaching_rank.value = this.original.teaching_rank;
                          if ($refs.highest_degree) $refs.highest_degree.value = this.original.highest_degree;
                          if ($refs.original_appointment_date) $refs.original_appointment_date.value = this.original.original_appointment_date;
                          this.employeeCheckState = 'idle';
                          this.employeeCheckMessage = '';
                          this.hasEmployeeNoServerError = false;
                          this.editMode = false;
                          this.profileChangeTick++;
                      }
                  }">
                @csrf
                @method('PUT')
                <input type="hidden" name="profile_action" value="profile">

                <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                    <div class="px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">User Information</h3>
                        <p class="text-sm text-gray-500">Stored in the users table.</p>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">First Name</label>
                            <input x-ref="first_name"
                                   :disabled="!editMode"
                                   type="text"
                                   name="first_name"
                                   value="{{ old('first_name', $nameParts['first_name'] ?? '') }}"
                                   class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600"
                                   required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Last Name</label>
                            <input x-ref="last_name"
                                   :disabled="!editMode"
                                   type="text"
                                   name="last_name"
                                   value="{{ old('last_name', $nameParts['last_name'] ?? '') }}"
                                   class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600"
                                   required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Middle Name</label>
                            <input x-ref="middle_name"
                                   :disabled="!editMode"
                                   type="text"
                                   name="middle_name"
                                   value="{{ old('middle_name', $nameParts['middle_name'] ?? '') }}"
                                   class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Suffix</label>
                            <input x-ref="suffix"
                                   :disabled="!editMode"
                                   type="text"
                                   name="suffix"
                                   value="{{ old('suffix', $nameParts['suffix'] ?? '') }}"
                                   class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600">
                        </div>

                        @if($showDepartment)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Department</label>
                                <select x-ref="department_id"
                                        :disabled="!editMode"
                                        name="department_id"
                                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600"
                                        required>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" @selected((string) old('department_id', $user->department_id) === (string) $dept->id)>
                                            {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Role</label>
                            <input type="text"
                                   value="{{ ucfirst(str_replace('_', ' ', $user->role)) }}"
                                   class="mt-1 w-full rounded-xl border border-gray-300 bg-gray-100 text-gray-600"
                                   disabled>
                            <p class="mt-2 text-xs text-gray-500">Role changes are managed during account provisioning.</p>
                        </div>
                    </div>
                </div>

                @if($showFacultyDetails)
                    <div class="bg-white rounded-2xl shadow-card border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b">
                            <h3 class="text-lg font-semibold text-gray-800">Faculty Profile Details</h3>
                            <p class="text-sm text-gray-500">Maintains faculty profile records and highest academic credential details.</p>
                        </div>

                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Employee Number</label>
                                <input x-ref="employee_no"
                                       :disabled="!editMode"
                                       type="text"
                                       name="employee_no"
                                       inputmode="numeric"
                                       maxlength="8"
                                       pattern="\d{2}(0[1-9]|1[0-2])-\d{3}"
                                       @input="formatEmployeeNoInput(); autoSetOriginalAppointmentDateFromEmployeeNo(); checkEmployeeNoAvailability()"
                                       @change="autoSetOriginalAppointmentDateFromEmployeeNo(); checkEmployeeNoAvailability(true)"
                                       @blur="autoSetOriginalAppointmentDateFromEmployeeNo(); checkEmployeeNoAvailability(true)"
                                       value="{{ old('employee_no', $profile?->employee_no) }}"
                                       class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600"
                                       required>
                                @error('employee_no')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                                <p class="text-xs mt-1"
                                   x-show="employeeCheckMessage !== '' && !hasEmployeeNoServerError"
                                   x-bind:class="{
                                       'text-gray-500': employeeCheckState === 'checking',
                                       'text-green-600': employeeCheckState === 'valid',
                                       'text-red-600': ['invalid', 'unavailable', 'error'].includes(employeeCheckState)
                                   }"
                                   x-text="employeeCheckMessage"></p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Employment Type</label>
                                <select x-ref="employment_type"
                                        :disabled="!editMode"
                                        name="employment_type"
                                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600"
                                        required>
                                    <option value="full_time" @selected(old('employment_type', $profile?->employment_type) === 'full_time')>Full-time</option>
                                    <option value="part_time" @selected(old('employment_type', $profile?->employment_type) === 'part_time')>Part-time</option>
                                </select>
                                @error('employment_type')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                @if($rankLevels->isNotEmpty())
                                    <label class="block text-sm font-medium text-gray-700">Academic Rank Level</label>
                                    <select x-ref="rank_level_id"
                                            :disabled="!editMode"
                                            name="rank_level_id"
                                            class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600"
                                            required>
                                        <option value="">Select Rank Level</option>
                                        @foreach($rankLevels as $level)
                                            <option value="{{ $level->id }}" @selected((string) old('rank_level_id', $profile?->rank_level_id) === (string) $level->id)>
                                                {{ $level->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('rank_level_id')
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                @else
                                    <label class="block text-sm font-medium text-gray-700">Teaching Rank</label>
                                    <input x-ref="teaching_rank"
                                           :disabled="!editMode"
                                           type="text"
                                           name="teaching_rank"
                                           value="{{ old('teaching_rank', $profile?->teaching_rank) }}"
                                           class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600"
                                           required>
                                    @error('teaching_rank')
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Highest Degree Earned</label>
                                <select x-ref="highest_degree"
                                        :disabled="!editMode"
                                        name="highest_degree"
                                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600"
                                        required>
                                    <option value="" disabled @selected(old('highest_degree', $highestDegree?->highest_degree) === null || old('highest_degree', $highestDegree?->highest_degree) === '')>
                                        Select highest degree earned
                                    </option>
                                    <option value="bachelors" @selected(old('highest_degree', $highestDegree?->highest_degree) === 'bachelors')>Bachelor's</option>
                                    <option value="masters" @selected(old('highest_degree', $highestDegree?->highest_degree) === 'masters')>Master's</option>
                                    <option value="doctorate" @selected(old('highest_degree', $highestDegree?->highest_degree) === 'doctorate')>Doctorate</option>
                                </select>
                                @error('highest_degree')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Date of Original Appointment</label>
                                <input x-ref="original_appointment_date"
                                       :disabled="!editMode"
                                       type="date"
                                       name="original_appointment_date"
                                       readonly
                                       aria-readonly="true"
                                       value="{{ old('original_appointment_date', optional($profile?->original_appointment_date)->format('Y-m-d')) }}"
                                       class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600">
                                @error('original_appointment_date')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                                <p class="text-xs text-gray-500 mt-2">
                                    Auto-filled from Employee Number using <span class="font-medium">YYMM</span> as <span class="font-medium">19YY/20YY-MM-01</span>.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                    <div class="px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">Account Status</h3>
                        <p class="text-sm text-gray-500">Controls system access for this account.</p>
                    </div>

                    <div class="p-6">
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select x-ref="status"
                                :disabled="!editMode"
                                name="status"
                                class="mt-1 w-full md:w-1/2 rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600"
                                required>
                            <option value="active" @selected(old('status', $user->status) === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $user->status) === 'inactive')>Inactive</option>
                        </select>
                    </div>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>

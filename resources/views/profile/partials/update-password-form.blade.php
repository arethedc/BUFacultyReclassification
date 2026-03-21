<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Update Password') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post"
          action="{{ route('password.update') }}"
          class="mt-6 space-y-6"
          x-data="{
              showCurrent: false,
              showNew: false,
              showConfirm: false,
              currentPassword: '',
              newPassword: '',
              confirmPassword: '',
              submitting: false,
              unloadGuardBound: false,
              leaveModalOpen: false,
              leaveAction: '',
              suppressUnloadPrompt: false,
              get hasMinLength() { return this.newPassword.length >= 6; },
              get hasNumber() { return /\d/.test(this.newPassword); },
              get hasLowercase() { return /[a-z]/.test(this.newPassword); },
              get hasUppercase() { return /[A-Z]/.test(this.newPassword); },
              get hasSpecial() { return /[^A-Za-z0-9]/.test(this.newPassword); },
              get passwordRulesSatisfied() {
                  return this.hasMinLength && this.hasNumber && this.hasLowercase && this.hasUppercase && this.hasSpecial;
              },
              hasPendingInput() {
                  return this.currentPassword !== '' || this.newPassword !== '' || this.confirmPassword !== '';
              },
              hasMismatch() {
                  return this.newPassword !== '' && this.confirmPassword !== '' && this.newPassword !== this.confirmPassword;
              },
              canSubmit() {
                  if (!this.hasPendingInput()) return false;
                  if (this.currentPassword.trim() === '' || this.newPassword.trim() === '' || this.confirmPassword.trim() === '') return false;
                  return this.passwordRulesSatisfied && !this.hasMismatch();
              },
              init() {
                  if (this.unloadGuardBound) return;
                  this.unloadGuardBound = true;

                  if (window.__profilePasswordReloadKeyGuard) {
                      window.removeEventListener('keydown', window.__profilePasswordReloadKeyGuard, true);
                  }
                  window.__profilePasswordReloadKeyGuard = (event) => {
                      const key = String(event?.key || '').toLowerCase();
                      const isReloadKey = key === 'f5' || ((event.ctrlKey || event.metaKey) && key === 'r');
                      if (!isReloadKey) return;
                      if (this.submitting || !this.hasPendingInput()) return;
                      event.preventDefault();
                      event.stopPropagation();
                      this.leaveAction = 'reload';
                      this.leaveModalOpen = true;
                  };
                  window.addEventListener('keydown', window.__profilePasswordReloadKeyGuard, true);

                  window.addEventListener('beforeunload', (event) => {
                      if (!this.suppressUnloadPrompt && !this.submitting && this.hasPendingInput()) {
                          event.preventDefault();
                          event.returnValue = '';
                      }
                  });
              },
              closeLeaveModal() {
                  this.leaveModalOpen = false;
                  this.leaveAction = '';
              },
              continueLeave() {
                  const action = this.leaveAction;
                  this.closeLeaveModal();
                  this.suppressUnloadPrompt = true;
                  if (action === 'reload') {
                      window.location.reload();
                  }
              }
          }"
          @submit="
              if (!canSubmit()) {
                  $event.preventDefault();
                  return;
              }
              submitting = true;
          ">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value="__('Current Password')" />
            <div class="relative mt-1">
                <x-text-input id="update_password_current_password"
                              name="current_password"
                              x-model="currentPassword"
                              x-bind:type="showCurrent ? 'text' : 'password'"
                              class="block w-full pr-10"
                              :required="hasPendingInput()"
                              autocomplete="current-password" />
                <button type="button"
                        @click="showCurrent = !showCurrent"
                        class="absolute inset-y-0 right-0 px-3 text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-bu focus:ring-offset-1 rounded-md"
                        :aria-label="showCurrent ? 'Hide current password' : 'Show current password'">
                    <svg x-show="!showCurrent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <svg x-show="showCurrent" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.73 5.08A10.45 10.45 0 0 1 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639a10.477 10.477 0 0 1-1.61 3.04M6.228 6.228A10.451 10.451 0 0 0 2.037 11.68a1.012 1.012 0 0 0 0 .639C3.423 16.49 7.36 19.5 12 19.5a10.45 10.45 0 0 0 5.772-1.728M9.88 9.88a3 3 0 1 0 4.243 4.243" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('New Password')" />
            <div class="relative mt-1">
                <x-text-input id="update_password_password"
                              name="password"
                              x-model="newPassword"
                              x-bind:type="showNew ? 'text' : 'password'"
                              class="block w-full pr-10"
                              minlength="6"
                              :required="hasPendingInput()"
                              autocomplete="new-password" />
                <button type="button"
                        @click="showNew = !showNew"
                        class="absolute inset-y-0 right-0 px-3 text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-bu focus:ring-offset-1 rounded-md"
                        :aria-label="showNew ? 'Hide new password' : 'Show new password'">
                    <svg x-show="!showNew" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <svg x-show="showNew" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.73 5.08A10.45 10.45 0 0 1 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639a10.477 10.477 0 0 1-1.61 3.04M6.228 6.228A10.451 10.451 0 0 0 2.037 11.68a1.012 1.012 0 0 0 0 .639C3.423 16.49 7.36 19.5 12 19.5a10.45 10.45 0 0 0 5.772-1.728M9.88 9.88a3 3 0 1 0 4.243 4.243" />
                    </svg>
                </button>
            </div>
            <div class="mt-3 space-y-1.5">
                <div class="flex items-center gap-2 text-sm" x-bind:class="hasMinLength ? 'text-green-600' : 'text-red-500'">
                    <span class="font-semibold" x-text="hasMinLength ? '✓' : '×'"></span>
                    <span>Has at least 6 characters</span>
                </div>
                <div class="flex items-center gap-2 text-sm" x-bind:class="hasNumber ? 'text-green-600' : 'text-red-500'">
                    <span class="font-semibold" x-text="hasNumber ? '✓' : '×'"></span>
                    <span>Includes number</span>
                </div>
                <div class="flex items-center gap-2 text-sm" x-bind:class="hasLowercase ? 'text-green-600' : 'text-red-500'">
                    <span class="font-semibold" x-text="hasLowercase ? '✓' : '×'"></span>
                    <span>Includes lowercase letter</span>
                </div>
                <div class="flex items-center gap-2 text-sm" x-bind:class="hasUppercase ? 'text-green-600' : 'text-red-500'">
                    <span class="font-semibold" x-text="hasUppercase ? '✓' : '×'"></span>
                    <span>Includes uppercase letter</span>
                </div>
                <div class="flex items-center gap-2 text-sm" x-bind:class="hasSpecial ? 'text-green-600' : 'text-red-500'">
                    <span class="font-semibold" x-text="hasSpecial ? '✓' : '×'"></span>
                    <span>Includes special symbol</span>
                </div>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
            <div class="relative mt-1">
                <x-text-input id="update_password_password_confirmation"
                              name="password_confirmation"
                              x-model="confirmPassword"
                              x-bind:type="showConfirm ? 'text' : 'password'"
                              class="block w-full pr-10"
                              minlength="6"
                              :required="hasPendingInput()"
                              autocomplete="new-password" />
                <button type="button"
                        @click="showConfirm = !showConfirm"
                        class="absolute inset-y-0 right-0 px-3 text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-bu focus:ring-offset-1 rounded-md"
                        :aria-label="showConfirm ? 'Hide confirm password' : 'Show confirm password'">
                    <svg x-show="!showConfirm" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <svg x-show="showConfirm" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.73 5.08A10.45 10.45 0 0 1 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639a10.477 10.477 0 0 1-1.61 3.04M6.228 6.228A10.451 10.451 0 0 0 2.037 11.68a1.012 1.012 0 0 0 0 .639C3.423 16.49 7.36 19.5 12 19.5a10.45 10.45 0 0 0 5.772-1.728M9.88 9.88a3 3 0 1 0 4.243 4.243" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
            <p x-show="hasMismatch()" x-cloak class="mt-2 text-sm text-red-600">
                New password and confirmation must match.
            </p>
            <p x-show="confirmPassword !== '' && !hasMismatch()" x-cloak class="mt-2 text-sm text-green-600">
                Passwords match.
            </p>
        </div>

        <div class="flex items-center gap-4">
            <button type="submit"
                    :disabled="!canSubmit() || submitting"
                    :class="(!canSubmit() || submitting) ? 'opacity-60 cursor-not-allowed' : ''"
                    class="px-5 py-2.5 rounded-xl bg-green-600 text-white text-sm font-semibold hover:bg-green-700 shadow-soft">
                <span x-text="submitting ? 'Saving...' : 'Reset Password'"></span>
            </button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>

        <div x-cloak x-show="leaveModalOpen" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40" @click="closeLeaveModal()"></div>
            <div class="relative w-full max-w-lg mx-4 rounded-2xl border bg-white shadow-xl">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-base font-semibold text-gray-900">Unsaved Changes</h3>
                    <p class="mt-1 text-sm text-gray-600">You have unsaved password inputs.</p>
                </div>
                <div class="px-6 py-4">
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                        <div class="font-semibold">Reload this page?</div>
                        <p class="mt-1 text-xs text-amber-800">If you continue, your current password inputs will be lost.</p>
                    </div>
                </div>
                <div class="px-6 py-4 border-t flex items-center justify-end gap-2">
                    <button type="button"
                            @click="closeLeaveModal()"
                            class="px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Stay
                    </button>
                    <button type="button"
                            @click="continueLeave()"
                            class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-sm font-semibold text-red-700 transition hover:bg-red-100">
                        Reload Without Saving
                    </button>
                </div>
            </div>
        </div>
    </form>
</section>

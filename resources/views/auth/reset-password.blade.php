<x-guest-layout>
    <form method="POST" action="{{ route('password.store') }}"
          x-data="{
              password: '',
              confirmation: '',
              showPassword: false,
              showConfirmation: false,
              get hasMinLength() { return this.password.length >= 6; },
              get hasNumber() { return /\d/.test(this.password); },
              get hasLowercase() { return /[a-z]/.test(this.password); },
              get hasUppercase() { return /[A-Z]/.test(this.password); },
              get hasSpecial() { return /[^A-Za-z0-9]/.test(this.password); },
              get meetsAllPasswordRules() {
                  return this.hasMinLength && this.hasNumber && this.hasLowercase && this.hasUppercase && this.hasSpecial;
              },
              get matches() { return this.password !== '' && this.password === this.confirmation; },
              get canSubmit() { return this.meetsAllPasswordRules && this.matches; }
          }">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Set Password')" />
            <div class="relative mt-1">
                <x-text-input id="password"
                              class="block w-full pr-10"
                              x-bind:type="showPassword ? 'text' : 'password'"
                              name="password"
                              required
                              minlength="6"
                              x-model="password"
                              autocomplete="new-password" />
                <button type="button"
                        @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 px-3 text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-bu focus:ring-offset-1 rounded-md"
                        :aria-label="showPassword ? 'Hide password' : 'Show password'">
                    <svg x-show="!showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
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
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <div class="relative mt-1">
                <x-text-input id="password_confirmation" class="block w-full pr-10"
                                    x-bind:type="showConfirmation ? 'text' : 'password'"
                                    name="password_confirmation"
                                    required
                                    minlength="6"
                                    x-model="confirmation"
                                    autocomplete="new-password" />
                <button type="button"
                        @click="showConfirmation = !showConfirmation"
                        class="absolute inset-y-0 right-0 px-3 text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-bu focus:ring-offset-1 rounded-md"
                        :aria-label="showConfirmation ? 'Hide confirm password' : 'Show confirm password'">
                    <svg x-show="!showConfirmation" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <svg x-show="showConfirmation" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.73 5.08A10.45 10.45 0 0 1 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639a10.477 10.477 0 0 1-1.61 3.04M6.228 6.228A10.451 10.451 0 0 0 2.037 11.68a1.012 1.012 0 0 0 0 .639C3.423 16.49 7.36 19.5 12 19.5a10.45 10.45 0 0 0 5.772-1.728M9.88 9.88a3 3 0 1 0 4.243 4.243" />
                    </svg>
                </button>
            </div>

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            <p x-show="confirmation !== '' && !matches" x-cloak class="mt-2 text-sm text-red-600">
                Passwords do not match.
            </p>
            <p x-show="matches" x-cloak class="mt-2 text-sm text-green-600">
                Passwords match.
            </p>
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button x-bind:disabled="!canSubmit"
                              x-bind:class="!canSubmit ? 'opacity-60 cursor-not-allowed' : ''">
                {{ __('Set Password and Log In') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

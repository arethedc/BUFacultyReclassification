<x-guest-layout>
    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
        <h2 class="text-lg font-semibold text-amber-800">Verification Link Expired</h2>
        <p class="mt-1 text-sm text-amber-700">
            The Verify Email and Set Password link has expired.
        </p>
        <p class="mt-1 text-sm text-amber-700">
            Click the button below to send a new activation email.
        </p>
    </div>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('activation.resend') }}" class="mt-4 space-y-4">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email"
                          class="block mt-1 w-full"
                          type="email"
                          name="email"
                          :value="old('email', $email ?? '')"
                          required
                          autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end">
            <x-primary-button>
                {{ __('Send New Activation Email') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>


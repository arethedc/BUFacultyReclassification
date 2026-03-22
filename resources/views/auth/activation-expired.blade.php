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
        <input type="hidden" name="activation_user_id" value="{{ (int) ($activationUserId ?? 0) }}">
        <input type="hidden" name="activation_hash" value="{{ (string) ($activationHash ?? '') }}">

        <div>
            <x-input-label :value="__('Email')" />
            <div class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-gray-700">
                {{ (string) ($email ?? '') }}
            </div>
            <x-input-error :messages="$errors->get('activation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end">
            <x-primary-button>
                {{ __('Send New Activation Email') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

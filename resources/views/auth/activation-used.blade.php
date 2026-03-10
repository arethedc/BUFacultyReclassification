<x-guest-layout>
    <div class="rounded-2xl border border-green-200 bg-green-50 px-5 py-4">
        <h2 class="text-lg font-semibold text-green-800">Email Verified</h2>
        <p class="mt-1 text-sm text-green-700">
            This Verify Email and Set Password link was already used.
        </p>
        <p class="mt-1 text-sm text-green-700">
            You can now log in to your account.
        </p>
    </div>

    <div class="mt-5 flex items-center justify-end">
        <a href="{{ route('login') }}"
           class="inline-flex items-center rounded-xl bg-bu px-4 py-2 text-sm font-semibold text-white hover:bg-bu-dark">
            Go to Login
        </a>
    </div>
</x-guest-layout>


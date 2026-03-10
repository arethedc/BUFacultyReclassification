<x-guest-layout>
    <style>
        @keyframes verify-bg-spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .verify-badge-bg {
            background: conic-gradient(
                #5bc84d 0deg,
                #52ba45 110deg,
                #63d056 220deg,
                #5bc84d 360deg
            );
            animation: verify-bg-spin 1.2s ease-out 1 forwards;
        }
    </style>

    <div class="rounded-2xl border border-green-200 bg-green-50 px-5 py-4">
        <div class="mb-3 flex flex-col items-center">
            <div class="relative h-24 w-24">
                <div class="verify-badge-bg absolute inset-0 rounded-full"></div>
                <div class="absolute inset-0 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-12 w-12 text-white" fill="none" stroke="currentColor" stroke-width="2.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12.5l4.2 4.2L19 7.8" />
                    </svg>
                </div>
            </div>
            <div class="mt-2 h-2 w-20 rounded-full bg-gray-300/70"></div>
        </div>
        <h2 class="text-lg font-semibold text-green-800">Email Verified</h2>
        <p class="mt-1 text-sm text-green-700">
            Your email has been successfully verified.
        </p>
        <p class="mt-1 text-sm text-green-700">
            Redirecting to Set Password...
        </p>
    </div>

    <div class="mt-5 flex items-center justify-end">
        <a href="{{ $setPasswordUrl }}"
           class="inline-flex items-center rounded-xl bg-bu px-4 py-2 text-sm font-semibold text-white hover:bg-bu-dark">
            Continue to Set Password
        </a>
    </div>

    <script>
        window.setTimeout(function () {
            window.location.assign(@json($setPasswordUrl));
        }, 1800);
    </script>
</x-guest-layout>

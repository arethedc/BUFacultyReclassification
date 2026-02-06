<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Faculty Dashboard
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow rounded">
                <p>Welcome, {{ auth()->user()->name }}</p>

                <ul class="mt-4 list-disc list-inside">
                    <li>Create reclassification form</li>
                    <li>Upload evidences</li>
                    <li>Track application status</li>
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>

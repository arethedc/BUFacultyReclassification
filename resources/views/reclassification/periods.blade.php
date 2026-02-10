<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-gray-800">Reclassification Periods</h2>
            <p class="text-sm text-gray-500">Open or close submission windows for faculty.</p>
        </div>
    </x-slot>

    <div class="py-12 bg-bu-muted min-h-screen">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-800">Create New Period</h3>
                <p class="text-sm text-gray-500 mt-1">Create a submission window. You can open it later.</p>

                <form method="POST" action="{{ route('reclassification.periods.store') }}" class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    @csrf
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Name</label>
                        <input type="text" name="name" required
                               class="w-full rounded-xl border-gray-300 focus:border-bu focus:ring-bu"
                               placeholder="AY 2026 1st Sem">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Start (optional)</label>
                        <input type="datetime-local" name="start_at"
                               class="w-full rounded-xl border-gray-300 focus:border-bu focus:ring-bu">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">End (optional)</label>
                        <input type="datetime-local" name="end_at"
                               class="w-full rounded-xl border-gray-300 focus:border-bu focus:ring-bu">
                    </div>
                    <div class="md:col-span-3 flex justify-end">
                        <button type="submit"
                                class="px-4 py-2 rounded-xl bg-bu text-white text-sm font-semibold shadow-soft hover:bg-bu-dark">
                            Create Period
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Existing Periods</h3>
                        <p class="text-sm text-gray-500">Only one period can be open at a time.</p>
                    </div>
                    @if($openPeriod)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border bg-green-50 text-green-700 border-green-200">
                            Open: {{ $openPeriod->name }}
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border bg-gray-50 text-gray-600 border-gray-200">
                            No open period
                        </span>
                    @endif
                </div>

                <div class="mt-5 overflow-hidden rounded-xl border">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left">
                            <tr>
                                <th class="px-4 py-2">Name</th>
                                <th class="px-4 py-2">Status</th>
                                <th class="px-4 py-2">Start</th>
                                <th class="px-4 py-2">End</th>
                                <th class="px-4 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($periods as $period)
                                <tr>
                                    <td class="px-4 py-2 font-medium text-gray-800">{{ $period->name }}</td>
                                    <td class="px-4 py-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] border {{ $period->is_open ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-50 text-gray-600 border-gray-200' }}">
                                            {{ $period->is_open ? 'Open' : 'Closed' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-gray-600">{{ optional($period->start_at)->format('Y-m-d H:i') ?? '-' }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ optional($period->end_at)->format('Y-m-d H:i') ?? '-' }}</td>
                                    <td class="px-4 py-2 text-right">
                                        <form method="POST" action="{{ route('reclassification.periods.toggle', $period) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="text-xs font-semibold {{ $period->is_open ? 'text-red-700' : 'text-bu' }} hover:underline">
                                                {{ $period->is_open ? 'Close' : 'Open' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">
                                        No submission periods yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

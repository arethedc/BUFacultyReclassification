<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-gray-800">Reclassification Settings</h2>
            <p class="text-sm text-gray-500">Manage departments and academic rank levels used across the system.</p>
        </div>
    </x-slot>

    <div class="py-12 bg-bu-muted min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Seed Defaults</h3>
                        <p class="text-sm text-gray-500">
                            Inserts default departments and rank levels if missing.
                        </p>
                    </div>
                    <form method="POST" action="{{ route('settings.reclassification.seed-defaults') }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-bu text-white text-sm font-semibold hover:bg-bu-dark shadow-soft transition"
                                onclick="return confirm('Seed default departments and rank levels now?')">
                            Seed Defaults
                        </button>
                    </form>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6 space-y-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Departments</h3>
                        <p class="text-sm text-gray-500">Add, edit, or delete department records.</p>
                    </div>

                    <form method="POST" action="{{ route('settings.reclassification.departments.store') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        @csrf
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Department Name</label>
                            <input type="text"
                                   name="name"
                                   value="{{ old('name') }}"
                                   required
                                   class="w-full rounded-xl border-gray-300 focus:border-bu focus:ring-bu text-sm"
                                   placeholder="e.g. CITE">
                        </div>
                        <div class="sm:self-end">
                            <button type="submit"
                                    class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-bu text-white text-sm font-semibold hover:bg-bu-dark transition">
                                Add Department
                            </button>
                        </div>
                    </form>

                    <div class="overflow-x-auto border border-gray-200 rounded-xl">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-gray-600">
                                <tr>
                                    <th class="px-4 py-3 text-left">Name</th>
                                    <th class="px-4 py-3 text-left">Users</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @forelse($departments as $department)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <form id="department-update-{{ $department->id }}"
                                                  method="POST"
                                                  action="{{ route('settings.reclassification.departments.update', $department) }}">
                                                @csrf
                                                @method('PUT')
                                                <input type="text"
                                                       name="name"
                                                       value="{{ $department->name }}"
                                                       required
                                                       class="w-full rounded-lg border-gray-300 focus:border-bu focus:ring-bu text-sm">
                                            </form>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600">{{ $department->users_count }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="inline-flex items-center gap-2">
                                                <button type="submit"
                                                        form="department-update-{{ $department->id }}"
                                                        class="px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 text-xs font-semibold hover:bg-gray-50">
                                                    Save
                                                </button>
                                                <form method="POST"
                                                      action="{{ route('settings.reclassification.departments.destroy', $department) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="px-3 py-1.5 rounded-lg border border-red-200 text-red-700 text-xs font-semibold hover:bg-red-50"
                                                            onclick="return confirm('Delete department {{ $department->name }}?')">
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-6 text-center text-gray-500">
                                            No departments found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6 space-y-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Academic Rank Levels</h3>
                        <p class="text-sm text-gray-500">Maintain code, title, and order sequence.</p>
                    </div>

                    <form method="POST" action="{{ route('settings.reclassification.rank-levels.store') }}" class="grid grid-cols-1 md:grid-cols-9 gap-3">
                        @csrf
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Code</label>
                            <input type="text"
                                   name="code"
                                   value="{{ old('code') }}"
                                   required
                                   class="w-full rounded-xl border-gray-300 focus:border-bu focus:ring-bu text-sm"
                                   placeholder="ASST_PROF_A">
                        </div>
                        <div class="md:col-span-4">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Title</label>
                            <input type="text"
                                   name="title"
                                   value="{{ old('title') }}"
                                   required
                                   class="w-full rounded-xl border-gray-300 focus:border-bu focus:ring-bu text-sm"
                                   placeholder="Assistant Professor A">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Order</label>
                            <input type="number"
                                   name="order_no"
                                   value="{{ old('order_no') }}"
                                   min="1"
                                   required
                                   class="w-full rounded-xl border-gray-300 focus:border-bu focus:ring-bu text-sm">
                        </div>
                        <div class="md:col-span-1 md:self-end">
                            <button type="submit"
                                    class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-bu text-white text-sm font-semibold hover:bg-bu-dark transition">
                                Add
                            </button>
                        </div>
                    </form>

                    <div class="overflow-x-auto border border-gray-200 rounded-xl">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-gray-600">
                                <tr>
                                    <th class="px-4 py-3 text-left">Code</th>
                                    <th class="px-4 py-3 text-left">Title</th>
                                    <th class="px-4 py-3 text-left">Order</th>
                                    <th class="px-4 py-3 text-left">Profiles</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @forelse($rankLevels as $rankLevel)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <form id="rank-level-update-{{ $rankLevel->id }}"
                                                  method="POST"
                                                  action="{{ route('settings.reclassification.rank-levels.update', $rankLevel) }}">
                                                @csrf
                                                @method('PUT')
                                                <input type="text"
                                                       name="code"
                                                       value="{{ $rankLevel->code }}"
                                                       required
                                                       class="w-full rounded-lg border-gray-300 focus:border-bu focus:ring-bu text-sm">
                                            </form>
                                        </td>
                                        <td class="px-4 py-3">
                                            <input type="text"
                                                   name="title"
                                                   form="rank-level-update-{{ $rankLevel->id }}"
                                                   value="{{ $rankLevel->title }}"
                                                   required
                                                   class="w-full rounded-lg border-gray-300 focus:border-bu focus:ring-bu text-sm">
                                        </td>
                                        <td class="px-4 py-3">
                                            <input type="number"
                                                   name="order_no"
                                                   form="rank-level-update-{{ $rankLevel->id }}"
                                                   value="{{ $rankLevel->order_no }}"
                                                   min="1"
                                                   required
                                                   class="w-24 rounded-lg border-gray-300 focus:border-bu focus:ring-bu text-sm">
                                        </td>
                                        <td class="px-4 py-3 text-gray-600">{{ $rankLevel->faculty_profiles_count }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="inline-flex items-center gap-2">
                                                <button type="submit"
                                                        form="rank-level-update-{{ $rankLevel->id }}"
                                                        class="px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 text-xs font-semibold hover:bg-gray-50">
                                                    Save
                                                </button>
                                                <form method="POST"
                                                      action="{{ route('settings.reclassification.rank-levels.destroy', $rankLevel) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="px-3 py-1.5 rounded-lg border border-red-200 text-red-700 text-xs font-semibold hover:bg-red-50"
                                                            onclick="return confirm('Delete rank level {{ $rankLevel->title }}?')">
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                            No rank levels found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>



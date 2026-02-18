<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-gray-800">Reclassification Periods</h2>
            <p class="text-sm text-gray-500">Open or close submission windows for faculty.</p>
        </div>
    </x-slot>

    <div class="py-12 bg-bu-muted min-h-screen"
         x-data="periodsPage({
             show: @js(session()->has('success') || $errors->any()),
             message: @js(session('success') ?? ($errors->first() ?? '')),
             type: @js(session()->has('success') ? 'success' : ($errors->any() ? 'error' : 'info'))
         })"
         x-init="init()">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-800">Create New Period</h3>
                <p class="text-sm text-gray-500 mt-1">Cycle is fixed at 3 years. Start years follow 2023, 2026, 2029... and used years are removed.</p>

                <form method="POST"
                      action="{{ route('reclassification.periods.store') }}"
                      data-loading-text="Creating period..."
                      x-data="{
                          startYear: '{{ old('start_year') }}',
                          endYear() { return this.startYear ? (Number(this.startYear) + 3) : ''; }
                      }"
                      class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Start Year</label>
                        @php
                            $maxStartYear = (int) date('Y') + 15;
                            $oldStartYear = (int) old('start_year', 0);
                            $currentYear = (int) date('Y');
                            $monthOptions = [
                                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun',
                                7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
                            ];
                            $timeOptions = [];
                            for ($h = 0; $h < 24; $h++) {
                                foreach ([0, 30] as $m) {
                                    $value = sprintf('%02d:%02d', $h, $m);
                                    $timeOptions[$value] = date('g:i A', strtotime($value));
                                }
                            }
                            $usedStartYears = collect($periods ?? [])
                                ->map(function ($period) {
                                    if (!empty($period->start_year)) {
                                        return (int) $period->start_year;
                                    }
                                    if (preg_match('/^(\d{4})-(\d{4})$/', (string) ($period->cycle_year ?? ''), $matches)) {
                                        return (int) $matches[1];
                                    }
                                    return null;
                                })
                                ->filter()
                                ->unique()
                                ->values()
                                ->all();
                        @endphp
                        <select name="start_year"
                                x-model="startYear"
                                required
                                class="w-full rounded-xl border-gray-300 focus:border-bu focus:ring-bu">
                            <option value="">Select start year</option>
                            @for($year = 2023; $year <= $maxStartYear; $year += 3)
                                @if(!in_array($year, $usedStartYears, true) || $oldStartYear === $year)
                                    <option value="{{ $year }}" @selected(old('start_year') == $year)>{{ $year }}</option>
                                @endif
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">End Year</label>
                        <input type="text"
                               x-bind:value="endYear()"
                               readonly
                               class="w-full rounded-xl border-gray-200 bg-gray-50 text-gray-700">
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Auto Name</label>
                        <div class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">
                            <span x-text="startYear && endYear() ? `AY ${startYear}-${endYear()}` : 'AY -'"></span>
                        </div>
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Submission Start (optional, {{ $currentYear }})</label>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                            <select name="start_month" class="w-full rounded-xl border-gray-300 focus:border-bu focus:ring-bu">
                                <option value="">Month</option>
                                @foreach($monthOptions as $monthNo => $monthLabel)
                                    <option value="{{ $monthNo }}" @selected((string) old('start_month') === (string) $monthNo)>{{ $monthLabel }}</option>
                                @endforeach
                            </select>
                            <select name="start_day" class="w-full rounded-xl border-gray-300 focus:border-bu focus:ring-bu">
                                <option value="">Day</option>
                                @for($day = 1; $day <= 31; $day++)
                                    <option value="{{ $day }}" @selected((string) old('start_day') === (string) $day)>{{ $day }}</option>
                                @endfor
                            </select>
                            <select name="start_time" class="w-full rounded-xl border-gray-300 focus:border-bu focus:ring-bu">
                                <option value="">Time</option>
                                @foreach($timeOptions as $timeValue => $timeLabel)
                                    <option value="{{ $timeValue }}" @selected((string) old('start_time') === (string) $timeValue)>{{ $timeLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Submission End (optional, {{ $currentYear }})</label>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                            <select name="end_month" class="w-full rounded-xl border-gray-300 focus:border-bu focus:ring-bu">
                                <option value="">Month</option>
                                @foreach($monthOptions as $monthNo => $monthLabel)
                                    <option value="{{ $monthNo }}" @selected((string) old('end_month') === (string) $monthNo)>{{ $monthLabel }}</option>
                                @endforeach
                            </select>
                            <select name="end_day" class="w-full rounded-xl border-gray-300 focus:border-bu focus:ring-bu">
                                <option value="">Day</option>
                                @for($day = 1; $day <= 31; $day++)
                                    <option value="{{ $day }}" @selected((string) old('end_day') === (string) $day)>{{ $day }}</option>
                                @endfor
                            </select>
                            <select name="end_time" class="w-full rounded-xl border-gray-300 focus:border-bu focus:ring-bu">
                                <option value="">Time</option>
                                @foreach($timeOptions as $timeValue => $timeLabel)
                                    <option value="{{ $timeValue }}" @selected((string) old('end_time') === (string) $timeValue)>{{ $timeLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="md:col-span-3 flex justify-end">
                        <button type="submit"
                                class="px-4 py-2 rounded-xl bg-bu text-white text-sm font-semibold shadow-soft hover:bg-bu-dark">
                            <span data-submit-label>Create Period</span>
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Existing Periods</h3>
                        <p class="text-sm text-gray-500">Only one period can be active at a time.</p>
                    </div>
                    @if($activePeriod)
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border bg-green-50 text-green-700 border-green-200">
                                Active: {{ $activePeriod->name }} ({{ $activePeriod->cycle_year ?? 'No cycle' }})
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border {{ !empty($openSubmissionPeriod) ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-gray-50 text-gray-600 border-gray-200' }}">
                                Submission: {{ !empty($openSubmissionPeriod) ? 'Open' : 'Closed' }}
                            </span>
                        </div>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border bg-gray-50 text-gray-600 border-gray-200">
                            No active period
                        </span>
                    @endif
                </div>

                <div class="mt-5 overflow-hidden rounded-xl border"
                     x-data="{ editingPeriodId: {{ session()->has('edit_period_id') ? (int) session('edit_period_id') : 'null' }} }">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left">
                            <tr>
                                <th class="px-4 py-2">Name</th>
                                <th class="px-4 py-2">Cycle</th>
                                <th class="px-4 py-2">Period Status</th>
                                <th class="px-4 py-2">Submission</th>
                                <th class="px-4 py-2">Start</th>
                                <th class="px-4 py-2">End</th>
                                <th class="px-4 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($periods as $period)
                                @php
                                    $status = (string) ($period->status ?? ($period->is_open ? 'active' : 'ended'));
                                    $statusLabel = ucfirst($status);
                                    $statusClass = match($status) {
                                        'active' => 'bg-green-50 text-green-700 border-green-200',
                                        'draft' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        default => 'bg-gray-50 text-gray-600 border-gray-200',
                                    };
                                    $isEditingFromSession = (int) session('edit_period_id', 0) === (int) $period->id;
                                    $periodStartMonth = optional($period->start_at)->format('n');
                                    $periodStartDay = optional($period->start_at)->format('j');
                                    $periodStartTime = optional($period->start_at)->format('H:i');
                                    $periodEndMonth = optional($period->end_at)->format('n');
                                    $periodEndDay = optional($period->end_at)->format('j');
                                    $periodEndTime = optional($period->end_at)->format('H:i');
                                    $startMonthValue = $isEditingFromSession ? old('start_month', $periodStartMonth) : $periodStartMonth;
                                    $startDayValue = $isEditingFromSession ? old('start_day', $periodStartDay) : $periodStartDay;
                                    $startTimeValue = $isEditingFromSession ? old('start_time', $periodStartTime) : $periodStartTime;
                                    $endMonthValue = $isEditingFromSession ? old('end_month', $periodEndMonth) : $periodEndMonth;
                                    $endDayValue = $isEditingFromSession ? old('end_day', $periodEndDay) : $periodEndDay;
                                    $endTimeValue = $isEditingFromSession ? old('end_time', $periodEndTime) : $periodEndTime;
                                @endphp
                                <tr>
                                    <td class="px-4 py-2 font-medium text-gray-800">{{ $period->name }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ $period->cycle_year ?? '-' }}</td>
                                    <td class="px-4 py-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] border {{ $statusClass }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] border {{ $period->is_open ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-gray-50 text-gray-600 border-gray-200' }}">
                                            {{ $period->is_open ? 'Open' : 'Closed' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-gray-600">{{ optional($period->start_at)->format('M d, h:i A') ?? '-' }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ optional($period->end_at)->format('M d, h:i A') ?? '-' }}</td>
                                    <td class="px-4 py-2 text-right">
                                        <div class="inline-flex items-center gap-3">
                                            <button type="button"
                                                    @click="editingPeriodId = editingPeriodId === {{ (int) $period->id }} ? null : {{ (int) $period->id }}"
                                                    class="text-xs font-semibold text-gray-700 hover:underline">
                                                Edit Window
                                            </button>
                                            <form method="POST"
                                                  action="{{ route('reclassification.periods.toggle', $period) }}"
                                                  data-loading-text="Updating period...">
                                                @csrf
                                                <button type="submit"
                                                        class="text-xs font-semibold {{ $status === 'active' ? 'text-red-700' : 'text-bu' }} hover:underline">
                                                    <span data-submit-label>{{ $status === 'active' ? 'End Period' : 'Set Active' }}</span>
                                                </button>
                                            </form>
                                            <form method="POST"
                                                  action="{{ route('reclassification.periods.submission.toggle', $period) }}"
                                                  data-loading-text="Updating submission...">
                                                @csrf
                                                <button type="submit"
                                                        @disabled($status !== 'active')
                                                        class="text-xs font-semibold {{ $status !== 'active' ? 'text-gray-400 cursor-not-allowed' : 'text-indigo-700 hover:underline' }}">
                                                    <span data-submit-label>{{ $period->is_open ? 'Close Submission' : 'Open Submission' }}</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <tr x-show="editingPeriodId === {{ (int) $period->id }}" x-cloak class="bg-gray-50/60">
                                    <td colspan="7" class="px-4 py-3">
                                        <form method="POST"
                                              action="{{ route('reclassification.periods.window.update', $period) }}"
                                              class="space-y-3"
                                              data-loading-text="Saving window...">
                                            @csrf
                                            <div class="text-xs font-semibold text-gray-700">Edit Submission Window (optional)</div>
                                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                                                <div class="space-y-1">
                                                    <label class="block text-[11px] font-semibold text-gray-600">Start</label>
                                                    <div class="grid grid-cols-3 gap-2">
                                                        <select name="start_month" class="w-full rounded-lg border-gray-300 focus:border-bu focus:ring-bu text-xs">
                                                            <option value="">Month</option>
                                                            @foreach($monthOptions as $monthNo => $monthLabel)
                                                                <option value="{{ $monthNo }}" @selected((string) $startMonthValue === (string) $monthNo)>{{ $monthLabel }}</option>
                                                            @endforeach
                                                        </select>
                                                        <select name="start_day" class="w-full rounded-lg border-gray-300 focus:border-bu focus:ring-bu text-xs">
                                                            <option value="">Day</option>
                                                            @for($day = 1; $day <= 31; $day++)
                                                                <option value="{{ $day }}" @selected((string) $startDayValue === (string) $day)>{{ $day }}</option>
                                                            @endfor
                                                        </select>
                                                        <select name="start_time" class="w-full rounded-lg border-gray-300 focus:border-bu focus:ring-bu text-xs">
                                                            <option value="">Time</option>
                                                            @foreach($timeOptions as $timeValue => $timeLabel)
                                                                <option value="{{ $timeValue }}" @selected((string) $startTimeValue === (string) $timeValue)>{{ $timeLabel }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="space-y-1">
                                                    <label class="block text-[11px] font-semibold text-gray-600">End</label>
                                                    <div class="grid grid-cols-3 gap-2">
                                                        <select name="end_month" class="w-full rounded-lg border-gray-300 focus:border-bu focus:ring-bu text-xs">
                                                            <option value="">Month</option>
                                                            @foreach($monthOptions as $monthNo => $monthLabel)
                                                                <option value="{{ $monthNo }}" @selected((string) $endMonthValue === (string) $monthNo)>{{ $monthLabel }}</option>
                                                            @endforeach
                                                        </select>
                                                        <select name="end_day" class="w-full rounded-lg border-gray-300 focus:border-bu focus:ring-bu text-xs">
                                                            <option value="">Day</option>
                                                            @for($day = 1; $day <= 31; $day++)
                                                                <option value="{{ $day }}" @selected((string) $endDayValue === (string) $day)>{{ $day }}</option>
                                                            @endfor
                                                        </select>
                                                        <select name="end_time" class="w-full rounded-lg border-gray-300 focus:border-bu focus:ring-bu text-xs">
                                                            <option value="">Time</option>
                                                            @foreach($timeOptions as $timeValue => $timeLabel)
                                                                <option value="{{ $timeValue }}" @selected((string) $endTimeValue === (string) $timeValue)>{{ $timeLabel }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-end gap-2">
                                                <button type="button"
                                                        @click="editingPeriodId = null"
                                                        class="px-3 py-1.5 rounded-lg border border-gray-300 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                                    Cancel
                                                </button>
                                                <button type="submit"
                                                        class="px-3 py-1.5 rounded-lg bg-bu text-white text-xs font-semibold hover:bg-bu-dark">
                                                    <span data-submit-label>Save Window</span>
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-500">
                                        No submission periods yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div x-cloak
             x-show="toast.show"
             x-transition
             class="fixed bottom-6 left-6 z-50">
            <div class="px-4 py-2 rounded-lg shadow-lg text-sm text-white"
                 :class="toast.type === 'success' ? 'bg-green-600' : (toast.type === 'error' ? 'bg-red-600' : 'bg-slate-800')">
                <span x-text="toast.message"></span>
            </div>
        </div>
    </div>

    <script>
        function periodsPage(initialToast) {
            return {
                toast: {
                    show: !!initialToast?.show,
                    message: initialToast?.message || '',
                    type: initialToast?.type || 'info',
                },
                toastTimer: null,
                init() {
                    if (this.toast.show) {
                        this.toastTimer = setTimeout(() => {
                            this.toast.show = false;
                        }, 3200);
                    }
                    this.bindLoadingStates();
                },
                bindLoadingStates() {
                    document.querySelectorAll('form[data-loading-text]').forEach((form) => {
                        if (form.dataset.loadingBound === '1') return;
                        form.dataset.loadingBound = '1';

                        form.addEventListener('submit', () => {
                            if (form.dataset.submitting === '1') return;
                            form.dataset.submitting = '1';
                            const submit = form.querySelector('button[type="submit"], input[type="submit"]');
                            if (!submit) return;

                            submit.disabled = true;
                            submit.classList.add('opacity-60', 'cursor-not-allowed');

                            const loadingText = form.dataset.loadingText || 'Saving...';
                            if (submit.tagName === 'INPUT') {
                                submit.value = loadingText;
                                return;
                            }

                            const label = submit.querySelector('[data-submit-label]') || submit;
                            if (!label.dataset.originalText) {
                                label.dataset.originalText = label.textContent || '';
                            }
                            label.textContent = loadingText;
                        });
                    });
                },
            };
        }
    </script>
</x-app-layout>

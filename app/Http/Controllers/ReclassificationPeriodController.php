<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\ReclassificationPeriod;

class ReclassificationPeriodController extends Controller
{
    private function hasStatusColumn(): bool
    {
        return Schema::hasColumn('reclassification_periods', 'status');
    }

    private function activePeriodQuery()
    {
        $query = ReclassificationPeriod::query();
        if ($this->hasStatusColumn()) {
            return $query->where('status', 'active');
        }

        return $query->where('is_open', true);
    }

    public function index()
    {
        $periods = ReclassificationPeriod::orderByDesc('created_at')->get();
        $activePeriod = $this->activePeriodQuery()
            ->orderByDesc('created_at')
            ->first();
        $openSubmissionPeriod = $this->activePeriodQuery()
            ->where('is_open', true)
            ->orderByDesc('created_at')
            ->first();

        return view('reclassification.periods', compact('periods', 'activePeriod', 'openSubmissionPeriod'));
    }

    public function store(Request $request)
    {
        if (!Schema::hasColumn('reclassification_periods', 'cycle_year')) {
            return back()
                ->withInput()
                ->withErrors([
                    'cycle_year' => 'Database update required. Run "php artisan migrate" to add cycle support.',
                ]);
        }

        $data = $request->validate([
            'start_year' => ['required', 'integer', 'digits:4', 'min:1900', 'max:2100'],
            'end_year' => ['required', 'integer', 'digits:4', 'gte:start_year', 'max:2100'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
        ]);

        $startYear = (int) $data['start_year'];
        $endYear = (int) $data['end_year'];
        $cycleYear = "{$startYear}-{$endYear}";

        // Prevent overlapping cycles (e.g. 2023-2026 overlaps 2025-2028).
        $overlap = ReclassificationPeriod::query()
            ->whereNotNull('cycle_year')
            ->get()
            ->first(function (ReclassificationPeriod $period) use ($startYear, $endYear) {
                if (!preg_match('/^(\d{4})-(\d{4})$/', (string) $period->cycle_year, $matches)) {
                    return false;
                }
                $existingStart = (int) $matches[1];
                $existingEnd = (int) $matches[2];

                return max($startYear, $existingStart) <= min($endYear, $existingEnd);
            });

        if ($overlap) {
            return back()
                ->withInput()
                ->withErrors([
                    'cycle_year' => "Cycle {$cycleYear} overlaps existing cycle {$overlap->cycle_year}.",
                ]);
        }

        $payload = [
            'name' => "AY {$cycleYear}",
            'cycle_year' => $cycleYear,
            'start_at' => $data['start_at'] ?? null,
            'end_at' => $data['end_at'] ?? null,
            'created_by_user_id' => $request->user()->id,
            'is_open' => false,
        ];
        if (Schema::hasColumn('reclassification_periods', 'status')) {
            $payload['status'] = 'draft';
        }

        ReclassificationPeriod::create($payload);

        return redirect()
            ->route('reclassification.periods')
            ->with('success', 'Submission period created.');
    }

    public function toggle(Request $request, ReclassificationPeriod $period)
    {
        $hasStatus = $this->hasStatusColumn();

        DB::transaction(function () use ($period, $hasStatus) {
            if ($hasStatus) {
                $isActive = (string) ($period->status ?? '') === 'active';

                if ($isActive) {
                    $period->update([
                        'status' => 'ended',
                        'is_open' => false,
                    ]);
                    return;
                }

                ReclassificationPeriod::query()
                    ->where('id', '!=', $period->id)
                    ->where('status', 'active')
                    ->update([
                        'status' => 'ended',
                        'is_open' => false,
                    ]);

                $period->update([
                    'status' => 'active',
                    'is_open' => false,
                ]);
                return;
            }

            if (!$period->is_open) {
                ReclassificationPeriod::where('id', '!=', $period->id)->update(['is_open' => false]);
            }
            $period->update(['is_open' => !$period->is_open]);
        });

        $period->refresh();
        $isNowActive = $hasStatus
            ? (string) ($period->status ?? '') === 'active'
            : (bool) $period->is_open;

        return redirect()
            ->route('reclassification.periods')
            ->with('success', $isNowActive ? 'Period set to Active. Open submissions separately when ready.' : 'Period ended.');
    }

    public function toggleSubmission(Request $request, ReclassificationPeriod $period)
    {
        $hasStatus = $this->hasStatusColumn();

        if ($hasStatus && (string) ($period->status ?? '') !== 'active') {
            return redirect()
                ->route('reclassification.periods')
                ->withErrors([
                    'period' => 'Only an active period can open/close submission.',
                ]);
        }

        $period->update([
            'is_open' => !$period->is_open,
        ]);

        return redirect()
            ->route('reclassification.periods')
            ->with('success', $period->is_open ? 'Submission is now Open for the active period.' : 'Submission is now Closed for the active period.');
    }
}

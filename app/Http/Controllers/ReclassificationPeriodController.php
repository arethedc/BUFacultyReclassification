<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ReclassificationPeriod;

class ReclassificationPeriodController extends Controller
{
    public function index()
    {
        $periods = ReclassificationPeriod::orderByDesc('created_at')->get();
        $openPeriod = $periods->firstWhere('is_open', true);

        return view('reclassification.periods', compact('periods', 'openPeriod'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'cycle_year' => ['required', 'regex:/^\d{4}\-\d{4}$/'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
        ]);

        $data['created_by_user_id'] = $request->user()->id;
        $data['is_open'] = false;

        ReclassificationPeriod::create($data);

        return redirect()
            ->route('reclassification.periods')
            ->with('success', 'Submission period created.');
    }

    public function toggle(Request $request, ReclassificationPeriod $period)
    {
        DB::transaction(function () use ($period) {
            if (!$period->is_open) {
                ReclassificationPeriod::where('id', '!=', $period->id)->update(['is_open' => false]);
            }
            $period->update(['is_open' => !$period->is_open]);
        });

        return redirect()
            ->route('reclassification.periods')
            ->with('success', $period->is_open ? 'Submission period opened.' : 'Submission period closed.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ReclassificationApplication;
use Illuminate\Http\Request;

class ReclassificationWorkflowController extends Controller
{
    public function submit(Request $request, ReclassificationApplication $application)
    {
        // Only owner faculty can submit
        abort_unless($request->user()->id === $application->faculty_user_id, 403);

        // Only draft/returned can submit
        abort_unless(in_array($application->status, ['draft', 'returned_to_faculty'], true), 422);

        $application->update([
            'status' => 'dean_review',
            'current_step' => 'dean',
            'returned_from' => null,
            'submitted_at' => $application->submitted_at ?? now(),
        ]);

        return redirect()
            ->route('reclassification.submitted')
            ->with('success', 'Submitted to Dean for review.');
    }

    public function returnToFaculty(Request $request, ReclassificationApplication $application)
    {
        // Reviewer roles only
        abort_unless(in_array($request->user()->role, ['dean','hr','vpaa','president'], true), 403);

        // Only return if currently in review stages
        abort_unless(in_array($application->status, ['dean_review','hr_review','vpaa_review','president_review'], true), 422);

        $application->update([
            'status' => 'returned_to_faculty',
            'current_step' => 'faculty',
            'returned_from' => $request->user()->role,
        ]);

        return back()->with('success', 'Returned to Faculty.');
    }

    public function forward(Request $request, ReclassificationApplication $application)
    {
        abort_unless(in_array($request->user()->role, ['dean','hr','vpaa','president'], true), 403);

        // Map forward chain
        $map = [
            'dean_review' => ['next_status' => 'hr_review', 'next_step' => 'hr'],
            'hr_review' => ['next_status' => 'vpaa_review', 'next_step' => 'vpaa'],
            'vpaa_review' => ['next_status' => 'president_review', 'next_step' => 'president'],
        ];

        if ($application->status === 'finalized') {
            return back()->with('success', 'Reclassification already finalized.');
        }

        if ($application->status === 'president_review') {
            $application->update([
                'status' => 'finalized',
                'current_step' => 'finalized',
                'finalized_at' => now(),
                'returned_from' => null,
            ]);

            return back()->with('success', 'Reclassification finalized.');
        }

        abort_unless(isset($map[$application->status]), 422);

        $next = $map[$application->status];

        $application->update([
            'status' => $next['next_status'],
            'current_step' => $next['next_step'],
            'returned_from' => null,
        ]);

        return back()->with('success', 'Forwarded to next stage.');
    }
}

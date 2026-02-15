<?php

namespace App\Http\Controllers;

use App\Models\ReclassificationApplication;
use App\Models\ReclassificationMoveRequest;
use App\Models\ReclassificationSectionEntry;
use Illuminate\Http\Request;

class ReclassificationMoveRequestController extends Controller
{
    private function criteriaMap(): array
    {
        return [
            '1' => ['a1', 'a2', 'a3', 'a4', 'a5', 'a6', 'a7', 'a8', 'a9', 'b', 'c'],
            '3' => ['c1', 'c2', 'c3', 'c4', 'c5', 'c6', 'c7', 'c8', 'c9'],
            '4' => ['a1', 'a2', 'b'],
            '5' => ['a', 'b', 'c1', 'c2', 'c3', 'd'],
        ];
    }

    public function store(Request $request, ReclassificationApplication $application, ReclassificationSectionEntry $entry)
    {
        abort_unless($request->user()->role === 'dean', 403);

        $entry->loadMissing('section');
        abort_unless($entry->section && $entry->section->reclassification_application_id === $application->id, 404);

        $validated = $request->validate([
            'target' => ['required', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $sourceSection = (string) $entry->section->section_code;
        $sourceCriterion = (string) $entry->criterion_key;
        [$targetSection, $targetCriterion] = array_pad(explode('|', (string) $validated['target'], 2), 2, null);
        $targetSection = (string) ($targetSection ?? '');
        $targetCriterion = (string) ($targetCriterion ?? '');

        $criteria = $this->criteriaMap();
        abort_unless(isset($criteria[$targetSection]), 422);
        abort_unless(in_array($targetCriterion, $criteria[$targetSection], true), 422);
        abort_unless(!($sourceSection === $targetSection && $sourceCriterion === $targetCriterion), 422);

        $note = trim((string) ($validated['note'] ?? ''));
        $duplicate = ReclassificationMoveRequest::query()
            ->where('reclassification_application_id', $application->id)
            ->where('source_section_code', $sourceSection)
            ->where('source_criterion_key', $sourceCriterion)
            ->where('target_section_code', $targetSection)
            ->where('target_criterion_key', $targetCriterion)
            ->where('note', $note)
            ->whereIn('status', ['pending', 'addressed'])
            ->exists();
        if ($duplicate) {
            return back()->with('success', 'Move request already exists.');
        }

        ReclassificationMoveRequest::create([
            'reclassification_application_id' => $application->id,
            'source_section_code' => $sourceSection,
            'source_criterion_key' => $sourceCriterion,
            'target_section_code' => $targetSection,
            'target_criterion_key' => $targetCriterion,
            'note' => $note,
            'requested_by_user_id' => $request->user()->id,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Move request added for faculty revision.');
    }

    public function address(Request $request, ReclassificationMoveRequest $moveRequest)
    {
        $application = $moveRequest->application()->firstOrFail();
        abort_unless($request->user()->id === $application->faculty_user_id, 403);
        abort_unless(in_array($application->status, ['draft', 'returned_to_faculty'], true), 422);
        if ($moveRequest->status === 'addressed') {
            return back()->with('success', 'Move request already addressed.');
        }
        if ($moveRequest->status === 'resolved') {
            return back()->with('success', 'Move request already resolved.');
        }
        abort_unless($moveRequest->status === 'pending', 422);

        $moveRequest->update([
            'status' => 'addressed',
            'resolved_by_user_id' => null,
            'resolved_at' => null,
        ]);

        return back()->with('success', 'Move request marked as addressed.');
    }

    public function resolve(Request $request, ReclassificationMoveRequest $moveRequest)
    {
        abort_unless(in_array($request->user()->role, ['dean', 'hr', 'vpaa', 'president'], true), 403);

        $application = $moveRequest->application()->with('faculty')->firstOrFail();
        abort_unless(in_array($application->status, ['dean_review', 'hr_review', 'vpaa_review', 'president_review'], true), 422);
        if ($moveRequest->status === 'resolved') {
            return back()->with('success', 'Move request already resolved.');
        }
        abort_unless($moveRequest->status === 'addressed', 422);

        if ($request->user()->role === 'dean') {
            $userDepartmentId = $request->user()->department_id;
            abort_unless($userDepartmentId && $application->faculty?->department_id === $userDepartmentId, 403);
        }

        $moveRequest->update([
            'status' => 'resolved',
            'resolved_by_user_id' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        return back()->with('success', 'Move request marked as resolved by reviewer.');
    }
}

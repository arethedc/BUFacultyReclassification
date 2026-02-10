<?php

namespace App\Http\Controllers;

use App\Models\ReclassificationApplication;
use App\Models\ReclassificationRowComment;
use App\Models\ReclassificationSectionEntry;
use Illuminate\Http\Request;

class ReclassificationRowCommentController extends Controller
{
    public function store(Request $request, ReclassificationApplication $application, ReclassificationSectionEntry $entry)
    {
        $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'visibility' => ['required', 'in:faculty_visible,internal'],
        ]);

        $entry->loadMissing('section');
        abort_unless($entry->section && $entry->section->reclassification_application_id === $application->id, 404);

        ReclassificationRowComment::create([
            'reclassification_application_id' => $application->id,
            'reclassification_section_entry_id' => $entry->id,
            'user_id' => $request->user()->id,
            'body' => $request->input('body'),
            'visibility' => $request->input('visibility', 'faculty_visible'),
        ]);

        return back()->with('success', 'Comment added.');
    }
}

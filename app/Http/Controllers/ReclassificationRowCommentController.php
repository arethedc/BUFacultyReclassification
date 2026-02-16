<?php

namespace App\Http\Controllers;

use App\Models\ReclassificationApplication;
use App\Models\ReclassificationRowComment;
use App\Models\ReclassificationSectionEntry;
use Illuminate\Http\Request;

class ReclassificationRowCommentController extends Controller
{
    private function isEntryRemoved(?ReclassificationSectionEntry $entry): bool
    {
        if (!$entry) {
            return false;
        }

        $data = is_array($entry->data) ? $entry->data : [];
        $value = $data['is_removed'] ?? false;

        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function respond(Request $request, string $message)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    public function store(Request $request, ReclassificationApplication $application, ReclassificationSectionEntry $entry)
    {
        abort_unless(in_array($request->user()->role, ['dean', 'hr', 'vpaa', 'president'], true), 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'visibility' => ['required', 'in:faculty_visible,internal'],
        ]);

        $entry->loadMissing('section');
        abort_unless($entry->section && $entry->section->reclassification_application_id === $application->id, 404);
        abort_unless(!$this->isEntryRemoved($entry), 422, 'This entry was removed by faculty.');

        $body = trim((string) $validated['body']);
        $visibility = (string) ($validated['visibility'] ?? 'faculty_visible');

        $recentDuplicate = ReclassificationRowComment::query()
            ->where('reclassification_application_id', $application->id)
            ->where('reclassification_section_entry_id', $entry->id)
            ->where('user_id', $request->user()->id)
            ->whereNull('parent_id')
            ->where('body', $body)
            ->where('visibility', $visibility)
            ->where('created_at', '>=', now()->subSeconds(15))
            ->exists();
        if ($recentDuplicate) {
            return $this->respond($request, 'Comment already saved.');
        }

        ReclassificationRowComment::create([
            'reclassification_application_id' => $application->id,
            'reclassification_section_entry_id' => $entry->id,
            'user_id' => $request->user()->id,
            'body' => $body,
            'visibility' => $visibility,
            'parent_id' => null,
            'status' => 'open',
        ]);

        return $this->respond($request, 'Comment added.');
    }

    public function reply(Request $request, ReclassificationRowComment $comment)
    {
        $application = $comment->application()->firstOrFail();

        abort_unless($request->user()->id === $application->faculty_user_id, 403);
        abort_unless($application->status === 'returned_to_faculty', 422);
        abort_unless($comment->parent_id === null, 422);
        abort_unless($comment->visibility === 'faculty_visible', 422);
        abort_unless($comment->status !== 'resolved', 422);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $body = trim((string) $validated['body']);

        $recentDuplicate = ReclassificationRowComment::query()
            ->where('reclassification_application_id', $comment->reclassification_application_id)
            ->where('reclassification_section_entry_id', $comment->reclassification_section_entry_id)
            ->where('user_id', $request->user()->id)
            ->where('parent_id', $comment->id)
            ->where('body', $body)
            ->where('created_at', '>=', now()->subSeconds(15))
            ->exists();
        if ($recentDuplicate) {
            return $this->respond($request, 'Reply already saved.');
        }

        ReclassificationRowComment::create([
            'reclassification_application_id' => $comment->reclassification_application_id,
            'reclassification_section_entry_id' => $comment->reclassification_section_entry_id,
            'user_id' => $request->user()->id,
            'body' => $body,
            'visibility' => 'faculty_visible',
            'parent_id' => $comment->id,
            'status' => 'open',
        ]);

        $comment->update([
            'status' => 'addressed',
            'resolved_by_user_id' => null,
            'resolved_at' => null,
        ]);

        return $this->respond($request, 'Reply sent. Comment marked as addressed.');
    }

    public function address(Request $request, ReclassificationRowComment $comment)
    {
        $application = $comment->application()->firstOrFail();

        abort_unless($request->user()->id === $application->faculty_user_id, 403);
        abort_unless($application->status === 'returned_to_faculty', 422);
        abort_unless($comment->parent_id === null, 422);
        abort_unless($comment->visibility === 'faculty_visible', 422);
        abort_unless($comment->status !== 'resolved', 422);

        $comment->update([
            'status' => 'addressed',
            'resolved_by_user_id' => null,
            'resolved_at' => null,
        ]);

        return $this->respond($request, 'Comment marked as addressed.');
    }

    public function resolve(Request $request, ReclassificationRowComment $comment)
    {
        abort_unless(in_array($request->user()->role, ['dean', 'hr', 'vpaa', 'president'], true), 403);

        $application = $comment->application()->with('faculty')->firstOrFail();
        abort_unless($comment->parent_id === null, 422);

        if ($request->user()->role === 'dean') {
            $userDepartmentId = $request->user()->department_id;
            abort_unless($userDepartmentId && $application->faculty?->department_id === $userDepartmentId, 403);
        }

        $comment->update([
            'status' => 'resolved',
            'resolved_by_user_id' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        return $this->respond($request, 'Comment marked as resolved.');
    }

    public function destroy(Request $request, ReclassificationRowComment $comment)
    {
        abort_unless(in_array($request->user()->role, ['dean', 'hr', 'vpaa', 'president'], true), 403);

        $application = $comment->application()->with('faculty')->firstOrFail();

        if ($request->user()->role === 'dean') {
            $userDepartmentId = $request->user()->department_id;
            abort_unless($userDepartmentId && $application->faculty?->department_id === $userDepartmentId, 403);
        }

        abort_unless(($comment->status ?? 'open') !== 'resolved', 422, 'Resolved comments cannot be removed.');

        ReclassificationRowComment::where('parent_id', $comment->id)->delete();
        $comment->delete();

        return $this->respond($request, 'Comment removed.');
    }
}

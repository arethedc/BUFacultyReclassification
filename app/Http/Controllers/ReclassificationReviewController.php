<?php

namespace App\Http\Controllers;

use App\Models\ReclassificationApplication;
use App\Models\ReclassificationSectionEntry;
use App\Support\ReclassificationEligibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReclassificationReviewController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $departmentId = $user->department_id;
        $role = strtolower((string) $user->role);
        if ($role === 'president') {
            return redirect()
                ->route('reclassification.review.approved')
                ->with('success', 'President approves the cycle list from Approved List.');
        }
        $status = $this->statusForRole($role);

        $applicationsQuery = ReclassificationApplication::with(['faculty.department', 'sections'])
            ->when($status, fn ($q) => $q->where('status', $status), fn ($q) => $q->whereRaw('1 = 0'));

        if ($role === 'dean' && $departmentId) {
            $applicationsQuery->whereHas('faculty', function ($query) use ($departmentId) {
                $query->where('department_id', $departmentId);
            });
        } elseif ($role === 'dean') {
            $applicationsQuery->whereRaw('1 = 0');
        }

        $applications = $applicationsQuery
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->get();

        return view('reclassification.reviewer.index', compact('applications'));
    }

    public function show(Request $request, ReclassificationApplication $application)
    {
        $role = strtolower((string) $request->user()->role);
        $status = $this->statusForRole($role);
        abort_unless($status && $application->status === $status, 404);

        $application->load([
            'faculty',
            'faculty.department',
            'faculty.facultyProfile',
            'sections.entries.evidences',
            'sections.entries.rowComments.author',
            'moveRequests.requestedBy',
        ]);

        if ($role === 'dean') {
            $userDepartmentId = $request->user()->department_id;
            if (!$userDepartmentId || $application->faculty?->department_id !== $userDepartmentId) {
                abort(403);
            }
        }

        $section2 = $application->sections->firstWhere('section_code', '2');
        $section2Data = $section2 ? $this->buildSectionTwoData($section2) : [];
        $section2Review = $section2 ? $this->buildSectionTwoReview($section2) : [];
        $eligibility = $application->faculty
            ? ReclassificationEligibility::evaluate($application, $application->faculty)
            : [
                'hasMasters' => false,
                'hasDoctorate' => false,
                'hasResearchEquivalent' => false,
                'hasAcceptedResearchOutput' => false,
            ];

        $facultyProfile = $application->faculty?->facultyProfile;
        $appointmentDate = $facultyProfile?->original_appointment_date
            ? $facultyProfile->original_appointment_date->format('M d, Y')
            : null;
        $yearsService = $facultyProfile?->original_appointment_date
            ? $facultyProfile->original_appointment_date->diffInYears(now())
            : null;
        $currentRankLabel = $this->resolveRankLabel($facultyProfile);

        $reviewer = $request->user();
        $reviewerRole = ucfirst($reviewer->role ?? 'Reviewer');
        $reviewerDept = $reviewer->department?->name;
        $canEditSection1C = $role === 'dean';
        $section1cUpdateRoute = $role === 'dean'
            ? 'reclassification.dean.section1c.update'
            : 'reclassification.review.section1c.update';

        return view('reclassification.reviewer.show', compact(
            'application',
            'section2Data',
            'section2Review',
            'eligibility',
            'appointmentDate',
            'yearsService',
            'currentRankLabel',
            'reviewerRole',
            'reviewerDept',
            'canEditSection1C',
            'section1cUpdateRoute'
        ));
    }

    public function saveSectionTwo(Request $request, ReclassificationApplication $application)
    {
        $role = strtolower((string) $request->user()->role);
        $status = $this->statusForRole($role);
        abort_unless($status && $application->status === $status, 403);

        if ($role !== 'dean') {
            abort(403);
        }
        $application->loadMissing('faculty');
        $userDepartmentId = $request->user()->department_id;
        if (!$userDepartmentId || $application->faculty?->department_id !== $userDepartmentId) {
            abort(403);
        }

        $section = $application->sections->firstWhere('section_code', '2');
        abort_unless($section, 404);

        $data = $request->validate([
            'section2' => ['array'],
            'section2.ratings' => ['array'],
            'section2.ratings.dean' => ['array'],
            'section2.ratings.chair' => ['array'],
            'section2.ratings.student' => ['array'],
            'section2.ratings.dean.i1' => ['nullable', 'numeric'],
            'section2.ratings.dean.i2' => ['nullable', 'numeric'],
            'section2.ratings.dean.i3' => ['nullable', 'numeric'],
            'section2.ratings.dean.i4' => ['nullable', 'numeric'],
            'section2.ratings.chair.i1' => ['nullable', 'numeric'],
            'section2.ratings.chair.i2' => ['nullable', 'numeric'],
            'section2.ratings.chair.i3' => ['nullable', 'numeric'],
            'section2.ratings.chair.i4' => ['nullable', 'numeric'],
            'section2.ratings.student.i1' => ['nullable', 'numeric'],
            'section2.ratings.student.i2' => ['nullable', 'numeric'],
            'section2.ratings.student.i3' => ['nullable', 'numeric'],
            'section2.ratings.student.i4' => ['nullable', 'numeric'],
            'section2.previous_points' => ['nullable', 'numeric'],
        ]);

        $ratings = $data['section2']['ratings'] ?? [];
        $previous = (float) ($data['section2']['previous_points'] ?? 0);

        $dean = $this->normalizeSectionTwoRater($ratings['dean'] ?? []);
        $chair = $this->normalizeSectionTwoRater($ratings['chair'] ?? []);
        $student = $this->normalizeSectionTwoRater($ratings['student'] ?? []);

        $deanPts = $this->sumRaterPoints($dean);
        $chairPts = $this->sumRaterPoints($chair);
        $studentPts = $this->sumRaterPoints($student);

        $weighted = ($deanPts * 0.4) + ($chairPts * 0.3) + ($studentPts * 0.3);
        $total = $weighted + ($previous / 3);
        if ($total > 120) $total = 120;

        $isComplete = $this->isSectionTwoRatingsComplete([
            'dean' => $dean,
            'chair' => $chair,
            'student' => $student,
        ]);

        $section->entries()->delete();

        $section->entries()->create([
            'criterion_key' => 'ratings',
            'title' => 'Instructional Competence Ratings',
            'points' => $weighted,
            'is_validated' => false,
            'data' => [
                'ratings' => [
                    'dean' => $dean,
                    'chair' => $chair,
                    'student' => $student,
                ],
                'weighted_total' => $weighted,
            ],
        ]);

        if ($previous > 0) {
            $section->entries()->create([
                'criterion_key' => 'previous_points',
                'title' => 'Previous Reclassification',
                'points' => $previous / 3,
                'is_validated' => false,
                'data' => ['value' => $previous],
            ]);
        }

        $section->update([
            'points_total' => $total,
            'is_complete' => $isComplete,
        ]);

        return back()->with('success', 'Section II saved.');
    }

    private function normalizeSectionTwoRater(array $rater): array
    {
        $normalized = [];
        foreach (['i1', 'i2', 'i3', 'i4'] as $key) {
            $value = $rater[$key] ?? null;
            if ($value === null || $value === '') {
                $normalized[$key] = null;
                continue;
            }

            $numeric = (float) $value;
            if ($numeric <= 0) {
                $normalized[$key] = null;
                continue;
            }
            if ($numeric < 1) {
                $numeric = 1;
            }
            if ($numeric > 4) {
                $numeric = 4;
            }
            $normalized[$key] = round($numeric, 2);
        }

        return $normalized;
    }

    private function isSectionTwoRatingsComplete(array $ratings): bool
    {
        foreach (['dean', 'chair', 'student'] as $rater) {
            foreach (['i1', 'i2', 'i3', 'i4'] as $item) {
                $value = $ratings[$rater][$item] ?? null;
                if ($value === null || $value === '') {
                    return false;
                }
            }
        }

        return true;
    }

    public function updateSectionOneC(Request $request, ReclassificationApplication $application, ReclassificationSectionEntry $entry)
    {
        $role = strtolower((string) $request->user()->role);
        $status = $this->statusForRole($role);
        abort_unless($status && $application->status === $status, 403);

        if ($role !== 'dean') {
            abort(403);
        }
        $application->loadMissing('faculty');
        $userDepartmentId = $request->user()->department_id;
        if (!$userDepartmentId || $application->faculty?->department_id !== $userDepartmentId) {
            abort(403);
        }

        $entry->loadMissing('section');
        if (!$entry->section || $entry->section->reclassification_application_id !== $application->id) {
            abort(404);
        }
        if ($entry->section->section_code !== '1' || $entry->criterion_key !== 'c') {
            abort(422);
        }

        $data = is_array($entry->data) ? $entry->data : [];
        $roleKey = $data['role'] ?? null;
        $level = $data['level'] ?? null;

        $ranges = [
            'speaker' => [
                'international' => [13, 15],
                'national' => [11, 12],
                'regional' => [9, 10],
                'provincial' => [7, 8],
                'municipal' => [4, 6],
                'school' => [1, 3],
            ],
            'resource' => [
                'international' => [11, 12],
                'national' => [9, 10],
                'regional' => [7, 8],
                'provincial' => [5, 6],
                'municipal' => [3, 4],
                'school' => [1, 2],
            ],
            'participant' => [
                'international' => [9, 10],
                'national' => [7, 8],
                'regional' => [5, 6],
                'provincial' => [3, 4],
                'municipal' => [2, 2],
                'school' => [1, 1],
            ],
        ];

        $range = $ranges[$roleKey][$level] ?? null;
        if (!$range) {
            abort(422, 'Invalid range for the selected role and level.');
        }

        $validated = $request->validate([
            'points' => ['required', 'numeric'],
        ]);

        $points = (float) $validated['points'];
        if ($points < $range[0] || $points > $range[1]) {
            return back()->withErrors('Points must be within the allowed range.');
        }

        $entry->update(['points' => $points]);

        $section = $entry->section->loadMissing('entries');
        $section->update([
            'points_total' => $this->recalculateSectionOnePoints($section->entries),
        ]);

        return back()->with('success', 'Section I-C points updated.');
    }

    private function buildSectionTwoData($section): array
    {
        $ratings = [
            'dean' => ['i1' => null, 'i2' => null, 'i3' => null, 'i4' => null],
            'chair' => ['i1' => null, 'i2' => null, 'i3' => null, 'i4' => null],
            'student' => ['i1' => null, 'i2' => null, 'i3' => null, 'i4' => null],
        ];
        $previous = 0;

        foreach ($section->entries as $entry) {
            $data = is_array($entry->data) ? $entry->data : [];
            if ($entry->criterion_key === 'ratings' && isset($data['ratings'])) {
                $ratings = array_replace_recursive($ratings, $data['ratings']);
            }
            if ($entry->criterion_key === 'previous_points') {
                $previous = (float) ($data['value'] ?? $data['points'] ?? 0);
            }
        }

        return [
            'ratings' => $ratings,
            'previous_points' => $previous,
        ];
    }

    private function buildSectionTwoReview($section): array
    {
        $ratings = [
            'dean' => ['i1' => null, 'i2' => null, 'i3' => null, 'i4' => null],
            'chair' => ['i1' => null, 'i2' => null, 'i3' => null, 'i4' => null],
            'student' => ['i1' => null, 'i2' => null, 'i3' => null, 'i4' => null],
        ];
        $previous = 0;

        foreach ($section->entries as $entry) {
            $data = is_array($entry->data) ? $entry->data : [];
            if ($entry->criterion_key === 'ratings' && isset($data['ratings'])) {
                $ratings = array_replace_recursive($ratings, $data['ratings']);
            }
            if ($entry->criterion_key === 'previous_points') {
                $previous = (float) ($data['value'] ?? $data['points'] ?? 0);
            }
        }

        $deanPts = $this->sumRaterPoints($ratings['dean'] ?? []);
        $chairPts = $this->sumRaterPoints($ratings['chair'] ?? []);
        $studentPts = $this->sumRaterPoints($ratings['student'] ?? []);
        $weighted = ($deanPts * 0.4) + ($chairPts * 0.3) + ($studentPts * 0.3);
        $total = $weighted + ($previous / 3);
        if ($total > 120) $total = 120;

        return [
            'ratings' => $ratings,
            'points' => [
                'dean' => $deanPts,
                'chair' => $chairPts,
                'student' => $studentPts,
                'weighted' => $weighted,
                'total' => $total,
                'previous' => $previous,
            ],
        ];
    }

    private function sumRaterPoints(array $ratings): float
    {
        return $this->pointsForItem(1, $ratings)
            + $this->pointsForItem(2, $ratings)
            + $this->pointsForItem(3, $ratings)
            + $this->pointsForItem(4, $ratings);
    }

    private function pointsForItem(int $itemNo, array $ratings): float
    {
        $key = 'i' . $itemNo;
        $rating = $this->normalizeRating($ratings[$key] ?? null);
        if ($rating === null) return 0;

        if ($itemNo === 1) return $this->pointsFromRatingItem1($rating);
        if ($itemNo === 2) return $this->pointsFromRatingItem2($rating);
        return $this->pointsFromRatingItem34($rating);
    }

    private function normalizeRating($value): ?float
    {
        if ($value === null || $value === '') return null;
        $num = (float) $value;
        return $num > 0 ? $num : null;
    }

    private function pointsFromRatingItem1(float $r): float
    {
        if ($r >= 3.72) return 40;
        if ($r >= 3.42) return 36;
        if ($r >= 3.12) return 32;
        if ($r >= 2.82) return 28;
        if ($r >= 2.52) return 24;
        if ($r >= 2.22) return 20;
        if ($r >= 1.92) return 16;
        if ($r >= 1.62) return 12;
        if ($r >= 1.31) return 8;
        return 4;
    }

    private function pointsFromRatingItem2(float $r): float
    {
        if ($r >= 3.72) return 30;
        if ($r >= 3.42) return 27;
        if ($r >= 3.12) return 24;
        if ($r >= 2.82) return 21;
        if ($r >= 2.52) return 18;
        if ($r >= 2.22) return 15;
        if ($r >= 1.92) return 12;
        if ($r >= 1.62) return 9;
        if ($r >= 1.31) return 6;
        return 3;
    }

    private function pointsFromRatingItem34(float $r): float
    {
        if ($r >= 3.72) return 25;
        if ($r >= 3.42) return 22.5;
        if ($r >= 3.12) return 20;
        if ($r >= 2.82) return 17.5;
        if ($r >= 2.52) return 15;
        if ($r >= 2.22) return 12.5;
        if ($r >= 1.92) return 10;
        if ($r >= 1.62) return 7.5;
        if ($r >= 1.31) return 5;
        return 2.5;
    }

    private function statusForRole(?string $role): ?string
    {
        return match ($role) {
            'dean' => 'dean_review',
            'hr' => 'hr_review',
            'vpaa' => 'vpaa_review',
            'president' => null,
            default => null,
        };
    }

    private function resolveRankLabel($profile): string
    {
        if ($profile && isset($profile->rank_level_id) && $profile->rank_level_id && Schema::hasTable('rank_levels')) {
            $title = DB::table('rank_levels')
                ->where('id', $profile->rank_level_id)
                ->value('title');
            if ($title) return $title;
        }

        if ($profile && $profile->teaching_rank) return $profile->teaching_rank;
        return 'Instructor';
    }

    private function recalculateSectionOnePoints($entries): float
    {
        $sum = fn ($key) => $entries->where('criterion_key', $key)->sum('points');
        $sumKeys = fn (array $keys) => $entries->whereIn('criterion_key', $keys)->sum('points');

        $aBase = $sumKeys(['a1','a2','a3','a4','a5','a6','a7']);
        $a8 = min($sum('a8'), 15);
        $a9 = min($sum('a9'), 10);
        $rawA = min($aBase + $a8 + $a9, 140);

        $rawB = min($sum('b'), 20);
        $rawC = min($sum('c'), 20);

        return min($rawA + $rawB + $rawC, 140);
    }
}

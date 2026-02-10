<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Support\ReclassificationEligibility;
use App\Models\ReclassificationApplication;
use App\Models\ReclassificationSection;
use App\Models\ReclassificationSectionEntry;
use App\Models\ReclassificationEvidence;

class ReclassificationFormController extends Controller
{
    /**
     * Get the user's latest application.
     * If none exists, auto-create draft + default sections.
     */
    private function getOrCreateDraft(Request $request): ReclassificationApplication
    {
        $user = $request->user();

        // get latest editable application (draft/returned)
        $app = ReclassificationApplication::where('faculty_user_id', $user->id)
            ->whereIn('status', ['draft', 'returned_to_faculty'])
            ->latest()
            ->first();

        if (!$app) {
            $app = ReclassificationApplication::create([
                'faculty_user_id' => $user->id,
                'cycle_year' => $this->currentCycleYear(),
                'status' => 'draft',
                'current_step' => 'faculty',
                'returned_from' => null,
                'submitted_at' => null,
                'finalized_at' => null,
            ]);

            // Create default sections 1..5
            $sections = [
                ['section_code' => '1', 'title' => 'Section I'],
                ['section_code' => '2', 'title' => 'Section II'],
                ['section_code' => '3', 'title' => 'Section III'],
                ['section_code' => '4', 'title' => 'Section IV'],
                ['section_code' => '5', 'title' => 'Section V'],
            ];

            foreach ($sections as $s) {
                ReclassificationSection::create([
                    'reclassification_application_id' => $app->id,
                    'section_code' => $s['section_code'],
                    'title' => $s['title'],
                    'is_complete' => false,
                    'points_total' => 0,
                ]);
            }
        }

        if (!$app->cycle_year) {
            $app->update(['cycle_year' => $this->currentCycleYear()]);
        }

        return $app->load('sections');
    }

    private function currentCycleYear(): string
    {
        return (string) config('reclassification.cycle_year', '2023-2026');
    }

    private function buildInitialSections(ReclassificationApplication $application): array
    {
        $maxByCode = [
            '1' => 140,
            '2' => 120,
            '3' => 70,
            '4' => 40,
            '5' => 30,
        ];

        return $application->sections->mapWithKeys(function ($section) use ($maxByCode) {
            $code = (string) $section->section_code;
            return [
                $code => [
                    'points' => (float) $section->points_total,
                    'max' => $maxByCode[$code] ?? 0,
                ],
            ];
        })->all();
    }

    public function show(Request $request)
    {
        $application = $this->getOrCreateDraft($request);

        $active = (int) $request->route('number', 1);
        if ($active < 1 || $active > 5) $active = 1;

        $section = $application->sections
            ->firstWhere('section_code', (string) $active);

        $sectionsData = $application->sections->mapWithKeys(function ($sec) {
            return [$sec->section_code => $this->buildSectionData($sec)];
        })->all();

        $eligibility = ReclassificationEligibility::evaluate($application, $request->user());

        $globalEvidence = $this->buildGlobalEvidence($application);

        $initialSections = $this->buildInitialSections($application);

        $user = $request->user()->load('facultyProfile');
        $profile = $user->facultyProfile;
        $appointmentDate = $profile?->original_appointment_date
            ? $profile->original_appointment_date->format('M d, Y')
            : null;
        $yearsService = $profile?->original_appointment_date
            ? $profile->original_appointment_date->diffInYears(now())
            : null;
        $currentRankLabel = $this->resolveRankLabel($profile, $eligibility['currentRank'] ?? null);

        return view('reclassification.show', compact(
            'application',
            'section',
            'sectionsData',
            'eligibility',
            'globalEvidence',
            'initialSections',
            'appointmentDate',
            'yearsService',
            'currentRankLabel',
            'profile'
        ));
    }

    public function section(Request $request, int $number)
    {
        abort_unless($number >= 1 && $number <= 5, 404);

        $application = $this->getOrCreateDraft($request);

        $section = $application->sections
            ->firstWhere('section_code', (string) $number);

        abort_unless($section, 404);

        $sectionsData = $application->sections->mapWithKeys(function ($sec) {
            return [$sec->section_code => $this->buildSectionData($sec)];
        })->all();

        $eligibility = ReclassificationEligibility::evaluate($application, $request->user());

        $globalEvidence = $this->buildGlobalEvidence($application);

        $initialSections = $this->buildInitialSections($application);

        $user = $request->user()->load('facultyProfile');
        $profile = $user->facultyProfile;
        $appointmentDate = $profile?->original_appointment_date
            ? $profile->original_appointment_date->format('M d, Y')
            : null;
        $yearsService = $profile?->original_appointment_date
            ? $profile->original_appointment_date->diffInYears(now())
            : null;
        $currentRankLabel = $this->resolveRankLabel($profile, $eligibility['currentRank'] ?? null);

        return view('reclassification.show', compact(
            'application',
            'section',
            'sectionsData',
            'eligibility',
            'globalEvidence',
            'initialSections',
            'appointmentDate',
            'yearsService',
            'currentRankLabel',
            'profile'
        ));
    }

    public function review(Request $request)
    {
        return redirect()->route('reclassification.show', ['tab' => 'review']);
    }

    public function reviewSave(Request $request)
    {
        $application = $this->getOrCreateDraft($request);

        abort_unless(in_array($application->status, ['draft', 'returned_to_faculty'], true), 403);

        $application->touch();

        return back()->with('success', 'Draft saved.');
    }

    public function resetSection(Request $request, int $number)
    {
        abort_unless($number >= 1 && $number <= 5, 404);

        $application = $this->getOrCreateDraft($request);

        abort_unless(in_array($application->status, ['draft', 'returned_to_faculty'], true), 403);

        $section = $application->sections
            ->firstWhere('section_code', (string) $number);

        abort_unless($section, 404);

        DB::transaction(function () use ($section) {
            $section->entries()->delete();
            $this->detachSectionEvidence($section);
            $section->update([
                'points_total' => 0,
                'is_complete' => false,
            ]);
        });

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Section reset.',
            ]);
        }

        return redirect()
            ->route('reclassification.section', $number)
            ->with('success', 'Section reset.');
    }

    public function resetApplication(Request $request)
    {
        $application = $this->getOrCreateDraft($request);

        abort_unless(in_array($application->status, ['draft', 'returned_to_faculty'], true), 403);

        DB::transaction(function () use ($application) {
            foreach ($application->sections as $section) {
                $section->entries()->delete();
                $this->detachSectionEvidence($section);
                $section->update([
                    'points_total' => 0,
                    'is_complete' => false,
                ]);
            }
            $application->touch();
        });

        $active = (int) $request->input('active', 1);
        if ($active < 1 || $active > 5) $active = 1;

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Reclassification reset.',
            ]);
        }

        return redirect()
            ->route('reclassification.section', $active)
            ->with('success', 'Reclassification reset.');
    }

    public function submitted(Request $request)
    {
        $user = $request->user();

        $application = ReclassificationApplication::where('faculty_user_id', $user->id)
            ->whereNotIn('status', ['draft', 'returned_to_faculty'])
            ->latest()
            ->first();

        if (!$application) {
            $application = ReclassificationApplication::where('faculty_user_id', $user->id)
                ->latest()
                ->first();
        }

        if (!$application) {
            return redirect()->route('reclassification.show');
        }

        return view('reclassification.submitted', compact('application'));
    }

    public function submittedSummary(Request $request)
    {
        $user = $request->user();

        $application = ReclassificationApplication::where('faculty_user_id', $user->id)
            ->whereNotIn('status', ['draft', 'returned_to_faculty'])
            ->latest()
            ->first();

        if (!$application) {
            $application = ReclassificationApplication::where('faculty_user_id', $user->id)
                ->latest()
                ->first();
        }

        if (!$application) {
            return redirect()->route('reclassification.show');
        }

        $application->load([
            'sections.entries',
            'sections.evidences',
        ]);

        $sectionsByCode = $application->sections->keyBy('section_code');
        $section2Review = $this->buildSectionTwoReview($sectionsByCode->get('2'));

        $eligibility = ReclassificationEligibility::evaluate($application, $request->user());

        $user = $request->user()->load('facultyProfile');
        $profile = $user->facultyProfile;
        $appointmentDate = $profile?->original_appointment_date
            ? $profile->original_appointment_date->format('M d, Y')
            : null;
        $yearsService = $profile?->original_appointment_date
            ? $profile->original_appointment_date->diffInYears(now())
            : null;
        $currentRankLabel = $this->resolveRankLabel($profile);

        return view('reclassification.submitted-summary', compact(
            'application',
            'section2Review',
            'appointmentDate',
            'yearsService',
            'currentRankLabel',
            'profile',
            'eligibility'
        ));
    }

    public function submittedSummaryShow(Request $request, ReclassificationApplication $application)
    {
        $user = $request->user();

        abort_unless($application->faculty_user_id === $user->id, 403);

        $application->load([
            'sections.entries',
            'sections.evidences',
        ]);

        $sectionsByCode = $application->sections->keyBy('section_code');
        $section2Review = $this->buildSectionTwoReview($sectionsByCode->get('2'));

        $eligibility = ReclassificationEligibility::evaluate($application, $request->user());

        $profile = $application->faculty?->facultyProfile;
        $appointmentDate = $profile?->original_appointment_date
            ? $profile->original_appointment_date->format('M d, Y')
            : null;
        $yearsService = $profile?->original_appointment_date
            ? $profile->original_appointment_date->diffInYears(now())
            : null;
        $currentRankLabel = $this->resolveRankLabel($profile, $profile?->teaching_rank);

        return view('reclassification.submitted-summary', compact(
            'application',
            'section2Review',
            'appointmentDate',
            'yearsService',
            'currentRankLabel',
            'profile',
            'eligibility'
        ));
    }

    public function detachEvidence(Request $request, ReclassificationEvidence $evidence)
    {
        $user = $request->user();

        $application = ReclassificationApplication::where('id', $evidence->reclassification_application_id)
            ->where('faculty_user_id', $user->id)
            ->firstOrFail();

        abort_unless(in_array($application->status, ['draft', 'returned_to_faculty'], true), 403);

        DB::table('reclassification_evidence_links')
            ->where('reclassification_evidence_id', $evidence->id)
            ->delete();

        $evidence->update([
            'reclassification_section_entry_id' => null,
            'reclassification_section_id' => null,
        ]);

        return response()->json(['ok' => true]);
    }

    public function uploadEvidence(Request $request)
    {
        $application = $this->getOrCreateDraft($request);

        abort_unless(in_array($application->status, ['draft', 'returned_to_faculty'], true), 403);

        $request->validate([
            // Accept either a single file or an array of files.
            'evidence_files' => ['required'],
            'evidence_files.*' => ['file', 'max:20480'],
        ]);

        $this->storeGlobalEvidenceFiles($request, $application, $request->user()->id, 'evidence_files');

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'evidence' => $this->buildGlobalEvidence($application),
            ]);
        }

        return back()->with('success', 'Evidence uploaded.');
    }

    public function deleteEvidence(Request $request, ReclassificationEvidence $evidence)
    {
        $user = $request->user();

        $application = ReclassificationApplication::where('id', $evidence->reclassification_application_id)
            ->where('faculty_user_id', $user->id)
            ->firstOrFail();

        abort_unless(in_array($application->status, ['draft', 'returned_to_faculty'], true), 403);

        $hasLinks = DB::table('reclassification_evidence_links')
            ->where('reclassification_evidence_id', $evidence->id)
            ->exists();

        if ($evidence->reclassification_section_entry_id || $hasLinks) {
            return response()->json(['message' => 'Evidence is attached. Detach first.'], 422);
        }

        if ($evidence->path) {
            Storage::disk($evidence->disk ?: 'public')->delete($evidence->path);
        }

        $evidence->delete();

        return response()->json(['ok' => true]);
    }

    public function saveSection(Request $request, int $number)
    {
        abort_unless($number >= 1 && $number <= 5, 404);

        $application = $this->getOrCreateDraft($request);

        abort_unless(in_array($application->status, ['draft', 'returned_to_faculty'], true), 403);

        $section = $application->sections
            ->firstWhere('section_code', (string) $number);

        abort_unless($section, 404);

        DB::transaction(function () use ($request, $application, $section, $number) {
            if ($number === 1) {
                $this->saveSectionOne($request, $application, $section);
                return;
            }
            if ($number === 3) {
                $this->saveSectionThree($request, $application, $section);
                return;
            }
            if ($number === 4) {
                $this->saveSectionFour($request, $application, $section);
                return;
            }
            if ($number === 5) {
                $this->saveSectionFive($request, $application, $section);
                return;
            }

            throw ValidationException::withMessages([
                'section' => 'Saving for this section is not implemented.',
            ]);
        });

        return redirect()
            ->route('reclassification.section', $number)
            ->with('success', 'Section saved.');
    }

    private function buildSectionData(ReclassificationSection $section): array
    {
        $section->loadMissing(['entries.evidences', 'entries.rowComments.author', 'evidences']);

        $code = $section->section_code;
        $evidenceByEntry = $section->evidences->groupBy('reclassification_section_entry_id');
        $resolveEvidence = function ($entryId) use ($evidenceByEntry, $section) {
            if (!$entryId) return [];
            $values = [];

            $entry = $section->entries->firstWhere('id', $entryId);
            if ($entry && $entry->relationLoaded('evidences')) {
                foreach ($entry->evidences as $ev) {
                    $values[] = 'e:' . $ev->id;
                }
            }

            $items = $evidenceByEntry->get($entryId);
            if ($items && $items->isNotEmpty()) {
                foreach ($items as $ev) {
                    $values[] = 'e:' . $ev->id;
                }
            }

            return array_values(array_unique($values));
        };
        $resolveComments = function ($entry) {
            if (!$entry || !$entry->relationLoaded('rowComments')) return [];
            return $entry->rowComments
                ->where('visibility', 'faculty_visible')
                ->sortBy('created_at')
                ->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'body' => $comment->body,
                        'author' => $comment->author?->name ?? 'Reviewer',
                        'created_at' => optional($comment->created_at)->toDateTimeString(),
                    ];
                })
                ->values()
                ->all();
        };

        if ($code === '1') {
            $data = [
                'a1' => ['honors' => null, 'evidence' => [], 'comments' => []],
                'a2' => [],
                'a3' => [],
                'a4' => [],
                'a5' => [],
                'a6' => [],
                'a7' => [],
                'a8' => [],
                'a9' => [],
                'b' => [],
                'c' => [],
                'b_prev' => '',
                'c_prev' => '',
                'existingEvidence' => [],
            ];

            foreach ($section->entries as $entry) {
                $row = is_array($entry->data) ? $entry->data : [];
                $row['id'] = $entry->id;

                if ($entry->criterion_key === 'a1') {
                    $data['a1']['honors'] = $row['honors'] ?? null;
                    $data['a1']['evidence'] = $resolveEvidence($entry->id);
                    $data['a1']['comments'] = $resolveComments($entry);
                    continue;
                }

                if ($entry->criterion_key === 'b_prev') {
                    $data['b_prev'] = $row['value'] ?? $row['points'] ?? '';
                    continue;
                }

                if ($entry->criterion_key === 'c_prev') {
                    $data['c_prev'] = $row['value'] ?? $row['points'] ?? '';
                    continue;
                }

                if (array_key_exists($entry->criterion_key, $data)) {
                    $row['evidence'] = $resolveEvidence($entry->id);
                    $row['comments'] = $resolveComments($entry);
                    $data[$entry->criterion_key][] = $row;
                }
            }

            foreach ($section->evidences as $evidence) {
                $data['existingEvidence'][] = [
                    'id' => $evidence->id,
                    'name' => $evidence->original_name ?: $evidence->path,
                    'status' => $evidence->status ?? 'pending',
                    'url' => $evidence->path
                        ? Storage::disk($evidence->disk ?: 'public')->url($evidence->path)
                        : null,
                    'mime_type' => $evidence->mime_type,
                    'size_bytes' => $evidence->size_bytes,
                    'uploaded_at' => optional($evidence->created_at)->toDateTimeString(),
                ];
            }

            return $data;
        }

        if ($code === '3') {
            $data = [
                'c1' => [],
                'c2' => [],
                'c3' => [],
                'c4' => [],
                'c5' => [],
                'c6' => [],
                'c7' => [],
                'c8' => [],
                'c9' => [],
                'previous_points' => '',
                'existingEvidence' => [],
            ];

            foreach ($section->entries as $entry) {
                $row = is_array($entry->data) ? $entry->data : [];
                $row['id'] = $entry->id;

                if ($entry->criterion_key === 'previous_points') {
                    $data['previous_points'] = $row['value'] ?? $row['points'] ?? '';
                    continue;
                }

                if (array_key_exists($entry->criterion_key, $data)) {
                    $row['evidence'] = $resolveEvidence($entry->id);
                    $row['comments'] = $resolveComments($entry);
                    $data[$entry->criterion_key][] = $row;
                }
            }

            foreach ($section->evidences as $evidence) {
                $data['existingEvidence'][] = [
                    'id' => $evidence->id,
                    'name' => $evidence->original_name ?: $evidence->path,
                    'status' => $evidence->status ?? 'pending',
                    'url' => $evidence->path
                        ? Storage::disk($evidence->disk ?: 'public')->url($evidence->path)
                        : null,
                    'mime_type' => $evidence->mime_type,
                    'size_bytes' => $evidence->size_bytes,
                    'uploaded_at' => optional($evidence->created_at)->toDateTimeString(),
                ];
            }

            return $data;
        }

        if ($code === '4') {
            $data = [
                'a1_years' => 0,
                'a2_years' => 0,
                'b_years' => 0,
                'a1_evidence' => [],
                'a2_evidence' => [],
                'b_evidence' => [],
                'a1_comments' => [],
                'a2_comments' => [],
                'b_comments' => [],
                'existingEvidence' => [],
            ];

            foreach ($section->entries as $entry) {
                $row = is_array($entry->data) ? $entry->data : [];
                $row['id'] = $entry->id;
                if ($entry->criterion_key === 'a1') {
                    $data['a1_years'] = $row['years'] ?? 0;
                    $data['a1_evidence'] = $resolveEvidence($entry->id);
                    $data['a1_comments'] = $resolveComments($entry);
                    continue;
                }
                if ($entry->criterion_key === 'a2') {
                    $data['a2_years'] = $row['years'] ?? 0;
                    $data['a2_evidence'] = $resolveEvidence($entry->id);
                    $data['a2_comments'] = $resolveComments($entry);
                    continue;
                }
                if ($entry->criterion_key === 'b') {
                    $data['b_years'] = $row['years'] ?? 0;
                    $data['b_evidence'] = $resolveEvidence($entry->id);
                    $data['b_comments'] = $resolveComments($entry);
                    continue;
                }
            }

            foreach ($section->evidences as $evidence) {
                $data['existingEvidence'][] = [
                    'id' => $evidence->id,
                    'name' => $evidence->original_name ?: $evidence->path,
                    'status' => $evidence->status ?? 'pending',
                    'url' => $evidence->path
                        ? Storage::disk($evidence->disk ?: 'public')->url($evidence->path)
                        : null,
                    'mime_type' => $evidence->mime_type,
                    'size_bytes' => $evidence->size_bytes,
                    'uploaded_at' => optional($evidence->created_at)->toDateTimeString(),
                ];
            }

            return $data;
        }

        if ($code === '5') {
            $data = [
                'a' => [],
                'b' => [],
                'c1' => [],
                'c2' => [],
                'c3' => [],
                'd' => [],
                'b_prev' => '',
                'c_prev' => '',
                'd_prev' => '',
                'previous_points' => '',
                'existingEvidence' => [],
            ];

            foreach ($section->entries as $entry) {
                $row = is_array($entry->data) ? $entry->data : [];
                $row['id'] = $entry->id;

                if ($entry->criterion_key === 'b_prev') {
                    $data['b_prev'] = $row['value'] ?? $row['points'] ?? '';
                    continue;
                }

                if ($entry->criterion_key === 'c_prev') {
                    $data['c_prev'] = $row['value'] ?? $row['points'] ?? '';
                    continue;
                }

                if ($entry->criterion_key === 'd_prev') {
                    $data['d_prev'] = $row['value'] ?? $row['points'] ?? '';
                    continue;
                }

                if ($entry->criterion_key === 'previous_points') {
                    $data['previous_points'] = $row['value'] ?? $row['points'] ?? '';
                    continue;
                }

                if (array_key_exists($entry->criterion_key, $data)) {
                    $row['evidence'] = $resolveEvidence($entry->id);
                    $row['comments'] = $resolveComments($entry);
                    $data[$entry->criterion_key][] = $row;
                }
            }

            foreach ($section->evidences as $evidence) {
                $data['existingEvidence'][] = [
                    'id' => $evidence->id,
                    'name' => $evidence->original_name ?: $evidence->path,
                    'status' => $evidence->status ?? 'pending',
                    'url' => $evidence->path
                        ? Storage::disk($evidence->disk ?: 'public')->url($evidence->path)
                        : null,
                    'mime_type' => $evidence->mime_type,
                    'size_bytes' => $evidence->size_bytes,
                    'uploaded_at' => optional($evidence->created_at)->toDateTimeString(),
                ];
            }

            return $data;
        }

        return [];
    }

    private function resolveRankLabel($profile, ?string $fallback = null): string
    {
        if ($profile && isset($profile->rank_level_id) && $profile->rank_level_id && Schema::hasTable('rank_levels')) {
            $title = DB::table('rank_levels')
                ->where('id', $profile->rank_level_id)
                ->value('title');
            if ($title) return $title;
        }

        if ($profile && $profile->teaching_rank) return $profile->teaching_rank;
        if ($fallback) return $fallback;
        return 'Instructor';
    }

    private function buildSectionTwoReview(?ReclassificationSection $section): array
    {
        if (!$section) return [];
        $section->loadMissing('entries');

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

    private function buildGlobalEvidence(ReclassificationApplication $application): array
    {
        $application->loadMissing(['sections']);
        $sectionMap = $application->sections->keyBy('id');
        $evidenceIds = ReclassificationEvidence::where('reclassification_application_id', $application->id)
            ->pluck('id')
            ->all();

        $linkStats = [];
        if (!empty($evidenceIds)) {
            $linkStats = DB::table('reclassification_evidence_links')
                ->select(
                    'reclassification_evidence_id',
                    DB::raw('count(*) as entry_count'),
                    DB::raw('min(reclassification_section_entry_id) as entry_id'),
                    DB::raw('min(reclassification_section_id) as section_id')
                )
                ->whereIn('reclassification_evidence_id', $evidenceIds)
                ->groupBy('reclassification_evidence_id')
                ->get()
                ->keyBy('reclassification_evidence_id')
                ->all();
        }

        return ReclassificationEvidence::where('reclassification_application_id', $application->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($evidence) use ($sectionMap, $linkStats) {
                $stats = $linkStats[$evidence->id] ?? null;
                $linkedEntryId = $stats?->entry_id ?? $evidence->reclassification_section_entry_id;
                $linkedSectionId = $stats?->section_id ?? $evidence->reclassification_section_id;
                $section = $sectionMap->get($linkedSectionId);
                $entryCount = $stats?->entry_count ?? ($evidence->reclassification_section_entry_id ? 1 : 0);
                return [
                    'id' => $evidence->id,
                    'name' => $evidence->original_name ?: $evidence->path,
                    'url' => $evidence->path
                        ? Storage::disk($evidence->disk ?: 'public')->url($evidence->path)
                        : null,
                    'mime_type' => $evidence->mime_type,
                    'size_bytes' => $evidence->size_bytes,
                    'uploaded_at' => optional($evidence->created_at)->toDateTimeString(),
                    'section_code' => $section?->section_code,
                    'section_title' => $section?->title,
                    'entry_id' => $linkedEntryId,
                    'entry_count' => $entryCount,
                ];
            })
            ->values()
            ->all();
    }

    private function saveSectionOne(Request $request, ReclassificationApplication $application, ReclassificationSection $section): void
    {
        $request->validate([
            'section1' => ['array'],
            'section1.evidence_files.*' => ['file', 'max:20480'],
        ]);

        $section->entries()->delete();
        $this->detachSectionEvidence($section);

        $uploaded = $this->storeEvidenceFiles($request, $application, $section, 1, $request->user()->id, 'section1.evidence_files');

        $input = $request->input('section1', []);
        $action = $request->input('action', 'draft');

        $aBase = 0;
        $a8Sum = 0;
        $a9Sum = 0;
        $bSum = 0;
        $cSum = 0;

        $a1Honors = data_get($input, 'a1.honors');
        $a1Evidence = data_get($input, 'a1.evidence', []);

        if ($a1Honors && $a1Honors !== 'none') {
            if ($action === 'submit') {
                $this->ensureEvidence([['evidence' => $a1Evidence]], 'section1.a1', $uploaded, $section, $application);
            }

            $points = $this->pointsA1($a1Honors);
            $aBase += $points;
            $this->createEntry($section, $application, 'a1', [
                'honors' => $a1Honors,
                'evidence' => $a1Evidence,
            ], $points, $a1Evidence, $uploaded);
        }

        $rowsA2 = $this->normalizeRows($input['a2'] ?? []);
        $rowsA3 = $this->normalizeRows($input['a3'] ?? []);
        $rowsA4 = $this->normalizeRows($input['a4'] ?? []);
        $rowsA5 = $this->normalizeRows($input['a5'] ?? []);
        $rowsA6 = $this->normalizeRows($input['a6'] ?? []);
        $rowsA7 = $this->normalizeRows($input['a7'] ?? []);
        $rowsA8 = $this->normalizeRows($input['a8'] ?? []);
        $rowsA9 = $this->normalizeRows($input['a9'] ?? []);
        $rowsB = $this->normalizeRows($input['b'] ?? []);
        $rowsC = $this->normalizeRows($input['c'] ?? []);

        if ($action === 'submit') {
            $this->ensureEvidence($rowsA2, 'section1.a2', $uploaded, $section, $application);
            $this->ensureEvidence($rowsA3, 'section1.a3', $uploaded, $section, $application);
            $this->ensureEvidence($rowsA4, 'section1.a4', $uploaded, $section, $application);
            $this->ensureEvidence($rowsA5, 'section1.a5', $uploaded, $section, $application);
            $this->ensureEvidence($rowsA6, 'section1.a6', $uploaded, $section, $application);
            $this->ensureEvidence($rowsA7, 'section1.a7', $uploaded, $section, $application);
            $this->ensureEvidence($rowsA8, 'section1.a8', $uploaded, $section, $application);
            $this->ensureEvidence($rowsA9, 'section1.a9', $uploaded, $section, $application);
            $this->ensureEvidence($rowsB, 'section1.b', $uploaded, $section, $application);
            $this->ensureEvidence($rowsC, 'section1.c', $uploaded, $section, $application);
        }

        $rowsA2 = $this->bucketOnce($rowsA2, fn ($r) => $r['category'] ?? '', fn ($r) => $this->pointsA('a2', $r));
        $rowsA3 = $this->bucketOnce($rowsA3, fn ($r) => ($r['option'] ?? '') ?: (($r['category'] ?? '') . '|' . ($r['thesis'] ?? '')), fn ($r) => $this->pointsA('a3', $r));
        $rowsA4 = $this->bucketOnce($rowsA4, fn ($r) => $r['category'] ?? '', fn ($r) => $this->pointsA('a4', $r));
        $rowsA5 = $this->bucketOnce($rowsA5, fn ($r) => $r['category'] ?? '', fn ($r) => $this->pointsA('a5', $r));
        $rowsA6 = $this->bucketOnce($rowsA6, fn ($r) => $r['category'] ?? '', fn ($r) => $this->pointsA('a6', $r));
        $rowsA7 = $this->bucketOnce($rowsA7, fn ($r) => $r['category'] ?? '', fn ($r) => $this->pointsA('a7', $r));
        $rowsA8 = $this->bucketOnce($rowsA8, fn ($r) => $r['relation'] ?? '', fn ($r) => $this->pointsA('a8', $r));
        $rowsA9 = $this->bucketOnce($rowsA9, fn ($r) => $r['level'] ?? '', fn ($r) => $this->pointsA('a9', $r));
        $rowsB = $this->bucketOnce($rowsB, fn ($r) => $r['hours'] ?? '', fn ($r) => $this->pointsB($r));
        $rowsC = $this->bucketOnce($rowsC, fn ($r) => ($r['role'] ?? '') . '|' . ($r['level'] ?? ''), fn ($r) => $this->pointsC($r));

        foreach ($rowsA2 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $aBase += $points;
            $this->createEntry($section, $application, 'a2', $row, $points, $row['evidence'] ?? null, $uploaded);
        }

        foreach ($rowsA3 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $aBase += $points;
            $this->createEntry($section, $application, 'a3', $row, $points, $row['evidence'] ?? null, $uploaded);
        }

        foreach ($rowsA4 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $aBase += $points;
            $this->createEntry($section, $application, 'a4', $row, $points, $row['evidence'] ?? null, $uploaded);
        }

        foreach ($rowsA5 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $aBase += $points;
            $this->createEntry($section, $application, 'a5', $row, $points, $row['evidence'] ?? null, $uploaded);
        }

        foreach ($rowsA6 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $aBase += $points;
            $this->createEntry($section, $application, 'a6', $row, $points, $row['evidence'] ?? null, $uploaded);
        }

        foreach ($rowsA7 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $aBase += $points;
            $this->createEntry($section, $application, 'a7', $row, $points, $row['evidence'] ?? null, $uploaded);
        }

        foreach ($rowsA8 as $row) {
            $points = min((float) ($row['points'] ?? 0), 5);
            $a8Sum += $points;
            $this->createEntry($section, $application, 'a8', $row, $points, $row['evidence'] ?? null, $uploaded);
        }

        foreach ($rowsA9 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $a9Sum += $points;
            $this->createEntry($section, $application, 'a9', $row, $points, $row['evidence'] ?? null, $uploaded);
        }

        foreach ($rowsB as $row) {
            $points = (float) ($row['points'] ?? 0);
            $bSum += $points;
            $this->createEntry($section, $application, 'b', $row, $points, $row['evidence'] ?? null, $uploaded);
        }

        foreach ($rowsC as $row) {
            $points = (float) ($row['points'] ?? 0);
            $cSum += $points;
            $this->createEntry($section, $application, 'c', $row, $points, $row['evidence'] ?? null, $uploaded);
        }

        $bPrev = (float) ($input['b_prev'] ?? 0);
        $cPrev = (float) ($input['c_prev'] ?? 0);

        if ($bPrev > 0) {
            $this->createEntry($section, $application, 'b_prev', ['value' => $bPrev], $bPrev / 3, null, $uploaded);
        }
        if ($cPrev > 0) {
            $this->createEntry($section, $application, 'c_prev', ['value' => $cPrev], $cPrev / 3, null, $uploaded);
        }

        $aTotal = $aBase + min($a8Sum, 15) + min($a9Sum, 10);
        $bTotal = min($bSum + ($bPrev / 3), 20);
        $cTotal = min($cSum + ($cPrev / 3), 20);

        $sectionTotal = min($aTotal + $bTotal + $cTotal, 140);

        $section->update([
            'points_total' => $sectionTotal,
            'is_complete' => $action === 'submit',
        ]);
    }

    private function saveSectionThree(Request $request, ReclassificationApplication $application, ReclassificationSection $section): void
    {
        $request->validate([
            'section3' => ['array'],
            'section3.evidence_files.*' => ['file', 'max:20480'],
        ]);

        $section->entries()->delete();
        $this->detachSectionEvidence($section);

        $uploaded = $this->storeEvidenceFiles($request, $application, $section, 3, $request->user()->id, 'section3.evidence_files');
        $input = $request->input('section3', []);
        $action = $request->input('action', 'draft');

        $rowsC1 = $this->normalizeRows($input['c1'] ?? []);
        $rowsC2 = $this->normalizeRows($input['c2'] ?? []);
        $rowsC3 = $this->normalizeRows($input['c3'] ?? []);
        $rowsC4 = $this->normalizeRows($input['c4'] ?? []);
        $rowsC5 = $this->normalizeRows($input['c5'] ?? []);
        $rowsC6 = $this->normalizeRows($input['c6'] ?? []);
        $rowsC7 = $this->normalizeRows($input['c7'] ?? []);
        $rowsC8 = $this->normalizeRows($input['c8'] ?? []);
        $rowsC9 = $this->normalizeRows($input['c9'] ?? []);

        if ($action === 'submit') {
            $this->ensureEvidence($rowsC1, 'section3.c1', $uploaded, $section, $application);
            $this->ensureEvidence($rowsC2, 'section3.c2', $uploaded, $section, $application);
            $this->ensureEvidence($rowsC3, 'section3.c3', $uploaded, $section, $application);
            $this->ensureEvidence($rowsC4, 'section3.c4', $uploaded, $section, $application);
            $this->ensureEvidence($rowsC5, 'section3.c5', $uploaded, $section, $application);
            $this->ensureEvidence($rowsC6, 'section3.c6', $uploaded, $section, $application);
            $this->ensureEvidence($rowsC7, 'section3.c7', $uploaded, $section, $application);
            $this->ensureEvidence($rowsC8, 'section3.c8', $uploaded, $section, $application);
            $this->ensureEvidence($rowsC9, 'section3.c9', $uploaded, $section, $application);
        }

        $rowsC1 = $this->bucketOnce($rowsC1, fn ($r) => ($r['authorship'] ?? '') . '|' . ($r['edition'] ?? '') . '|' . ($r['publisher'] ?? ''), fn ($r) => $this->pointsBook($r));
        $rowsC2 = $this->bucketOnce($rowsC2, fn ($r) => ($r['authorship'] ?? '') . '|' . ($r['edition'] ?? '') . '|' . ($r['publisher'] ?? ''), fn ($r) => $this->pointsWorkbook($r));
        $rowsC3 = $this->bucketOnce($rowsC3, fn ($r) => ($r['authorship'] ?? '') . '|' . ($r['edition'] ?? '') . '|' . ($r['publisher'] ?? ''), fn ($r) => $this->pointsCompilation($r));
        $rowsC4 = $this->bucketOnce($rowsC4, fn ($r) => ($r['kind'] ?? '') . '|' . ($r['authorship'] ?? '') . '|' . ($r['scope'] ?? ''), fn ($r) => $this->pointsArticle($r));
        $rowsC5 = $this->bucketOnce($rowsC5, fn ($r) => $r['level'] ?? '', fn ($r) => $this->pointsConference($r));
        $rowsC6 = $this->bucketOnce($rowsC6, fn ($r) => ($r['role'] ?? '') . '|' . ($r['level'] ?? ''), fn ($r) => $this->pointsCompleted($r));
        $rowsC7 = $this->bucketOnce($rowsC7, fn ($r) => ($r['role'] ?? '') . '|' . ($r['level'] ?? ''), fn ($r) => $this->pointsProposal($r));
        $rowsC9 = $this->bucketOnce($rowsC9, fn ($r) => $r['service'] ?? '', fn ($r) => $this->pointsEditorial($r));

        $sum = 0;

        foreach ($rowsC1 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $sum += $points;
            $this->createEntry($section, $application, 'c1', $row, $points, $row['evidence'] ?? null, $uploaded);
        }
        foreach ($rowsC2 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $sum += $points;
            $this->createEntry($section, $application, 'c2', $row, $points, $row['evidence'] ?? null, $uploaded);
        }
        foreach ($rowsC3 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $sum += $points;
            $this->createEntry($section, $application, 'c3', $row, $points, $row['evidence'] ?? null, $uploaded);
        }
        foreach ($rowsC4 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $sum += $points;
            $this->createEntry($section, $application, 'c4', $row, $points, $row['evidence'] ?? null, $uploaded);
        }
        foreach ($rowsC5 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $sum += $points;
            $this->createEntry($section, $application, 'c5', $row, $points, $row['evidence'] ?? null, $uploaded);
        }
        foreach ($rowsC6 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $sum += $points;
            $this->createEntry($section, $application, 'c6', $row, $points, $row['evidence'] ?? null, $uploaded);
        }
        foreach ($rowsC7 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $sum += $points;
            $this->createEntry($section, $application, 'c7', $row, $points, $row['evidence'] ?? null, $uploaded);
        }
        foreach ($rowsC8 as $row) {
            $points = 5;
            $sum += $points;
            $this->createEntry($section, $application, 'c8', $row, $points, $row['evidence'] ?? null, $uploaded);
        }
        foreach ($rowsC9 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $sum += $points;
            $this->createEntry($section, $application, 'c9', $row, $points, $row['evidence'] ?? null, $uploaded);
        }

        $prev = (float) ($input['previous_points'] ?? 0);
        if ($prev > 0) {
            $this->createEntry($section, $application, 'previous_points', ['value' => $prev], $prev / 3, null, $uploaded);
        }

        $total = $sum + ($prev / 3);
        if ($total > 70) $total = 70;

        $section->update([
            'points_total' => $total,
            'is_complete' => $action === 'submit',
        ]);
    }

    private function saveSectionFour(Request $request, ReclassificationApplication $application, ReclassificationSection $section): void
    {
        $request->validate([
            'section4' => ['array'],
            'section4.evidence_files.*' => ['file', 'max:20480'],
        ]);

        $section->entries()->delete();
        $this->detachSectionEvidence($section);

        $uploaded = $this->storeEvidenceFiles($request, $application, $section, 4, $request->user()->id, 'section4.evidence_files');

        $input = $request->input('section4', []);
        $action = $request->input('action', 'draft');

        $a1Years = (float) ($input['a']['a1_years'] ?? 0);
        $a2Years = (float) ($input['a']['a2_years'] ?? 0);
        $bYears = (float) ($input['b']['years'] ?? 0);

        $a1Evidence = $input['a']['a1_evidence'] ?? [];
        $a2Evidence = $input['a']['a2_evidence'] ?? [];
        $bEvidence = $input['b']['evidence'] ?? [];

        $a1Rows = [['evidence' => $a1Evidence]];
        $a2Rows = [['evidence' => $a2Evidence]];
        $bRows = [['evidence' => $bEvidence]];

        if ($action === 'submit') {
            if ($a1Years > 0) $this->ensureEvidence($a1Rows, 'section4.a.a1', $uploaded, $section, $application);
            if ($a2Years > 0) $this->ensureEvidence($a2Rows, 'section4.a.a2', $uploaded, $section, $application);
        }

        $bUnlocked = $a1Years >= 5 || $a2Years >= 3;
        if ($bYears > 0 && $bUnlocked && $action === 'submit') {
            $this->ensureEvidence($bRows, 'section4.b', $uploaded, $section, $application);
        }

        $a1Raw = $a1Years * 2;
        $a2Raw = $a2Years * 3;
        $a1Capped = min($a1Raw, 20);
        $a2Capped = min($a2Raw, 40);
        $aTotal = min($a1Capped + $a2Capped, 40);

        $bRaw = $bYears * 2;
        $bCapped = min($bRaw, 20);

        $isPartTime = $request->user()->facultyProfile?->employment_type === 'part_time';
        if ($isPartTime) {
            $a1Capped = $a1Capped / 2;
            $a2Capped = $a2Capped / 2;
            $aTotal = $aTotal / 2;
            $bCapped = $bCapped / 2;
        }

        $final = max($aTotal, $bCapped);
        if ($final > 40) $final = 40;

        $this->createEntry($section, $application, 'a1', [
            'years' => $a1Years,
            'evidence' => $a1Evidence,
        ], $a1Capped, $a1Evidence, $uploaded);

        $this->createEntry($section, $application, 'a2', [
            'years' => $a2Years,
            'evidence' => $a2Evidence,
        ], $a2Capped, $a2Evidence, $uploaded);

        $this->createEntry($section, $application, 'b', [
            'years' => $bYears,
            'evidence' => $bEvidence,
        ], $bUnlocked ? $bCapped : 0, $bEvidence, $uploaded);

        $section->update([
            'points_total' => $final,
            'is_complete' => $action === 'submit',
        ]);
    }

    private function saveSectionFive(Request $request, ReclassificationApplication $application, ReclassificationSection $section): void
    {
        $request->validate([
            'section5' => ['array'],
            'section5.evidence_files.*' => ['file', 'max:20480'],
        ]);

        $section->entries()->delete();
        $this->detachSectionEvidence($section);

        $uploaded = $this->storeEvidenceFiles($request, $application, $section, 5, $request->user()->id, 'section5.evidence_files');
        $input = $request->input('section5', []);
        $action = $request->input('action', 'draft');

        $rowsA = $this->normalizeRows($input['a'] ?? []);
        $rowsB = $this->normalizeRows($input['b'] ?? []);
        $rowsC1 = $this->normalizeRows($input['c1'] ?? []);
        $rowsC2 = $this->normalizeRows($input['c2'] ?? []);
        $rowsC3 = $this->normalizeRows($input['c3'] ?? []);
        $rowsD = $this->normalizeRows($input['d'] ?? []);

        if ($action === 'submit') {
            $this->ensureEvidence($rowsA, 'section5.a', $uploaded, $section, $application);
            $this->ensureEvidence($rowsB, 'section5.b', $uploaded, $section, $application);
            $this->ensureEvidence($rowsC1, 'section5.c1', $uploaded, $section, $application);
            $this->ensureEvidence($rowsC2, 'section5.c2', $uploaded, $section, $application);
            $this->ensureEvidence($rowsC3, 'section5.c3', $uploaded, $section, $application);
            $this->ensureEvidence($rowsD, 'section5.d', $uploaded, $section, $application);
        }

        $rowsA = $this->bucketOnce($rowsA, fn ($r) => ($r['kind'] ?? '') . '|' . (($r['kind'] ?? '') === 'scholarship' ? ($r['grant'] ?? '') : ($r['level'] ?? '')), fn ($r) => $this->pointsS5A($r));
        $rowsB = $this->bucketOnce($rowsB, fn ($r) => ($r['role'] ?? '') . '|' . ($r['level'] ?? ''), fn ($r) => $this->pointsS5B($r));
        $rowsC1 = $this->bucketOnce($rowsC1, fn ($r) => $r['role'] ?? '', fn ($r) => $this->pointsS5C1($r));
        $rowsC2 = $this->bucketOnce($rowsC2, fn ($r) => $r['type'] ?? '', fn ($r) => $this->pointsS5C2($r));
        $rowsC3 = $this->bucketOnce($rowsC3, fn ($r) => $r['role'] ?? '', fn ($r) => $this->pointsS5C3($r));
        $rowsD = $this->bucketOnce($rowsD, fn ($r) => $r['role'] ?? '', fn ($r) => $this->pointsS5D($r));

        $sumA = 0;
        foreach ($rowsA as $row) {
            $points = (float) ($row['points'] ?? 0);
            $sumA += $points;
            $this->createEntry($section, $application, 'a', $row, $points, $row['evidence'] ?? null, $uploaded);
        }

        $sumB = 0;
        foreach ($rowsB as $row) {
            $points = (float) ($row['points'] ?? 0);
            $sumB += $points;
            $this->createEntry($section, $application, 'b', $row, $points, $row['evidence'] ?? null, $uploaded);
        }

        $sumC1 = 0;
        foreach ($rowsC1 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $sumC1 += $points;
            $this->createEntry($section, $application, 'c1', $row, $points, $row['evidence'] ?? null, $uploaded);
        }

        $sumC2 = 0;
        foreach ($rowsC2 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $sumC2 += $points;
            $this->createEntry($section, $application, 'c2', $row, $points, $row['evidence'] ?? null, $uploaded);
        }

        $sumC3 = 0;
        foreach ($rowsC3 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $sumC3 += $points;
            $this->createEntry($section, $application, 'c3', $row, $points, $row['evidence'] ?? null, $uploaded);
        }

        $sumD = 0;
        foreach ($rowsD as $row) {
            $points = (float) ($row['points'] ?? 0);
            $sumD += $points;
            $this->createEntry($section, $application, 'd', $row, $points, $row['evidence'] ?? null, $uploaded);
        }

        $bPrev = (float) ($input['b_prev'] ?? 0);
        $cPrev = (float) ($input['c_prev'] ?? 0);
        $dPrev = (float) ($input['d_prev'] ?? 0);
        $prev = (float) ($input['previous_points'] ?? 0);

        if ($bPrev > 0) {
            $this->createEntry($section, $application, 'b_prev', ['value' => $bPrev], $bPrev / 3, null, $uploaded);
        }
        if ($cPrev > 0) {
            $this->createEntry($section, $application, 'c_prev', ['value' => $cPrev], $cPrev / 3, null, $uploaded);
        }
        if ($dPrev > 0) {
            $this->createEntry($section, $application, 'd_prev', ['value' => $dPrev], $dPrev / 3, null, $uploaded);
        }
        if ($prev > 0) {
            $this->createEntry($section, $application, 'previous_points', ['value' => $prev], $prev / 3, null, $uploaded);
        }

        $sumA = min($sumA, 5);
        $sumB = min($sumB + ($bPrev / 3), 10);
        $sumC1 = min($sumC1, 10);
        $sumC2 = min($sumC2, 5);
        $sumC3 = min($sumC3, 10);
        $sumC = min($sumC1 + $sumC2 + $sumC3 + ($cPrev / 3), 15);
        $sumD = min($sumD + ($dPrev / 3), 10);

        $total = $sumA + $sumB + $sumC + $sumD + ($prev / 3);
        if ($total > 30) $total = 30;

        $section->update([
            'points_total' => $total,
            'is_complete' => $action === 'submit',
        ]);
    }

    private function storeEvidenceFiles(
        Request $request,
        ReclassificationApplication $application,
        ReclassificationSection $section,
        int $sectionNumber,
        int $userId,
        string $inputKey
    ): array
    {
        $files = $request->file($inputKey, []);
        if ($files && !is_array($files)) {
            $files = [$files];
        }
        $uploaded = [];

        foreach ($files as $index => $file) {
            if (!$file) continue;

            $path = $file->store("reclassification/{$application->id}/section{$sectionNumber}", 'public');

            $uploaded[$index] = ReclassificationEvidence::create([
                'reclassification_application_id' => $application->id,
                'reclassification_section_id' => $section->id,
                'reclassification_section_entry_id' => null,
                'uploaded_by_user_id' => $userId,
                'disk' => 'public',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
                'label' => "Section {$sectionNumber} upload",
                'status' => 'pending',
            ]);
        }

        return $uploaded;
    }

    private function storeGlobalEvidenceFiles(
        Request $request,
        ReclassificationApplication $application,
        int $userId,
        string $inputKey
    ): array {
        $files = $request->file($inputKey, []);
        if ($files && !is_array($files)) {
            $files = [$files];
        }
        $uploaded = [];

        foreach ($files as $index => $file) {
            if (!$file) continue;

            $path = $file->store("reclassification/{$application->id}/global", 'public');

            $uploaded[$index] = ReclassificationEvidence::create([
                'reclassification_application_id' => $application->id,
                'reclassification_section_id' => null,
                'reclassification_section_entry_id' => null,
                'uploaded_by_user_id' => $userId,
                'disk' => 'public',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
                'label' => 'Global upload',
                'status' => 'pending',
            ]);
        }

        return $uploaded;
    }

    private function createEntry(
        ReclassificationSection $section,
        ReclassificationApplication $application,
        string $key,
        array $row,
        float $points,
        $evidenceIndex,
        array $uploaded
    ): void {
        $row['points'] = $points;

        $entry = ReclassificationSectionEntry::create([
            'reclassification_section_id' => $section->id,
            'criterion_key' => $key,
            'title' => $row['title'] ?? $row['text'] ?? null,
            'description' => null,
            'evidence_note' => null,
            'points' => $points,
            'is_validated' => false,
            'data' => $row,
        ]);

        $values = $this->parseEvidenceValues($evidenceIndex);
        if (count($values) === 0) {
            return;
        }

        foreach ($values as $value) {
            if (str_starts_with($value, 'n:')) {
                $index = (int) substr($value, 2);
                if (!isset($uploaded[$index])) continue;
                $this->attachExistingEvidence($uploaded[$index]->id, $entry, $section, $application, $key);
                continue;
            }

            if (str_starts_with($value, 'e:')) {
                $id = (int) substr($value, 2);
                $this->attachExistingEvidence($id, $entry, $section, $application, $key);
                continue;
            }

            if (is_numeric($value)) {
                $index = (int) $value;
                if (!isset($uploaded[$index])) continue;
                $this->attachExistingEvidence($uploaded[$index]->id, $entry, $section, $application, $key);
            }
        }
    }

    private function parseEvidenceValues($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $values = is_array($value) ? $value : [$value];
        $out = [];

        foreach ($values as $val) {
            $val = trim((string) $val);
            if ($val === '') {
                continue;
            }
            $out[] = $val;
        }

        return array_values(array_unique($out));
    }

    private function attachExistingEvidence(
        int $evidenceId,
        ReclassificationSectionEntry $entry,
        ReclassificationSection $section,
        ReclassificationApplication $application,
        string $label
    ): void {
        $evidence = ReclassificationEvidence::where('id', $evidenceId)
            ->where('reclassification_application_id', $application->id)
            ->first();

        if (!$evidence) {
            return;
        }

        DB::table('reclassification_evidence_links')->updateOrInsert(
            [
                'reclassification_evidence_id' => $evidence->id,
                'reclassification_section_entry_id' => $entry->id,
            ],
            [
                'reclassification_section_id' => $section->id,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        if (!$evidence->label) {
            $evidence->update(['label' => $label]);
        }
    }

    private function detachSectionEvidence(ReclassificationSection $section): void
    {
        $entryIds = $section->entries()->pluck('id')->all();
        if (!empty($entryIds)) {
            DB::table('reclassification_evidence_links')
                ->whereIn('reclassification_section_entry_id', $entryIds)
                ->delete();
        }

        ReclassificationEvidence::where('reclassification_section_id', $section->id)
            ->update([
                'reclassification_section_entry_id' => null,
                'reclassification_section_id' => null,
            ]);
    }

    private function normalizeRows($rows): array
    {
        if (!is_array($rows)) return [];

        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) continue;
            if (array_key_exists('evidence', $row)) {
                if (is_array($row['evidence'])) {
                    $row['evidence'] = array_values(array_filter(array_map('strval', $row['evidence']), fn ($v) => $v !== ''));
                } else {
                    $raw = trim((string) $row['evidence']);
                    if ($raw === '') {
                        $row['evidence'] = [];
                    } elseif (str_contains($raw, ',')) {
                        $row['evidence'] = array_values(array_filter(array_map('trim', explode(',', $raw)), fn ($v) => $v !== ''));
                    } else {
                        $row['evidence'] = [$raw];
                    }
                }
            }
            if ($this->isRowEmpty($row)) continue;
            $out[] = $row;
        }

        return $out;
    }

    private function ensureEvidence(
        array $rows,
        string $fieldBase,
        array $uploaded,
        ReclassificationSection $section,
        ReclassificationApplication $application
    ): void
    {
        $existingIds = ReclassificationEvidence::where('reclassification_application_id', $application->id)
            ->pluck('id')
            ->map(fn ($id) => 'e:' . $id)
            ->all();

        $existingSet = array_flip($existingIds);

        foreach ($rows as $index => $row) {
            $hasEvidence = false;
            if (array_key_exists('evidence', $row)) {
                $values = $this->parseEvidenceValues($row['evidence']);
                $hasEvidence = count($values) > 0;
            }
            if (!$hasEvidence) {
                throw ValidationException::withMessages([
                    "{$fieldBase}.{$index}.evidence" => 'Evidence is required for each filled entry.',
                ]);
            }

            $values = $this->parseEvidenceValues($row['evidence']);
            foreach ($values as $value) {
                if (str_starts_with($value, 'e:')) {
                    if (!isset($existingSet[$value])) {
                        throw ValidationException::withMessages([
                            "{$fieldBase}.{$index}.evidence" => 'Selected evidence file is missing. Please re-upload your evidence files.',
                        ]);
                    }
                    continue;
                }

                if (str_starts_with($value, 'n:')) {
                    $single = (int) substr($value, 2);
                    if (!isset($uploaded[$single])) {
                        throw ValidationException::withMessages([
                            "{$fieldBase}.{$index}.evidence" => 'Selected evidence file is missing. Please re-upload your evidence files.',
                        ]);
                    }
                    continue;
                }

                if (is_numeric($value)) {
                    $single = (int) $value;
                    if (!isset($uploaded[$single])) {
                        throw ValidationException::withMessages([
                            "{$fieldBase}.{$index}.evidence" => 'Selected evidence file is missing. Please re-upload your evidence files.',
                        ]);
                    }
                    continue;
                }

                throw ValidationException::withMessages([
                    "{$fieldBase}.{$index}.evidence" => 'Selected evidence file is invalid.',
                ]);
            }
        }
    }

    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (is_string($value) && trim($value) !== '') return false;
            if (is_numeric($value) && (float) $value !== 0.0) return false;
            if (is_array($value) && !empty($value)) return false;
            if (is_bool($value) && $value === true) return false;
        }

        return true;
    }

    private function pointsA1(string $honors): float
    {
        if ($honors === 'summa') return 3;
        if ($honors === 'magna') return 2;
        if ($honors === 'cum') return 1;
        return 0;
    }

    private function pointsA(string $key, array $row): float
    {
        $cat = $row['category'] ?? '';
        $thesis = $row['thesis'] ?? '';
        $rel = $row['relation'] ?? '';
        $lvl = $row['level'] ?? '';
        $units = $row['units'] ?? null;
        if ($units === null || $units === '') {
            if (isset($row['blocks']) && $row['blocks'] !== '') {
                $units = ((float) $row['blocks']) * 9;
            } else {
                $units = 0;
            }
        }
        $hasNineUnits = ((float) $units) >= 9;

        if ($key === 'a2') {
            if ($cat === 'teaching') return 10;
            if ($cat === 'not_teaching') return 5;
            return 0;
        }

        if ($key === 'a3') {
            $opt = (string) ($row['option'] ?? '');
            if ($opt === 'teaching_with_thesis') return 100;
            if ($opt === 'teaching_without_thesis') return 90;
            if ($opt === 'not_teaching_with_thesis') return 80;
            if ($opt === 'not_teaching_without_thesis') return 70;

            if ($cat === 'teaching' && $thesis === 'with') return 100;
            if ($cat === 'teaching' && $thesis === 'without') return 90;
            if ($cat === 'not_teaching' && $thesis === 'with') return 80;
            if ($cat === 'not_teaching' && $thesis === 'without') return 70;
            return 0;
        }

        if ($key === 'a4') {
            if ($cat === 'specialization') return $hasNineUnits ? 4 : 0;
            if ($cat === 'other') return $hasNineUnits ? 3 : 0;
            return 0;
        }

        if ($key === 'a5') {
            if ($cat === 'teaching') return 15;
            if ($cat === 'not_teaching') return 10;
            return 0;
        }

        if ($key === 'a6') {
            if ($cat === 'specialization') return $hasNineUnits ? 5 : 0;
            if ($cat === 'other') return $hasNineUnits ? 4 : 0;
            return 0;
        }

        if ($key === 'a7') {
            if ($cat === 'teaching') return 140;
            if ($cat === 'not_teaching') return 120;
            return 0;
        }

        if ($key === 'a8') {
            if ($rel === 'direct') return 10;
            if ($rel === 'not_direct') return 5;
            return 0;
        }

        if ($key === 'a9') {
            if ($lvl === 'international') return 5;
            if ($lvl === 'national') return 3;
            return 0;
        }

        return 0;
    }

    private function pointsB(array $row): float
    {
        $h = (string) ($row['hours'] ?? '');
        if ($h === '120') return 15;
        if ($h === '80') return 10;
        if ($h === '50') return 6;
        if ($h === '20') return 4;
        return 0;
    }

    private function pointsC(array $row): float
    {
        $role = trim((string) ($row['role'] ?? ''));
        $level = trim((string) ($row['level'] ?? ''));

        $minMap = [
            'speaker' => [
                'international' => 13,
                'national' => 11,
                'regional' => 9,
                'provincial' => 7,
                'municipal' => 4,
                'school' => 1,
            ],
            'resource' => [
                'international' => 11,
                'national' => 9,
                'regional' => 7,
                'provincial' => 5,
                'municipal' => 3,
                'school' => 1,
            ],
            'participant' => [
                'international' => 9,
                'national' => 7,
                'regional' => 5,
                'provincial' => 3,
                'municipal' => 2,
                'school' => 1,
            ],
        ];

        return (float) ($minMap[$role][$level] ?? 0);
    }

    private function bucketOnce(array $rows, callable $keyFn, callable $pointsFn): array
    {
        $seen = [];
        return array_map(function ($row) use ($keyFn, $pointsFn, &$seen) {
            $key = (string) $keyFn($row);
            $points = (float) $pointsFn($row);
            if ($key === '' || $points <= 0) {
                $row['points'] = 0;
                $row['counted'] = false;
                return $row;
            }
            if (isset($seen[$key])) {
                $row['points'] = 0;
                $row['counted'] = false;
                return $row;
            }
            $seen[$key] = true;
            $row['points'] = $points;
            $row['counted'] = true;
            return $row;
        }, $rows);
    }

    private function pointsBook(array $row): float
    {
        $a = $row['authorship'] ?? '';
        $ed = $row['edition'] ?? '';
        $pub = $row['publisher'] ?? '';
        if (!$a || !$ed || !$pub) return 0;

        $map = [
            'sole' => [
                'new' => ['registered' => 20, 'printed_approved' => 18],
                'revised' => ['registered' => 16, 'printed_approved' => 14],
            ],
            'co' => [
                'new' => ['registered' => 14, 'printed_approved' => 12],
                'revised' => ['registered' => 10, 'printed_approved' => 8],
            ],
        ];

        return (float) ($map[$a][$ed][$pub] ?? 0);
    }

    private function pointsWorkbook(array $row): float
    {
        $a = $row['authorship'] ?? '';
        $ed = $row['edition'] ?? '';
        $pub = $row['publisher'] ?? '';
        if (!$a || !$ed || !$pub) return 0;

        $map = [
            'sole' => [
                'new' => ['registered' => 15, 'printed_approved' => 13],
                'revised' => ['registered' => 11, 'printed_approved' => 9],
            ],
            'co' => [
                'new' => ['registered' => 9, 'printed_approved' => 8],
                'revised' => ['registered' => 7, 'printed_approved' => 6],
            ],
        ];

        return (float) ($map[$a][$ed][$pub] ?? 0);
    }

    private function pointsCompilation(array $row): float
    {
        $a = $row['authorship'] ?? '';
        $ed = $row['edition'] ?? '';
        $pub = $row['publisher'] ?? '';
        if (!$a || !$ed || !$pub) return 0;

        $map = [
            'sole' => [
                'new' => ['registered' => 12, 'printed_approved' => 11],
                'revised' => ['registered' => 10, 'printed_approved' => 9],
            ],
            'co' => [
                'new' => ['registered' => 8, 'printed_approved' => 7],
                'revised' => ['registered' => 6, 'printed_approved' => 5],
            ],
        ];

        return (float) ($map[$a][$ed][$pub] ?? 0);
    }

    private function pointsArticle(array $row): float
    {
        $kind = $row['kind'] ?? '';
        $scope = $row['scope'] ?? '';
        if (!$kind || !$scope) return 0;

        if ($kind === 'otherpub') {
            $other = [
                'national_periodicals' => 5,
                'local_periodicals' => 4,
                'university_newsletters' => 3,
            ];
            return (float) ($other[$scope] ?? 0);
        }

        $auth = $row['authorship'] ?? '';
        if (!$auth) return 0;

        $key = "{$kind}_{$auth}_{$scope}";
        $map = [
            'refereed_sole_international' => 40,
            'refereed_co_international' => 36,
            'refereed_sole_national' => 38,
            'refereed_co_national' => 34,
            'refereed_sole_university' => 36,
            'refereed_co_university' => 32,
            'nonrefereed_sole_international' => 30,
            'nonrefereed_co_international' => 24,
            'nonrefereed_sole_national' => 28,
            'nonrefereed_co_national' => 22,
            'nonrefereed_sole_university' => 20,
            'nonrefereed_co_university' => 20,
        ];

        return (float) ($map[$key] ?? 0);
    }

    private function pointsConference(array $row): float
    {
        $map = [
            'international' => 15,
            'national' => 13,
            'regional' => 11,
            'institutional' => 9,
        ];
        return (float) ($map[$row['level'] ?? ''] ?? 0);
    }

    private function pointsCompleted(array $row): float
    {
        $principal = ['international' => 20, 'national' => 18, 'regional' => 16, 'institutional' => 14];
        $team = ['international' => 15, 'national' => 13, 'regional' => 11, 'institutional' => 9];
        $role = $row['role'] ?? '';
        $level = $row['level'] ?? '';
        $map = $role === 'team' ? $team : $principal;
        return (float) ($map[$level] ?? 0);
    }

    private function pointsProposal(array $row): float
    {
        $principal = ['international' => 15, 'national' => 13, 'regional' => 11, 'institutional' => 9];
        $team = ['international' => 11, 'national' => 9, 'regional' => 7, 'institutional' => 5];
        $role = $row['role'] ?? '';
        $level = $row['level'] ?? '';
        $map = $role === 'team' ? $team : $principal;
        return (float) ($map[$level] ?? 0);
    }

    private function pointsEditorial(array $row): float
    {
        $map = ['chief' => 15, 'editor' => 10, 'consultant' => 5];
        return (float) ($map[$row['service'] ?? ''] ?? 0);
    }

    private function pointsS5A(array $row): float
    {
        $kind = $row['kind'] ?? '';
        if (!$kind) return 0;

        if ($kind !== 'scholarship') {
            $lvl = $row['level'] ?? '';
            $map = ['international' => 5, 'national' => 4, 'regional' => 3, 'local' => 2, 'school' => 1];
            return (float) ($map[$lvl] ?? 0);
        }

        $grant = $row['grant'] ?? '';
        $map = ['full' => 5, 'partial_4' => 4, 'partial_3' => 3, 'travel_2' => 2, 'travel_1' => 1];
        return (float) ($map[$grant] ?? 0);
    }

    private function pointsS5B(array $row): float
    {
        $role = $row['role'] ?? '';
        $level = $row['level'] ?? '';
        if (!$role || !$level) return 0;

        $officer = ['international' => 10, 'national' => 8, 'regional' => 6, 'local' => 4, 'school' => 2];
        $chairman = ['international' => 5, 'national' => 4, 'regional' => 3, 'local' => 2, 'school' => 1];
        $committee = ['international' => 4, 'national' => 3, 'regional' => 2, 'local' => 1.5, 'school' => 1];
        $member = ['international' => 3, 'national' => 2.5, 'regional' => 2, 'local' => 1, 'school' => 0.5];

        $mapByRole = [
            'officer' => $officer,
            'chairman' => $chairman,
            'member_committee' => $committee,
            'member' => $member,
        ];

        return (float) ($mapByRole[$role][$level] ?? 0);
    }

    private function pointsS5C1(array $row): float
    {
        $role = $row['role'] ?? '';
        if (!$role) return 0;
        $per = ['overall' => 7, 'chairman' => 5, 'member' => 2];
        return $per[$role] ?? 0;
    }

    private function pointsS5C2(array $row): float
    {
        $type = $row['type'] ?? '';
        if (!$type) return 0;
        $per = ['campus' => 5, 'department' => 3, 'class' => 1];
        return $per[$type] ?? 0;
    }

    private function pointsS5C3(array $row): float
    {
        $role = $row['role'] ?? '';
        if (!$role) return 0;
        $per = ['overall' => 5, 'chairman' => 3, 'member' => 1];
        return $per[$role] ?? 0;
    }

    private function pointsS5D(array $row): float
    {
        $role = $row['role'] ?? '';
        if (!$role) return 0;
        $per = ['chairman' => 5, 'coordinator' => 3, 'participant' => 1];
        return $per[$role] ?? 0;
    }
}

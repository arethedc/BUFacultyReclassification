<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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

        return $app->load('sections');
    }

    public function show(Request $request)
    {
        $application = $this->getOrCreateDraft($request);

        $active = (int) $request->route('number', 1);
        if ($active < 1 || $active > 5) $active = 1;

        $section = $application->sections
            ->firstWhere('section_code', (string) $active);

        $sectionData = ($section && $section->section_code === '1')
            ? $this->buildSectionData($section)
            : [];

        return view('reclassification.show', compact('application', 'section', 'sectionData'));
    }

    public function section(Request $request, int $number)
    {
        abort_unless($number >= 1 && $number <= 5, 404);

        $application = $this->getOrCreateDraft($request);

        $section = $application->sections
            ->firstWhere('section_code', (string) $number);

        abort_unless($section, 404);

        $sectionData = ($section->section_code === '1')
            ? $this->buildSectionData($section)
            : [];

        return view('reclassification.show', compact('application', 'section', 'sectionData'));
    }

    public function review(Request $request)
    {
        $application = $this->getOrCreateDraft($request);

        return view('reclassification.review', compact('application'));
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

        return view('reclassification.submitted-summary', compact('application'));
    }

    public function saveSection(Request $request, int $number)
    {
        abort_unless($number >= 1 && $number <= 5, 404);

        $application = $this->getOrCreateDraft($request);

        abort_unless(in_array($application->status, ['draft', 'returned_to_faculty'], true), 403);

        $section = $application->sections
            ->firstWhere('section_code', (string) $number);

        abort_unless($section, 404);

        if ($number !== 1) {
            return back()->with('error', 'Saving for this section is not implemented yet.');
        }

        DB::transaction(function () use ($request, $application, $section) {
            $this->saveSectionOne($request, $application, $section);
        });

        return back()->with('success', 'Section I saved.');
    }

    private function buildSectionData(ReclassificationSection $section): array
    {
        $section->loadMissing(['entries', 'evidences']);

        $data = [
            'a1' => ['honors' => null],
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
            'existingEvidence' => [],
        ];

        foreach ($section->entries as $entry) {
            $row = is_array($entry->data) ? $entry->data : [];
            $row['id'] = $entry->id;

            if ($entry->criterion_key === 'a1') {
                $data['a1']['honors'] = $row['honors'] ?? null;
                continue;
            }

            if (array_key_exists($entry->criterion_key, $data)) {
                $data[$entry->criterion_key][] = $row;
            }
        }

        foreach ($section->evidences as $evidence) {
            $data['existingEvidence'][] = [
                'id' => $evidence->id,
                'name' => $evidence->original_name ?: $evidence->path,
                'status' => $evidence->status ?? 'pending',
            ];
        }

        return $data;
    }

    private function saveSectionOne(Request $request, ReclassificationApplication $application, ReclassificationSection $section): void
    {
        $request->validate([
            'section1' => ['array'],
            'section1.evidence_files.*' => ['file', 'max:20480'],
        ]);

        $section->entries()->delete();
        ReclassificationEvidence::where('reclassification_section_id', $section->id)->delete();

        $uploaded = $this->storeEvidenceFiles($request, $application, $section, 1, $request->user()->id);

        $input = $request->input('section1', []);
        $action = $request->input('action', 'draft');

        $aBase = 0;
        $a8Sum = 0;
        $a9Sum = 0;
        $bSum = 0;
        $cSum = 0;

        $a1Honors = data_get($input, 'a1.honors');
        if ($a1Honors) {
            $points = $this->pointsA1($a1Honors);
            $aBase += $points;
            $this->createEntry($section, 'a1', ['honors' => $a1Honors], $points, null, $uploaded);
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

        $this->ensureEvidence($rowsA2, 'section1.a2', $uploaded);
        $this->ensureEvidence($rowsA3, 'section1.a3', $uploaded);
        $this->ensureEvidence($rowsA4, 'section1.a4', $uploaded);
        $this->ensureEvidence($rowsA5, 'section1.a5', $uploaded);
        $this->ensureEvidence($rowsA6, 'section1.a6', $uploaded);
        $this->ensureEvidence($rowsA7, 'section1.a7', $uploaded);
        $this->ensureEvidence($rowsA8, 'section1.a8', $uploaded);
        $this->ensureEvidence($rowsA9, 'section1.a9', $uploaded);
        $this->ensureEvidence($rowsB, 'section1.b', $uploaded);
        $this->ensureEvidence($rowsC, 'section1.c', $uploaded);

        foreach ($rowsA2 as $row) {
            $points = $this->pointsA('a2', $row);
            $aBase += $points;
            $this->createEntry($section, 'a2', $row, $points, $row['evidence'] ?? null, $uploaded);
        }

        foreach ($rowsA3 as $row) {
            $points = $this->pointsA('a3', $row);
            $aBase += $points;
            $this->createEntry($section, 'a3', $row, $points, $row['evidence'] ?? null, $uploaded);
        }

        foreach ($rowsA4 as $row) {
            $points = $this->pointsA('a4', $row);
            $aBase += $points;
            $this->createEntry($section, 'a4', $row, $points, $row['evidence'] ?? null, $uploaded);
        }

        foreach ($rowsA5 as $row) {
            $points = $this->pointsA('a5', $row);
            $aBase += $points;
            $this->createEntry($section, 'a5', $row, $points, $row['evidence'] ?? null, $uploaded);
        }

        foreach ($rowsA6 as $row) {
            $points = $this->pointsA('a6', $row);
            $aBase += $points;
            $this->createEntry($section, 'a6', $row, $points, $row['evidence'] ?? null, $uploaded);
        }

        foreach ($rowsA7 as $row) {
            $points = $this->pointsA('a7', $row);
            $aBase += $points;
            $this->createEntry($section, 'a7', $row, $points, $row['evidence'] ?? null, $uploaded);
        }

        foreach ($rowsA8 as $row) {
            $points = min($this->pointsA('a8', $row), 5);
            $a8Sum += $points;
            $this->createEntry($section, 'a8', $row, $points, $row['evidence'] ?? null, $uploaded);
        }

        foreach ($rowsA9 as $row) {
            $points = $this->pointsA('a9', $row);
            $a9Sum += $points;
            $this->createEntry($section, 'a9', $row, $points, $row['evidence'] ?? null, $uploaded);
        }

        foreach ($rowsB as $row) {
            $points = $this->pointsB($row);
            $bSum += $points;
            $this->createEntry($section, 'b', $row, $points, $row['evidence'] ?? null, $uploaded);
        }

        foreach ($rowsC as $row) {
            $points = $this->pointsC($row);
            $cSum += $points;
            $this->createEntry($section, 'c', $row, $points, $row['evidence'] ?? null, $uploaded);
        }

        $aTotal = $aBase + min($a8Sum, 15) + min($a9Sum, 10);
        $bTotal = min($bSum, 20);
        $cTotal = min($cSum, 20);

        $sectionTotal = min($aTotal + $bTotal + $cTotal, 140);

        $section->update([
            'points_total' => $sectionTotal,
            'is_complete' => $action === 'submit',
        ]);
    }

    private function storeEvidenceFiles(
        Request $request,
        ReclassificationApplication $application,
        ReclassificationSection $section,
        int $sectionNumber,
        int $userId
    ): array
    {
        $files = $request->file('section1.evidence_files', []);
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

    private function createEntry(
        ReclassificationSection $section,
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

        $index = is_numeric($evidenceIndex) ? (int) $evidenceIndex : null;
        if ($index === null || !isset($uploaded[$index])) {
            return;
        }

        $evidence = $uploaded[$index];
        $evidence->update([
            'reclassification_section_entry_id' => $entry->id,
            'label' => 'Section I',
        ]);
    }

    private function normalizeRows($rows): array
    {
        if (!is_array($rows)) return [];

        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) continue;
            if ($this->isRowEmpty($row)) continue;
            $out[] = $row;
        }

        return $out;
    }

    private function ensureEvidence(array $rows, string $fieldBase, array $uploaded): void
    {
        foreach ($rows as $index => $row) {
            $hasEvidence = array_key_exists('evidence', $row) && $row['evidence'] !== '' && $row['evidence'] !== null;
            if (!$hasEvidence) {
                throw ValidationException::withMessages([
                    "{$fieldBase}.{$index}.evidence" => 'Evidence is required for each filled entry.',
                ]);
            }

            $evidenceIndex = $row['evidence'];
            if (is_numeric($evidenceIndex)) {
                $evidenceIndex = (int) $evidenceIndex;
                if (!isset($uploaded[$evidenceIndex])) {
                    throw ValidationException::withMessages([
                        "{$fieldBase}.{$index}.evidence" => 'Selected evidence file is missing. Please re-upload your evidence files.',
                    ]);
                }
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
        $blocks = (int) ($row['blocks'] ?? 0);

        if ($key === 'a2') {
            if ($cat === 'teaching') return 10;
            if ($cat === 'not_teaching') return 5;
            return 0;
        }

        if ($key === 'a3') {
            if ($cat === 'teaching' && $thesis === 'with') return 100;
            if ($cat === 'teaching' && $thesis === 'without') return 90;
            if ($cat === 'not_teaching' && $thesis === 'with') return 80;
            if ($cat === 'not_teaching' && $thesis === 'without') return 70;
            return 0;
        }

        if ($key === 'a4') {
            if ($cat === 'specialization') return $blocks * 4;
            if ($cat === 'other') return $blocks * 3;
            return 0;
        }

        if ($key === 'a5') {
            if ($cat === 'teaching') return 15;
            if ($cat === 'not_teaching') return 10;
            return 0;
        }

        if ($key === 'a6') {
            if ($cat === 'specialization') return $blocks * 5;
            if ($cat === 'other') return $blocks * 4;
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
}

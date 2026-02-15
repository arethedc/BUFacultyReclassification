<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\ReclassificationApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ReclassificationAdminController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $status = $request->get('status', 'all');
        $departmentId = $request->get('department_id');
        $cycleYear = $request->get('cycle_year');
        $rankLevelId = $request->get('rank_level_id');

        $query = ReclassificationApplication::query()
            ->with([
                'faculty.department',
                'faculty.facultyProfile' . (Schema::hasTable('rank_levels') ? '.rankLevel' : ''),
            ]);

        if ($status === 'submitted') {
            $query->whereIn('status', [
                'dean_review',
                'hr_review',
                'vpaa_review',
                'president_review',
                'finalized',
            ]);
        } elseif ($status === 'all') {
            $query->where('status', '!=', 'draft');
        } else {
            $query->where('status', $status);
        }

        if (!empty($departmentId)) {
            $query->whereHas('faculty', function ($builder) use ($departmentId) {
                $builder->where('department_id', $departmentId);
            });
        }

        if (!empty($cycleYear)) {
            $query->where('cycle_year', $cycleYear);
        }

        if (!empty($rankLevelId)) {
            $query->whereHas('faculty.facultyProfile', function ($builder) use ($rankLevelId) {
                $builder->where('rank_level_id', $rankLevelId);
            });
        }

        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $builder->whereHas('faculty', function ($faculty) use ($q) {
                    $faculty->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                })->orWhereHas('faculty.facultyProfile', function ($profile) use ($q) {
                    $profile->where('employee_no', 'like', "%{$q}%");
                });
            });
        }

        $applications = $query
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->appends([
                'q' => $q,
                'status' => $status,
                'department_id' => $departmentId,
                'cycle_year' => $cycleYear,
                'rank_level_id' => $rankLevelId,
            ]);

        $departments = Department::orderBy('name')->get();
        $cycleYears = ReclassificationApplication::query()
            ->select('cycle_year')
            ->whereNotNull('cycle_year')
            ->distinct()
            ->orderByDesc('cycle_year')
            ->pluck('cycle_year');
        $rankLevels = Schema::hasTable('rank_levels')
            ? \App\Models\RankLevel::orderBy('order_no')->get()
            : collect();

        return view('reclassification.admin.index', compact(
            'applications',
            'departments',
            'cycleYears',
            'rankLevels',
            'q',
            'status',
            'departmentId',
            'cycleYear',
            'rankLevelId'
        ));
    }

    public function deanIndex(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $status = $request->get('status', 'all');
        $cycleYear = $request->get('cycle_year');
        $rankLevelId = $request->get('rank_level_id');

        $departmentId = $request->user()->department_id;
        abort_unless($departmentId, 403);

        $query = ReclassificationApplication::query()
            ->with([
                'faculty.department',
                'faculty.facultyProfile' . (Schema::hasTable('rank_levels') ? '.rankLevel' : ''),
            ])
            ->whereHas('faculty', function ($builder) use ($departmentId) {
                $builder->where('department_id', $departmentId);
            });

        if ($status === 'submitted') {
            $query->whereIn('status', [
                'dean_review',
                'hr_review',
                'vpaa_review',
                'president_review',
                'finalized',
            ]);
        } elseif ($status === 'all') {
            $query->where('status', '!=', 'draft');
        } else {
            $query->where('status', $status);
        }

        if (!empty($cycleYear)) {
            $query->where('cycle_year', $cycleYear);
        }

        if (!empty($rankLevelId)) {
            $query->whereHas('faculty.facultyProfile', function ($builder) use ($rankLevelId) {
                $builder->where('rank_level_id', $rankLevelId);
            });
        }

        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $builder->whereHas('faculty', function ($faculty) use ($q) {
                    $faculty->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                })->orWhereHas('faculty.facultyProfile', function ($profile) use ($q) {
                    $profile->where('employee_no', 'like', "%{$q}%");
                });
            });
        }

        $applications = $query
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->appends([
                'q' => $q,
                'status' => $status,
                'cycle_year' => $cycleYear,
                'rank_level_id' => $rankLevelId,
            ]);

        $cycleYears = ReclassificationApplication::query()
            ->select('cycle_year')
            ->whereNotNull('cycle_year')
            ->distinct()
            ->orderByDesc('cycle_year')
            ->pluck('cycle_year');
        $rankLevels = Schema::hasTable('rank_levels')
            ? \App\Models\RankLevel::orderBy('order_no')->get()
            : collect();

        return view('reclassification.dean.submissions', compact(
            'applications',
            'cycleYears',
            'rankLevels',
            'q',
            'status',
            'cycleYear',
            'rankLevelId'
        ));
    }

    public function approved(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $departmentId = $request->get('department_id');
        $cycleYear = $request->get('cycle_year');

        $query = ReclassificationApplication::query()
            ->with([
                'faculty.department',
                'approvedBy',
            ])
            ->where('status', 'finalized');

        if (!empty($departmentId)) {
            $query->whereHas('faculty', function ($builder) use ($departmentId) {
                $builder->where('department_id', $departmentId);
            });
        }

        if (!empty($cycleYear)) {
            $query->where('cycle_year', $cycleYear);
        }

        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $builder->whereHas('faculty', function ($faculty) use ($q) {
                    $faculty->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            });
        }

        $applications = $query
            ->orderByDesc('approved_at')
            ->orderByDesc('finalized_at')
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->appends([
                'q' => $q,
                'department_id' => $departmentId,
                'cycle_year' => $cycleYear,
            ]);

        $departments = Department::orderBy('name')->get();
        $cycleYears = ReclassificationApplication::query()
            ->select('cycle_year')
            ->whereNotNull('cycle_year')
            ->distinct()
            ->orderByDesc('cycle_year')
            ->pluck('cycle_year');

        return view('reclassification.admin.approved', compact(
            'applications',
            'departments',
            'cycleYears',
            'q',
            'departmentId',
            'cycleYear'
        ));
    }
}

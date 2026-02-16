<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\ReclassificationApplication;
use App\Models\ReclassificationPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ReclassificationAdminController extends Controller
{
    private function activePeriod(): ?ReclassificationPeriod
    {
        if (!Schema::hasTable('reclassification_periods')) {
            return null;
        }

        return ReclassificationPeriod::query()
            ->where('is_open', true)
            ->orderByDesc('created_at')
            ->first();
    }

    private function applyPeriodScope($query, ?ReclassificationPeriod $period): void
    {
        if (!$period) {
            $query->whereRaw('1 = 0');
            return;
        }

        $hasPeriodId = Schema::hasColumn('reclassification_applications', 'period_id');

        if (!$hasPeriodId) {
            if (!empty($period->cycle_year)) {
                $query->where('cycle_year', $period->cycle_year);
            } else {
                $query->whereRaw('1 = 0');
            }
            return;
        }

        $query->where(function ($builder) use ($period) {
            $builder->where('period_id', $period->id);
            if (!empty($period->cycle_year)) {
                $builder->orWhere(function ($fallback) use ($period) {
                    $fallback->whereNull('period_id')
                        ->where('cycle_year', $period->cycle_year);
                });
            }
        });
    }

    private function applyApprovedFilters($query, string $q = '', $departmentId = null, $cycleYear = null)
    {
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
    }

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

        $this->applyApprovedFilters($query, $q, $departmentId, $cycleYear);

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

        $title = 'Approved Reclassifications';
        $subtitle = 'Applications finalized after final approval.';
        $indexRoute = route('reclassification.admin.approved');
        $backRoute = route('hr.dashboard');
        $showDepartmentFilter = true;
        $showVpaaActions = false;
        $showPresidentActions = false;
        $batchReadyCount = 0;
        $batchBlockingCount = 0;

        return view('reclassification.admin.approved', compact(
            'applications',
            'departments',
            'cycleYears',
            'q',
            'departmentId',
            'cycleYear',
            'title',
            'subtitle',
            'indexRoute',
            'backRoute',
            'showDepartmentFilter',
            'showVpaaActions',
            'showPresidentActions',
            'batchReadyCount',
            'batchBlockingCount'
        ));
    }

    public function deanApproved(Request $request)
    {
        $departmentId = $request->user()->department_id;
        abort_unless($departmentId, 403);

        $q = trim((string) $request->get('q', ''));
        $cycleYear = $request->get('cycle_year');

        $query = ReclassificationApplication::query()
            ->with(['faculty.department', 'approvedBy'])
            ->where('status', 'finalized')
            ->whereHas('faculty', fn ($faculty) => $faculty->where('department_id', $departmentId));

        $this->applyApprovedFilters($query, $q, $departmentId, $cycleYear);

        $applications = $query
            ->orderByDesc('approved_at')
            ->orderByDesc('finalized_at')
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->appends([
                'q' => $q,
                'cycle_year' => $cycleYear,
            ]);

        $departments = Department::where('id', $departmentId)->get();
        $cycleYears = ReclassificationApplication::query()
            ->whereHas('faculty', fn ($faculty) => $faculty->where('department_id', $departmentId))
            ->select('cycle_year')
            ->whereNotNull('cycle_year')
            ->distinct()
            ->orderByDesc('cycle_year')
            ->pluck('cycle_year');

        $title = 'Approved Reclassifications';
        $subtitle = 'Finalized applications in your assigned department.';
        $indexRoute = route('dean.approved');
        $backRoute = route('dean.dashboard');
        $showDepartmentFilter = false;
        $showVpaaActions = false;
        $showPresidentActions = false;
        $batchReadyCount = 0;
        $batchBlockingCount = 0;

        return view('reclassification.admin.approved', compact(
            'applications',
            'departments',
            'cycleYears',
            'q',
            'departmentId',
            'cycleYear',
            'title',
            'subtitle',
            'indexRoute',
            'backRoute',
            'showDepartmentFilter',
            'showVpaaActions',
            'showPresidentActions',
            'batchReadyCount',
            'batchBlockingCount'
        ));
    }

    public function reviewerApproved(Request $request)
    {
        $role = strtolower((string) $request->user()->role);
        abort_unless(in_array($role, ['vpaa', 'president'], true), 403);

        $q = trim((string) $request->get('q', ''));
        $departmentId = $request->get('department_id');
        $activePeriod = $this->activePeriod();

        $statusScope = ['president_review', 'finalized'];

        $query = ReclassificationApplication::query()
            ->with(['faculty.department', 'approvedBy'])
            ->whereIn('status', $statusScope);
        $this->applyPeriodScope($query, $activePeriod);

        $this->applyApprovedFilters($query, $q, $departmentId, null);

        $applications = $query
            ->orderByDesc('approved_at')
            ->orderByDesc('submitted_at')
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->appends([
                'q' => $q,
                'department_id' => $departmentId,
            ]);

        $departments = Department::orderBy('name')->get();
        $cycleYears = collect();

        $title = $role === 'vpaa'
            ? 'VPAA Approved List'
            : 'President Approval List';
        $subtitle = $role === 'vpaa'
            ? 'Gather approved submissions in the active cycle and forward the list to President.'
            : 'Finalize active-cycle submissions approved by VPAA.';
        $indexRoute = route('reclassification.review.approved');
        $backRoute = $role === 'vpaa'
            ? route('vpaa.dashboard')
            : route('president.dashboard');
        $showDepartmentFilter = true;
        $showCycleFilter = false;
        $showVpaaActions = $role === 'vpaa';
        $showPresidentActions = $role === 'president';

        $batchReadyCount = 0;
        $batchBlockingCount = 0;
        if ($activePeriod) {
            if ($role === 'vpaa') {
                $readyQuery = ReclassificationApplication::query()
                    ->where('status', 'vpaa_review');
                $this->applyPeriodScope($readyQuery, $activePeriod);
                $batchReadyCount = $readyQuery->count();

                $blockingQuery = ReclassificationApplication::query()
                    ->whereIn('status', ['dean_review', 'hr_review', 'returned_to_faculty']);
                $this->applyPeriodScope($blockingQuery, $activePeriod);
                $batchBlockingCount = $blockingQuery->count();
            } else {
                $readyQuery = ReclassificationApplication::query()
                    ->where('status', 'president_review');
                $this->applyPeriodScope($readyQuery, $activePeriod);
                $batchReadyCount = $readyQuery->count();

                $blockingQuery = ReclassificationApplication::query()
                    ->whereIn('status', ['dean_review', 'hr_review', 'vpaa_review', 'returned_to_faculty']);
                $this->applyPeriodScope($blockingQuery, $activePeriod);
                $batchBlockingCount = $blockingQuery->count();
            }
        }

        return view('reclassification.admin.approved', compact(
            'applications',
            'departments',
            'cycleYears',
            'q',
            'departmentId',
            'activePeriod',
            'title',
            'subtitle',
            'indexRoute',
            'backRoute',
            'showDepartmentFilter',
            'showCycleFilter',
            'showVpaaActions',
            'showPresidentActions',
            'batchReadyCount',
            'batchBlockingCount'
        ));
    }

    public function forwardApprovedToPresident(Request $request)
    {
        abort_unless(strtolower((string) $request->user()->role) === 'vpaa', 403);

        $activePeriod = $this->activePeriod();
        if (!$activePeriod) {
            return back()->withErrors([
                'approved_list' => 'No active submission period found. Open a period first.',
            ]);
        }

        $blockingQuery = ReclassificationApplication::query()
            ->whereIn('status', ['dean_review', 'hr_review', 'returned_to_faculty']);
        $this->applyPeriodScope($blockingQuery, $activePeriod);
        $blockingCount = $blockingQuery->count();
        if ($blockingCount > 0) {
            return back()->withErrors([
                'approved_list' => 'Cannot forward list. Some active-cycle submissions are still not completed before VPAA.',
            ]);
        }

        $readyQuery = ReclassificationApplication::query()
            ->where('status', 'vpaa_review');
        $this->applyPeriodScope($readyQuery, $activePeriod);
        $readyCount = (clone $readyQuery)->count();
        if ($readyCount === 0) {
            return back()->withErrors([
                'approved_list' => 'No VPAA-approved submissions found to forward.',
            ]);
        }

        $readyQuery->update([
            'status' => 'president_review',
            'current_step' => 'president',
            'returned_from' => null,
        ]);

        return back()->with('success', "{$readyCount} active-cycle submissions forwarded to President.");
    }

    public function finalizeApprovedByPresident(Request $request)
    {
        abort_unless(strtolower((string) $request->user()->role) === 'president', 403);

        $activePeriod = $this->activePeriod();
        if (!$activePeriod) {
            return back()->withErrors([
                'approved_list' => 'No active submission period found. Open a period first.',
            ]);
        }

        $blockingQuery = ReclassificationApplication::query()
            ->whereIn('status', ['dean_review', 'hr_review', 'vpaa_review', 'returned_to_faculty']);
        $this->applyPeriodScope($blockingQuery, $activePeriod);
        $blockingCount = $blockingQuery->count();
        if ($blockingCount > 0) {
            return back()->withErrors([
                'approved_list' => 'Cannot finalize list. Some active-cycle submissions are not yet ready for President approval.',
            ]);
        }

        $appsQuery = ReclassificationApplication::query()
            ->where('status', 'president_review')
            ->with(['sections.entries', 'faculty.facultyProfile', 'faculty.facultyHighestDegree'])
            ;
        $this->applyPeriodScope($appsQuery, $activePeriod);
        $apps = $appsQuery->get();

        if ($apps->isEmpty()) {
            return back()->withErrors([
                'approved_list' => 'No President-review submissions found to finalize.',
            ]);
        }

        $workflow = app(ReclassificationWorkflowController::class);
        foreach ($apps as $app) {
            $workflow->finalizeForApproval($app, $request->user());
        }

        return back()->with('success', "{$apps->count()} active-cycle submissions finalized and promotions applied.");
    }

    public function history(Request $request)
    {
        $role = strtolower((string) $request->user()->role);
        abort_unless(in_array($role, ['dean', 'hr', 'vpaa', 'president'], true), 403);

        $q = trim((string) $request->get('q', ''));
        $departmentId = $request->get('department_id');
        $cycleYear = $request->get('cycle_year');

        if ($role === 'dean') {
            $departmentId = $request->user()->department_id;
            abort_unless($departmentId, 403);
        }

        $query = ReclassificationApplication::query()
            ->with(['faculty.department', 'approvedBy', 'period'])
            ->where('status', 'finalized');

        if (!empty($departmentId)) {
            $query->whereHas('faculty', fn ($faculty) => $faculty->where('department_id', $departmentId));
        }

        if (!empty($cycleYear)) {
            $query->where('cycle_year', $cycleYear);
        }

        if ($q !== '') {
            $query->whereHas('faculty', function ($faculty) use ($q) {
                $faculty->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
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

        $cyclesQuery = ReclassificationApplication::query()
            ->where('status', 'finalized');
        if (!empty($departmentId)) {
            $cyclesQuery->whereHas('faculty', fn ($faculty) => $faculty->where('department_id', $departmentId));
        }

        $cycleSummaries = $cyclesQuery
            ->selectRaw('cycle_year, COUNT(*) as total')
            ->whereNotNull('cycle_year')
            ->groupBy('cycle_year')
            ->orderByDesc('cycle_year')
            ->get();

        $cycleYears = $cycleSummaries->pluck('cycle_year')->values();
        $applicationsByCycle = $applications
            ->groupBy(fn ($app) => (string) ($app->cycle_year ?: 'No Cycle'))
            ->sortKeysDesc();
        $departments = $role === 'dean'
            ? Department::where('id', $departmentId)->get()
            : Department::orderBy('name')->get();

        $title = 'Reclassification History';
        $subtitle = $role === 'dean'
            ? 'Finalized reclassifications in your department, grouped by cycle.'
            : 'Finalized reclassifications by cycle.';
        $indexRoute = route('reclassification.history');
        $backRoute = match ($role) {
            'dean' => route('dean.dashboard'),
            'hr' => route('hr.dashboard'),
            'vpaa' => route('vpaa.dashboard'),
            default => route('president.dashboard'),
        };
        $showDepartmentFilter = $role !== 'dean';

        return view('reclassification.admin.history', compact(
            'applications',
            'applicationsByCycle',
            'cycleSummaries',
            'cycleYears',
            'departments',
            'q',
            'departmentId',
            'cycleYear',
            'title',
            'subtitle',
            'indexRoute',
            'backRoute',
            'showDepartmentFilter'
        ));
    }
}

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FacultyProfileController;
use App\Http\Controllers\ReclassificationFormController;
use App\Http\Controllers\ReclassificationPeriodController;
use App\Http\Controllers\ReclassificationReviewController;
use App\Http\Controllers\ReclassificationWorkflowController;
use App\Http\Controllers\ReclassificationEvidenceReviewController;
use App\Http\Controllers\ReclassificationRowCommentController;
use App\Http\Controllers\ReclassificationMoveRequestController;
use App\Http\Controllers\ReclassificationAdminController;
use App\Models\ReclassificationApplication;
use App\Models\ReclassificationPeriod;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    // Generic dashboard entrypoint: redirect to role dashboard
    Route::get('/dashboard', function () {
        $role = strtolower((string) (request()->user()->role ?? ''));

        return match ($role) {
            'faculty' => redirect()->route('faculty.dashboard'),
            'dean' => redirect()->route('dean.dashboard'),
            'hr' => redirect()->route('hr.dashboard'),
            'vpaa' => redirect()->route('vpaa.dashboard'),
            'president' => redirect()->route('president.dashboard'),
            default => view('dashboard'),
        };
    })->name('dashboard');

    /*
    |----------------------------------------------------------------------
    | Role dashboards
    |----------------------------------------------------------------------
    */
    Route::middleware(['role:faculty'])->get('/faculty/dashboard', function () {
        $user = request()->user()->load(['department', 'facultyProfile']);
        $applications = ReclassificationApplication::where('faculty_user_id', $user->id)
            ->latest()
            ->get();

        return view('dashboards.faculty', compact('user', 'applications'));
    })->name('faculty.dashboard');

    Route::middleware(['role:dean'])->get('/dean/dashboard', function () {
        $user = request()->user()->load('department');
        $departmentId = $user->department_id;

        $statusCounts = ReclassificationApplication::query()
            ->where('status', '!=', 'draft')
            ->when($departmentId, function ($query) use ($departmentId) {
                $query->whereHas('faculty', function ($faculty) use ($departmentId) {
                    $faculty->where('department_id', $departmentId);
                });
            })
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $recentApplications = ReclassificationApplication::query()
            ->when($departmentId, function ($query) use ($departmentId) {
                $query->whereHas('faculty', function ($faculty) use ($departmentId) {
                    $faculty->where('department_id', $departmentId);
                });
            })
            ->where('status', '!=', 'draft')
            ->with(['faculty.department'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->take(6)
            ->get();

        $facultyCount = User::query()
            ->where('role', 'faculty')
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
            ->count();

        $departmentName = $user->department?->name ?? 'Department';

        return view('dashboards.dean', compact(
            'statusCounts',
            'recentApplications',
            'facultyCount',
            'departmentName'
        ));
    })->name('dean.dashboard');

    Route::middleware(['role:dean'])->group(function () {
        Route::get('/dean/users/create', [UserController::class, 'create'])
            ->name('dean.users.create');
        Route::post('/dean/users', [UserController::class, 'store'])
            ->name('dean.users.store');
        Route::get('/dean/faculty', [FacultyProfileController::class, 'index'])
            ->name('dean.faculty.index');
        Route::get('/dean/submissions', [ReclassificationAdminController::class, 'deanIndex'])
            ->name('dean.submissions');
    });

    Route::middleware(['role:dean,hr,vpaa,president'])->prefix('reclassification')->group(function () {
        Route::get('/review-queue', [ReclassificationReviewController::class, 'index'])
            ->name('reclassification.review.queue');
        Route::get('/review/{application}', [ReclassificationReviewController::class, 'show'])
            ->name('reclassification.review.show');
        Route::post('/review/{application}/section2', [ReclassificationReviewController::class, 'saveSectionTwo'])
            ->name('reclassification.review.section2.save');
        Route::post('/review/{application}/section1-c/{entry}', [ReclassificationReviewController::class, 'updateSectionOneC'])
            ->name('reclassification.review.section1c.update');

        Route::get('/dean/review', [ReclassificationReviewController::class, 'index'])
            ->name('reclassification.dean.review');
        Route::get('/dean/review/{application}', [ReclassificationReviewController::class, 'show'])
            ->name('reclassification.dean.review.show');
        Route::post('/dean/review/{application}/section2', [ReclassificationReviewController::class, 'saveSectionTwo'])
            ->name('reclassification.dean.section2.save');
        Route::post('/dean/review/{application}/section1-c/{entry}', [ReclassificationReviewController::class, 'updateSectionOneC'])
            ->name('reclassification.dean.section1c.update');
    });

    Route::middleware(['role:hr'])->get('/hr/dashboard', function () {
        $statusCounts = ReclassificationApplication::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $openPeriod = ReclassificationPeriod::query()
            ->where('is_open', true)
            ->orderByDesc('created_at')
            ->first();

        $recentApplications = ReclassificationApplication::query()
            ->with('faculty.department')
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $facultyCount = User::query()->where('role', 'faculty')->count();

        return view('dashboards.hr', compact(
            'statusCounts',
            'openPeriod',
            'recentApplications',
            'facultyCount'
        ));
    })->name('hr.dashboard');

    Route::middleware(['role:hr'])->prefix('reclassification')->group(function () {
        Route::get('/periods', [ReclassificationPeriodController::class, 'index'])
            ->name('reclassification.periods');
        Route::post('/periods', [ReclassificationPeriodController::class, 'store'])
            ->name('reclassification.periods.store');
        Route::post('/periods/{period}/toggle', [ReclassificationPeriodController::class, 'toggle'])
            ->name('reclassification.periods.toggle');
        Route::get('/submissions', [ReclassificationAdminController::class, 'index'])
            ->name('reclassification.admin.submissions');
        Route::get('/approved', [ReclassificationAdminController::class, 'approved'])
            ->name('reclassification.admin.approved');
    });

    Route::middleware(['role:vpaa'])->get('/vpaa/dashboard', function () {
        $statusCounts = ReclassificationApplication::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $recentApplications = ReclassificationApplication::query()
            ->with('faculty.department')
            ->where('status', '!=', 'draft')
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $totalSubmissions = ReclassificationApplication::query()
            ->where('status', '!=', 'draft')
            ->count();

        return view('dashboards.vpaa', compact(
            'statusCounts',
            'recentApplications',
            'totalSubmissions'
        ));
    })->name('vpaa.dashboard');

    Route::middleware(['role:president'])->get('/president/dashboard', function () {
        $statusCounts = ReclassificationApplication::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $recentApplications = ReclassificationApplication::query()
            ->with('faculty.department')
            ->where('status', '!=', 'draft')
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $totalSubmissions = ReclassificationApplication::query()
            ->where('status', '!=', 'draft')
            ->count();

        return view('dashboards.president', compact(
            'statusCounts',
            'recentApplications',
            'totalSubmissions'
        ));
    })->name('president.dashboard');

    Route::middleware(['role:vpaa,president'])->prefix('reclassification')->group(function () {
        Route::get('/all-submissions', [ReclassificationAdminController::class, 'index'])
            ->name('reclassification.review.submissions');
    });

    /*
    |----------------------------------------------------------------------
    | User Management (HR only)
    |----------------------------------------------------------------------
    */
    Route::middleware(['role:hr'])->group(function () {
        Route::resource('users', UserController::class)->only([
            'index', 'create', 'store', 'edit', 'update'
        ]);
    });

    /*
    |----------------------------------------------------------------------
    | Reclassification (Faculty form pages)
    |----------------------------------------------------------------------
    | ✅ Single source of truth:
    | - GET show + sections via controller
    | - POST actions for workflow + evidence review
    */
    Route::middleware(['role:faculty'])->prefix('reclassification')->group(function () {

        // Main
        Route::get('/', [ReclassificationFormController::class, 'show'])
            ->name('reclassification.show');

        // Sections (1..5)
        Route::get('/section/{number}', [ReclassificationFormController::class, 'section'])
            ->whereNumber('number')
            ->name('reclassification.section');

        Route::post('/section/{number}', [ReclassificationFormController::class, 'saveSection'])
            ->whereNumber('number')
            ->name('reclassification.section.save');

        Route::post('/section/{number}/reset', [ReclassificationFormController::class, 'resetSection'])
            ->whereNumber('number')
            ->name('reclassification.section.reset');

        // Review page (optional)
        Route::get('/review', [ReclassificationFormController::class, 'review'])
            ->name('reclassification.review');
        Route::post('/review', [ReclassificationFormController::class, 'reviewSave'])
            ->name('reclassification.review.save');

        Route::post('/reset', [ReclassificationFormController::class, 'resetApplication'])
            ->name('reclassification.reset');

        // Submitted / under review screen
        Route::get('/submitted', [ReclassificationFormController::class, 'submitted'])
            ->name('reclassification.submitted');

        // Submitted summary (read-only)
        Route::get('/submitted-summary', [ReclassificationFormController::class, 'submittedSummary'])
            ->name('reclassification.submitted-summary');
        Route::get('/submitted-summary/{application}', [ReclassificationFormController::class, 'submittedSummaryShow'])
            ->name('reclassification.submitted-summary.show');

        // Workflow actions
        Route::post('/{application}/submit', [ReclassificationWorkflowController::class, 'submit'])
            ->name('reclassification.submit');

        Route::post('/evidences', [ReclassificationFormController::class, 'uploadEvidence'])
            ->name('reclassification.evidence.upload');

        Route::post('/evidences/{evidence}/detach', [ReclassificationFormController::class, 'detachEvidence'])
            ->name('reclassification.evidence.detach');

        Route::delete('/evidences/{evidence}', [ReclassificationFormController::class, 'deleteEvidence'])
            ->name('reclassification.evidence.delete');

        Route::post('/move-requests/{moveRequest}/address', [ReclassificationMoveRequestController::class, 'address'])
            ->name('reclassification.move-requests.address');

        Route::post('/row-comments/{comment}/reply', [ReclassificationRowCommentController::class, 'reply'])
            ->name('reclassification.row-comments.reply');
        Route::post('/row-comments/{comment}/address', [ReclassificationRowCommentController::class, 'address'])
            ->name('reclassification.row-comments.address');
    });

    /*
    |----------------------------------------------------------------------
    | Reviewer actions (Dean/HR/VPAA/President)
    |----------------------------------------------------------------------
    */
    Route::middleware(['role:dean,hr,vpaa,president'])->prefix('reclassification')->group(function () {

        // Workflow actions
        Route::post('/{application}/return', [ReclassificationWorkflowController::class, 'returnToFaculty'])
            ->name('reclassification.return');

        Route::post('/{application}/forward', [ReclassificationWorkflowController::class, 'forward'])
            ->name('reclassification.forward');

        // Evidence review actions
        Route::post('/evidences/{evidence}/accept', [ReclassificationEvidenceReviewController::class, 'accept'])
            ->name('reclassification.evidence.accept');

        Route::post('/evidences/{evidence}/reject', [ReclassificationEvidenceReviewController::class, 'reject'])
            ->name('reclassification.evidence.reject');

        Route::post('/{application}/entries/{entry}/comments', [ReclassificationRowCommentController::class, 'store'])
            ->name('reclassification.row-comments.store');

        Route::post('/row-comments/{comment}/resolve', [ReclassificationRowCommentController::class, 'resolve'])
            ->name('reclassification.row-comments.resolve');

        Route::post('/{application}/entries/{entry}/move-request', [ReclassificationMoveRequestController::class, 'store'])
            ->name('reclassification.move-requests.store');

        Route::post('/move-requests/{moveRequest}/resolve', [ReclassificationMoveRequestController::class, 'resolve'])
            ->name('reclassification.move-requests.resolve');
    });

    /*
    |----------------------------------------------------------------------
    | Alias route for dashboard link
    |----------------------------------------------------------------------
    */
    Route::middleware(['role:faculty'])->get('/faculty/reclassification', function () {
        return redirect()->route('reclassification.show');
    })->name('faculty.reclassification');

    /*
    |----------------------------------------------------------------------
    | Profile (Breeze)
    |----------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |----------------------------------------------------------------------
    | Faculty Profiles (HR / internal)
    |----------------------------------------------------------------------
    */
    Route::get('/faculty-profiles/{user}/edit', [FacultyProfileController::class, 'edit'])
        ->name('faculty-profiles.edit');

    Route::put('/faculty-profiles/{user}', [FacultyProfileController::class, 'update'])
        ->name('faculty-profiles.update');

    Route::get('/faculty', [FacultyProfileController::class, 'index'])
        ->name('faculty.index');
});

require __DIR__.'/auth.php';

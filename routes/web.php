<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FacultyProfileController;
use App\Http\Controllers\ReclassificationFormController;
use App\Http\Controllers\ReclassificationWorkflowController;
use App\Http\Controllers\ReclassificationEvidenceReviewController;
use App\Models\ReclassificationApplication;

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

    // Generic dashboard (optional)
    Route::get('/dashboard', function () {
        return view('dashboard');
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
        return view('dashboards.dean');
    })->name('dean.dashboard');

    Route::middleware(['role:hr'])->get('/hr/dashboard', function () {
        return view('dashboards.hr');
    })->name('hr.dashboard');

    Route::middleware(['role:vpaa'])->get('/vpaa/dashboard', function () {
        return view('dashboards.vpaa');
    })->name('vpaa.dashboard');

    Route::middleware(['role:president'])->get('/president/dashboard', function () {
        return view('dashboards.president');
    })->name('president.dashboard');

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

        // Review page (optional)
        Route::get('/review', [ReclassificationFormController::class, 'review'])
            ->name('reclassification.review');

        // Submitted / under review screen
        Route::get('/submitted', [ReclassificationFormController::class, 'submitted'])
            ->name('reclassification.submitted');

        // Submitted summary (read-only)
        Route::get('/submitted-summary', [ReclassificationFormController::class, 'submittedSummary'])
            ->name('reclassification.submitted-summary');

        // Workflow actions
        Route::post('/{application}/submit', [ReclassificationWorkflowController::class, 'submit'])
            ->name('reclassification.submit');
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

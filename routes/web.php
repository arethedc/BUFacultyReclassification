<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware(['auth'])->group(function () {

   Route::middleware(['auth', 'role:faculty'])->get('/faculty/dashboard', function () {
    return view('dashboards.faculty');
})->name('faculty.dashboard');

Route::middleware(['auth', 'role:dean'])->get('/dean/dashboard', function () {
    return view('dashboards.dean');
})->name('dean.dashboard');

Route::middleware(['auth', 'role:hr'])->get('/hr/dashboard', function () {
    return view('dashboards.hr');
})->name('hr.dashboard');

Route::middleware(['auth', 'role:vpaa'])->get('/vpaa/dashboard', function () {
    return view('dashboards.vpaa');
})->name('vpaa.dashboard');

Route::middleware(['auth', 'role:president'])->get('/president/dashboard', function () {
    return view('dashboards.president');
})->name('president.dashboard');
Route::middleware(['auth', 'role:faculty'])->group(function () {

    Route::get('/reclassification', function () {
        return view('reclassification.show');
    })->name('faculty.reclassification');

});


Route::middleware(['auth'])->group(function () {

    // User Management (HR)
    Route::view('/users', 'users.index')->name('users.index');
    Route::view('/users/create', 'users.create')->name('users.create');
    Route::view('/users/{id}/edit', 'users.edit')->name('users.edit');

});
Route::middleware(['auth'])->group(function () {

    // Reclassification – Faculty
    Route::view(
        '/reclassification/section-1',
        'reclassification.section1'
    )->name('reclassification.section1');
      Route::view(
        '/reclassification/section-2',
        'reclassification.section2'
    )->name('reclassification.section');
 Route::view(
        '/reclassification/section3',
        'reclassification.section3'
    )->name('reclassification.section');
    Route::view(
        '/reclassification/section4',
        'reclassification.section4'
    )->name('reclassification.section');
    Route::view(
        '/reclassification/section5',
        'reclassification.section5'
    )->name('reclassification.section');

     Route::view(
        '/reclassification/review',
        'reclassification.review'
    )->name('reclassification.section');


    Route::prefix('reclassification')->name('reclassification.')->group(function () {
    Route::get('/section1', fn() => view('reclassification.section1'))->name('section1');
    Route::get('/section2', fn() => view('reclassification.section2'))->name('section2');
    Route::get('/section3', fn() => view('reclassification.section3'))->name('section3');
    Route::get('/section4', fn() => view('reclassification.section4'))->name('section4');
    Route::get('/section5', fn() => view('reclassification.section5'))->name('section5');
    Route::get('/review', fn() => view('reclassification.review'))->name('review');
});
});
});

require __DIR__.'/auth.php';

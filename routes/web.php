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


});

require __DIR__.'/auth.php';

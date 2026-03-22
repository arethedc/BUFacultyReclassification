<?php


use App\Http\Controllers\ReclassificationEvidenceReviewController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active_user'])->group(function () {
    Route::post('/reclassification/evidences/{evidence}/accept', [ReclassificationEvidenceReviewController::class, 'accept']);
    Route::post('/reclassification/evidences/{evidence}/reject', [ReclassificationEvidenceReviewController::class, 'reject']);
});

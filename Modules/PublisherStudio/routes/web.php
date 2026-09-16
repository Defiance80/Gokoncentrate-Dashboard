<?php

use Illuminate\Support\Facades\Route;
use Modules\PublisherStudio\Http\Controllers\Auth\PublisherAuthController;
use Modules\PublisherStudio\Http\Controllers\StudioController;
use Modules\PublisherStudio\Http\Controllers\SubmissionController;
use Modules\PublisherStudio\Http\Controllers\Backend\PublisherReviewController;
use Modules\PublisherStudio\Http\Controllers\Backend\StorageSettingsController;

/*
|--------------------------------------------------------------------------
| Publisher Studio (publisher-facing, separate "publisher" auth guard)
|--------------------------------------------------------------------------
| Lives under /studio and is fully isolated from the /app admin backend.
*/
Route::prefix('studio')->name('studio.')->group(function () {

    Route::middleware('publisher.guest')->group(function () {
        Route::get('login', [PublisherAuthController::class, 'showLogin'])->name('login');
        Route::post('login', [PublisherAuthController::class, 'login'])->name('login.attempt');
        Route::get('register', [PublisherAuthController::class, 'showRegister'])->name('register');
        Route::post('register', [PublisherAuthController::class, 'register'])->name('register.attempt');
    });

    Route::middleware('publisher.auth')->group(function () {
        Route::get('/', [StudioController::class, 'dashboard'])->name('dashboard');
        Route::post('logout', [PublisherAuthController::class, 'logout'])->name('logout');

        Route::get('submissions', [SubmissionController::class, 'index'])->name('submissions.index');
        Route::get('submissions/create', [SubmissionController::class, 'create'])->name('submissions.create');
        Route::post('submissions', [SubmissionController::class, 'store'])->name('submissions.store');
        Route::get('submissions/{submission}/edit', [SubmissionController::class, 'edit'])->name('submissions.edit');
        Route::put('submissions/{submission}', [SubmissionController::class, 'update'])->name('submissions.update');
        Route::delete('submissions/{submission}', [SubmissionController::class, 'destroy'])->name('submissions.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Admin review queue + storage settings (admin backend under /app)
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'app', 'as' => 'backend.', 'middleware' => ['auth', 'admin']], function () {
    Route::get('publisher-submissions/index_data', [PublisherReviewController::class, 'index_data'])->name('publisher-submissions.index_data');
    Route::get('publisher-submissions', [PublisherReviewController::class, 'index'])->name('publisher-submissions.index');
    Route::get('publisher-submissions/{submission}', [PublisherReviewController::class, 'show'])->name('publisher-submissions.show');
    Route::post('publisher-submissions/{submission}/approve', [PublisherReviewController::class, 'approve'])->name('publisher-submissions.approve');
    Route::post('publisher-submissions/{submission}/reject', [PublisherReviewController::class, 'reject'])->name('publisher-submissions.reject');

    Route::get('publisher-storage', [StorageSettingsController::class, 'edit'])->name('publisher-storage.edit');
    Route::put('publisher-storage', [StorageSettingsController::class, 'update'])->name('publisher-storage.update');
});

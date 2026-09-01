<?php

use Illuminate\Support\Facades\Route;
use Modules\Magazine\Http\Controllers\API\MagazinePrintController;
use Modules\Magazine\Http\Controllers\Backend\MagazinesController;
use Modules\Magazine\Http\Controllers\Backend\MagazineIssuesController;

/*
|--------------------------------------------------------------------------
| Magazine redirect (tracking + redirect to MagCloud)
|--------------------------------------------------------------------------
*/

Route::get('r/magazine/{id}/print', [MagazinePrintController::class, 'redirect'])
    ->whereNumber('id')
    ->name('magazine.print.redirect');

/*
|--------------------------------------------------------------------------
| Backend (admin) routes
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => 'app', 'as' => 'backend.', 'middleware' => ['auth', 'admin']], function () {
    Route::get('magazines/index_data', [MagazinesController::class, 'index_data'])->name('magazines.index_data');
    Route::resource('magazines', MagazinesController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])->names('magazines');
    Route::get('magazine-issues/index_data', [MagazineIssuesController::class, 'index_data'])->name('magazine-issues.index_data');
    Route::resource('magazine-issues', MagazineIssuesController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])->names('magazine-issues');
});

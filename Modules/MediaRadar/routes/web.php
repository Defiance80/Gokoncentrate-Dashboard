<?php

use Illuminate\Support\Facades\Route;
use Modules\MediaRadar\Http\Controllers\Backend\MediaCandidatesController;
use Modules\MediaRadar\Http\Controllers\Backend\MediaImportController;
use Modules\MediaRadar\Http\Controllers\Backend\MediaDiscoveryRulesController;
use Modules\MediaRadar\Http\Controllers\Backend\MediaRadarDashboardController;
use Modules\MediaRadar\Http\Controllers\Backend\MediaRadarSettingsController;
use Modules\MediaRadar\Http\Controllers\Backend\MediaRunsController;
use Modules\MediaRadar\Http\Controllers\Backend\MediaSourcesController;

/*
|--------------------------------------------------------------------------
| Media Radar backend routes
|--------------------------------------------------------------------------
|
| Media Radar lives inside the existing admin dashboard, behind the same
| auth + admin middleware as every other module.
|
*/

Route::group(['prefix' => 'app', 'as' => 'backend.', 'middleware' => ['auth', 'admin']], function () {

    Route::get('media-radar', [MediaRadarDashboardController::class, 'index'])->name('media-radar.index');

    /*
     * Approval queue
     */
    Route::group(['prefix' => 'media-radar/candidates', 'as' => 'media-radar-candidates.'], function () {
        Route::get('/', [MediaCandidatesController::class, 'index'])->name('index');
        Route::get('index_data', [MediaCandidatesController::class, 'index_data'])->name('index_data');
        Route::get('import', [MediaImportController::class, 'create'])->name('import.create');
        Route::post('import/preview', [MediaImportController::class, 'preview'])->name('import.preview');
        Route::post('import', [MediaImportController::class, 'store'])->name('import.store');
        Route::post('bulk-action', [MediaCandidatesController::class, 'bulk_action'])->name('bulk_action');
        Route::get('{candidate}', [MediaCandidatesController::class, 'show'])->whereNumber('candidate')->name('show');
        Route::put('{candidate}', [MediaCandidatesController::class, 'update'])->whereNumber('candidate')->name('update');
        Route::post('{candidate}/approve', [MediaCandidatesController::class, 'approve'])->whereNumber('candidate')->name('approve');
        Route::post('{candidate}/reject', [MediaCandidatesController::class, 'reject'])->whereNumber('candidate')->name('reject');
        Route::post('{candidate}/schedule', [MediaCandidatesController::class, 'schedule'])->whereNumber('candidate')->name('schedule');
        Route::post('{candidate}/publish', [MediaCandidatesController::class, 'publish'])->whereNumber('candidate')->name('publish');
        Route::post('{candidate}/archive', [MediaCandidatesController::class, 'archive'])->whereNumber('candidate')->name('archive');
        Route::post('{candidate}/analyze', [MediaCandidatesController::class, 'analyze'])->whereNumber('candidate')->name('analyze');
        Route::post('{candidate}/trust', [MediaCandidatesController::class, 'trust'])->whereNumber('candidate')->name('trust');
        Route::post('{candidate}/block', [MediaCandidatesController::class, 'block'])->whereNumber('candidate')->name('block');
    });

    /*
     * Discovery rules (search parameters)
     */
    Route::group(['prefix' => 'media-radar/rules', 'as' => 'media-radar-rules.'], function () {
        Route::get('index_data', [MediaDiscoveryRulesController::class, 'index_data'])->name('index_data');
        Route::post('{rule}/run', [MediaDiscoveryRulesController::class, 'run'])->whereNumber('rule')->name('run');
        Route::post('{rule}/duplicate', [MediaDiscoveryRulesController::class, 'duplicate'])->whereNumber('rule')->name('duplicate');
        Route::post('{rule}/update-status', [MediaDiscoveryRulesController::class, 'update_status'])->whereNumber('rule')->name('update_status');
        Route::get('{rule}/runs', [MediaDiscoveryRulesController::class, 'runs'])->whereNumber('rule')->name('runs');
    });

    Route::resource('media-radar/rules', MediaDiscoveryRulesController::class)
        ->parameters(['rules' => 'rule'])
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
        ->names('media-radar-rules');

    /*
     * Trusted and blocked creators
     */
    Route::group(['prefix' => 'media-radar/sources', 'as' => 'media-radar-sources.'], function () {
        Route::get('/', [MediaSourcesController::class, 'index'])->name('index');
        Route::get('trusted_data', [MediaSourcesController::class, 'trusted_data'])->name('trusted_data');
        Route::get('blocked_data', [MediaSourcesController::class, 'blocked_data'])->name('blocked_data');
        Route::post('trusted', [MediaSourcesController::class, 'store_trusted'])->name('store_trusted');
        Route::post('blocked', [MediaSourcesController::class, 'store_blocked'])->name('store_blocked');
        Route::post('trusted/{source}/refresh', [MediaSourcesController::class, 'refresh'])->whereNumber('source')->name('refresh');
        Route::delete('trusted/{source}', [MediaSourcesController::class, 'destroy_trusted'])->whereNumber('source')->name('destroy_trusted');
        Route::delete('blocked/{source}', [MediaSourcesController::class, 'destroy_blocked'])->whereNumber('source')->name('destroy_blocked');
    });

    /*
     * Search run monitoring
     */
    Route::group(['prefix' => 'media-radar/runs', 'as' => 'media-radar-runs.'], function () {
        Route::get('/', [MediaRunsController::class, 'index'])->name('index');
        Route::get('index_data', [MediaRunsController::class, 'index_data'])->name('index_data');
        Route::get('{run}', [MediaRunsController::class, 'show'])->whereNumber('run')->name('show');
        Route::post('{run}/retry', [MediaRunsController::class, 'retry'])->whereNumber('run')->name('retry');
    });

    /*
     * Settings, including the auto/manual approval switch
     */
    Route::group(['prefix' => 'media-radar/settings', 'as' => 'media-radar-settings.'], function () {
        Route::get('/', [MediaRadarSettingsController::class, 'index'])->name('index');
        Route::post('/', [MediaRadarSettingsController::class, 'store'])->name('store');
        Route::post('toggle-auto-approve', [MediaRadarSettingsController::class, 'toggle_auto_approve'])->name('toggle_auto_approve');
    });
});

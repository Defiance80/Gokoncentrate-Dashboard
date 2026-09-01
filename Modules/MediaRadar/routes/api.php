<?php

use Illuminate\Support\Facades\Route;
use Modules\MediaRadar\Http\Controllers\API\MediaRadarAdminController;

/*
|--------------------------------------------------------------------------
| Media Radar admin API
|--------------------------------------------------------------------------
|
| Authenticated with Sanctum. Every action additionally checks the Media Radar
| permissions. No public route exposes provider credentials, and no public
| request ever triggers a YouTube or Vimeo search.
|
*/

Route::prefix('admin/media-radar')->middleware(['auth:sanctum'])->group(function () {

    // Discovery rules
    Route::get('rules', [MediaRadarAdminController::class, 'rules']);
    Route::post('rules', [MediaRadarAdminController::class, 'storeRule']);
    Route::get('rules/{rule}', [MediaRadarAdminController::class, 'showRule'])->whereNumber('rule');
    Route::patch('rules/{rule}', [MediaRadarAdminController::class, 'updateRule'])->whereNumber('rule');
    Route::delete('rules/{rule}', [MediaRadarAdminController::class, 'destroyRule'])->whereNumber('rule');
    Route::post('rules/{rule}/run', [MediaRadarAdminController::class, 'runRule'])->whereNumber('rule');
    Route::get('rules/{rule}/runs', [MediaRadarAdminController::class, 'ruleRuns'])->whereNumber('rule');

    // Candidates
    Route::get('candidates', [MediaRadarAdminController::class, 'candidates']);
    Route::get('candidates/{candidate}', [MediaRadarAdminController::class, 'showCandidate'])->whereNumber('candidate');
    Route::patch('candidates/{candidate}', [MediaRadarAdminController::class, 'updateCandidate'])->whereNumber('candidate');
    Route::post('candidates/{candidate}/approve', [MediaRadarAdminController::class, 'approveCandidate'])->whereNumber('candidate');
    Route::post('candidates/{candidate}/reject', [MediaRadarAdminController::class, 'rejectCandidate'])->whereNumber('candidate');
    Route::post('candidates/{candidate}/archive', [MediaRadarAdminController::class, 'archiveCandidate'])->whereNumber('candidate');
    Route::post('candidates/{candidate}/analyze', [MediaRadarAdminController::class, 'analyzeCandidate'])->whereNumber('candidate');
    Route::post('candidates/{candidate}/schedule', [MediaRadarAdminController::class, 'scheduleCandidate'])->whereNumber('candidate');
    Route::post('candidates/{candidate}/publish', [MediaRadarAdminController::class, 'publishCandidate'])->whereNumber('candidate');
    Route::post('candidates/{candidate}/trust', [MediaRadarAdminController::class, 'trustSource'])->whereNumber('candidate');
    Route::post('candidates/{candidate}/block', [MediaRadarAdminController::class, 'blockSource'])->whereNumber('candidate');

    // Sources
    Route::get('sources', [MediaRadarAdminController::class, 'sources']);
    Route::delete('sources/{source}/trust', [MediaRadarAdminController::class, 'untrustSource'])->whereNumber('source');
    Route::delete('sources/{source}/block', [MediaRadarAdminController::class, 'unblockSource'])->whereNumber('source');

    // Runs
    Route::get('runs', [MediaRadarAdminController::class, 'runs']);
    Route::get('runs/{run}', [MediaRadarAdminController::class, 'showRun'])->whereNumber('run');
    Route::post('runs/{run}/retry', [MediaRadarAdminController::class, 'retryRun'])->whereNumber('run');

    // System
    Route::get('status', [MediaRadarAdminController::class, 'status']);
    Route::get('provider-status', [MediaRadarAdminController::class, 'providerStatus']);
});

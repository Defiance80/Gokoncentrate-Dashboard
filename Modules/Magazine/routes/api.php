<?php

use Illuminate\Support\Facades\Route;
use Modules\Magazine\Http\Controllers\API\MagazineController;
use Modules\Magazine\Http\Controllers\API\MagazinePrintController;

/*
|--------------------------------------------------------------------------
| Magazine API (public read + optional auth for events)
|--------------------------------------------------------------------------
*/

Route::get('magazines', [MagazineController::class, 'index']);
Route::get('magazines/{slug}/issues', [MagazineController::class, 'issues']);
Route::get('magazine-issues/{slug}', [MagazineController::class, 'show']);

Route::post('magazine-issues/{id}/print/events', [MagazinePrintController::class, 'storeEvent'])
    ->whereNumber('id');

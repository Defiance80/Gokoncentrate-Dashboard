<?php

use Illuminate\Support\Facades\Route;
use Modules\Tokens\Http\Controllers\API\TokensController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public routes
Route::get('token-settings', [TokensController::class, 'settings']);

// Authenticated routes
Route::group(['middleware' => 'auth:sanctum'], function () {
    // GET /api/me - per INTEGRATIONS.md contract
    Route::get('me', [TokensController::class, 'me']);
});

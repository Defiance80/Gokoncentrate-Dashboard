<?php

use Illuminate\Support\Facades\Route;
use Modules\Tokens\Http\Controllers\Backend\TokenSettingsController;
use Modules\Tokens\Http\Controllers\Backend\TokenUsersController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['prefix' => 'app', 'as' => 'backend.', 'middleware' => ['auth', 'admin']], function () {

    // Token Settings Routes
    Route::group(['prefix' => 'token-settings', 'as' => 'token-settings.'], function () {
        Route::get('/', [TokenSettingsController::class, 'index'])->name('index');
        Route::post('/', [TokenSettingsController::class, 'store'])->name('store');
    });

    // Token Users Routes
    Route::group(['prefix' => 'token-users', 'as' => 'token-users.'], function () {
        Route::get('/', [TokenUsersController::class, 'index'])->name('index');
        Route::get('/index_data', [TokenUsersController::class, 'index_data'])->name('index_data');
        Route::get('/details/{id}', [TokenUsersController::class, 'details'])->name('details');

        // Token adjustment actions
        Route::post('/{id}/add-tokens', [TokenUsersController::class, 'addTokens'])->name('add_tokens');
        Route::post('/{id}/deduct-tokens', [TokenUsersController::class, 'deductTokens'])->name('deduct_tokens');
        Route::post('/{id}/set-balance', [TokenUsersController::class, 'setBalance'])->name('set_balance');

        // Earning suspension
        Route::post('/{id}/suspend-earning', [TokenUsersController::class, 'suspendEarning'])->name('suspend_earning');
        Route::post('/{id}/unsuspend-earning', [TokenUsersController::class, 'unsuspendEarning'])->name('unsuspend_earning');
    });
});

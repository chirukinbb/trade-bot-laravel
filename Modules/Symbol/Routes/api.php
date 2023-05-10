<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware(['auth:sanctum','ability:user'])->as('symbol::')->prefix('symbol')->group(function () {
    Route::get('/',[\Modules\Symbol\Http\Controllers\Api\ActionController::class,'symbols'])->name('index');
    Route::get('list',[\Modules\Symbol\Http\Controllers\Api\ActionController::class,'symbols'])->name('symbols');
    Route::post('add',[\Modules\Symbol\Http\Controllers\Api\ActionController::class,'store'])->name('store');
    Route::post('delete',[\Modules\Symbol\Http\Controllers\Api\ActionController::class,'delete'])->name('delete');
});

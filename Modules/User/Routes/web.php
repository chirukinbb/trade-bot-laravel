<?php

/**
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Route;

Route::get('/',[\Modules\User\Http\Controllers\PageController::class,'login'])->name('login');
Route::get('logout',[\Modules\User\Http\Controllers\ActionController::class,'logout'])->name('logout');
Route::post('login',[\Modules\User\Http\Controllers\ActionController::class,'login'])->name('login.action');

Route::prefix('user')->group(function() {
    Route::get('/', [\Modules\User\Http\Controllers\ActionController::class,'index'])->name('user::index');
});

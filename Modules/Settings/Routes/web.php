<?php

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

use Illuminate\Support\Facades\Route;

Route::get('settings', 'PageController@index')->middleware('auth')->name('settings');
Route::post('settings', 'ActionController@save')->middleware('auth')->name('settings.action');

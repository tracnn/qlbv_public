<?php

use Illuminate\Http\Request;
use App\Http\Controllers\PacsController;

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
Route::get('/get-pacs-viewer-link', 'PacsController@getPacsViewerLink')->name('get-pacs-viewer-link');
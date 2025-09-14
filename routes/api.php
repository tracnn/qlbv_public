<?php

use Illuminate\Http\Request;

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

// Existing API routes
Route::get('/get-pacs-viewer-link', 'PacsController@getPacsViewerLink')->name('get-pacs-viewer-link');

// Dashboard API Routes for Sở y tế
Route::middleware(['throttle:60,1', 'api.auth'])->group(function () {
    // Dashboard Statistics APIs
    Route::get('/dashboard/treatment-stats', 'ApiController@getTreatmentStats');
    Route::get('/dashboard/patient-stats', 'ApiController@getPatientStats');
    Route::get('/dashboard/revenue-stats', 'ApiController@getRevenueStats');
    Route::get('/dashboard/transaction-stats', 'ApiController@getTransactionStats');
    Route::get('/dashboard/inpatient-stats', 'ApiController@getInpatientStats');
    Route::get('/dashboard/outpatient-stats', 'ApiController@getOutpatientStats');
    Route::get('/dashboard/average-inpatient-days', 'ApiController@getAverageInpatientDays');
    
    // Detail Data APIs
    Route::get('/treatments', 'ApiController@getTreatments');
    Route::get('/services', 'ApiController@getServices');
    
    // Service-specific APIs
    Route::get('/services/by-type/{id}', 'ApiController@getServicesByType');
    Route::get('/examinations/paraclinical', 'ApiController@getExamParaclinical');
    Route::get('/examinations/imaging', 'ApiController@getDiagnosticImaging');
    Route::get('/examinations/prescription', 'ApiController@getPrescription');
    Route::get('/examinations/fee', 'ApiController@getFee');
    Route::get('/examinations/by-room', 'ApiController@getExaminationsByRoom');
});
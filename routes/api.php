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
    Route::get('/dashboard/disease-by-ward', 'ApiController@getDiseaseByWardStats');
    
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


    Route::get('/medical-center-dashboard', 'MedicalCenterDashboardController@index')->name('medical-center-dashboard.index');
    Route::get('/medical-center-dashboard/data', 'MedicalCenterDashboardController@getDashboardData')->name('medical-center-dashboard.data');
    Route::get('/medical-center-dashboard/kham-benh', 'MedicalCenterDashboardController@getKhamBenh')->name('medical-center-dashboard.kham-benh');
    Route::get('/medical-center-dashboard/noi-tru', 'MedicalCenterDashboardController@getNoiTru')->name('medical-center-dashboard.noi-tru');
    Route::get('/medical-center-dashboard/ngoai-tru', 'MedicalCenterDashboardController@getNgoaiTru')->name('medical-center-dashboard.ngoai-tru');
    Route::get('/medical-center-dashboard/can-lam-sang', 'MedicalCenterDashboardController@getCanLamSang')->name('medical-center-dashboard.can-lam-sang');
    Route::get('/medical-center-dashboard/phau-thuat-thu-thuat', 'MedicalCenterDashboardController@getPhauThuatThuThuat')->name('medical-center-dashboard.phau-thuat-thu-thuat');
    Route::get('/medical-center-dashboard/service-wait-execution-time', 'MedicalCenterDashboardController@getServiceWaitAndExecutionTime')->name('medical-center-dashboard.service-wait-execution-time');
    Route::get('/medical-center-dashboard/thoi-gian-cho-cdha', 'MedicalCenterDashboardController@getThoiGianChoCdha')->name('medical-center-dashboard.thoi-gian-cho-cdha');
    Route::get('/medical-center-dashboard/service-by-type/{id}', 'MedicalCenterDashboardController@getServiceByType')->name('medical-center-dashboard.service-by-type');

    // Tra cứu vi phạm y lệnh theo đợt điều trị (cho HIS/màn hình khác)
    Route::get('order-check/violations', function (\Illuminate\Http\Request $request) {
        $request->validate(['treatment_code' => 'required_without:treatment_id', 'treatment_id' => 'required_without:treatment_code']);

        $q = \App\Models\OrderCheck\OrderCheckViolation::query();
        if ($request->filled('treatment_code')) {
            $q->where('treatment_code', $request->input('treatment_code'));
        }
        if ($request->filled('treatment_id')) {
            $q->where('treatment_id', $request->input('treatment_id'));
        }
        if ($request->filled('status')) {
            $q->where('status', $request->input('status'));
        }

        return response()->json($q->orderBy('detected_at', 'desc')->get([
            'id', 'rule_code', 'severity', 'order_ref_type', 'order_ref_id',
            'message', 'detail', 'status', 'detected_at',
        ]));
    });
});
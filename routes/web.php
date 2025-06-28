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

Route::get('/', 'WelcomeController@index')->name('welcome');
Route::get('get-district-by-city','WelcomeController@getdistrictbycity')->name('get-district-by-city');
Route::get('get-ward-by-district','WelcomeController@getwardbydistrict')->name('get-ward-by-district');

Route::post('medical-register', 'WelcomeController@MedicalRegister')->name('Medical.Register');

Auth::routes();

/* Dashboard */
Route::get('khth/get-sticky-note', 'KHTH\KHTHController@getStickyNote')->name('khth.get-sticky-note');
Route::get('khth/chart-nhapvien', 'KHTH\KHTHController@chartNhapvien')->name('khth.chart_nhapvien');
Route::get('khth/chart-kham', 'KHTH\KHTHController@chartKham')->name('khth.chart_kham');
Route::get('khth/chart-xetnghiem', 'KHTH\KHTHController@chartXetnghiem')->name('khth.chart_xetnghiem');
Route::get('khth/chart-pttt', 'KHTH\KHTHController@chartPttt')->name('khth.chart_pttt');
Route::get('khth/chart-cls', 'KHTH\KHTHController@chartCls')->name('khth.chart_cls');
Route::get('dashboard', 'KHTH\KHTHController@dashboard')->name('khth.dashboard');
/* --Dashboard */

Route::get('/view-emr', 'KHTH\KHTHController@viewEmr')->name('view-emr');
//->middleware('throttle:60,1');

//Route::get('/view-guide', 'KHTH\KHTHController@viewGuide')->name('view-guide');
Route::get('/view-guide', 'PatientController@viewGuide')->name('view-guide');

//Route::get('/view-guide-content', 'KHTH\KHTHController@viewGuideContent')->name('view-guide-content');
Route::get('/view-guide-content', 'PatientController@viewGuideContent')->name('view-guide-content');
//->middleware('throttle:60,1');
Route::get('/view-emr-search', 'KHTH\KHTHController@viewEmr')->name('view-emr-search');
Route::get('/view-document', 'Emr\EmrController@viewDocument')->name('view-document');
//->middleware('throttle:60,1');
Route::get('index/view-doc', 'Emr\EmrController@viewDocByAdmin')->name('view-doc');
//->middleware('throttle:60,1');//Test

Route::group(['middleware' => ['auth', 'check.first.login']], function () {
    //    Route::get('/link1', function ()    {
//        // Uses Auth Middleware
//    });
    //Please do not remove this if you want adminlte:route and adminlte:link commands to works correctly.
    #adminlte_routes
    
    //qhis-plus
    Route::get('/tra-cuu-ls-kcb', 'QHisPlus@index')->name('tra-cuu-ls-kcb-index');
    Route::post('/tra-cuu-ls-kcb', 'QHisPlus@traCuuLsKcb')->name('tra-cuu-ls-kcb');
    Route::get('/tra-cuu-ls-kcb/chi-tiet-ho-so/{id}', 'QHisPlus@chiTietHoSo')->name('chi-tiet-ho-so');

    Route::group(['middleware' => ['checkrole:dashboard']], function () {
        Route::get('fetch-noi-tru', 'HomeController@fetchNoitru')->name('fetch-noi-tru');
        Route::get('fetch-dieu-tri-ngoai-tru', 'HomeController@fetchDieutriNgoaitru')->name('fetch-dieu-tri-ngoai-tru');
        Route::get('fetch-doanh-thu', 'HomeController@fetchDoanhthu')->name('fetch-doanh-thu');
        Route::get('fetch-treatment', 'HomeController@fetchTreatment')->name('fetch-treatment');

        Route::get('fetch-new-patient', 'HomeController@fetchNewpatient')->name('fetch-new-patient');
        Route::get('fetch-chuyen-vien', 'HomeController@fetchChuyenvien')->name('fetch-chuyen-vien');
        Route::get('fetch-service-by-type/{id}', 'HomeController@fetchServiceByType')->name('fetch-service-by-type');

        Route::get('fetch-kham-by-room', 'HomeController@fetchKhamByRoom')->name('fetch-kham-by-room');

        Route::get('fetch-out-treatment-group-treatment-type', 
            'HomeController@fetchOutTreatmentGroupTreatmentType')
        ->name('fetch-out-treatment-group-treatment-type');

        Route::get('home/xml_chart', 'HomeController@xml_chart')->name('home.xml_chart');
        Route::get('fetch-patient-in-room-ngoai-tru', 'HomeController@fetchPatientInRoomDieutriNgoaitru')
        ->name('fetch-patient-in-room-ngoai-tru');
        Route::get('home/treatment_type_chart', 'HomeController@treatment_type_chart')->name('home.treatment_type_chart');
        Route::get('home/treatment_number_chart', 'HomeController@treatment_number_chart')->name('home.treatment_number_chart');
        Route::get('home/top_service_sl_chart', 'HomeController@top_service_sl_chart')->name('home.top_service_sl_chart');
        Route::get('home/top_service_st_chart', 'HomeController@top_service_st_chart')->name('home.top_service_st_chart');
        Route::get('home/noitru_by_department_chart', 'HomeController@noitru_by_department_chart')->name('home.noitru_by_department_chart');
        Route::get('home/noitru_by_patient_type_chart', 'HomeController@noitru_by_patient_type_chart')->name('home.noitru_by_patient_type_chart');

        Route::get('fetch-exam-paraclinical', 'HomeController@fetchExamAndParraclinical')
        ->name('fetch-exam-paraclinical');
        Route::get('fetch-diagnotic-imaging', 'HomeController@fetchDiagnoticImaging')
        ->name('fetch-diagnotic-imaging');
        
        Route::get('fetch-transaction', 'HomeController@fetchTransaction')
        ->name('fetch-transaction');

        Route::get('fetch-average-day-inpatient', 'HomeController@fetchAverageDayInpatient')
        ->name('fetch-average-day-inpatient');

    });
    
    Route::get('/', 'HomeController@index')->name('home');
    Route::get('/home', 'HomeController@index')->name('home');

    /* Báo cáo dịch vụ kỹ thuật */
    Route::get('khth/dich-vu-ky-thuat-index', 'KHTH\KHTHController@TKDichVuKyThuatIndex')->name('khth.dich-vu-ky-thuat-index');
    Route::get('khth/get-danh-muc-dvkt', 'KHTH\KHTHController@GetDanhMucDVKT')->name('khth.get-danh-muc-dvkt');
    Route::get('khth/dich-vu-ky-thuat-index/search', 'KHTH\KHTHController@TKDichVuKyThuatSearch')
        ->name('khth.dich-vu-ky-thuat-search');
    Route::get('khth/get-dvkt', 'KHTH\KHTHController@get_dvkt')->name('khth.get-dvkt');
    Route::get('khth/get-danh-muc-icd', 'KHTH\KHTHController@GetDanhMucICD')->name('khth.get-danh-muc-icd');

    Route::get('khth/dvkt-export-xls', 'KHTH\KHTHController@exportDVKT')->name('khth.dvkt-export-xls');
    Route::get('khth/patient-chart', 'KHTH\KHTHController@patientChart')->name('khth.patient-chart');

    /* /Báo cáo dịch vụ kỹ thuật*/

    //Chart home page

	//Profile User
	Route::get('user/changepass','ChangePasswordController@index')->name('user.changepass');
	Route::post('user/changepass','ChangePasswordController@postCredentials');

	// /* Roles */
	// Route::group(['prefix' => 'user-manager/', 'middleware' => ['role:superadministrator']], function(){
	// 	Route::get('', 'SuperAdministratorController@index')->name('superadministrator.index');
	// });
	// /* /Roles */

    /*
        EMR manage
        //, 'middleware' => ['permission:check-hein-card']
    */
        

    Route::group(['prefix' => 'emr/'], function () {  
        Route::get('index', 'Emr\EmrController@index')->name('emr.index');
        Route::get('index/search', 'Emr\EmrController@search')->name('emr.search');
        Route::get('index/check-emr', 'Emr\EmrController@checkemr')->name('emr.check-emr');
        Route::get('index/get-list-emr-treatment', 'Emr\EmrController@list_emr_treatment')->name('emr.get-list-emr-treatment');
    });
    
    Route::group(['prefix' => 'emr-checker/'], function () {  
        Route::get('emr-checker-detail', 'Emr\EmrCheckerController@indexEmrCheckerDetail')
        ->name('emr-checker.emr-checker-detail');
        Route::get('emr-checker-detail-fetch-data', 'Emr\EmrCheckerController@fetchDataCheckDetail')
        ->name('emr-checker.emr-checker-detail-fetch-data');
        Route::get('emr-checker-index', 'Emr\EmrCheckerController@indexEmrChecker')
        ->name('emr-checker.emr-checker-index');
        Route::get('emr-checker-list', 'Emr\EmrCheckerController@listEmrChecker')
        ->name('emr-checker.emr-checker-list');
        Route::post('emr-checker-index/set-permission', 'Emr\EmrCheckerController@setPermission')
        ->name('emr-checker.set-permission');
        Route::get('emr-checker-bhxh-index', 'Emr\EmrCheckerController@indexEmrCheckerBhxh')
        ->name('emr-checker.emr-checker-bhxh-index');
        Route::get('emr-checker-bhxh-list', 'Emr\EmrCheckerController@listEmrCheckerBhxh')
        ->name('emr-checker.emr-checker-bhxh-list');
        Route::post('emr-checker-bhxh-delete-multiple', 'Emr\EmrCheckerController@deleteMultiple')
        ->name('emr-checker.emr-checker-bhxh-delete-multiple');
    });

    /*
        Treatment result manage
        //, 'middleware' => ['permission:check-hein-card']
    */
    Route::group(['prefix' => 'treatment-result/'], function () {  
        Route::get('index', 'Emr\EmrController@TreatmentResultIndex')->name('treatment-result.index');
        Route::get('index/search', 'Emr\EmrController@TreatmentResultSearch')->name('treatment-result.search');
        Route::get('index/qr-code', 'Emr\EmrController@TreatmentResultQrCode')->name('treatment-result.qr-code');
        Route::post('index/update-phone', 'Emr\EmrController@updatePhone')->name('treatment-result.update-phone');
        Route::get('index/view-mety', 'Emr\EmrController@viewMety')->name('view-mety');
        //Route::get('index/view-doc', 'Emr\EmrController@viewDocByAdmin')->name('view-doc');
    });

	/*
		Manager Medical Register
	*/
	Route::group(['prefix' => 'medreg/', 'middleware' => ['checkrole:manager']], function () {
	    Route::get('', 'MedReg\Manager\MedRegController@index')->name('medreg.index');
	    Route::get('search', 'MedReg\Manager\MedRegController@search')->name('medreg.search');
	    Route::get('view', 'MedReg\Manager\MedRegController@view')->name('medreg.view');
	    Route::get('delete', 'MedReg\Manager\MedRegController@delete')->name('medreg.delete');
	    Route::get('export', 'MedReg\Manager\MedRegController@export')->name('medreg.export');
	});

    /*
        Vaccination
    */
    Route::group(['prefix' => 'vaccination/', 'middleware' => ['checkrole:vaccination']], function () {  
        Route::get('index', 'Vaccination\VaccinationController@index')->name('vaccination.index');
        Route::get('index/data-vaccin', 'Vaccination\VaccinationController@dataVaccin')->name('vaccination.data');
        Route::get('get-vaccin', 'Vaccination\VaccinationController@get_vaccin')->name('vaccination.get-vaccin');
        //Route::post('create', 'Vaccination\VaccinationController@create')->name('vaccination.create');
        //Route::post('update', 'Vaccination\VaccinationController@update')->name('vaccination.update');
        //Route::delete('delete', 'Vaccination\VaccinationController@delete')->name('vaccination.delete');
        
        Route::resource('vaccines', 'Vaccination\VaccineController');
        Route::resource('patients', 'Vaccination\PatientController');
        Route::resource('pre_vaccination_checks', 'Vaccination\PreVaccinationCheckController');

        Route::get('index/pre_vaccination_checks/create/{patient_id}', 
            'Vaccination\PreVaccinationCheckController@create')
        ->name('pre_vaccination_checks.create');
        Route::post('index/pre_vaccination_checks/store/{patient_id}', 
            'Vaccination\PreVaccinationCheckController@store')
        ->name('pre_vaccination_checks.store');
        Route::get('index/pre_vaccination_checks/edit/{id}', 'Vaccination\PreVaccinationCheckController@edit')
        ->name('prevaccination.edit');
        Route::delete('index/pre_vaccination_checks/destroy/{id}', 
            'Vaccination\PreVaccinationCheckController@destroy')
        ->name('prevaccination.destroy');

        Route::get('index/vaccination.fetch-vaccinations', 'Vaccination\VaccinationController@fetchVaccinations')
        ->name('vaccination.fetch-vaccinations');

        Route::get('index/create/{patient_id}', 'Vaccination\VaccinationController@createVaccination')
        ->name('vaccination.create');
        Route::post('index/store/{patient_id}', 'Vaccination\VaccinationController@storeVaccination')->name('vaccination.store');
        Route::get('index/edit/{id}', 'Vaccination\VaccinationController@editVaccination')->name('vaccination.edit');
        Route::put('index/update/{id}', 'Vaccination\VaccinationController@updateVaccination')->name('vaccination.update');
        Route::delete('index/destroy/{id}', 'Vaccination\VaccinationController@destroyVaccination')->name('vaccination.destroy');
    });

    /*
        KSK
    */
    Route::group(['prefix' => 'ksk/', 'middleware' => ['checkrole:ksk']], function () {  
        Route::get('index', 'KHTH\KskController@index')->name('ksk.index');
        Route::get('index/get-danh-sach', 'KHTH\KskController@get_danhsach')->name('ksk.get-danh-sach');
        Route::post('index/ksk-kham-the-luc', 'KHTH\KskController@khamtheluc')->name('ksk.kham-the-luc');
        Route::post('index/ksk-kham-noi', 'KHTH\KskController@khamnoi')->name('ksk.kham-noi');
        Route::post('index/ksk-kham-rhm', 'KHTH\KskController@khamrhm')->name('ksk.kham-rhm');
        Route::post('index/ksk-kham-tmh', 'KHTH\KskController@khamtmh')->name('ksk.kham-tmh');
        Route::post('index/ksk-kham-san', 'KHTH\KskController@khamsan')->name('ksk.kham-san');
        Route::post('index/ksk-kham-mat', 'KHTH\KskController@khammat')->name('ksk.kham-mat');
        Route::post('index/ksk-kham-tongket', 'KHTH\KskController@khamtongket')->name('ksk.kham-tongket');
        Route::get('index/ksk-export-xls', 'KHTH\KskController@exportXLS')->name('ksk.export-xls');
        Route::post('index/ksk-tiepdon', 'KHTH\KskController@tiepdon')->name('ksk.tiepdon');
        Route::get('index/ksk-kq-cls', 'KHTH\KskController@kqcls')->name('ksk.kq-cls');
        Route::get('index/ksk-check-emr', 'KHTH\KskController@checkemr')->name('ksk.check-emr');
        Route::get('index/ksk-download-avatar', 'KHTH\KskController@downloadAvatar')->name('ksk.download-avatar');
        Route::get('index/ksk-get-patient', 'KHTH\KskController@getPatient')->name('ksk.get-patient');
        Route::get('index/ksk-get-check-emr', 'KHTH\KskController@getCheckEmr')->name('ksk.get-check-emr');
        Route::post('index/ksk-tu-van', 'KHTH\KskController@tuvan')->name('ksk.tuvan');

        Route::get('index/category-ksk-contract', 'Category\CategoryHISController@listKskContract')
        ->name('category-his.fetch-ksk-contract');
    });

    Route::get('index/category-department-catalog', 'Category\CategoryHISController@listDepartmentCatalog')
    ->name('category-his.fetch-department-catalog');
    Route::get('index/category-patient-type', 'Category\CategoryHISController@listPatientType')
    ->name('category-his.fetch-patient-type');
    Route::get('index/category-treatment-type', 'Category\CategoryHISController@listTreatmentType')
    ->name('category-his.fetch-treatment-type');
    Route::get('index/category-treatment-end-type', 'Category\CategoryHISController@listTreatmentEndType')
    ->name('category-his.fetch-treatment-end-type');
    Route::get('index/category-document-type', 'Category\CategoryHISController@listDocumentType')
    ->name('category-his.fetch-document-type');
	/*
		Category
	*/
    Route::group(['prefix' => 'category/', 'middleware' => ['checkrole:category-manager']], function () { 	
    	Route::get('{category}', 'Category\Manager\CategoryController@index')->name('category.index');
    	Route::get('{category}/search', 'Category\Manager\CategoryController@search')->name('category.search');

        //fetch category to partials blade
        Route::get('bhyt/category-bhyt-fetch-xml-error-catalog', 'Category\CategoryBHYTController@fetchXmlErrorCatalog')
        ->name('category-bhyt.fetch-xml-error-catalog');
        Route::get('bhyt/category-bhyt-fetch-qd130-xml-error-catalog', 'Category\CategoryBHYTController@fetchQd130XmlErrorCatalog')
        ->name('category-bhyt.fetch-qd130-xml-error-catalog');
        //end

        Route::get('bhyt/medicine-catalog', 'Category\CategoryBHYTController@indexMedicineCatalog')
        ->name('category-bhyt.medicine-catalog');
        Route::get('bhyt/fetch-medicine-catalog', 'Category\CategoryBHYTController@fetchMedicineCatalog')
        ->name('category-bhyt.fetch-medicine-catalog');

        Route::get('bhyt/medical-supply-catalog', 'Category\CategoryBHYTController@indexMedicalSupplyCatalog')
        ->name('category-bhyt.medical-supply-catalog');
        Route::get('bhyt/fetch-medical-supply-catalog', 'Category\CategoryBHYTController@fetchMedicalSupplyCatalog')
        ->name('category-bhyt.fetch-medical-supply-catalog');

        Route::get('bhyt/service-catalog', 'Category\CategoryBHYTController@indexServiceCatalog')
        ->name('category-bhyt.service-catalog');
        Route::get('bhyt/fetch-service-catalog', 'Category\CategoryBHYTController@fetchServiceCatalog')
        ->name('category-bhyt.fetch-service-catalog');

        Route::get('bhyt/medical-staff', 'Category\CategoryBHYTController@indexMedicalStaff')
        ->name('category-bhyt.medical-staff');
        Route::get('bhyt/fetch-medical-staff', 'Category\CategoryBHYTController@fetchMedicalStaff')
        ->name('category-bhyt.fetch-medical-staff');

        Route::get('bhyt/department-bed-catalog', 'Category\CategoryBHYTController@indexDepartmentBedCatalog')
        ->name('category-bhyt.department-bed-catalog');
        Route::get('bhyt/fetch-department-bed-catalog', 'Category\CategoryBHYTController@fetchDepartmentBedCatalog')
        ->name('category-bhyt.fetch-department-bed-catalog');

        Route::get('bhyt/equipment-catalog', 'Category\CategoryBHYTController@indexEquipmentCatalog')
        ->name('category-bhyt.equipment-catalog');
        Route::get('bhyt/fetch-equipment-catalog', 'Category\CategoryBHYTController@fetchEquipmentCatalog')
        ->name('category-bhyt.fetch-equipment-catalog');
        Route::get('bhyt/qd130-xml-error-catalog', 'Category\CategoryBHYTController@indexQd130XmlErrorCatalog')
        ->name('category-bhyt.qd130-xml-error-catalog');
        Route::get('bhyt/category-bhyt-fetch-qd130-xml-error-catalog-datatable', 'Category\CategoryBHYTController@fetchQd130XmlErrorCatalogDatatable')
        ->name('category-bhyt.fetch-qd130-xml-error-catalog-datatable');
        Route::post('/category-bhyt/update-qd130-xml-error-catalog', 'Category\CategoryBHYTController@updateQd130XmlErrorCatalog')
        ->name('category-bhyt.update-qd130-xml-error-catalog');

        Route::get('bhyt/category-bhyt-import-index', 'Category\CategoryBHYTController@importIndex')
        ->name('category-bhyt.import-index');
        Route::post('bhyt/category-bhyt-import', 'Category\CategoryBHYTController@import')
        ->name('category-bhyt.import');
    });

	/* Insurance , 'middleware' => ['checkrole:manager'] */
    Route::group(['prefix' => 'insurance/'], function () { 	
    	Route::get('check-card', 'Insurance\Manager\InsuranceController@checkCard')->name('insurance.check-card');
    	Route::get('check-card/search', 'Insurance\Manager\InsuranceController@search')->name('insurance.check-card.search');
    	Route::get('check-card/getqrcode', 'Insurance\Manager\InsuranceController@getqrcode')->name('insurance.check-card.getqrcode');

        Route::get('medicine-search', 'Insurance\Manager\MedicineSearchController@index')->name('insurance.medicine-search');
        Route::get('insurance.medicine-search.get-data', 'Insurance\Manager\MedicineSearchController@getdata')->name('insurance.medicine-search.get-data');

    	Route::group(['prefix' => 'check-entered'], function (){
    		Route::get('outpatient', 'Insurance\Manager\CheckEnteredController@checkEnteredOutpatient')->name('insurance.check-entered.outpatient');
    		
            /* Inpatient */
            Route::get('inpatient', 'Insurance\Manager\CheckInpatientController@index')->name('insurance.check-entered.inpatient');
            Route::get('inpatient/search', 'Insurance\Manager\CheckInpatientController@search')->name('insurance.check-entered.inpatient.search');
            /* /Inpatient */

    		/* Database for BHYT */
    		Route::get('insurance', 'Insurance\Manager\CheckEnteredController@checkEnteredInsurance')->name('insurance.check-entered.insurance');
    		Route::get('insurance/search', 'Insurance\Manager\CheckEnteredController@searchEnteredInsurance')->name('insurance.check-entered.insurance.search');
    		Route::get('insurance/detail', 'Insurance\Manager\CheckEnteredController@detailEnteredInsurance')->name('insurance.check-entered.insurance.detail');
    		Route::get('insurance/check-bussines-rules', 'Insurance\Manager\CheckEnteredController@checkBussinesRules')->name('insurance.check-entered.insurance.check-bussines-rules');
            Route::get('report', 'Insurance\Manager\CheckEnteredController@reportBussinesRules')->name('insurance.check-entered.report');
            Route::get('report/search', 'Insurance\Manager\CheckEnteredController@searchReportBussinesRules')->name('insurance.check-entered.report.search');

    		/* /Database for BHYT */
    	});
    	
    });
    /* /Insurance */

    /* Insurance card*/
    Route::group(['prefix' => 'insurance-card/', 'middleware' => ['checkrole:superadministrator']], function () {
        Route::get('add-new', 'Insurance\Manager\InsuranceCardController@addnew')->name('insurance-card.add-new');
        Route::post('add-new', 'Insurance\Manager\InsuranceCardController@store')->name('insurance-card.store');
        Route::get('index', 'Insurance\Manager\InsuranceCardController@index')->name('insurance-card.index');
        Route::get('index/search', 'Insurance\Manager\InsuranceCardController@search')->name('insurance-card.search');
        Route::get('index/detail', 'Insurance\Manager\InsuranceCardController@detail')->name('insurance-card.detail');
        Route::get('index/delete', 'Insurance\Manager\InsuranceCardController@delete')->name('insurance-card.delete');
    });
    /* /Insurance card*/

    /* User function */
    Route::group(['prefix' => 'system/', 'middleware' => ['checkrole:superadministrator']], function () {
        Route::get('user-function', 'System\SystemController@index')->name('system.user-function.index');
        Route::get('user-function/search', 'System\SystemController@search')->name('system.user-function.search');
        Route::get('user-function/detail-inpatient-bill', 'System\SystemController@detailInpatientBill')->name('system.user-function.detail-inpatient-bill');
        Route::post('user-function/check-card', 'System\SystemController@checkCard')->name('system.user-function.check-card');

        Route::get('check-queue-work', 'System\SystemController@checkQueueWork')->name('system.check-queue-work');
        Route::get('check-error', 'System\CheckErrorController@index')->name('system.check-error');
        Route::get('check-error/search', 'System\CheckErrorController@search')->name('system.check-error.search');

        /* Upload XML */
        Route::get('upload-xml', 'System\UploadXML@uploadXML')->name('system.upload-xml');
        Route::post('upload-xml', 'System\UploadXML@doUploadXML')->name('system.do-upload-xml');

        /* System parameter */
        Route::get('sys-param', 'System\SystemController@sysparam')->name('system.sys-param');
        Route::post('sys-param', 'System\SystemController@editSysparam')->name('system.edit-sys-param');

        /* Tracnn Important */
        Route::post('entry-remove', 'System\SystemController@entry_remove')->name('system.entry-remove');
        Route::post('entry-update', 'System\SystemController@entry_update')->name('system.entry-update');
        Route::post('entry-plus', 'System\SystemController@entry_plus')->name('system.entry-plus');
        Route::post('entry-minus', 'System\SystemController@entry_minus')->name('system.entry-minus');
        Route::post('entry-open', 'System\SystemController@entry_open')->name('system.entry-open');
		
        /* System mannager */
        Route::get('sys-man', 'System\SystemController@sysMan')->name('system.sys-man');
    });

    /* Hồ sơ BHYT */
    Route::group(['prefix' => 'bhyt/', 'middleware' => ['checkrole:xml-man']], function () {
        Route::get('index/category-imported-by', 'Category\CategoryHISController@fetchImportedBy')
        ->name('category-his.fetch-imported-by');

        /* BHYT */
        Route::get('index', 'BHYT\BHYTController@index')->name('bhyt.index');
        Route::get('index/search', 'BHYT\BHYTController@searchXML')->name('bhyt.search');
        Route::get('index/get-xml', 'BHYT\BHYTController@getxml')->name('bhyt.get-xml');
        Route::get('index/check-card','BHYT\BHYTController@checkcard')->name('bhyt.check-card');
        Route::get('index/process-check-card','BHYT\BHYTController@processCheckcard')->name('bhyt.process-check-card');
        Route::get('index/detailxml/{ma_lk}', 'BHYT\BHYTController@detailxml')->name('bhyt.detailxml');

        Route::get('index/kcb-trai-tuyen','BHYT\BHYTController@kcbtraituyen')->name('bhyt.kcb-trai-tuyen');
        Route::get('index/dvkt-co-dieu-kien', 'BHYT\BHYTController@dvktcodieukien')->name('bhyt.dvkt-co-dieu-kien');
        Route::get('index/thuoc-co-dieu-kien', 'BHYT\BHYTController@thuoccodieukien')->name('bhyt.thuoc-co-dieu-kien');
        Route::delete('xml-delete/{ma_lk}', 'BHYT\BHYTController@xml_delete')->name('xml.delete');

        Route::get('qd130/index', 'BHYT\BHYTQd130Controller@index')->name('bhyt.qd130.index');
        Route::get('qd130/index/fetch-data', 'BHYT\BHYTQd130Controller@fetchData')->name('bhyt.qd130.fetch-data');
        Route::get('qd130/import/index', 'BHYT\BHYTQd130Controller@importIndex')->name('bhyt.qd130.import.index');
        Route::post('qd130/index/upload-data', 'BHYT\BHYTQd130Controller@uploadData')->name('bhyt.qd130.upload-data');
        Route::get('qd130/index/detail-xml/{ma_lk}', 'BHYT\BHYTQd130Controller@detailXml')->name('bhyt.qd130.detail-xml');

        Route::post('qd130/export-xml', 'BHYT\BHYTQd130Controller@exportXml')
        ->name('bhyt.qd130.export-xml')
        ->middleware('checkrole:superadministrator');;
        Route::get('qd130/export-qd130-xml-errors', 'BHYT\BHYTQd130Controller@exportQd130XmlErrors')
        ->name('bhyt.qd130.export-qd130-xml-errors');
        Route::delete('qd130/delete-xml/{ma_lk}', 'BHYT\BHYTQd130Controller@deleteXml')->name('bhyt.qd130.delete-xml');
        Route::get('qd130/export-qd130-xml-xlsx', 'BHYT\BHYTQd130Controller@exportQd130XmlXlsx')
        ->name('bhyt.qd130.export-qd130-xml-xlsx');
        Route::get('qd130/job-status', 'BHYT\BHYTQd130Controller@checkJobStatus')
        ->name('bhyt.qd130.jobs.status');
        Route::get('qd130/export-7980a-data', 'BHYT\BHYTQd130Controller@export7980aData')
        ->name('bhyt.qd130.export-7980a-data');
    });

    /*
        Danh mục BHYT
    */
    Route::group(['prefix' => 'danh-muc/', 'middleware' => ['checkrole:superadministrator']], function () {  
        Route::get('dvkt-co-dieu-kien','Category\Manager\CategoryController@dvktCoDieuKien')->name('danh-muc.dvkt-co-dieu-kien');
        Route::post('dvkt-co-dieu-kien', 'Category\Manager\CategoryController@updateDvktCoDieuKien')
            ->name('danh-muc.update-dvkt-co-dieu-kien');
        Route::get('dm-thuoc-co-dieu-kien','Category\Manager\CategoryController@dmtCoDieuKien')
        ->name('danh-muc.dm-thuoc-co-dieu-kien');
        Route::post('dm-thuoc-co-dieu-kien','Category\Manager\CategoryController@updateDmtCoDieuKien')
            ->name('danh-muc.update-dm-thuoc-co-dieu-kien');
        Route::get('dm-khoa-phong','Category\Manager\CategoryController@dmKhoaphong')->name('danh-muc.dm-khoa-phong');       
    });

    Route::get('get-user-id', 'System\SystemController@getUserId')->name('system.get-user-id');
    /* /User function */

    /*
        KHTH
    */
    Route::group(['prefix' => 'khth/', 'middleware' => ['checkrole:administrator']], function () {  
        Route::get('so-luot-kham-index','KHTH\KHTHController@SoLuotKhamIndex')->name('khth.so-luot-kham-index');  
        Route::get('so-luot-kham-index/get-data','KHTH\KHTHController@SoLuotKhamGetData')->name('khth.so-luot-kham-get-data');

        Route::get('chi-phi-kham-benh-index','KHTH\KHTHController@ChiPhiKhamBenhIndex')->name('khth.chi-phi-kham-benh-index');  
        Route::get('chi-phi-kham-benh-index/get-chi-phi','KHTH\KHTHController@getChiphiKCB')->name('khth.chi-phi-kham-benh-get-chi-phi');

        Route::get('dieu-tri-noi-tru-index','KHTH\KHTHController@DieuTriNoiTruIndex')->name('khth.dieu-tri-noi-tru-index');  
        Route::get('dieu-tri-noi-tru-index/search','KHTH\KHTHController@DieuTriNoiTruSearch')->name('khth.dieu-tri-noi-tru-search');
        Route::get('noi-tru-theo-khoa-index','KHTH\KHTHController@NoiTruTheoKhoaIndex')->name('khth.noi-tru-theo-khoa-index');  
        Route::get('noi-tru-theo-khoa-index/search','KHTH\KHTHController@NoiTruTheoKhoaSearch')->name('khth.noi-tru-theo-khoa-search');
        Route::get('xet-nghiem-chan-doan-index','KHTH\KHTHController@XetNghiemChanDoan')->name('khth.xet-nghiem-chan-doan-index');

        Route::get('cong-van-19031-index','KHTH\KHTHController@CongVan19031Index')->name('khth.cong-van-19031-index');  
        Route::get('cong-van-19031-index/search','KHTH\KHTHController@CongVan19031Search')->name('khth.cong-van-19031-search');

        Route::get('sticky-note', 'KHTH\KHTHController@stickyNote')->name('khth.sticky-note');
        Route::post('save-sticky-note', 'KHTH\KHTHController@saveStickyNote')->name('khth.save-sticky-note');

        Route::get('bn-sar-cov-2-index','KHTH\KHTHController@BNSarCov2Index')->name('khth.bn-sar-cov-2-index');
        Route::get('get-sar-cov-2','KHTH\KHTHController@getsarcov2')->name('khth.chart_sarcov2');
        Route::get('get-result','KHTH\KHTHController@get_result')->name('khth.get-result');
        Route::get('get-sarcov2-ct','KHTH\KHTHController@get_sarcov2_ct')->name('khth.get-sarcov2-ct');
        Route::get('thong-ke-in-index','KHTH\KHTHController@thongkein')->name('khth.thong-ke-in-index');

        /*Gia tang chi phi theo ND75*/
        Route::get('gia-tang-chi-phi-index','KHTH\KHTHController@giatangchiphi')->name('khth.gia-tang-chi-phi-index');
        Route::get('khth.chi-phi-nd75-processing','KHTH\KHTHController@fetchChiphiND75')->name('khth.chi-phi-nd75-processing');
        /*End*/
        
        /* Super Administrator */
        Route::get('thong-ke-noitru-index','KHTH\KHTHController@thongkenoitru')->name('khth.thong-ke-noitru-index')
        ->middleware('checkrole:superadministrator');
        Route::get('inpatient-processing','KHTH\KHTHController@inpatientProcessing')->name('khth.inpatient-processing')
        ->middleware('checkrole:superadministrator');
        Route::get('thong-ke-doanh-thu-index','KHTH\KHTHController@thongkedoanhthu')->name('khth.thong-ke-doanh-thu-index')
        ->middleware('checkrole:superadministrator');
        Route::get('revenue-processing','KHTH\KHTHController@getrevenue')->name('khth.revenue-processing')
        ->middleware('checkrole:superadministrator');

    });

    Route::group(['prefix' => 'queue'], function () { 
        Route::get('/', 'QueueNumberController@index')->name('queue.index');
        Route::post('/register','QueueNumberController@register')->name('queue.register');
        Route::get('/manage','QueueNumberController@manager')->name('queue.manage');
        Route::get('/ticket-print','QueueNumberController@printTicket')->name('queue.ticket.print');
    });

    /*
        User
    */
    Route::group(['prefix' => 'users/', 'middleware' => ['checkrole:superadministrator']], function () {
        Route::get('/index', 'UserController@index')->name('users.index');
        Route::get('/get-users', 'UserController@getUser')->name('users.get-users');
        Route::get('/{id}/permissions', 'UserController@editPermissions')->name('users.edit_permissions');
        Route::post('/{id}/permissions', 'UserController@updatePermissions')->name('users.update_permissions');
        Route::get('/{id}/roles', 'UserController@editRoles')->name('users.edit_roles');
        Route::post('/{id}/roles', 'UserController@updateRoles')->name('users.update_roles');
    });

    /* Thu ngân */
    Route::group(['prefix' => 'accountant/', 'middleware' => ['checkrole:thungan']], function () {  
        Route::post('{id}/checkout', 'AccountantController@checkout')->name('accountant.checkout');
        Route::post('{id}/deposit', 'AccountantController@deposit')->name('accountant.deposit');
        Route::get('broadcast', 'AccountantController@broadcast')->name('accountant.broadcast');
        Route::get('clearBroadcast', 'AccountantController@clearBroadcast')->name('accountant.clearBroadcast');
        Route::post('save-payment', 'AccountantController@savePayment')->name('accountant.save-payment');
        Route::get('payment-report', 'AccountantController@paymentReport')->name('accountant.payment-report');
        Route::get('get-payment', 'AccountantController@getPayment')->name('accountant.get-payment');
        Route::get('export-payment', 'AccountantController@exportPaymentExcel')->name('accountant.export-payment');

    });

    /* Reports Dược*/
    Route::group(['prefix' => 'reports-duoc/', 'middleware' => ['checkrole:duoc']], function () {  
        Route::get('su-dung-thuoc-index', 'ReportController@indexDrug')
        ->name('reports-duoc.su-dung-thuoc-index');
        Route::get('fetch-drug-use', 'ReportController@fetchDrugUse')
        ->name('reports-duoc.fetch-drug-use');
        Route::get('export-drugs-use', 'ReportController@exportDrugUse')
        ->name('reports-duoc.export-drugs-use');
    });

    /* Reports Administrator*/
    Route::group(['prefix' => 'reports-administrator/', 'middleware' => ['checkrole:administrator']], function () {  
        Route::get('clinic-visit-cost-index', 'ReportController@indexCVCRReport')
        ->name('reports-administrator.clinic-visit-cost-index');
        Route::get('fetch-cvcr', 'ReportController@fetchCVCRData')
        ->name('reports-administrator.fetch-cvcr');

        Route::get('number-drug-prescription-index', 'ReportController@indexNDPReport')
        ->name('reports-administrator.number-drug-prescription-index');
        Route::get('fetch-ndp', 'ReportController@fetchNDPData')
        ->name('reports-administrator.fetch-ndp');

        Route::get('export-ndp-data', 'ReportController@exportNDPData')
        ->name('reports-administrator.export-ndp-data');
    });

    /* Reports Account Payment*/
    Route::group(['prefix' => 'reports-administrator/', 'middleware' => ['checkrole:thungan-tonghop']], function () {  
        Route::get('accoutant-payment-index', 'ReportController@paymentAccountant')
        ->name('reports-administrator.accoutant-payment-index');
        Route::get('fetch-accoutant-payment', 'ReportController@fetchPaymentAccountant')
        ->name('reports-administrator.fetch-accoutant-payment');
        Route::get('export-accoutant-payment-data', 'ReportController@exportAPData')
        ->name('reports-administrator.export-accoutant-payment-data');

        Route::get('accoutant-debt-index', 'ReportController@debtAccountant')
        ->name('reports-administrator.accoutant-debt-index');
        Route::get('fetch-accoutant-debt', 'ReportController@fetchAccountantDebt')
        ->name('reports-administrator.fetch-accoutant-debt');
        Route::get('export-debt-data', 'ReportController@exportDebtData')
        ->name('reports-administrator.export-debt-data');
        Route::get('accoutant-revenue-index', 'ReportController@accoutantRevenue')
        ->name('reports-administrator.accoutant-revenue-index');
        Route::get('fetch-accoutant-revenue', 'ReportController@fetchAccountantRevenue')
        ->name('reports-administrator.fetch-accountant-revenue');
        Route::get('export-accountant-revenue-data', 'ReportController@exportAccountantRevenue')
        ->name('reports-administrator.export-accountant-revenue-data');
        Route::get('export-accountant-revenue-data-detail', 'ReportController@exportAccountantRevenueDetail')
        ->name('reports-administrator.export-accountant-revenue-data-detail');
    });

    /* Reports Account Payment*/
    Route::group(['prefix' => 'reports-administrator/', 'middleware' => ['checkrole:qlcl']], function () {  
        Route::get('list-patient-pt', 'ReportController@listPatientPt')
        ->name('reports-administrator.list-patient-pt');
        Route::get('fetch-patient-pt', 'ReportController@fetchPatientPt')
        ->name('reports-administrator.fetch-patient-pt');

        Route::get('index-patient-count-by-department', 'ReportController@indexPatientCountByDepartment')
        ->name('reports-administrator.index-patient-count-by-department');
        Route::get('fetch-patient-count-by-department', 'ReportController@fetchPatientCountByDepartment')
        ->name('reports-administrator.fetch-patient-count-by-department');
    });

    /* Nurse Module*/
    Route::group(['prefix' => 'nurse/', 'middleware' => ['checkrole:dieu-duong']], function () {  
        Route::get('execute/medication/order/index', 'NurseController@executeMedicationOrderIndex')
        ->name('nurse.execute.medication.order.index');
        Route::get('execute/medication/fetch/data', 'NurseController@fetchDataNurseExecute')
        ->name('nurse.execute.medication.fetch.data');
    });

    /* Quản lý hồ sơ bệnh án */
    Route::group(['prefix' => 'bhxh/', 'middleware' => ['checkrole:bhxh']], function () {
        Route::get('index', 'BhxhController@index')->name('bhxh.index');
        Route::get('index/emr-checker-list', 'BhxhController@listEmrChecker')
        ->name('bhxh.emr-checker-list');
        Route::get('index/emr-checker-detail', 'BhxhController@emrCheckerDetail')
        ->name('bhxh.emr-checker-detail');
        Route::get('index/emr-checker-document-list', 'BhxhController@emrCheckerDocumentList')
        ->name('bhxh.emr-checker-document-list');
    });

});

Route::get('/api/view-pdf', 'Emr\EmrController@viewPdf')->name('api.view-pdf');
Route::get('/api/secure-view-pdf', 'Emr\EmrController@securePdfView')->name('api.secure-view-pdf');
Route::get('/secure-view-doc', 'Emr\EmrController@viewDocByToken')->name('secure-view-doc');
Route::get('/encrypt-token', 'PatientController@encryptToken');
Route::get('/encrypt-token-general', 'PatientController@encryptTokenGeneral');
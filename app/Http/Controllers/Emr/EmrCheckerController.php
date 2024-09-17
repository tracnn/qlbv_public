<?php

namespace App\Http\Controllers\Emr;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Services\CheckEmrService;

class EmrCheckerController extends Controller
{
    protected $checkEmrService;

    // Inject CheckService vào controller
    public function __construct(CheckEmrService $checkEmrService)
    {
        $this->checkEmrService = $checkEmrService;
    }

    public function indexEmrCheckerDetail()
    {
        return view('emr-checker.emr-checker-detail');
    }

    public function fetchDataCheckDetail(Request $request)
    {
        $treatment_code = $request->input('treatment_code');

        if ($treatment_code) {
             // Lấy thông tin chi tiết từ service
            $results = $this->checkEmrService->getTreatmentDetails($treatment_code);
            
             // Trả về view đã được render
            $html = view('emr-checker.partials.treatment-detail', compact('results'))->render();

            if ($results->isNotEmpty()) {
                //Kiểm tra nghiệp vụ
                $html .= $this->getPartialViewBasedOnPermission($treatment_code);
            }

            // Hiển thị đơn thuốc ngoại trú nếu có quyền
            if (auth()->user()->can('emr-check-medicine-outpatient')) {
                $medicine_results = $this->checkEmrService->getMedicineOutpatientDetails($treatment_code);

                if ($medicine_results->isNotEmpty()) {
                    $html .= view('emr-checker.partials.medicine-outpatient-detail', compact('medicine_results'))->render();
                }
            }
            
            return response($html);
        } else {
            return response()->json(['error' => 'Treatment code is required'], 400);
        }
    }

    public function getPartialViewBasedOnPermission($treatment_code)
    {
        $html = '';

        // Kiểm tra quyền 'emr-check-bangke' và 'emr-check-bangke-signer'
        if (auth()->user()->can('emr-check-bangke')) {
            $html .= $this->checkEmrService->checkBangKeAndSigner($treatment_code);
        }

        // Kiểm tra quyền 'emr-check-accountant'
        if (auth()->user()->can('emr-check-accountant')) {
            $html .= $this->checkEmrService->checkAccountant($treatment_code);
        }

        // Kiểm tra quyền 'emr-check-general-info'
        if (auth()->user()->can('emr-check-general-info')) {
            $html .= $this->checkEmrService->checkGeneralInfo($treatment_code);
        }

        // Kiểm tra quyền 'emr-check-bbhc-info'
        if (auth()->user()->can('emr-check-bbhc-info')) {
            $html .= $this->checkEmrService->checkBbhcInfo($treatment_code);
        }

        // Nếu không có quyền nào được kiểm tra
        if (empty($html)) {
            $messages = $this->checkEmrService->getMessages();
            $html = $messages['no_permission']['error'];
        }

        return $html;
    }
}

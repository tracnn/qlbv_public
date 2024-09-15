<?php

namespace App\Http\Controllers\Emr;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use DB;

class EmrCheckerController extends Controller
{
    public function indexEmrCheckerDetail()
    {
        return view('emr-checker.emr-checker-detail');
    }

    public function fetchDataCheckDetail(Request $request)
    {
        $treatment_code = $request->input('treatment_code');

        if ($treatment_code) {
            // Sử dụng Query Builder của Laravel để thực thi câu lệnh SQL
            $results = DB::connection('HISPro')
                ->table('his_treatment as tm')
                ->where('tm.treatment_code', $treatment_code)
                ->get();
            
             // Trả về view đã được render
            $html = view('emr-checker.partials.treatment-detail', compact('results'))->render();

            if ($results->isNotEmpty()) {
                //Kiểm tra nghiệp vụ
                $html .= $this->getPartialViewBasedOnPermission($treatment_code);
            }

            return response($html);
        } else {
            return response()->json(['error' => 'Treatment code is required'], 400);
        }
    }

    public function getPartialViewBasedOnPermission($treatment_code)
    {
        // Mảng chứa các thông báo kiểm tra và mã lỗi tương ứng
        $messages = [
            'emr-check-bangke-signer' => [
                'check' => '<h3>Bảng kê thanh toán: Chữ ký của bệnh nhân</h3>',
                'error' => '<label class="alert alert-danger">Bảng kê chưa có chữ ký của bệnh nhân</label>',
                'success' => '<label class="alert alert-success">Bảng kê đã có chữ ký của bệnh nhân</label>',
            ],
            'emr-check-bangke' => [
                'check' => '<h3>Bảng kê thanh toán:</h3>',
                'error' => '<label class="alert alert-danger">Chưa tạo bảng kê</label>',
                'success' => '<label class="alert alert-success">Đã tạo bảng kê</label>',
            ],
            'no_permission' => [
                'error' => '<div class="alert alert-danger"><strong>Bạn chưa được phân quyền để kiểm tra hồ sơ</strong></div>',
            ]
        ];

        // Mảng chứa các partial view được kết hợp lại
        $html = '';

        // Kiểm tra quyền 'emr-check-bangke' trước
        if (auth()->user()->can('emr-check-bangke')) {
            $html .= $messages['emr-check-bangke']['check'];

            // Kiểm tra xem đã tạo bảng kê hay chưa
            $documentExists = DB::connection('EMR_RS')
                ->table('emr_document')
                ->where('treatment_code', $treatment_code)
                ->where('document_type_id', 28)
                ->where('is_delete', 0)
                ->exists(); // Kiểm tra xem có bảng kê nào không

            // Nếu chưa tạo bảng kê
            if (!$documentExists) {
                $html .= $messages['emr-check-bangke']['error'];
                return $html; // Nếu chưa tạo bảng kê thì dừng lại và trả về thông báo
            }

            // Nếu đã tạo bảng kê, hiển thị thông báo thành công
            $html .= $messages['emr-check-bangke']['success'];

            // Sau khi kiểm tra bảng kê, tiếp tục kiểm tra chữ ký nếu có quyền 'emr-check-bangke-signer'
            if (auth()->user()->can('emr-check-bangke-signer')) {
                $html .= $messages['emr-check-bangke-signer']['check'];

                // Kiểm tra xem bảng kê có chữ ký của bệnh nhân hay chưa
                $hasSignature = DB::connection('EMR_RS')
                    ->table('emr_document')
                    ->where('treatment_code', $treatment_code)
                    ->where('document_type_id', 28)
                    ->where('is_delete', 0)
                    ->where('signers', 'NOT LIKE', '%#@!@#%')
                    ->exists(); // Kiểm tra xem có bản ghi nào không có chữ ký

                // Sử dụng mã lỗi để xác định thông báo và nối vào dòng
                if ($hasSignature) {
                    $html .= $messages['emr-check-bangke-signer']['error']; // Bảng kê chưa có chữ ký
                } else {
                    $html .= $messages['emr-check-bangke-signer']['success']; // Bảng kê đã có chữ ký
                }
            }
        }

        // Nếu không có bất kỳ quyền nào được kiểm tra
        if (empty($html)) {
            // Thông báo lỗi lớn và rõ ràng nếu không có quyền
            $html = $messages['no_permission']['error'];
        }

        // Trả về HTML đã được kết hợp
        return $html;
    }

}

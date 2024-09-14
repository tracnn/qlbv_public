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

            //Kiểm tra nghiệp vụ
            $html .= $this->getPartialViewBasedOnPermission($treatment_code);

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
                'check' => '<h3>Kiểm tra chữ ký bệnh nhân trên bảng kê</h3>',
                'error' => '<label class="alert alert-danger">Bảng kê chưa có chữ ký của bệnh nhân</label>',
                'success' => '<label class="alert alert-success">Bảng kê đã có chữ ký của bệnh nhân</label>',
            ],
            'emr-check-bangke' => [
                'check' => 'Kiểm tra bảng kê:',
                // Bạn có thể thêm thông báo lỗi và thành công cho 'emr-check-bangke' nếu cần
            ],
            'no_permission' => [
                'error' => '<div class="alert alert-danger"><strong>Bạn chưa được phân quyền để kiểm tra hồ sơ</strong></div>',
            ]
        ];

        // Mảng chứa các partial view được kết hợp lại
        $html = '';

        // Kiểm tra các permission khác nhau và kết hợp các partial views
        // if (auth()->user()->can('emr-check-bangke')) {
        //     // Nếu người dùng có quyền 'emr-check-bangke', trả về partial view tương ứng
        //     $html .= view('emr-checker.partials.treatment-detail-bangke', compact('results'))->render();
        // }

        if (auth()->user()->can('emr-check-bangke-signer')) {

            $html .= $messages['emr-check-bangke-signer']['check'];

            $exists = DB::connection('EMR_RS')
                ->table('emr_document')
                ->where('treatment_code', $treatment_code)
                ->where('document_type_id', 28)
                ->where('signers', 'NOT LIKE', '%#@!@#%')
                ->exists(); // Kiểm tra xem có bản ghi nào không

            // Sử dụng mã lỗi để xác định thông báo và nối vào dòng
            if ($exists) {
                $html .= $messages['emr-check-bangke-signer']['error']; // Bảng kê chưa có chữ ký
            } else {
                $html .= $messages['emr-check-bangke-signer']['success']; // Bảng kê đã có chữ ký
            }
        }

        // Nếu không có bất kỳ quyền nào được kiểm tra
        if (empty($html)) {
            // Thông báo lỗi lớn và rõ ràng nếu không có quyền
            $html = $errorMessages['no_permission'];
        }
        // Trả về HTML đã được kết hợp
        return $html;
    }

}

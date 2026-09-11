<?php

use Illuminate\Database\Seeder;
use App\Models\BHYT\Xml3176ErrorCatalog;

/**
 * Nạp sẵn 20 error_code Nhóm A để cấu hình trước lần scan đầu; TẮT rule cũ trùng
 * (#2498 đã thay giấy chuyển tuyến). Idempotent (updateOrCreate).
 */
class Xml3176ErrorCatalogNhomASeeder extends Seeder
{
    public function run()
    {
        $rules = [
            ['XML1', 'XML1_NGAY_SINH_GREATER_NGAY_VAO', 'Ngày sinh lớn hơn ngày vào viện'],
            ['XML2', 'XML2_LIEU_DUNG_INVALID_FORMAT', 'Liều dùng không đúng định dạng 130'],
            ['XML2', 'XML2_PRESCRIPTION_EXCEEDS_30_DAYS', 'Kê thuốc quá số ngày cho phép'],
            ['XML2', 'XML2_LIEU_DUNG_QUANTITY_MISMATCH', 'Tổng lượng theo liều khác số lượng thanh toán'],
            ['XML2', 'XML2_LIEU_DUNG_UNIT_INVALID', 'Đơn vị trong liều dùng khác đơn vị tính của thuốc'],
            ['XML3', 'XML3_NGAY_TH_YL_EQUALS_NGAY_KQ', 'Thời gian thực hiện y lệnh trùng thời gian kết quả'],
            ['XML3', 'XML3_NGAY_KQ_GREATER_NGAY_RA', 'Ngày kết quả dịch vụ lớn hơn ngày ra viện'],
            ['XML3', 'XML3_EXECUTION_TIME_UNDER_3MIN', 'Thời gian thực hiện nhỏ hơn quy định'],
            ['XML3', 'XML3_SAME_DOCTOR_ORDER_AND_EXECUTE', 'Bác sĩ vừa ra y lệnh vừa thực hiện'],
            ['XML4', 'XML4_MA_CHI_SO_EMPTY', 'Mã chỉ số để trống'],
            ['XML4', 'XML4_TEN_CHI_SO_EMPTY', 'Tên chỉ số để trống'],
            ['XML4', 'XML4_XN_MISSING_VALUE_RESULT', 'Xét nghiệm không nhập giá trị và kết quả'],
            ['XML5', 'XML5_DIEN_BIEN_DUPLICATE', 'Diễn biến điều trị trùng nhau'],
            ['XML7', 'XML7_SO_NGAY_NGHI_MISMATCH', 'Số ngày nghỉ không đúng (đến − từ)'],
            ['XML7', 'XML7_NGOAITRU_TUNGAY_BEFORE_NGAY_RA', 'Bắt đầu nghỉ ngoại trú trước ngày ra viện'],
            ['XML7', 'XML7_NGOAITRU_DENNGAY_BEFORE_NGAY_RA', 'Đến ngày nghỉ ngoại trú trước ngày ra viện'],
            ['XML8', 'XML8_TOMTAT_KQ_TOO_SHORT', 'Tóm tắt kết quả quá ngắn'],
            ['XMLComplete', 'XMLComplete_SECOND_SURGERY_FULL_PAYMENT', 'PTTT lần 2 trong ngày thanh toán 100%'],
            ['XMLComplete', 'XMLComplete_XML4_NGAY_KQ_MISMATCH_XML3', 'Ngày KQ XML4 khác ngày KQ XML3'],
        ];

        foreach ($rules as $r) {
            list($xml, $code, $name) = $r;
            Xml3176ErrorCatalog::updateOrCreate(
                ['xml' => $xml, 'error_code' => $code],
                ['error_name' => $name, 'description' => $name, 'critical_error' => true, 'is_check' => true]
            );
        }

        // Tắt rule cũ trùng: #2498 (Complete) đã thay thế; rule cũ không chấp nhận XML14.
        Xml3176ErrorCatalog::where('error_code', 'XML1_ADMIN_INFO_ERROR_GIAY_CHUYEN_TUYEN')
            ->update(['is_check' => false]);
    }
}

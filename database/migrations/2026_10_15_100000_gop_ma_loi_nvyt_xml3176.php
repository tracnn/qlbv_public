<?php

use App\Models\BHYT\Xml3176ErrorCatalog;
use Illuminate\Database\Migrations\Migration;

/**
 * Gop 34 ma loi dinh danh NVYT (XML1..14) thanh 3 ma XMLComplete o muc ho so.
 * Nap 3 ma moi bang firstOrCreate (khong ghi de muc nguoi van hanh da chinh), tat 34 ma cu
 * (is_check = false, khong xoa de giu lich su loi da ghi). Danh sach ma cu duoc ghi cung o day (khong phu thuoc hang so trong app); test doi chieu voi NvytRule::MA_CU.
 */
class GopMaLoiNvytXml3176 extends Migration
{
    const GHI_CHU = 'Đã thay bằng XMLComplete_NVYT_* (15/10/2026)';

    const MA_CU = [
        'XML1_INFO_ERROR_MA_TTDV', 'XML1_INVALID_MEDICAL_STAFF_MA_TTDV',
        'XML2_MISSING_MEDICAL_STAFF_CODE', 'XML2_INVALID_MEDICAL_STAFF_CODE',
        'XML3_INFO_ERROR_GROUP_CODE_NGUOI_THUC_HIEN', 'XML3_INFO_ERROR_NGUOI_THUC_HIEN_NOT_FOUND',
        'XML3_INFO_ERROR_MA_BAC_SI', 'XML3_INFO_ERROR_MA_BAC_SI_NOT_FOUND',
        'XML4_INFO_ERROR_MA_BS_DOC_KQ', 'XML4_INFO_ERROR_MA_BS_DOC_KQ_NOT_FOUND',
        'XML5_INFO_ERROR_NGUOI_THUC_HIEN', 'XML5_INFO_ERROR_NGUOI_THUC_HIEN_NOT_FOUND',
        'XML7_INFO_ERROR_MA_TTDV', 'XML7_INFO_ERROR_MA_TTDV_NOT_FOUND',
        'XML7_INFO_ERROR_MA_BS', 'XML7_INFO_ERROR_MA_BS_NOT_FOUND',
        'XML8_INFO_ERROR_MA_TTDV', 'XML8_INFO_ERROR_MA_TTDV_NOT_FOUND',
        'XML9_INFO_ERROR_MA_TTDV', 'XML9_INVALID_MEDICAL_STAFF_MA_TTDV',
        'XML10_INFO_ERROR_MA_TTDV', 'XML10_INFO_ERROR_MA_TTDV_NOT_FOUND',
        'XML11_INFO_ERROR_MA_BS', 'XML11_INVALID_MEDICAL_STAFF_MA_BS',
        'XML11_INFO_ERROR_MA_TTDV', 'XML11_INVALID_MEDICAL_STAFF_MA_TTDV',
        'XML13_INFO_ERROR_MA_BAC_SI', 'XML13_INVALID_MEDICAL_STAFF_MA_BAC_SI',
        'XML13_INFO_ERROR_MA_TTDV', 'XML13_INVALID_MEDICAL_STAFF_MA_TTDV',
        'XML14_INFO_ERROR_MA_BAC_SI', 'XML14_INVALID_MEDICAL_STAFF_MA_BAC_SI',
        'XML14_INFO_ERROR_MA_TTDV', 'XML14_INVALID_MEDICAL_STAFF_MA_TTDV',
    ];
    public function up()
    {
        $moi = [
            'XMLComplete_NVYT_THIEU' => [
                'error_name' => 'Thiếu mã nhân viên y tế',
                'description' => 'Hồ sơ có trường mã nhân viên y tế (thủ trưởng đơn vị, bác sĩ chỉ định, người thực hiện, BS đọc kết quả) bị để trống ở một hoặc nhiều XML.',
                'critical_error' => true,
            ],
            'XMLComplete_NVYT_KHONG_CO_TRONG_DM' => [
                'error_name' => 'Mã nhân viên y tế không có trong danh mục NVYT',
                'description' => 'Mã nhân viên y tế đúng dạng CCHN/GPHN (hoặc số định danh với thủ trưởng đơn vị) nhưng không có trong danh mục NVYT đã đồng bộ về phần mềm.',
                'critical_error' => false,
            ],
            'XMLComplete_NVYT_SAI_DINH_DANG' => [
                'error_name' => 'Mã nhân viên y tế sai định danh',
                'description' => 'Mã nhân viên y tế không đúng loại định danh của vai trò (vai trò chuyên môn phải dùng mã CCHN; gửi tài khoản, CCCD, mã BHXH hoặc số CCHN thiếu đuôi là sai).',
                'critical_error' => false,
            ],
        ];
        // Ep critical_error / is_check: ma moi co the da bi tao truoc khi migrate (code moi chay truoc,
        // getCriticalErrorStatus mac dinh true) nen firstOrCreate don thuan se de lai muc sai.
        foreach ($moi as $ma => $f) {
            Xml3176ErrorCatalog::updateOrCreate(
                ['xml' => 'XMLComplete', 'error_code' => $ma],
                $f + ['is_check' => true]
            );
        }

        $ghiChu = self::GHI_CHU;
        Xml3176ErrorCatalog::whereIn('error_code', self::MA_CU)->get()->each(function ($r) use ($ghiChu) {
            $mo = (string) $r->description;
            $r->is_check = false;
            if (strpos($mo, $ghiChu) === false) {
                $r->description = $mo === '' ? $ghiChu : $mo . ' — ' . $ghiChu;
            }
            $r->save();
        });
    }

    public function down()
    {
        // Chi bat lai ma cu; giu 3 ma moi de khong mat lich su loi da ghi.
        Xml3176ErrorCatalog::whereIn('error_code', self::MA_CU)->update(['is_check' => true]);
    }
}

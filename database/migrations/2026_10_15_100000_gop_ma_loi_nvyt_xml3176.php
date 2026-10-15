<?php

use App\Models\BHYT\Xml3176ErrorCatalog;
use App\Services\Xml3176\NvytRule;
use Illuminate\Database\Migrations\Migration;

/**
 * Gop 34 ma loi dinh danh NVYT (XML1..14) thanh 3 ma XMLComplete o muc ho so.
 * Nap 3 ma moi bang firstOrCreate (khong ghi de muc nguoi van hanh da chinh), tat 34 ma cu
 * (is_check = false, khong xoa de giu lich su loi da ghi). Xem NvytRule::MA_CU.
 */
class GopMaLoiNvytXml3176 extends Migration
{
    public function up()
    {
        $moi = [
            'XMLComplete_NVYT_THIEU' => [
                'error_name' => NvytRule::TEN['NVYT_THIEU'],
                'description' => 'Hồ sơ có trường mã nhân viên y tế (thủ trưởng đơn vị, bác sĩ chỉ định, người thực hiện, BS đọc kết quả) bị để trống ở một hoặc nhiều XML.',
                'critical_error' => true,
            ],
            'XMLComplete_NVYT_KHONG_CO_TRONG_DM' => [
                'error_name' => NvytRule::TEN['NVYT_KHONG_CO_TRONG_DM'],
                'description' => 'Mã nhân viên y tế đúng dạng CCHN/GPHN (hoặc số định danh với thủ trưởng đơn vị) nhưng không có trong danh mục NVYT đã đồng bộ về phần mềm.',
                'critical_error' => false,
            ],
            'XMLComplete_NVYT_SAI_DINH_DANG' => [
                'error_name' => NvytRule::TEN['NVYT_SAI_DINH_DANG'],
                'description' => 'Mã nhân viên y tế không đúng loại định danh của vai trò (vai trò chuyên môn phải dùng mã CCHN; gửi tài khoản, CCCD, mã BHXH hoặc số CCHN thiếu đuôi là sai).',
                'critical_error' => false,
            ],
        ];
        foreach ($moi as $ma => $f) {
            Xml3176ErrorCatalog::firstOrCreate(
                ['xml' => 'XMLComplete', 'error_code' => $ma],
                $f + ['is_check' => true]
            );
        }

        Xml3176ErrorCatalog::whereIn('error_code', NvytRule::MA_CU)->update(['is_check' => false]);
    }

    public function down()
    {
        // Chi bat lai ma cu; giu 3 ma moi de khong mat lich su loi da ghi.
        Xml3176ErrorCatalog::whereIn('error_code', NvytRule::MA_CU)->update(['is_check' => true]);
    }
}

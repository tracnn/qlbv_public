<?php

use Illuminate\Database\Seeder;
use App\Models\BHYT\Xml3176ErrorCatalog;

/**
 * Nap san 4 ma loi VTYT/thuoc thanh toan ngoai gia DVKT phai di kem DVKT me duoc BHYT tra
 * (ghi chu "Chua bao gom ..." trong QD phe duyet gia). Idempotent (updateOrCreate).
 */
class Xml3176ErrorCatalogVatPhamKemDvktSeeder extends Seeder
{
    public function run()
    {
        $rules = [
            ['XMLComplete_VTYT_KHONG_CO_DVKT_ME', 'VTYT thanh toán ngoài giá không có DVKT mẹ được BHYT thanh toán',
                'VTYT thuộc danh mục "chưa bao gồm trong giá DVKT" được đề nghị quỹ thanh toán nhưng hồ sơ không có DVKT mẹ, hoặc DVKT mẹ không được quỹ thanh toán (BN tự trả).', true],
            ['XMLComplete_THUOC_KHONG_CO_DVKT_ME', 'Thuốc thanh toán ngoài giá không có DVKT mẹ được BHYT thanh toán',
                'Thuốc chỉ dùng kèm DVKT (cản quang, dược chất phóng xạ...) được đề nghị quỹ thanh toán nhưng hồ sơ không có DVKT mẹ được quỹ thanh toán.', true],
            ['XMLComplete_GOI_VTYT_KHONG_KHOP', 'Gói VTYT không khớp DVKT mẹ được BHYT thanh toán',
                'Dòng VTYT ghi MA_DICH_VU/GOI_VTYT nhưng DVKT mẹ không có trong hồ sơ, không được quỹ thanh toán, số gói vượt số lần DVKT mẹ được thanh toán, hoặc DVKT gắn vào không thuộc danh sách DVKT mẹ hợp lệ của vật phẩm.', true],
            ['XMLComplete_THUOC_DVKT_ME_KHONG_KHOP', 'Thuốc gắn DVKT mẹ không được BHYT thanh toán',
                'Dòng thuốc ghi MA_DICH_VU nhưng DVKT mẹ không có trong hồ sơ, không được quỹ thanh toán, hoặc không thuộc danh sách DVKT mẹ hợp lệ của thuốc (vd thuốc cản quang gắn vào chụp không tiêm cản quang).', true],
            ['XMLComplete_DVKT_THIEU_VAT_PHAM_KEM', 'DVKT bắt buộc kèm vật phẩm nhưng không có vật phẩm',
                'DVKT mà hầu hết hồ sơ BHYT đều kèm vật phẩm (vd CLVT có tiêm thuốc cản quang) được quỹ thanh toán nhưng hồ sơ không có vật phẩm đi kèm được quỹ thanh toán.', false],
        ];

        foreach ($rules as $r) {
            list($code, $name, $description, $critical) = $r;
            Xml3176ErrorCatalog::updateOrCreate(
                ['xml' => 'XMLComplete', 'error_code' => $code],
                ['error_name' => $name, 'description' => $description, 'critical_error' => $critical, 'is_check' => true]
            );
        }
    }
}

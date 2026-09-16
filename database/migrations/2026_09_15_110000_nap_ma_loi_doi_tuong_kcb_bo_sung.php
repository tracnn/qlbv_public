<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\BHYT\Xml3176ErrorCatalog;

/**
 * Nap them 3 ma loi ma doi tuong KCB bo sung (1.1, 3.6, 1.17) vao danh muc ma loi.
 *
 * Cac migration nap danh muc truoc do da chay tren moi truong da trien khai nen khong chay
 * lai; can migration rieng de dong moi den duoc CSDL that.
 *
 * BAT BUOC chay TRUOC khi quy tac no lan dau: thieu dong danh muc thi
 * getCriticalErrorStatus() tra mac dinh TRUE va chan xuat XML.
 *
 * KHONG goi lai seeder nap 13 ma o day: seeder dung updateOrCreate cho ca 13 ma, se ghi
 * de critical_error/is_check ma nguoi van hanh da tu chinh qua man danh muc ma loi cho
 * 10 ma cu. Migration nay chi dung firstOrCreate cho dung 3 ma moi, chi tao khi chua co,
 * khong dung den 10 ma con lai.
 */
class NapMaLoiDoiTuongKcbBoSung extends Migration
{
    public function up()
    {
        $danhMuc = [
            ['XML1_DOI_TUONG_KCB_DKBD_KHAC_CSKCB', 'Đến đúng nơi đăng ký ban đầu nhưng MA_DKBD khác MA_CSKCB', 'Mã 1.1: mọi mã trong MA_DKBD phải bằng MA_CSKCB'],
            ['XML1_DOI_TUONG_KCB_THIEU_MA_KHUVUC', 'Thiếu mã khu vực', 'Mã 3.6: MA_KHUVUC không được để trống'],
            ['XML1_DOI_TUONG_KCB_BENH_NGOAI_PL1', 'Tự đến cơ sở cấp chuyên sâu nhưng bệnh không thuộc Phụ lục I TT 01/2025', 'Mã 1.17: MA_BENH_CHINH phải thuộc danh mục bệnh Phụ lục I Thông tư 01/2025/TT-BYT, kể cả điều kiện người dưới 18 tuổi'],
        ];

        foreach ($danhMuc as $dong) {
            list($maLoi, $ten, $moTa) = $dong;

            Xml3176ErrorCatalog::firstOrCreate(
                ['xml' => 'XML1', 'error_code' => $maLoi],
                [
                    'error_name'     => $ten,
                    'description'    => $moTa,
                    'critical_error' => false,
                    'is_check'       => true,
                ]
            );
        }
    }

    public function down()
    {
        // Co Y KHONG lui: xoa dong danh muc se lam getCriticalErrorStatus() quay ve mac
        // dinh TRUE va chan xuat XML.
    }
}

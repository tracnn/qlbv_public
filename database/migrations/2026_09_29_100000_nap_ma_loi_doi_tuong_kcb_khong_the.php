<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\BHYT\Xml3176ErrorCatalog;

/**
 * Nap ma loi XML1_DOI_TUONG_KCB_KHONG_THE_SAI_MA (khong co ma the BHYT nhung ma doi
 * tuong khong phai 9) vao danh muc ma loi.
 *
 * BAT BUOC chay truoc khi quy tac chay: thieu dong danh muc thi getCriticalErrorStatus()
 * mac dinh la NGHIEM TRONG, quy tac no lan dau tu ghi dong o muc do va chan xuat ca 342
 * ho so dang vi pham (do 29/09/2026).
 *
 * Muc CANH BAO, giong cac ma doi tuong KCB khac. Nguoi van hanh tu nang len qua man danh
 * muc ma loi neu muon chan.
 *
 * firstOrCreate: chi tao khi chua co, KHONG ghi de muc nguoi van hanh da chinh. Khong goi
 * seeder (updateOrCreate se ghi de ca cac ma cu).
 */
class NapMaLoiDoiTuongKcbKhongThe extends Migration
{
    public function up()
    {
        Xml3176ErrorCatalog::firstOrCreate(
            ['xml' => 'XML1', 'error_code' => 'XML1_DOI_TUONG_KCB_KHONG_THE_SAI_MA'],
            [
                'error_name'     => 'Không có mã thẻ BHYT nhưng mã đối tượng không phải 9',
                'description'    => 'MA_THE_BHYT để trống thì mã đối tượng phải là 9 (người bệnh không KCB BHYT); chỉ báo khi T_BHTT = 0, phần T_BHTT > 0 thuộc XML1_DOI_TUONG_KCB_THIEU_THE_BHYT',
                'critical_error' => false,
                'is_check'       => true,
            ]
        );
    }

    public function down()
    {
        // Co Y KHONG lui: xoa dong danh muc thi getCriticalErrorStatus() quay ve mac dinh
        // NGHIEM TRONG va chan xuat.
    }
}

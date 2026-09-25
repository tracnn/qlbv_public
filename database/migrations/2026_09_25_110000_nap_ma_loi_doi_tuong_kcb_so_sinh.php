<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\BHYT\Xml3176ErrorCatalog;

/**
 * Nap ma loi XML1_DOI_TUONG_KCB_KHONG_PHAI_SO_SINH (ma doi tuong 1.7 nhung nguoi benh qua
 * 28 ngay tuoi luc vao vien) vao danh muc ma loi.
 *
 * Muc NGHIEM TRONG theo nguoi dung chot: BHXH chac chan tu choi ho so nay (000007199029),
 * gui len chi mat cong sua lai. Khac cac ma doi tuong KCB khac (deu la canh bao).
 *
 * firstOrCreate: chi tao khi chua co, KHONG ghi de muc nguoi van hanh da chinh qua man
 * danh muc ma loi. Khong goi seeder (updateOrCreate se ghi de ca cac ma cu).
 */
class NapMaLoiDoiTuongKcbSoSinh extends Migration
{
    public function up()
    {
        Xml3176ErrorCatalog::firstOrCreate(
            ['xml' => 'XML1', 'error_code' => 'XML1_DOI_TUONG_KCB_KHONG_PHAI_SO_SINH'],
            [
                'error_name'     => 'Không phải đối tượng trẻ sơ sinh phải điều trị ngay sau khi sinh ra',
                'description'    => 'Mã 1.7: người bệnh phải không quá 28 ngày tuổi lúc vào viện',
                'critical_error' => true,
                'is_check'       => true,
            ]
        );
    }

    public function down()
    {
        // Co Y KHONG lui: xoa dong danh muc thi getCriticalErrorStatus() quay ve mac dinh.
    }
}

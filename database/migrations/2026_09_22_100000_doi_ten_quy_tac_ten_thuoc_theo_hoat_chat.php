<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

/**
 * Quy tac A_BHYT_DRUG_NAME_MISMATCH nay doi chieu TEN HOAT CHAT (HIS khai
 * active_ingr_bhyt_name, danh muc cot ten_hoat_chat) chu khong phai ten thuong mai. Ten
 * hien tren man Quan ly quy tac phai noi dung dieu do.
 *
 * Chi doi TEN. Ma quy tac giu nguyen de khong vo lich su vi pham; trang thai bat/tat giu
 * nguyen de khong ghi de lua chon nguoi dung da chinh tren man hinh.
 */
class DoiTenQuyTacTenThuocTheoHoatChat extends Migration
{
    const MA = 'A_BHYT_DRUG_NAME_MISMATCH';

    public function up()
    {
        DB::table('order_check_rules')
            ->where('code', self::MA)
            ->update(['name' => 'Tên hoạt chất lệch danh mục BHYT', 'updated_at' => now()]);
    }

    public function down()
    {
        DB::table('order_check_rules')
            ->where('code', self::MA)
            ->update(['name' => 'Tên thuốc lệch danh mục BHYT', 'updated_at' => now()]);
    }
}

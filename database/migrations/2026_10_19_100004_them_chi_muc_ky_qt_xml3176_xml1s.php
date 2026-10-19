<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

/**
 * Chi muc (ma_cskcb, nam_qt, thang_qt) tren xml3176_xml1s cho truy van dung ban 01/BH.
 *
 * VI SAO ALGORITHM=INPLACE, LOCK=NONE: bang XML1 lon va dang duoc job nap/kiem ghi lien tuc;
 * khong duoc khoa ghi. Neu MySQL khong thuc hien duoc o che do nay thi lenh bao loi ngay
 * thay vi am tham khoa bang.
 * VI SAO chi chay tren MySQL: SQLite cua test khong co cu phap nay.
 */
class ThemChiMucKyQtXml3176Xml1s extends Migration
{
    const BANG = 'xml3176_xml1s';
    const CHI_MUC = 'idx_xml1_cskcb_ky';

    private function daCoChiMuc()
    {
        $da = DB::select(
            'SHOW INDEX FROM ' . self::BANG . ' WHERE Key_name = ?',
            [self::CHI_MUC]
        );

        return !empty($da);
    }

    public function up()
    {
        if (DB::getDriverName() !== 'mysql' || $this->daCoChiMuc()) {
            return;
        }

        DB::statement(
            'ALTER TABLE ' . self::BANG . ' ADD INDEX ' . self::CHI_MUC
            . ' (ma_cskcb, nam_qt, thang_qt), ALGORITHM=INPLACE, LOCK=NONE'
        );
    }

    public function down()
    {
        if (DB::getDriverName() !== 'mysql' || !$this->daCoChiMuc()) {
            return;
        }

        DB::statement('ALTER TABLE ' . self::BANG . ' DROP INDEX ' . self::CHI_MUC);
    }
}

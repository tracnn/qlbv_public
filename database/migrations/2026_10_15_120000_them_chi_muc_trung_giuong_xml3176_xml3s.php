<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Chi muc ghep (ma_giuong, ma_khoa, ngay_th_yl) cho xml3176_xml3s.
 *
 * Truy van kiem trung giuong (Xml3176Xml3Checker) loc theo ma_khoa, ma_giuong va
 * khoang ngay_th_yl/ngay_kq. Cac chi muc don cot ma_khoa/ma_giuong it chon loc nen
 * quet nhieu dong tren bang ~1,5 trieu dong; chi muc ghep nay thu hep ngay tu dau.
 *
 * Tren MySQL/MariaDB thu ALGORITHM=INPLACE, LOCK=NONE de khong khoa bang khi tao;
 * neu khong ho tro thi lui ve ADD INDEX thuong.
 */
class ThemChiMucTrungGiuongXml3176Xml3s extends Migration
{
    const BANG = 'xml3176_xml3s';
    const TEN = 'xml3176_xml3s_giuong_khoa_th_yl_index';

    public function up()
    {
        if ($this->coIndex()) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            $sql = 'ALTER TABLE ' . self::BANG . ' ADD INDEX ' . self::TEN . ' (ma_giuong, ma_khoa, ngay_th_yl)';
            try {
                DB::statement($sql . ', ALGORITHM=INPLACE, LOCK=NONE');
            } catch (QueryException $e) {
                // Khong ho tro INPLACE/LOCK=NONE: tao cach thuong.
                if (!$this->coIndex()) {
                    DB::statement($sql);
                }
            }

            return;
        }

        Schema::table(self::BANG, function (Blueprint $t) {
            $t->index(['ma_giuong', 'ma_khoa', 'ngay_th_yl'], self::TEN);
        });
    }

    public function down()
    {
        if (!$this->coIndex()) {
            return;
        }

        Schema::table(self::BANG, function (Blueprint $t) {
            $t->dropIndex(self::TEN);
        });
    }

    protected function coIndex()
    {
        // Laravel 5.5 khong co Schema::hasIndex.
        switch (DB::getDriverName()) {
            case 'mysql':
                return count(DB::select('SHOW INDEX FROM ' . self::BANG . ' WHERE Key_name = ?', [self::TEN])) > 0;
            case 'sqlite':
                foreach (DB::select("PRAGMA index_list('" . self::BANG . "')") as $dong) {
                    if (($dong->name ?? null) === self::TEN) {
                        return true;
                    }
                }

                return false;
            default:
                return false;
        }
    }
}

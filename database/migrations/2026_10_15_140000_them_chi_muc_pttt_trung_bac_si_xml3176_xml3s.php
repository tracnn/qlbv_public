<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Chi muc ghep (ma_bac_si, ngay_yl, ngay_th_yl, ngay_kq) cho xml3176_xml3s.
 *
 * Truy van kiem "PTTT trung bac si" (so sanh cheo giua cac ho so) loc theo ma_bac_si
 * va cac moc thoi gian ngay_yl/ngay_th_yl/ngay_kq. Dung tien to (100, 14, 14, 14) vi
 * do dai that lon nhat tren prod: ma_bac_si 17, cac cot ngay 12.
 *
 * Tren MySQL/MariaDB thu ALGORITHM=INPLACE, LOCK=NONE de khong khoa bang khi tao;
 * neu khong ho tro thi lui ve ADD INDEX thuong. SQLite (test) dung Schema khong tien to.
 */
class ThemChiMucPtttTrungBacSiXml3176Xml3s extends Migration
{
    const BANG = 'xml3176_xml3s';
    const TEN = 'xml3176_xml3s_bs_ngay_yl_th_kq_index';

    public function up()
    {
        if ($this->coIndex()) {
            return;
        }

        // Chi muc chi de toi uu: loi tao chi muc khong duoc chan cac migration sau.
        try {
            $this->taoChiMuc();
        } catch (QueryException $e) {
            Log::warning('Khong tao duoc chi muc ' . self::TEN . ' tren bang ' . self::BANG . ': ' . $e->getMessage());
        }
    }

    protected function taoChiMuc()
    {
        if ($this->driver() === 'mysql') {
            $sql = 'ALTER TABLE ' . self::BANG . ' ADD INDEX ' . self::TEN
                . ' (ma_bac_si(100), ngay_yl(14), ngay_th_yl(14), ngay_kq(14))';
            try {
                $this->chayAlter($sql . ', ALGORITHM=INPLACE, LOCK=NONE');
            } catch (QueryException $e) {
                // Khong ho tro INPLACE/LOCK=NONE: tao cach thuong.
                if ($this->coIndex()) {
                    return;
                }
                try {
                    $this->chayAlter($sql);
                } catch (QueryException $e2) {
                    Log::warning('Khong tao duoc chi muc ' . self::TEN . ' tren bang ' . self::BANG
                        . '. Loi INPLACE: ' . $e->getMessage() . ' | Loi thuong: ' . $e2->getMessage());
                }
            }

            return;
        }

        Schema::table(self::BANG, function (Blueprint $t) {
            $t->index(['ma_bac_si', 'ngay_yl', 'ngay_th_yl', 'ngay_kq'], self::TEN);
        });
    }

    protected function driver()
    {
        return DB::getDriverName();
    }

    protected function chayAlter($sql)
    {
        DB::statement($sql);
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

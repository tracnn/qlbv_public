<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Chi muc ghep (ma_dich_vu, ma_cskcb) cho service_catalogs.
 *
 * Truy van tra DVKT la "(ma_cskcb is null or ma_cskcb='' or ma_cskcb=?) and ma_dich_vu=?".
 * Tren may chu that MySQL chon chi muc ma_cskcb (it chon loc) nen quet rat nhieu dong;
 * chi muc ghep nay dua ma_dich_vu len dau de ke hoach truy van dung.
 */
class ThemChiMucMaDichVuServiceCatalogs extends Migration
{
    const TEN = 'service_catalogs_ma_dich_vu_ma_cskcb_index';

    public function up()
    {
        if ($this->coIndex()) {
            return;
        }

        // Chi muc chi de toi uu: loi tao chi muc khong duoc chan cac migration sau.
        try {
            $this->taoChiMuc();
        } catch (QueryException $e) {
            Log::warning('Khong tao duoc chi muc ' . self::TEN . ' tren bang service_catalogs: ' . $e->getMessage());
        }
    }

    protected function taoChiMuc()
    {
        Schema::table('service_catalogs', function (Blueprint $t) {
            $t->index(['ma_dich_vu', 'ma_cskcb'], self::TEN);
        });
    }

    public function down()
    {
        if (!$this->coIndex()) {
            return;
        }

        Schema::table('service_catalogs', function (Blueprint $t) {
            $t->dropIndex(self::TEN);
        });
    }

    protected function coIndex()
    {
        // Laravel 5.5 khong co Schema::hasIndex.
        switch (DB::getDriverName()) {
            case 'mysql':
                return count(DB::select('SHOW INDEX FROM service_catalogs WHERE Key_name = ?', [self::TEN])) > 0;
            case 'sqlite':
                foreach (DB::select("PRAGMA index_list('service_catalogs')") as $dong) {
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

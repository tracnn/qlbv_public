<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Index rieng cho updated_at cua check_hein_cards.
 *
 * Man Ket qua tra cuu the loc va sap xep theo updated_at ("thoi gian tra cuu"). Index ghep
 * san co (created_at, updated_at) de updated_at o cot THU HAI nen khong dung duoc - moi lan
 * tai quet toan bang + filesort (~46 nghin dong, them ~23 nghin/thang).
 */
class ThemIndexUpdatedAtVaoCheckHeinCards extends Migration
{
    const TEN = 'check_hein_cards_updated_at_index';

    public function up()
    {
        if ($this->coIndex()) {
            return;
        }

        Schema::table('check_hein_cards', function (Blueprint $t) {
            $t->index('updated_at', self::TEN);
        });
    }

    public function down()
    {
        if (!$this->coIndex()) {
            return;
        }

        Schema::table('check_hein_cards', function (Blueprint $t) {
            $t->dropIndex(self::TEN);
        });
    }

    protected function coIndex()
    {
        // Laravel 5.5 khong co Schema::hasIndex; chi MySQL moi can kiem (SQLite cua test tao
        // bang moi, khong co index nay).
        if (DB::getDriverName() !== 'mysql') {
            return false;
        }

        return count(DB::select('SHOW INDEX FROM check_hein_cards WHERE Key_name = ?', [self::TEN])) > 0;
    }
}

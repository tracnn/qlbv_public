<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Cot ma co so KCB cho ket qua kiem tra the BHYT.
 *
 * Bang dang RONG nen khong can va nguoc.
 */
class ThemMaCskcbVaoCheckHeinCards extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('check_hein_cards', 'ma_cskcb')) {
            return;
        }

        Schema::table('check_hein_cards', function (Blueprint $t) {
            $t->string('ma_cskcb', 20)->nullable()->after('ma_lk');
            $t->index('ma_cskcb');
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('check_hein_cards', 'ma_cskcb')) {
            return;
        }

        Schema::table('check_hein_cards', function (Blueprint $t) {
            $t->dropIndex(['ma_cskcb']);
            $t->dropColumn('ma_cskcb');
        });
    }
}

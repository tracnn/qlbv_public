<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Gia tri DA GUI len cong BHXH khi tra the: so the, ho ten, ngay sinh, noi DKBD.
 *
 * Cong bao loi thuong khong tra ba truong dau - 1.213/1.525 dong loi chi con ma ho so. Khong
 * index: man tim bang LIKE %x% nen index khong dung toi, bang ~46 nghin dong.
 */
class ThemThongTinGuiVaoCheckHeinCards extends Migration
{
    const COT = ['ma_the_gui', 'ho_ten_gui', 'ngay_sinh_gui', 'ma_dkbd_gui'];

    public function up()
    {
        if (Schema::hasColumn('check_hein_cards', 'ma_the_gui')) {
            return;
        }

        Schema::table('check_hein_cards', function (Blueprint $t) {
            $t->string('ma_the_gui', 50)->nullable();
            $t->string('ho_ten_gui', 255)->nullable();
            $t->string('ngay_sinh_gui', 20)->nullable();
            $t->string('ma_dkbd_gui', 20)->nullable();
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('check_hein_cards', 'ma_the_gui')) {
            return;
        }

        Schema::table('check_hein_cards', function (Blueprint $t) {
            $t->dropColumn(self::COT);
        });
    }
}

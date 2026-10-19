<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Ghi nhan NGUON cua ban 01/BH: 'xml3176' (chup tu xml3176_xml1s) hoac 'excel' (nhap tu tep).
 *
 * VI SAO default 'xml3176': moi ban da co deu sinh tu XML3176; mac dinh giu nguyen y nghia cho
 * chung, khong can cap nhat du lieu cu. Hai cot tep chi dung cho ban excel (dau vet tep da nop).
 */
class ThemNguonVaoBh01Ban extends Migration
{
    public function up()
    {
        Schema::table('bh01_ban', function (Blueprint $table) {
            $table->string('nguon', 10)->default('xml3176');
            $table->string('ten_tep_nguon')->nullable();
            $table->string('duong_dan_tep_nguon')->nullable();
        });
    }

    public function down()
    {
        Schema::table('bh01_ban', function (Blueprint $table) {
            $table->dropColumn(['nguon', 'ten_tep_nguon', 'duong_dan_tep_nguon']);
        });
    }
}

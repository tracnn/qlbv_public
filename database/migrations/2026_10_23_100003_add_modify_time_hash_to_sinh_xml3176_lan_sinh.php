<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Bang theo doi XML3176: luu modify_time cua ho so HIS lan quet gan nhat va dau van tay noi dung
 * (sha1) de quet theo MODIFY_TIME chi ghi tep khi noi dung doi. Hai cot nullable: dong cu giu NULL.
 */
class AddModifyTimeHashToSinhXml3176LanSinh extends Migration
{
    public function up()
    {
        Schema::table('sinh_xml3176_lan_sinh', function (Blueprint $table) {
            $table->bigInteger('modify_time')->nullable();
            $table->char('noi_dung_hash', 40)->nullable();
        });
    }

    public function down()
    {
        Schema::table('sinh_xml3176_lan_sinh', function (Blueprint $table) {
            $table->dropColumn(['modify_time', 'noi_dung_hash']);
        });
    }
}

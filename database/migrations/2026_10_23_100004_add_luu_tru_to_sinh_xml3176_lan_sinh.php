<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Bang theo doi XML3176: duong tuong doi cua ban sao luu tru (<yyyyMMdd>\<id>_<ten_tep>), dau vet xoa ban sao
 * (luc + user) va chi muc created_at cho man theo doi. Cac cot nullable: dong cu giu NULL.
 */
class AddLuuTruToSinhXml3176LanSinh extends Migration
{
    public function up()
    {
        Schema::table('sinh_xml3176_lan_sinh', function (Blueprint $table) {
            $table->string('tep_luu', 255)->nullable();
            $table->timestamp('xoa_luu_luc')->nullable();
            $table->unsignedInteger('xoa_luu_boi')->nullable();
            $table->index('created_at', 'sinh_xml3176_lan_sinh_created_at_index');
        });
    }

    public function down()
    {
        Schema::table('sinh_xml3176_lan_sinh', function (Blueprint $table) {
            $table->dropIndex('sinh_xml3176_lan_sinh_created_at_index');
            $table->dropColumn(['tep_luu', 'xoa_luu_luc', 'xoa_luu_boi']);
        });
    }
}

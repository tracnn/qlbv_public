<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Hai cot cua chuoi kiem -> xuat -> ky -> gui (spec 2026-09-29).
 *
 * chain_token: ma phien xu ly. Moi lan dung mot chuoi moi cho ho so (nap, hoac lenh
 * xml3176:chay-lai-tu-xuat) sinh ma moi; moi job trong chuoi mang ma cua chuoi da sinh ra
 * no va tu thoi khi thay ma tren ho so da khac. Chan truong hop nap lai giua chung: job
 * xuat cua chuoi cu con nam cho se thay "khong co loi nghiem trong" (loi cu vua bi xoa)
 * va xuat du lieu moi chua ai kiem.
 *
 * signed_file_path: duong dan tep da ky tren disk exportXml3176. Buoc ky ghi, buoc gui
 * doc. Truoc day duong dan duoc truyen thang qua tham so job gui, nhung trong mot chuoi
 * dung san tu luc nap thi luc do chua ai biet ten tep (ten co gio phut giay luc ghi).
 */
class ThemChainTokenVaSignedFilePathVaoXml3176Informations extends Migration
{
    public function up()
    {
        Schema::table('xml3176_informations', function (Blueprint $table) {
            $table->string('chain_token', 32)->nullable()->after('checked_at');
            $table->string('signed_file_path')->nullable()->after('sign_method');
        });
    }

    public function down()
    {
        Schema::table('xml3176_informations', function (Blueprint $table) {
            $table->dropColumn(['chain_token', 'signed_file_path']);
        });
    }
}

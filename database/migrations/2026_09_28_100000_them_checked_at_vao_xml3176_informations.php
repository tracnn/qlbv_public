<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Dau "da kiem loi xong" cua mot ho so.
 *
 * Truoc day buoc xuat va buoc kiem loi chay tren HAI hang doi khac nhau
 * (JobExportXml3176 va JobXml3176) nen hai worker chay song song: worker xuat
 * hoi "ho so nay co loi nghiem trong khong" khi bang loi con trong, tra loi
 * khong, roi xuat + ky + gui cong. Do ngay 28/09/2026: 50 ho so xuat luc
 * 11:17:38, dong loi nghiem trong dau tien mai 11:17:39 moi duoc ghi.
 *
 * Cot nay cho buoc xuat biet khi nao kiem loi da xong de doi.
 */
class ThemCheckedAtVaoXml3176Informations extends Migration
{
    public function up()
    {
        Schema::table('xml3176_informations', function (Blueprint $table) {
            $table->timestamp('checked_at')->nullable()->after('imported_by');
        });
    }

    public function down()
    {
        Schema::table('xml3176_informations', function (Blueprint $table) {
            $table->dropColumn('checked_at');
        });
    }
}

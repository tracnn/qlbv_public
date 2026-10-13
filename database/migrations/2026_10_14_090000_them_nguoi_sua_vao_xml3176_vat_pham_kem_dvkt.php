<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Ghi nguoi va thoi diem sua cuoi qua man quan tri (giai trinh voi BHXH). Lenh hoc khong ghi. */
class ThemNguoiSuaVaoXml3176VatPhamKemDvkt extends Migration
{
    public function up()
    {
        Schema::table('xml3176_vat_pham_kem_dvkt', function (Blueprint $table) {
            $table->unsignedInteger('nguoi_sua_id')->nullable();
            $table->timestamp('sua_luc')->nullable();
        });
    }

    public function down()
    {
        Schema::table('xml3176_vat_pham_kem_dvkt', function (Blueprint $table) {
            $table->dropColumn(['nguoi_sua_id', 'sua_luc']);
        });
    }
}

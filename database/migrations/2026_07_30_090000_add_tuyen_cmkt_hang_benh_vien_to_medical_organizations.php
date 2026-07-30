<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Anh xa cot cua danh muc co so KCB khai tuyen_cmkt va hang_benh_vien, va truoc day con dua
 * ca hai vao required_fields - tuc nguoi dung BUOC phai dien hai cot do trong tep Excel. Nhung
 * bang khong co cot nao nhu vay, va ghi qua Eloquent thi fillable am tham loc chung di: gia tri
 * nguoi dung nhap chua bao gio duoc luu.
 *
 * Them cot NULLABLE va bo hai truong khoi required_fields: du lieu cu khong co hai cot nay nen
 * khong the dat NOT NULL, va tep BHXH cap khong luon co du hai cot.
 */
class AddTuyenCmktHangBenhVienToMedicalOrganizations extends Migration
{
    public function up()
    {
        Schema::table('medical_organizations', function (Blueprint $table) {
            $table->string('tuyen_cmkt')->nullable()->after('ten_cskcb');
            $table->string('hang_benh_vien')->nullable()->after('tuyen_cmkt');
        });
    }

    public function down()
    {
        Schema::table('medical_organizations', function (Blueprint $table) {
            $table->dropColumn(['tuyen_cmkt', 'hang_benh_vien']);
        });
    }
}

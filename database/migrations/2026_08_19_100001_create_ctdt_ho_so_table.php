<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Mot HOSO = mot ban ghi = mot don vi ky / gui / ghi de.
 *
 * Mot tep HSCHUNGTU co the chua nhieu HOSO. Neu lay ca TEP lam don vi giao dich thi
 * khong ghi de, khong gui lai, khong tra trang thai o muc tung ho so duoc.
 */
class CreateCtdtHoSoTable extends Migration
{
    public function up()
    {
        Schema::create('ctdt_ho_so', function (Blueprint $table) {
            $table->increments('id');

            // Khoa NGHIEP VU, khong phai danh tinh tep: ban sua gui lai mang Id GUID moi,
            // khoa theo GUID thi khong bao gio dung ban cu.
            $table->string('ma_ho_so', 100)->unique();
            $table->string('id_goi_xml', 64)->nullable();

            $table->string('dich_vu', 10)->index();      // CT2025 | GBT | GCS
            $table->string('loai_hs', 2);                // 39 | 60 | 61
            $table->string('macskcb', 5)->index();
            $table->string('ngay_lap', 8)->nullable();
            $table->integer('so_luong_ho_so')->nullable();
            $table->integer('so_chung_tu')->default(0);

            $table->string('duong_dan_goc')->nullable();
            $table->string('duong_dan_da_ky')->nullable();

            $table->timestamp('imported_at')->nullable();
            $table->string('imported_by')->nullable()->index();
            $table->text('import_error')->nullable();

            $table->timestamp('checked_at')->nullable();
            $table->integer('so_loi')->default(0)->index();

            $table->boolean('is_signed')->default(false);
            $table->string('sign_method')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->text('signed_error')->nullable();

            $table->timestamp('submitted_at')->nullable()->index();
            $table->string('submitted_by')->nullable()->index();
            $table->string('submit_error')->nullable()->index();
            $table->text('submitted_message')->nullable();

            // Cot tra cuu quan trong nhat khi doi soat voi BHXH.
            $table->string('ma_gd', 50)->nullable()->index();
            $table->string('ma_ket_qua', 10)->nullable()->index();
            $table->string('thoi_gian_tiep_nhan', 14)->nullable();

            // Noi them mot dong moi lan gui: giu dau vet doi soat sau khi ghi de.
            $table->text('lich_su_gui')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ctdt_ho_so');
    }
}

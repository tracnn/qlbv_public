<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Mot HO SO = mot tep Excel = mot don vi ky / gui / ghi de.
 *
 * Mot tep chua ca danh muc; trang thai gui, MaGD va ma ket qua deu theo TUNG TEP. Lay
 * dong lam don vi giao dich thi khong the noi lan gui nao ung voi noi dung nao.
 */
class CreateTt12HoSoTable extends Migration
{
    public function up()
    {
        Schema::create('tt12_ho_so', function (Blueprint $table) {
            $table->increments('id');

            // Khoa NGHIEP VU: nap lai cung mot danh muc lan hai phai la ho so khac,
            // vi no la mot lan gui khac va se co MaGD khac.
            $table->string('ma_ho_so', 100)->unique();

            $table->string('mau', 10)->index();          // MAU_01..MAU_06
            $table->string('loai_hs', 2);                // 70|71|10|11|12|72
            $table->string('ma_cskcb', 5)->index();

            $table->string('ten_tep')->nullable();
            $table->integer('so_dong')->default(0);

            // GUID gan vao thuoc tinh Id cua the DANHSACH_*. Sinh MOT LAN luc nap: sinh
            // lai moi lan ky thi ban ky lan hai khac ban lan mot, ma chu ky XMLDSig tham
            // chieu chinh #Id nay.
            $table->string('id_danh_sach', 64)->nullable();

            $table->timestamp('imported_at')->nullable();
            $table->string('imported_by')->nullable()->index();
            $table->text('import_error')->nullable();

            $table->timestamp('checked_at')->nullable();
            $table->integer('so_loi')->default(0)->index();

            $table->boolean('is_signed')->default(false);
            $table->string('sign_method')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->text('signed_error')->nullable();
            $table->string('duong_dan_da_ky')->nullable();

            $table->timestamp('submitted_at')->nullable()->index();
            $table->string('submitted_by')->nullable()->index();
            $table->text('submit_error')->nullable();
            $table->text('submitted_message')->nullable();

            // Cot tra cuu quan trong nhat khi doi soat voi BHXH.
            $table->string('ma_gd', 100)->nullable()->index();
            $table->string('ma_ket_qua', 10)->nullable()->index();
            $table->string('thoi_gian_tiep_nhan', 14)->nullable();

            // Dau vet buoc day sang bang danh muc. dong_bo_at rong nghia la du lieu chua
            // vao danh muc, du cong da tra 200.
            $table->timestamp('dong_bo_at')->nullable();
            $table->integer('dong_bo_so_dong')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tt12_ho_so');
    }
}

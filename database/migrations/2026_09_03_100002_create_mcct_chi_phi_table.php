<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Cac dong DataCCT cua MOT phien tra.
 *
 * KHONG dat unique (ma_the, id_cong): moi lan tra la mot anh chup moi, trung id_cong giua
 * cac phien la chuyen duong nhien - do chinh la thu cho phep so sanh hai lan tra.
 *
 * Nam truong duPhong cua cong KHONG luu: phu luc ghi ro chung luon la chuoi rong.
 */
class CreateMcctChiPhiTable extends Migration
{
    public function up()
    {
        Schema::create('mcct_chi_phi', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('tra_cuu_id')->index();

            $table->bigInteger('id_cong')->nullable()->index();
            $table->string('ma_the', 20)->nullable();
            $table->string('ma_cskcb', 10)->nullable();

            $table->date('ngay_vao')->nullable();
            $table->date('ngay_ra')->nullable();
            $table->string('ma_doi_tuong_kcb', 10)->nullable();

            $table->decimal('t_bn_cct_mcct', 15, 2)->default(0);
            $table->decimal('t_bn_cct_luy_ke', 15, 2)->default(0);

            $table->date('ngay_nhan_cong')->nullable();
            $table->date('ngay_nhan')->nullable();
            $table->date('ngay_tra_cuu')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mcct_chi_phi');
    }
}

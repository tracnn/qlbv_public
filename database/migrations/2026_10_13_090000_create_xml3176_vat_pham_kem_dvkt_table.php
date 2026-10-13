<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bang tra vat pham (VTYT/thuoc) thanh toan ngoai gia DVKT -> DVKT me hop le.
 * Hoc tu du lieu da lien ket (lenh xml3176:hoc-vat-pham-kem-dvkt) va bo sung tay qua Excel.
 * Do dai cot trong khoa duy nhat bi gioi han de vua chi muc utf8mb4.
 */
class CreateXml3176VatPhamKemDvktTable extends Migration
{
    public function up()
    {
        Schema::create('xml3176_vat_pham_kem_dvkt', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('loai', 10);
            $table->string('ma_vat_pham', 100);
            $table->string('ten_vat_pham', 1024)->nullable();
            $table->string('ten_khop', 191)->default('');
            $table->string('ma_dvkt', 50);
            $table->string('ten_dvkt', 1024)->nullable();
            $table->string('nguon', 10);
            $table->integer('so_lan_gan')->default(0);
            $table->decimal('ty_le_gan', 5, 4)->nullable();
            $table->boolean('hieu_luc')->default(true);
            $table->string('ghi_chu', 1024)->nullable();
            $table->timestamps();

            $table->unique(['loai', 'ma_vat_pham', 'ten_khop', 'ma_dvkt'], 'xml3176_vpkd_khoa_unique');
            $table->index(['loai', 'hieu_luc'], 'xml3176_vpkd_loai_hieu_luc_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('xml3176_vat_pham_kem_dvkt');
    }
}

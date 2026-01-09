<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateXml3176Xml4sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xml3176_xml4s', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_lk');
            $table->integer('stt');
            $table->string('ma_dich_vu')->nullable();
            $table->string('ma_chi_so')->nullable();
            $table->string('ten_chi_so')->nullable();
            $table->string('gia_tri')->nullable();
            $table->string('don_vi_do')->nullable();
            $table->text('mo_ta')->nullable();
            $table->text('ket_luan')->nullable();
            $table->string('ngay_kq')->nullable();
            $table->string('ma_bs_doc_kq')->nullable();
            $table->text('du_phong')->nullable();
            $table->timestamps();

            // Adding index
            $table->unique(['ma_lk', 'stt'], 'xml3176_xml4s_ma_lk_stt_unique');
            $table->index('ma_lk');
            $table->index('ma_dich_vu');
            $table->index('ma_chi_so');
            $table->index('ngay_kq');
            $table->index('ma_bs_doc_kq');
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('xml3176_xml4s');
    }
}

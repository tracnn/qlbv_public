<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDanhMucVatTuTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('medical_supply_catalogs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_vat_tu');
            $table->string('nhom_vat_tu');
            $table->string('ten_vat_tu');
            $table->string('ma_hieu')->nullable();
            $table->string('quy_cach')->nullable();
            $table->string('hang_sx')->nullable();
            $table->string('nuoc_sx')->nullable();
            $table->string('don_vi_tinh')->nullable();
            $table->decimal('don_gia', 18, 2)->nullable();
            $table->decimal('don_gia_bh', 18, 2)->nullable();
            $table->decimal('tyle_tt_bh', 18, 2)->nullable();
            $table->integer('so_luong')->nullable();
            $table->integer('dinh_muc')->nullable();
            $table->string('nha_thau')->nullable();
            $table->string('tt_thau');
            $table->string('tu_ngay')->nullable();
            $table->string('den_ngay_hd')->nullable();
            $table->string('ma_cskcb')->nullable();
            $table->integer('loai_thau')->nullable();
            $table->integer('ht_thau')->nullable();
            $table->string('den_ngay')->nullable();
            $table->timestamps();

            // Adding unique constraint
            $table->unique(['ma_vat_tu', 'tt_thau']);

            //Adding index
            $table->index(['ma_vat_tu', 'tt_thau']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('medical_supply_catalogs');
    }
}

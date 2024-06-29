<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrangThietBiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('equipment_catalogs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ten_tb');
            $table->string('ky_hieu');
            $table->string('congty_sx');
            $table->string('nuoc_sx');
            $table->integer('nam_sx');
            $table->integer('nam_sd');
            $table->string('ma_may');
            $table->string('so_luu_hanh');
            $table->string('hd_tu')->nullable();
            $table->string('hd_den')->nullable();
            $table->string('tu_ngay')->nullable();
            $table->string('den_ngay')->nullable();
            $table->timestamps();

            // Adding unique constraint if needed
            $table->unique(['ma_may']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('equipment_catalogs');
    }
}

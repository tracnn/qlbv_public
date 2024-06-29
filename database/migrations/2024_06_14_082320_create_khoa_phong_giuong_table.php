<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateKhoaPhongGiuongTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('department_bed_catalogs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('ma_loai_kcb')->nullable();
            $table->string('ma_khoa');
            $table->string('ten_khoa');
            $table->integer('ban_kham')->nullable();
            $table->integer('giuong_pd')->nullable();
            $table->integer('giuong_2015')->nullable();
            $table->integer('giuong_tk')->nullable();
            $table->integer('giuong_hstc')->nullable();
            $table->integer('giuong_hscc')->nullable();
            $table->integer('ldlk')->nullable();
            $table->integer('lien_khoa')->nullable();
            $table->string('den_ngay')->nullable();
            $table->timestamps();

            // Adding unique constraint if needed
            $table->unique(['ma_khoa']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('department_bed_catalogs');
    }
}

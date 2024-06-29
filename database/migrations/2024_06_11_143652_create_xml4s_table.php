<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateXml4sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xml4s', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ma_lk', 100);
            $table->integer('stt')->nullable();
            $table->string('ma_dich_vu', 50);
            $table->string('ma_chi_so', 50)->nullable();
            $table->string('ten_chi_so', 255)->nullable();
            $table->string('gia_tri', 255)->nullable();
            $table->string('ma_may', 50)->nullable();
            $table->text('mo_ta')->nullable();
            $table->text('ket_luan')->nullable();
            $table->string('ngay_kq', 12);
            $table->timestamps();

            $table->unique(['ma_lk', 'stt'], 'unique_ma_lk_stt');
            $table->index('ma_lk');
            $table->index('ngay_kq');
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
        Schema::dropIfExists('xml4s');
    }
}
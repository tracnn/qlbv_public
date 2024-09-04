<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNhanVienYTeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('medical_staffs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('ma_loai_kcb')->nullable();
            $table->string('ma_khoa');
            $table->string('ten_khoa');
            $table->string('ma_bhxh');
            $table->string('ho_ten');
            $table->tinyInteger('gioi_tinh');
            $table->integer('chucdanh_nn');
            $table->string('vi_tri')->nullable();
            $table->string('macchn');
            $table->string('ngaycap_cchn');
            $table->string('noicap_cchn');
            $table->string('phamvi_cm')->nullable();
            $table->string('phamvi_cmbs')->nullable();
            $table->string('dvkt_khac', 1024)->nullable();
            $table->string('vb_phancong')->nullable();
            $table->integer('thoigian_dk');
            $table->string('thoigian_ngay');
            $table->string('thoigian_tuan');
            $table->string('cskcb_khac')->nullable();
            $table->string('cskcb_cgkt')->nullable();
            $table->string('qd_cgkt')->nullable();
            $table->string('tu_ngay');
            $table->string('den_ngay')->nullable();
            $table->timestamps();

            // Adding unique constraint
            $table->unique(['ma_bhxh']);

            //Adding index
            $table->index(['macchn']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('medical_staffs');
    }
}

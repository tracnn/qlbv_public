<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateXml2Qd130sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qd130_xml2s', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_lk');
            $table->integer('stt');
            $table->string('ma_thuoc')->nullable();
            $table->string('ma_pp_chebien')->nullable();
            $table->string('ma_cskcb_thuoc')->nullable();
            $table->string('ma_nhom')->nullable();
            $table->string('ten_thuoc')->nullable();
            $table->string('don_vi_tinh')->nullable();
            $table->text('ham_luong')->nullable();
            $table->string('duong_dung')->nullable();
            $table->string('dang_bao_che')->nullable();
            $table->text('lieu_dung')->nullable();
            $table->text('cach_dung')->nullable();
            $table->string('so_dang_ky')->nullable();
            $table->string('tt_thau')->nullable();
            $table->integer('pham_vi')->nullable();
            $table->double('tyle_tt_bh')->nullable();
            $table->double('so_luong')->nullable();
            $table->double('don_gia')->nullable();
            $table->double('thanh_tien_bv')->nullable();
            $table->double('thanh_tien_bh')->nullable();
            $table->double('t_nguonkhac_nsnn')->nullable();
            $table->double('t_nguonkhac_vtnn')->nullable();;
            $table->double('t_nguonkhac_vttn')->nullable();;
            $table->double('t_nguonkhac_cl')->nullable();;
            $table->double('t_nguonkhac')->nullable();;
            $table->double('muc_huong')->nullable();;
            $table->double('t_bhtt')->nullable();;
            $table->double('t_bncct')->nullable();;
            $table->double('t_bntt')->nullable();;
            $table->string('ma_khoa')->nullable();;
            $table->string('ma_bac_si')->nullable();;
            $table->string('ma_dich_vu')->nullable();;
            $table->string('ngay_yl')->nullable();;
            $table->string('ngay_th_yl')->nullable();;
            $table->string('ma_pttt')->nullable();;
            $table->integer('nguon_ctra')->nullable();;
            $table->string('vet_thuong_tp')->nullable();
            $table->text('du_phong')->nullable();
            $table->timestamps();

            //Adding index
            $table->unique(['ma_lk', 'stt'], 'unique_ma_lk_stt');
            $table->index('ma_lk');
            $table->index('ma_thuoc');
            $table->index('ma_nhom');
            $table->index('ma_bac_si');
            $table->index('ngay_yl');
            $table->index('ngay_th_yl');
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
        Schema::dropIfExists('qd130_xml2s');
    }
}

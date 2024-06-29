<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCheckHeinCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('check_hein_cards', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_lk', 100);
            $table->string('ma_tracuu', 10);
            $table->string('ma_kiemtra', 10);
            $table->string('ma_ketqua', 255);
            $table->text('ghi_chu')->nullable();
            $table->string('ma_the', 255)->nullable();
            $table->string('ho_ten', 255)->nullable();
            $table->string('ngay_sinh', 100)->nullable();
            $table->string('dia_chi', 255)->nullable();
            $table->string('ma_the_cu', 255)->nullable();
            $table->string('ma_the_moi', 255)->nullable();
            $table->string('ma_dkbd', 255)->nullable();
            $table->string('cq_bhxh', 255)->nullable();
            $table->string('gioi_tinh', 255)->nullable();
            $table->string('gt_the_tu', 255)->nullable();
            $table->string('gt_the_den', 255)->nullable();
            $table->string('ma_kv', 100)->nullable();
            $table->string('ngay_du5nam', 100)->nullable();
            $table->string('maso_bhxh', 255)->nullable();
            $table->string('gt_the_tumoi', 100)->nullable();
            $table->string('gt_the_denmoi', 100)->nullable();
            $table->string('ma_dkbd_moi', 100)->nullable();
            $table->string('ten_dkbd_moi', 255)->nullable();
            $table->timestamps();
            
            $table->index('ma_the');
            $table->unique('ma_lk');
            $table->index('ma_tracuu');
            $table->index('ma_kiemtra');
            $table->index('ma_ketqua');
            $table->index(['created_at','updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('check_hein_cards');
    }
}
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Bang theo doi tung lan sinh XML3176 truc tiep tu HIS (module SinhXml3176).
 * Dong moi nhat (id lon nhat) cua (treatment_id, loai_ban) quyet dinh co sinh lai hay khong.
 */
class CreateSinhXml3176LanSinhTable extends Migration
{
    public function up()
    {
        Schema::create('sinh_xml3176_lan_sinh', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('treatment_id');
            $table->string('treatment_code', 50);
            $table->string('loai_ban', 20);
            $table->bigInteger('fee_lock_time')->nullable();
            $table->bigInteger('out_time')->nullable();
            $table->string('trang_thai', 20);
            $table->text('ly_do')->nullable();
            $table->string('ten_tep', 255)->nullable();
            $table->string('thu_muc', 255)->nullable();
            $table->string('cac_bang', 100)->nullable();
            $table->timestamps();

            $table->index(['treatment_id', 'loai_ban', 'id'], 'sinh_xml3176_lan_sinh_tra_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sinh_xml3176_lan_sinh');
    }
}

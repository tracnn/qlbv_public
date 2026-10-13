<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Lich su luot hoc bang tra vat pham kem DVKT me (man quan tri va lenh dong lenh). */
class CreateXml3176VpkdLanHocTable extends Migration
{
    public function up()
    {
        Schema::create('xml3176_vpkd_lan_hoc', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('trang_thai', 20)->index();
            $table->text('ket_qua')->nullable();
            $table->text('loi')->nullable();
            $table->timestamp('bat_dau_luc')->nullable();
            $table->timestamp('xong_luc')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('xml3176_vpkd_lan_hoc');
    }
}

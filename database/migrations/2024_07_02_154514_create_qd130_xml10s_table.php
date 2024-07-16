<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQd130Xml10sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qd130_xml10s', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_lk')->unique();
            $table->string('so_seri')->nullable();
            $table->string('so_ct')->nullable();
            $table->unsignedTinyInteger('so_ngay')->nullable();
            $table->string('don_vi', 1024)->nullable();
            $table->text('chan_doan_rv')->nullable();
            $table->string('tu_ngay')->nullable();
            $table->string('den_ngay')->nullable();
            $table->string('ma_ttdv')->nullable();
            $table->string('ten_bs')->nullable();
            $table->string('ma_bs')->nullable();
            $table->string('ngay_ct')->nullable();
            $table->text('du_phong')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('qd130_xml10s');
    }
}

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
            $table->string('ma_lk', 100)->unique();
            $table->string('so_seri', 200);
            $table->string('so_ct', 200);
            $table->unsignedTinyInteger('so_ngay');
            $table->string('don_vi', 1024);
            $table->text('chan_doan_rv')->nullable();
            $table->string('tu_ngay', 8);
            $table->string('den_ngay', 8);
            $table->string('ma_ttdv', 10);
            $table->string('ten_bs', 255);
            $table->string('ma_bs', 200);
            $table->string('ngay_ct', 8);
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

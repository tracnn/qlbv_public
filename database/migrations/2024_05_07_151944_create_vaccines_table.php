<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVaccinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vaccines', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code')->unique(); // Mã vắc xin duy nhất
            $table->string('name'); // Tên vắc xin
            $table->string('manufacturer')->nullable(); // Nhà sản xuất
            $table->string('recommended_age')->nullable(); // Độ tuổi khuyến cáo
            $table->integer('dose_interval')->nullable(); // Khoảng thời gian giữa các liều (có thể null)
            $table->text('storage_requirements')->nullable(); // Yêu cầu bảo quản (có thể null)
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
        Schema::dropIfExists('vaccines');
    }
}

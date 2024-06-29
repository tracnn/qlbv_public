<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateXmlErrorChecksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xml_error_checks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('xml'); // Ghi nhận lỗi này thuộc XML nào
            $table->string('ma_lk'); // Mã hồ sơ có lỗi
            $table->integer('stt'); // Số thứ tự
            $table->string('ngay_yl')->nullable(); // Ngày y lệnh
            $table->string('ngay_kq')->nullable(); // Ngày kết quả
            $table->string('error_code'); // Mã lỗi
            $table->text('description'); // Mô tả lỗi
            $table->timestamps();

            // Add indexes
            $table->index('xml');
            $table->index('ma_lk');
            $table->index('error_code');
            $table->index(['created_at', 'updated_at']);

            // Add unique constraint
            $table->unique(['xml', 'ma_lk', 'stt', 'error_code']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('xml_error_checks');
    }
}
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVaccinationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vaccinations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('patient_id')->unsigned(); // Khóa ngoại liên kết với bảng Patients
            $table->integer('vaccine_id')->unsigned(); // Khóa ngoại liên kết với bảng Vaccines
            $table->date('date_of_vaccination'); // Ngày tiêm
            $table->integer('dose_number'); // Số liều
            $table->string('administered_amount'); // Liều lượng
            $table->string('administered_by')->nullable(); // Người tiêm
            $table->text('description_effect')->nullable(); // Mô tả tác dụng phụ
            $table->string('severity_effect')->nullable(); // Mức độ nghiêm trọng của tác dụng phụ
            $table->date('date_noted_effect')->nullable(); // Ngày ghi nhận tác dụng phụ

            $table->foreign('patient_id')->references('id')->on('patients')
                  ->onDelete('cascade'); // Thêm ràng buộc khóa ngoại và đặt chính sách xóa
            $table->foreign('vaccine_id')->references('id')->on('vaccines')
                  ->onDelete('cascade'); // Thêm ràng buộc khóa ngoại và đặt chính sách xóa
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
        Schema::dropIfExists('vaccinations');
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePreVaccinationChecksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pre_vaccination_checks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('patient_id')->unsigned(); // Khóa ngoại liên kết với bảng Patients
            $table->integer('vaccine_id')->unsigned(); // Khóa ngoại liên kết với bảng Vaccines
            $table->string('weight')->nullable(); // Cân nặng của bệnh nhân
            $table->string('temperature')->nullable(); // Nhiệt độ của bệnh nhân
            $table->boolean('anaphylactic_reaction')->default(false); // Phản ứng phản vệ mức độ III trở lên
            $table->boolean('acute_or_chronic_disease')->default(false); // Đang mắc bệnh cấp tính hoặc mạn tính tiến triển
            $table->boolean('corticosteroids')->default(false); // Đang hoặc mới kết thúc điều trị corticosteroids
            $table->boolean('fever_or_hypothermia')->default(false); // Bệnh nhân có sốt hoặc hạ thân nhiệt
            $table->boolean('immune_deficiency')->default(false); // Suy giảm miễn dịch
            $table->boolean('abnormal_heart')->default(false); // Bất thường về nghe tim
            $table->boolean('abnormal_lungs')->default(false); // Bất thường về nhịp thở/nghe phổi
            $table->boolean('abnormal_consciousness')->default(false); // Bất thường về tri giác
            $table->text('other_contraindications')->nullable(); // Các chống chỉ định khác nếu có
            $table->boolean('specialist_exam')->default(false); // Khám sàng lọc theo chuyên khoa
            $table->text('specialist_exam_details')->nullable(); // Chi tiết khám sàng lọc chuyên khoa
            $table->boolean('eligible_for_vaccination')->default(true); // Đủ điều kiện tiêm chủng
            $table->boolean('contraindication')->default(false); // Chống chỉ định tiêm chủng
            $table->boolean('postponed')->default(false); // Tạm hoãn tiêm chủng
            $table->timestamp('time')->nullable(); // Thời gian thực hiện kiểm tra
            $table->string('administered_by'); // Người kiểm tra
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')
                  ->onDelete('cascade'); // Thêm ràng buộc khóa ngoại và đặt chính sách xóa
            $table->foreign('vaccine_id')->references('id')->on('vaccines')
                  ->onDelete('cascade'); // Thêm ràng buộc khóa ngoại và đặt chính sách xóa
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pre_vaccination_checks');
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGiaobanReportCellsTable extends Migration
{
    public function up()
    {
        Schema::create('giaoban_report_cells', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('report_id');
            $table->unsignedInteger('dept_config_id')->nullable(); // null = dòng ghi chú/tổng cấp báo cáo
            $table->string('metric_code', 50);
            $table->decimal('auto_value', 12, 2)->nullable();   // null = chỉ tiêu nhập tay thuần
            $table->decimal('manual_value', 12, 2)->nullable(); // null = chưa sửa tay
            $table->text('note')->nullable();                   // dùng cho metric_code = 'note' (ghi chú khoa)
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['report_id', 'dept_config_id', 'metric_code'], 'giaoban_cells_unique');
            $table->index('report_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('giaoban_report_cells');
    }
}

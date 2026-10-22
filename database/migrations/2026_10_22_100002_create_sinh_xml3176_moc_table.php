<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Moc quet theo loai ban (chinh_thuc, thong_tuyen) cua module SinhXml3176.
 */
class CreateSinhXml3176MocTable extends Migration
{
    public function up()
    {
        Schema::create('sinh_xml3176_moc', function (Blueprint $table) {
            $table->string('loai_ban', 20)->primary();
            $table->bigInteger('moc')->default(0);
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sinh_xml3176_moc');
    }
}

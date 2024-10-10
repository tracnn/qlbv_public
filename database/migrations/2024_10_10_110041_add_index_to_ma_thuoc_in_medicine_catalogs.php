<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexToMaThuocInMedicineCatalogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('medicine_catalogs', function (Blueprint $table) {
            // Thêm index cho cột 'ma_thuoc'
            $table->index('ma_thuoc');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('medicine_catalogs', function (Blueprint $table) {
            // Xóa index khi rollback
            $table->dropIndex(['ma_thuoc']);
        });
    }
}

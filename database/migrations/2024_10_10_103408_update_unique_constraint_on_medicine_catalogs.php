<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateUniqueConstraintOnMedicineCatalogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('medicine_catalogs', function (Blueprint $table) {
            // Xóa unique constraint cũ nếu có
            $table->dropUnique('medicine_catalogs_ma_thuoc_ham_luong_so_dang_ky_tt_thau_unique'); // Thay 'ma_thuoc' bằng tên của unique constraint cũ nếu có

            // Thêm unique constraint mới
            $table->unique(['ma_thuoc', 'ten_thuoc', 'ham_luong', 'so_dang_ky', 'don_gia_bh', 
                'tt_thau', 'tu_ngay'], 'unique_medicine_catalog');
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
            // Xóa unique constraint mới
            $table->dropUnique('unique_medicine_catalog');

            // Khôi phục lại unique constraint cũ nếu cần
            $table->unique('medicine_catalogs_ma_thuoc_ham_luong_so_dang_ky_tt_thau_unique'); // Thay bằng tên và cột của unique constraint cũ
        });
    }
}

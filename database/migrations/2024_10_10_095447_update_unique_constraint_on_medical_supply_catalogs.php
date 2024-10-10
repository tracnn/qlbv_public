<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateUniqueConstraintOnMedicalSupplyCatalogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('medical_supply_catalogs', function (Blueprint $table) {
            // Xóa unique constraint cũ nếu có
            $table->dropUnique('medical_supply_catalogs_ma_vat_tu_tt_thau_unique'); // Thay 'ma_vat_tu' bằng tên của unique constraint cũ nếu khác

            // Thêm unique constraint mới
            $table->unique(['ma_vat_tu', 'tt_thau', 'don_gia_bh', 'tu_ngay'], 'unique_medical_supply');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('medical_supply_catalogs', function (Blueprint $table) {
            // Xóa unique constraint mới
            $table->dropUnique('unique_medical_supply');

            // Khôi phục lại unique constraint cũ nếu cần
            $table->unique('medical_supply_catalogs_ma_vat_tu_tt_thau_unique'); // Thay bằng tên và cột của unique constraint cũ
        });
    }
}

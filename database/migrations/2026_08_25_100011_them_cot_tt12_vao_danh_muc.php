<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Bo sung cot TT12 cho bon bang danh muc.
 *
 * VE CAP TU_NGAY_HD / DEN_NGAY_HD: TT12 tach doi y nghia ma bang cu gop lam mot -
 * TU_NGAY/DEN_NGAY la hieu luc cua DONG danh muc, con TU_NGAY_HD/DEN_NGAY_HD la thoi
 * han HOP DONG hoac hop dong cung ung. Cot tu_ngay/den_ngay san co giu nguyen y nghia
 * HIEU LUC - dung voi cach Xml3176Xml3Checker dang doc - va cap _hd la cot moi. Doi y
 * nghia cot cu se lam moi checker dang chay phai sua theo.
 *
 * medical_staffs.ma_cskcb la LOI TIEM AN da co tu truoc: config/catalog_import_mapping.php
 * da anh xa ma_cskcb va CatalogImportService::DANH_MUC_THEO_CO_SO da liet ke
 * medical_staff la danh muc theo tung co so, nhung BANG CHUA HE CO COT NAY. Luong import
 * thu cong dang am tham danh roi ma co so cua nhan vien y te.
 */
class ThemCotTt12VaoDanhMuc extends Migration
{
    public function up()
    {
        Schema::table('medicine_catalogs', function (Blueprint $t) {
            $t->string('tu_ngay_hd', 8)->nullable()->after('tt_thau');
            $t->string('den_ngay_hd', 8)->nullable()->after('tu_ngay_hd');
            $t->text('ma_dvkt')->nullable();
            $t->string('tccl', 50)->nullable();
            $t->string('bo_phan_vt', 5)->nullable();
            $t->string('ten_khoa_hoc', 500)->nullable();
            $t->string('nguon_goc', 500)->nullable();
            $t->text('pp_chebien')->nullable();
            $t->string('ma_dl_nhap', 10)->nullable();
            $t->string('ma_dl_cb', 10)->nullable();
            $t->string('tlhh_cb', 10)->nullable();
            $t->string('tlhh_bq', 10)->nullable();
            $t->string('ma_cskcb_thuoc', 20)->nullable();
        });

        Schema::table('medical_supply_catalogs', function (Blueprint $t) {
            $t->string('so_luu_hanh', 50)->nullable();
            $t->text('tinhnang_kt')->nullable();
            $t->string('tu_ngay_hd', 8)->nullable();
            $t->string('ma_cskcb_tbyt', 20)->nullable();
        });

        Schema::table('service_catalogs', function (Blueprint $t) {
            $t->text('ten_dvkt_gia')->nullable();
            $t->string('so_luong_cgkt', 10)->nullable();
            $t->string('qd_dvkt', 50)->nullable();
            $t->string('qd_pd_gia', 50)->nullable();
            $t->text('ghi_chu')->nullable();
            $t->string('gia_thanh_toan', 20)->nullable();
        });

        Schema::table('equipment_catalogs', function (Blueprint $t) {
            $t->string('ma_cskcb', 20)->nullable()->index();
        });

        Schema::table('medical_staffs', function (Blueprint $t) {
            $t->string('ma_cskcb', 20)->nullable()->index();
        });
    }

    public function down()
    {
        Schema::table('medicine_catalogs', function (Blueprint $t) {
            $t->dropColumn(['tu_ngay_hd', 'den_ngay_hd', 'ma_dvkt', 'tccl', 'bo_phan_vt',
                'ten_khoa_hoc', 'nguon_goc', 'pp_chebien', 'ma_dl_nhap', 'ma_dl_cb',
                'tlhh_cb', 'tlhh_bq', 'ma_cskcb_thuoc']);
        });

        Schema::table('medical_supply_catalogs', function (Blueprint $t) {
            $t->dropColumn(['so_luu_hanh', 'tinhnang_kt', 'tu_ngay_hd', 'ma_cskcb_tbyt']);
        });

        Schema::table('service_catalogs', function (Blueprint $t) {
            $t->dropColumn(['ten_dvkt_gia', 'so_luong_cgkt', 'qd_dvkt', 'qd_pd_gia',
                'ghi_chu', 'gia_thanh_toan']);
        });

        Schema::table('equipment_catalogs', function (Blueprint $t) {
            $t->dropIndex(['ma_cskcb']);
            $t->dropColumn('ma_cskcb');
        });

        Schema::table('medical_staffs', function (Blueprint $t) {
            $t->dropIndex(['ma_cskcb']);
            $t->dropColumn('ma_cskcb');
        });
    }
}

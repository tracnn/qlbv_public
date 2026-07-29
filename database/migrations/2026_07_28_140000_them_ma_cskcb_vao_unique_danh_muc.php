<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Them ma_cskcb vao rang buoc UNIQUE cua ba danh muc theo co so.
 *
 * Dot truoc da them ma_cskcb vao unique_keys trong config/catalog_import_mapping.php nhung
 * KHONG mo rong rang buoc CSDL, nen nhap danh muc cua co so thu hai bi tu choi:
 *   Duplicate entry '...' for key 'unique_medicine_catalog'
 * Loi do bi catch nuot trong CatalogImportService roi continue - dong bi bo IM LANG.
 *
 * Index moi RONG HON index cu nen moi to hop dang hop le van hop le; khong can don du lieu.
 */
class ThemMaCskcbVaoUniqueDanhMuc extends Migration
{
    public function up()
    {
        Schema::table('medicine_catalogs', function (Blueprint $t) {
            $t->dropUnique('unique_medicine_catalog');
            $t->unique(['ma_thuoc', 'ten_thuoc', 'ham_luong', 'so_dang_ky', 'don_gia_bh',
                'tt_thau', 'tu_ngay', 'ma_cskcb'], 'unique_medicine_catalog');
        });

        Schema::table('medical_supply_catalogs', function (Blueprint $t) {
            $t->dropUnique('unique_medical_supply');
            $t->unique(['ma_vat_tu', 'ten_vat_tu', 'tt_thau', 'don_gia_bh', 'tu_ngay',
                'ma_cskcb'], 'unique_medical_supply');
        });

        Schema::table('service_catalogs', function (Blueprint $t) {
            $t->dropUnique('service_catalogs_ma_dich_vu_don_gia_quy_trinh_tu_ngay_unique');
            $t->unique(['ma_dich_vu', 'ten_dich_vu', 'don_gia', 'quy_trinh', 'tu_ngay',
                'ma_cskcb'], 'unique_service_catalog');
        });
    }

    public function down()
    {
        Schema::table('medicine_catalogs', function (Blueprint $t) {
            $t->dropUnique('unique_medicine_catalog');
            $t->unique(['ma_thuoc', 'ten_thuoc', 'ham_luong', 'so_dang_ky', 'don_gia_bh',
                'tt_thau', 'tu_ngay'], 'unique_medicine_catalog');
        });

        Schema::table('medical_supply_catalogs', function (Blueprint $t) {
            $t->dropUnique('unique_medical_supply');
            $t->unique(['ma_vat_tu', 'ten_vat_tu', 'tt_thau', 'don_gia_bh', 'tu_ngay'],
                'unique_medical_supply');
        });

        Schema::table('service_catalogs', function (Blueprint $t) {
            $t->dropUnique('unique_service_catalog');
            $t->unique(['ma_dich_vu', 'ten_dich_vu', 'don_gia', 'quy_trinh', 'tu_ngay'],
                'service_catalogs_ma_dich_vu_don_gia_quy_trinh_tu_ngay_unique');
        });
    }
}

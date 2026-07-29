<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Them ma_cskcb cho service_catalogs.
 *
 * medicine_catalogs va medical_supply_catalogs da co cot nay tu truoc; rieng
 * service_catalogs thi khong, trong khi config/catalog_import_mapping.php VAN khai
 * 'ma_cskcb' cho danh muc dich vu - nen moi lan nhap, gia tri bi bo IM LANG.
 *
 * De nullable va KHONG dien gia tri cho dong cu: dong rong nghia la dung chung moi co so,
 * nho vay trien khai khong lam tat cac kiem tra danh muc dang chay tren may chu that.
 */
class AddMaCskcbToServiceCatalogs extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('service_catalogs', 'ma_cskcb')) {
            return;
        }

        Schema::table('service_catalogs', function (Blueprint $t) {
            $t->string('ma_cskcb')->nullable()->after('ten_dich_vu');
            $t->index('ma_cskcb');
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('service_catalogs', 'ma_cskcb')) {
            return;
        }

        Schema::table('service_catalogs', function (Blueprint $t) {
            $t->dropIndex(['ma_cskcb']);
            $t->dropColumn('ma_cskcb');
        });
    }
}

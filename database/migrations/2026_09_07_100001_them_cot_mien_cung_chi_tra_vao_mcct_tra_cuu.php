<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Hai cot phuc vu cach tinh theo diem c khoan 2 Dieu 18 ND 188/2025/ND-CP.
 *
 * VI SAO KHONG DOI NGHIA COT nguong_ap_dung SAN CO: cac ban ghi cu dang luu 6 x luong co so
 * hien hanh. Doi nghia cot se khien cung mot cot mang hai y nghia khac nhau tuy theo ban ghi
 * duoc tao truoc hay sau ban va nay - khong the phan biet khi doi soat. Giu cot cu nguyen
 * nghia, ghi cach tinh moi vao cot moi.
 */
class ThemCotMienCungChiTraVaoMcctTraCuu extends Migration
{
    public function up()
    {
        Schema::table('mcct_tra_cuu', function (Blueprint $table) {
            // R = (so thang con lai) x luong co so hien hanh. Day moi la con so nguoi benh
            // con phai cung chi tra, khac voi nguong_ap_dung = 6 x luong co so.
            $table->decimal('so_tien_con_phai_dong', 15, 2)->nullable()->after('nguong_ap_dung');

            // A = tong da cung chi tra tu 01/01 den TRUOC ngay doi luong co so. Luu lai de
            // giai thich duoc con so tren khi doi soat ve sau.
            $table->decimal('da_dong_truoc_moc', 15, 2)->nullable()->after('so_tien_con_phai_dong');
        });
    }

    public function down()
    {
        Schema::table('mcct_tra_cuu', function (Blueprint $table) {
            $table->dropColumn(['so_tien_con_phai_dong', 'da_dong_truoc_moc']);
        });
    }
}

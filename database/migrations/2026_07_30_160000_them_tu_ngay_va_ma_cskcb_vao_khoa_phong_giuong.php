<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Danh muc Khoa Phong Giuong: them TU_NGAY va MA_CSKCB.
 *
 * TU_NGAY: tep BHXH cap co cot nay (do tren tep that: 14/91 dong co gia tri, dang chuoi
 * yyyymmdd nhu 20260513) nhung bang khong co cot de luu, nen cot bi bo qua IM LANG - nhap
 * xong khong bao loi gi, chi la mat du lieu. Kieu varchar khop voi den_ngay san co; dung hai
 * kieu khac nhau cho hai cot cung ban chat se gay loi so sanh ve sau.
 *
 * MA_CSKCB: danh muc nay tro thanh danh muc theo tung co so KCB.
 *
 * KHOA DUY NHAT la diem rui ro nhat: khoa cu chi gom ma_khoa, nen co so 01929 va 37470 cung
 * ma khoa K24 se DE LEN NHAU. Them cot khong tu giai quyet viec nay - phai doi khoa.
 * Bang dang 0 dong nen doi khoa khong vuong du lieu trung; de sau khi da nhap thi phai xu ly
 * trung truoc moi doi duoc.
 */
class ThemTuNgayVaMaCskcbVaoKhoaPhongGiuong extends Migration
{
    public function up()
    {
        Schema::table('department_bed_catalogs', function (Blueprint $t) {
            $t->string('tu_ngay')->nullable()->after('lien_khoa');
            $t->string('ma_cskcb', 20)->nullable()->after('tu_ngay')->index();
        });

        Schema::table('department_bed_catalogs', function (Blueprint $t) {
            $t->dropUnique('department_bed_catalogs_ma_khoa_unique');
            $t->unique(['ma_khoa', 'ma_cskcb'], 'unique_department_bed_catalog');
        });
    }

    public function down()
    {
        Schema::table('department_bed_catalogs', function (Blueprint $t) {
            $t->dropUnique('unique_department_bed_catalog');
            $t->unique('ma_khoa', 'department_bed_catalogs_ma_khoa_unique');
        });

        Schema::table('department_bed_catalogs', function (Blueprint $t) {
            $t->dropIndex(['ma_cskcb']);
            $t->dropColumn(['tu_ngay', 'ma_cskcb']);
        });
    }
}

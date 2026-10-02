<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Noi khoa duy nhat ba bang danh muc de chua duoc CA HAI dong cua mot lan thay doi.
 *
 * TT12 quy dinh: khi thong tin danh muc thay doi thi gui bang cap nhat gom 02 dong -
 * dong thu nhat ghi thong tin cu voi DEN_NGAY la ngay ngung ap dung, dong thu hai ghi
 * thong tin moi voi TU_NGAY la ngay bat dau va DEN_NGAY de trong. Khoa cu khong co
 * tu_ngay nen hai dong nay DE LEN NHAU va chi mot dong song sot.
 *
 * Ba bang thuoc/vat tu/dich vu DA co tu_ngay va ma_cskcb trong khoa tu migration
 * 2026_07_28_140000 - khong dung toi o day.
 *
 * medical_staffs la ca nang nhat: mau TT12 KHONG CON cot MA_BHXH, ma khoa duy nhat cua
 * bang lai dung la ma_bhxh (da nullable tu 2026_04_09_100001). MySQL cho phep nhieu NULL
 * trong unique index, nen nhap TT12 se chen trung TOAN BO moi lan, am tham. Doi sang
 * so_dinh_danh + ma_khoa + ma_cskcb + tu_ngay: mot nguoi co the lam o hai khoa nen
 * ma_khoa phai nam trong khoa.
 *
 * DON TRUNG TRUOC KHI NOI KHOA. Ban dau migration dem va nem, nhung may chu san pham co
 * 72 to hop trung o medical_staffs (do nhap TT12 khi chua co khoa nay) nen update.bat
 * dung lai moi lan. Nay giu dong id LON NHAT moi nhom (lan nhap moi nhat), con cac dong
 * thua thi CHEP sang bang <ten_bang>_trung_tt12 roi moi xoa - nguoi van hanh van xem lai
 * duoc. Khong co khoa ngoai nao tro vao ba bang nay.
 */
class DoiKhoaDuyNhatDanhMucTt12 extends Migration
{
    public function up()
    {
        $this->donTrung('medical_staffs', ['so_dinh_danh', 'ma_khoa', 'ma_cskcb', 'tu_ngay']);
        $this->donTrung('department_bed_catalogs', ['ma_khoa', 'ma_cskcb', 'tu_ngay']);
        $this->donTrung('equipment_catalogs', ['ma_may', 'ma_cskcb', 'tu_ngay']);

        Schema::table('medical_staffs', function (Blueprint $t) {
            $t->dropUnique('medical_staffs_ma_bhxh_unique');
            $t->index('ma_bhxh');
            $t->unique(['so_dinh_danh', 'ma_khoa', 'ma_cskcb', 'tu_ngay'], 'unique_medical_staff');
        });

        Schema::table('department_bed_catalogs', function (Blueprint $t) {
            $t->dropUnique('unique_department_bed_catalog');
            $t->unique(['ma_khoa', 'ma_cskcb', 'tu_ngay'], 'unique_department_bed_catalog');
        });

        Schema::table('equipment_catalogs', function (Blueprint $t) {
            $t->dropUnique('equipment_catalogs_ma_may_unique');
            $t->unique(['ma_may', 'ma_cskcb', 'tu_ngay'], 'unique_equipment_catalog');
        });
    }

    public function down()
    {
        Schema::table('medical_staffs', function (Blueprint $t) {
            $t->dropUnique('unique_medical_staff');
            $t->dropIndex(['ma_bhxh']);
            $t->unique('ma_bhxh', 'medical_staffs_ma_bhxh_unique');
        });

        Schema::table('department_bed_catalogs', function (Blueprint $t) {
            $t->dropUnique('unique_department_bed_catalog');
            $t->unique(['ma_khoa', 'ma_cskcb'], 'unique_department_bed_catalog');
        });

        Schema::table('equipment_catalogs', function (Blueprint $t) {
            $t->dropUnique('unique_equipment_catalog');
            $t->unique('ma_may', 'equipment_catalogs_ma_may_unique');
        });
    }

    /**
     * Xoa dong trung theo khoa MOI, giu dong id lon nhat moi nhom.
     *
     * So sanh bang '=' nen nhom co cot khoa NULL KHONG bi dung toi: unique index cua MySQL
     * cho phep nhieu NULL, cac dong do khong chan viec noi khoa.
     *
     * Dong bi xoa duoc chep truoc sang <bang>_trung_tt12. CREATE ... LIKE + INSERT IGNORE de
     * chay lai lan hai (migration chet giua chung) khong nhan doi va khong vo.
     */
    private function donTrung($bang, array $khoa)
    {
        $khop = implode(' AND ', array_map(function ($k) {
            return "a.`$k` = b.`$k`";
        }, $khoa));
        $saoLuu = $bang . '_trung_tt12';

        DB::statement("CREATE TABLE IF NOT EXISTS `$saoLuu` LIKE `$bang`");
        DB::statement("INSERT IGNORE INTO `$saoLuu` SELECT a.* FROM `$bang` a"
            . " WHERE EXISTS (SELECT 1 FROM `$bang` b WHERE $khop AND b.id > a.id)");

        DB::delete("DELETE a FROM `$bang` a JOIN `$bang` b ON $khop AND b.id > a.id");
    }
}

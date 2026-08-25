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
 * DEM VA NEM chu khong tu xoa: du lieu danh muc la thu nguoi van hanh phai nhin truoc
 * khi mat. Tren CSDL phat trien cac bang nay dang 0 dong nen migration se chay tron;
 * tren may chu san pham thi chua do.
 */
class DoiKhoaDuyNhatDanhMucTt12 extends Migration
{
    public function up()
    {
        $this->chanNeuTrung('medical_staffs', ['so_dinh_danh', 'ma_khoa', 'ma_cskcb', 'tu_ngay']);
        $this->chanNeuTrung('department_bed_catalogs', ['ma_khoa', 'ma_cskcb', 'tu_ngay']);
        $this->chanNeuTrung('equipment_catalogs', ['ma_may', 'ma_cskcb', 'tu_ngay']);

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
     * Dem so to hop trung theo khoa MOI. Nem kem so lieu neu con trung.
     *
     * Nem chu khong xoa: nguoi van hanh phai duoc nhin va quyet dinh giu dong nao.
     * Migration bao loi thi `php artisan migrate` dung lai va khong bang nao bi doi khoa
     * nua chung - dung dieu ta muon.
     */
    private function chanNeuTrung($bang, array $khoa)
    {
        $dem = DB::table($bang)
            ->select($khoa)
            ->selectRaw('COUNT(*) as so_dong')
            ->groupBy($khoa)
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if (count($dem) === 0) {
            return;
        }

        throw new \RuntimeException(
            'Bang ' . $bang . ' con ' . count($dem) . ' to hop trung theo khoa moi ('
            . implode(', ', $khoa) . '). Xu ly trung truoc roi chay lai migrate. '
            . 'Truy van xem chi tiet: SELECT ' . implode(', ', $khoa) . ', COUNT(*) FROM '
            . $bang . ' GROUP BY ' . implode(', ', $khoa) . ' HAVING COUNT(*) > 1;'
        );
    }
}

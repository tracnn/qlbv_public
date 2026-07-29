<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

/**
 * Cat khoang trang thua o cac cot KHOA cua ba danh muc, va don ban trung sinh ra tu do.
 *
 * Nguyen nhan: o Excel hay dinh TAB o cuoi. Gia tri do truoc day duoc luu nguyen, trong khi
 * khoa so khop khi nhap lai dung trim() cua PHP - von cat ca tab. Hai ben lech nhau nen
 * dong da co bi coi la moi va CHEN THEM MOI LAN NHAP.
 *
 * Da gap that: ma dich vu '24.0019.1685.K.01910' dinh tab sinh 5 ban trong service_catalogs.
 *
 * Luu y: TRIM() cua MySQL chi cat DAU CACH, khong cat tab - nen phai loc bang REGEXP va cat
 * bang PHP, khong dung duoc mot cau UPDATE TRIM().
 */
class CatKhoangTrangThuaTrongKhoaDanhMuc extends Migration
{
    /** Cot khoa can lam sach cua tung bang */
    const BANG = [
        'service_catalogs' => ['ma_dich_vu', 'ten_dich_vu', 'quy_trinh', 'tu_ngay', 'ma_cskcb'],
        'medicine_catalogs' => ['ma_thuoc', 'ten_thuoc', 'ham_luong', 'so_dang_ky', 'tt_thau', 'tu_ngay', 'ma_cskcb'],
        'medical_supply_catalogs' => ['ma_vat_tu', 'ten_vat_tu', 'tt_thau', 'tu_ngay', 'ma_cskcb'],
    ];

    public function up()
    {
        foreach (self::BANG as $bang => $cot) {
            $dieuKien = [];

            foreach ($cot as $c) {
                $dieuKien[] = "$c REGEXP '^[[:space:]]|[[:space:]]$'";
            }

            $ban = DB::table($bang)->whereRaw('(' . implode(' OR ', $dieuKien) . ')')->get();

            foreach ($ban as $r) {
                $moi = [];

                foreach ($cot as $c) {
                    if ($r->$c !== null) {
                        $moi[$c] = trim($r->$c);
                    }
                }

                // Sau khi cat, dong nay co the trung mot dong SACH da co -> xoa ban ban
                // thay vi cap nhat, neu khong se vap rang buoc UNIQUE.
                $q = DB::table($bang)->where('id', '<>', $r->id);

                foreach ($moi as $c => $v) {
                    $q->where($c, $v);
                }

                if ($q->exists()) {
                    DB::table($bang)->where('id', $r->id)->delete();
                    continue;
                }

                DB::table($bang)->where('id', $r->id)->update($moi);
            }
        }
    }

    public function down()
    {
        // Khong the khoi phuc khoang trang da cat, va cung khong nen.
    }
}

<?php

namespace App\Services\SinhXml3176\Bang;

use App\Services\SinhXml3176\DuLieuHoSo;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;

/**
 * Danh sach dich vu tong hop (list2) cua loi HIS, dung chung cho XML1/XML4/XML5.
 * Port HoSoProcessor.CreateNoiDungFile L:325-355 ([AX]:134), phan dieu kien tien/thuc hien:
 * giu dong khi (IS_NO_EXECUTE != 1 va PRICE > 0 va (khong phai BHYT, hoac BHYT co VIR_TOTAL_HEIN_PRICE > 0,
 * hoac BHYT co VIR_TOTAL_HEIN_PRICE == 0 va OTHER_SOURCE_PRICE > 0)) hoac IS_NO_EXECUTE == 1.
 * Giu thu tu $d->dichVu. Phan dieu kien dau cua DLL (loai BHYT, AMOUNT, IS_EXPEND) khong nam o day.
 */
class DichVuTongHop
{
    /** @return array cac dong $d->dichVu thoa list2, giu thu tu */
    public static function loc(DuLieuHoSo $d, HisDanhMuc $dm)
    {
        $idBhyt = $dm->idDoiTuongBhyt();
        $ra = [];
        foreach ($d->dichVu as $o) {
            if (self::thoa($o, $idBhyt)) {
                $ra[] = $o;
            }
        }

        return $ra;
    }

    /** @param int|null $idBhyt null => khong dong nao la BHYT */
    public static function thoa($o, $idBhyt)
    {
        if ((int) $o->is_no_execute === 1) {
            return true;
        }
        if (!((float) $o->price > 0)) {
            return false;
        }

        return $idBhyt === null || (int) $o->patient_type_id !== $idBhyt || self::coTienBhHoacNguonKhac($o);
    }

    /** BHYT: VIR_TOTAL_HEIN_PRICE > 0, hoac == 0 ma OTHER_SOURCE_PRICE > 0 (null khong thoa). */
    public static function coTienBhHoacNguonKhac($o)
    {
        $hein = $o->vir_total_hein_price;
        if (!GiaTriBang::co($hein)) {
            return false;
        }
        if ((float) $hein > 0) {
            return true;
        }

        return (float) $hein == 0 && GiaTriBang::co($o->other_source_price) && (float) $o->other_source_price > 0;
    }
}

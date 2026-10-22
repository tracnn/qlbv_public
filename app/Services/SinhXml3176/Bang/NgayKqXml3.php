<?php

namespace App\Services\SinhXml3176\Bang;

use App\Services\SinhXml3176\DuLieuHoSo;

/**
 * MA_GIUONG (#32, [AX]:500) va NGAY_KQ (#39, [AX]:551-558, DLL L:868-996) cua XML3.
 *
 * Sua loi DLL [AX]:608: giuong khong tinh duoc NGAY_KQ (giuong cuoi, BED_LOG khong co FINISH_TIME, khong co
 *   END/FINISH_TIME) thi roi ve OUT_TIME cua ho so; van rong hoac thoi gian khong hop le thi nem LoiDungBang
 *   (DLL long.Parse("" + "00") vo ca XML3).
 */
class NgayKqXml3
{
    const LOAI_GIUONG = [3, 4, 19, 20];

    public static function laGiuong($ss)
    {
        return GiaTriBang::co($ss->tdl_hein_service_type_id) && in_array((int) $ss->tdl_hein_service_type_id, self::LOAI_GIUONG, true);
    }

    /** MA_GIUONG: BED_CODE (distinct, noi ';') theo (SERVICE_REQ_ID, dich vu, SHARE_COUNT) -> BED_LOG_ID -> khoang thoi gian; khong thay "." */
    public static function maGiuong($ss, array $giuong)
    {
        $tim = function (callable $dk) use ($giuong) {
            return array_values(array_filter($giuong, $dk));
        };
        $ds = $tim(function ($g) use ($ss) {
            return GiaTriBang::co($g->service_req_id) && (int) $g->service_req_id === (int) $ss->service_req_id
                && (int) $g->bed_service_type_id === (int) $ss->service_id && self::bang($g->share_count, $ss->share_count);
        });
        if (count($ds) === 0) {
            $ds = $tim(function ($g) use ($ss) {
                return GiaTriBang::co($ss->bed_log_id) && (int) $g->id === (int) $ss->bed_log_id;
            });
        }
        if (count($ds) === 0) {
            $chiDinh = (float) $ss->tdl_intruction_time;
            $ds = $tim(function ($g) use ($ss, $chiDinh) {
                return (int) $g->bed_service_type_id === (int) $ss->service_id && self::bang($g->share_count, $ss->share_count)
                    && GiaTriBang::co($g->start_time) && (float) $g->start_time <= $chiDinh
                    && GiaTriBang::co($g->finish_time) && (float) $g->finish_time >= $chiDinh;
            });
        }
        if (count($ds) === 0) {
            return '.';
        }
        $ma = [];
        foreach ($ds as $g) {
            $ma[GiaTriBang::chu($g->bed_code)] = true;
        }

        return implode(';', array_keys($ma));
    }

    /**
     * @param array $chon cac dong XML3 da chon (DLL list2)
     * @param array $ch cau hinh: bed_time, ngay_kq
     * @throws LoiDungBang
     */
    public static function ngayKq(DuLieuHoSo $d, $ss, $ngayThYl, array $chon, array $ch, $laVatTu)
    {
        $kq = self::phut12(GiaTriBang::co($ss->end_time) ? $ss->end_time : $ss->finish_time);
        $laGiuong = self::laGiuong($ss);

        if ($ch['bed_time'] !== '1') {
            if ($laGiuong) {
                if (GiaTriBang::co($d->hoSo->out_time)) {
                    $kq = self::phut12($d->hoSo->out_time);
                }
                $sau = self::mocSauNhat(array_filter($chon, function ($o) use ($ss) {
                    return self::laGiuong($o) && (string) $o->id !== (string) $ss->id;
                }), $ss);
                if ($sau !== null) {
                    $kq = self::phut12($sau);
                }

                return self::chanTran($d, $ss, $kq, $ngayThYl);
            }

            return self::khongPhaiGiuong($ss, $kq, $ngayThYl, $ch, $laVatTu);
        }

        $nhat = null;
        if (GiaTriBang::co($ss->bed_log_id)) {
            foreach ($d->giuong as $g) {
                if ((int) $g->id === (int) $ss->bed_log_id) {
                    $nhat = $g;
                    break;
                }
            }
        }
        if ($nhat === null) {
            return self::khongPhaiGiuong($ss, $kq, $ngayThYl, $ch, $laVatTu);
        }
        if (!$laGiuong) {
            return $kq; // DLL: co BED_LOG nhung khong phai giuong thi giu END/FINISH_TIME
        }
        if (GiaTriBang::co($nhat->finish_time)) {
            $kq = self::phut12($nhat->finish_time);
        }
        $cung = array_filter($d->dichVu, function ($o) use ($ss) {
            return GiaTriBang::co($o->bed_log_id) && (int) $o->bed_log_id === (int) $ss->bed_log_id;
        });
        if (count($cung) > 1) {
            $sau = self::mocSauNhat($cung, $ss);
            if ($sau !== null && (float) $sau > 0) {
                $kq = self::phut12($sau);
            }
        }

        return self::chanTran($d, $ss, $kq, $ngayThYl);
    }

    /** NGAY_KQ_OPTION=1 va VTYT => ""; NGAY_KQ <= NGAY_TH_YL va VTYT => NGAY_TH_YL + 5 phut. */
    private static function khongPhaiGiuong($ss, $kq, $ngayThYl, array $ch, $laVatTu)
    {
        if ($ch['ngay_kq'] === '1' && $laVatTu) {
            return '';
        }
        if (trim($kq) !== '' && $laVatTu && (float) $kq <= (float) $ngayThYl) {
            $t = ThoiGianHis::ngay($ngayThYl . '00');
            if ($t !== null) {
                $t->modify('+5 minutes');

                return ThoiGianHis::phut($t);
            }
        }

        return $kq;
    }

    /** Chan tran giuong: AMOUNT >= 1 => <= NGAY_TH_YL + AMOUNT*24h; AMOUNT < 1 => <= NGAY_TH_YL + 4h. */
    private static function chanTran(DuLieuHoSo $d, $ss, $kq, $ngayThYl)
    {
        if ($kq === '' && GiaTriBang::co($d->hoSo->out_time)) {
            $kq = self::phut12($d->hoSo->out_time); // [AX]:608
        }
        if ($kq === '') {
            throw new LoiDungBang('XML3', 'Giường (dịch vụ ID ' . $ss->id . ') không có thời gian kết quả: '
                . 'không có giường kế tiếp, BED_LOG.FINISH_TIME, END/FINISH_TIME, hồ sơ chưa có OUT_TIME');
        }
        $ketQua = ThoiGianHis::ngay($kq . '00');
        $thucHien = ThoiGianHis::ngay($ngayThYl . '00');
        if ($ketQua === null || $thucHien === null) {
            throw new LoiDungBang('XML3', 'Giường (dịch vụ ID ' . $ss->id . ') có thời gian không hợp lệ: NGAY_KQ '
                . $kq . ', NGAY_TH_YL ' . $ngayThYl);
        }
        $luong = (float) $ss->amount;
        $giay = $luong >= 1 ? (int) round($luong * 24 * 3600) : 4 * 3600;
        if ($ketQua->getTimestamp() - $thucHien->getTimestamp() > $giay) {
            $ketQua = clone $thucHien;
            $ketQua->modify('+' . $giay . ' seconds');
        }

        return ThoiGianHis::phut($ketQua);
    }

    /** Moc (USE_TIME ?? TDL_INTRUCTION_TIME) nho nhat trong cac dong co moc sau dong $ss; khong co => null. */
    private static function mocSauNhat(array $dong, $ss)
    {
        $goc = self::moc($ss);
        $nhoNhat = null;
        foreach ($dong as $o) {
            $m = self::moc($o);
            if ($m === null || $goc === null || (float) $m <= (float) $goc) {
                continue;
            }
            if ($nhoNhat === null || (float) $m < (float) $nhoNhat) {
                $nhoNhat = $m;
            }
        }

        return $nhoNhat;
    }

    private static function moc($o)
    {
        return ThoiGianHis::chuoi(GiaTriBang::co($o->use_time) ? $o->use_time : $o->tdl_intruction_time);
    }

    private static function phut12($v)
    {
        $s = ThoiGianHis::chuoi($v);

        return $s === null ? '' : substr($s, 0, 12);
    }

    /** So sanh Nullable<long> kieu C#: null == null la dung. */
    private static function bang($a, $b)
    {
        $coA = GiaTriBang::co($a);
        $coB = GiaTriBang::co($b);

        return $coA === $coB && (!$coA || (int) $a === (int) $b);
    }
}

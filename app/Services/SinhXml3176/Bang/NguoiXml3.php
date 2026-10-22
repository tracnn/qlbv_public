<?php

namespace App\Services\SinhXml3176\Bang;

use App\Services\SinhXml3176\DuLieuHoSo;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;

/**
 * MA_BAC_SI (#33, [AX]:501) va NGUOI_THUC_HIEN (#34, [AX]:540-550, DLL L:594-801, 1089) cua XML3, ban IS_3176.
 *
 * Hai danh sach nhu DLL: $chiCchn (list7, chi DIPLOMA - dung de quyet dinh nhanh) va $ra (list8, DIPLOMA khong
 * co thi CCCD - xuat ra). Quy tac QD3176: nguoi khong co DIPLOMA thi dung CCCD (IDENTIFICATION_NUMBER).
 *
 * Sua loi DLL [AX]:619 (phu): giuong (3/4/19/20) cung ap quy tac CCCD (DLL chi lay DIPLOMA cho nguoi giuong).
 * Giu nguyen DLL [AX]:619: nhanh list11 == null la ma chet (bo); ekip them hai lan (distinct khi noi);
 *   so HST_BHYT_CODE goc voi NGUOI_THUC_HIEN_OPTION (khong trim tung phan, nhu DLL Split(',')).
 */
class NguoiXml3
{
    /** MA_BAC_SI: kham (5) theo MA_BAC_SI_EXAM_OPTION; loai khac DIPLOMA cua REQUEST_LOGINNAME. */
    public static function maBacSi(DuLieuHoSo $d, HisDanhMuc $dm, $ss, $tuyChonKham)
    {
        $dang = [];
        if ((int) $ss->tdl_hein_service_type_id === 5 && GiaTriBang::co($ss->tdl_hein_service_type_id)) {
            if ($tuyChonKham === '1' || $tuyChonKham === '2') {
                $con = array_filter($d->dichVu, function ($o) use ($ss) {
                    return GiaTriBang::co($o->parent_service_req_id) && (int) $o->parent_service_req_id === (int) $ss->service_req_id;
                });
                $con = GiaTriBang::sapOnDinh($con, function ($a, $b) {
                    return GiaTriBang::soSanh((float) $a->intruction_time, (float) $b->intruction_time);
                });
                foreach ($con as $o) {
                    if (GiaTriBang::co($o->request_loginname) && !in_array((string) $o->request_loginname, $dang, true)) {
                        $dang[] = (string) $o->request_loginname;
                    }
                }
                if ($tuyChonKham === '2' && GiaTriBang::co($ss->execute_loginname) && !in_array((string) $ss->execute_loginname, $dang, true)) {
                    $dang[] = (string) $ss->execute_loginname;
                }
            } elseif (GiaTriBang::co($ss->execute_loginname)) {
                $dang[] = (string) $ss->execute_loginname;
            }
        } elseif (GiaTriBang::co($ss->request_loginname)) {
            $dang[] = (string) $ss->request_loginname;
        }

        $ma = [];
        foreach ($dang as $l) {
            $ma[] = self::cchn($dm, $l);
        }

        return GiaTriBang::noiKhacNhau($ma);
    }

    /**
     * @param bool $laVatTu
     * @param string $nhomEkip HIS.QD_130_BYT.NGUOI_THUC_HIEN_OPTION (rong => "0")
     */
    public static function nguoiThucHien(DuLieuHoSo $d, HisDanhMuc $dm, $ss, $laVatTu, $nhomEkip)
    {
        $chiCchn = [];
        $ra = [];
        $them = function ($login) use ($dm, &$chiCchn, &$ra) {
            if (!GiaTriBang::co($login)) {
                return;
            }
            $c = self::cchn($dm, $login);
            if (trim($c) !== '') {
                $chiCchn[] = $c;
                $ra[] = $c;

                return;
            }
            $cccd = self::cccd($dm, $login);
            if (trim($cccd) !== '') {
                $ra[] = $cccd;
            }
        };
        $themEkip = function () use ($d, $ss, $them) {
            if (!GiaTriBang::co($ss->ekip_id)) {
                return;
            }
            foreach ($d->ekip as $e) {
                if ((int) $e->ekip_id === (int) $ss->ekip_id) {
                    $them($e->loginname);
                }
            }
        };

        if (!GiaTriBang::co($ss->tdl_hein_service_type_id)) {
            return '';
        }
        $loai = (int) $ss->tdl_hein_service_type_id;

        if (NgayKqXml3::laGiuong($ss)) {
            $dangNhap = [$ss->execute_loginname, $ss->assigned_execute_loginname, $ss->request_loginname];
            $chon = null;
            foreach ($dangNhap as $l) {
                if (GiaTriBang::co($l) && trim(self::cchn($dm, $l)) !== '') {
                    $chon = $l;
                    break;
                }
            }
            if ($chon === null) { // [AX]:619 (phu): khong ai co DIPLOMA => nguoi dau tien co CCCD
                foreach ($dangNhap as $l) {
                    if (GiaTriBang::co($l) && trim(self::cccd($dm, $l)) !== '') {
                        $chon = $l;
                        break;
                    }
                }
            }
            $them($chon);
        }

        if ($loai !== 5 && !$laVatTu) {
            $nhom = explode(',', $nhomEkip === null || $nhomEkip === '' ? '0' : $nhomEkip);
            if (in_array(GiaTriBang::chu($ss->hst_bhyt_code), $nhom, true)) {
                $themEkip();
            }
            if (count($chiCchn) === 0) {
                $them($ss->sampler_loginname);
                if (trim(GiaTriBang::chu($ss->subclinical_result_loginname)) !== '') {
                    foreach (explode(';', (string) $ss->subclinical_result_loginname) as $l) {
                        $them($l);
                    }
                }
                $them($ss->execute_loginname);
            }
            $themEkip();
            if (count($chiCchn) === 0 && !GiaTriBang::co($ss->start_time)) {
                $them($ss->request_loginname);
            }
        }

        return GiaTriBang::noiKhacNhau($ra);
    }

    private static function cchn(HisDanhMuc $dm, $login)
    {
        $nv = $dm->nhanVien($login);

        return $nv !== null ? GiaTriBang::chu($nv->diploma) : '';
    }

    private static function cccd(HisDanhMuc $dm, $login)
    {
        $nv = $dm->nhanVien($login);

        return $nv !== null ? GiaTriBang::chu($nv->identification_number) : '';
    }
}

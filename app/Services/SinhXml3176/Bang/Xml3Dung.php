<?php

namespace App\Services\SinhXml3176\Bang;

use App\Services\SinhXml3176\DuLieuHoSo;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;

/**
 * XML3 - chi tiet DVKT va VTYT. Port Xml3Processor.ProcessDetailsData (DLL QD130/3176, ban do [AX]:448-582),
 * ban IS_3176. Tien o TienXml3, NGAY_KQ/MA_GIUONG o NgayKqXml3, MA_BAC_SI/NGUOI_THUC_HIEN o NguoiXml3.
 * Chi sua cac loi DLL duoi day (va trong ba lop tren); con lai giu nguyen hanh vi DLL.
 *
 * Sua loi DLL [AX]:605: dong da thuc hien co PRICE = 0 bi bo, tru khi HIS.QD_130_BYT.LAY_CA_DVU_0_DONG == "1"
 *   (DLL: nhanh B lay het, key khong co tac dung). Dong da loc doi tuong thanh toan o HisNapHoSo (idDoiTuongTt).
 * Sua loi DLL [AX]:606: ID doi tuong BHYT lay qua HisDanhMuc::idDoiTuongBhyt(); khong co => nem LoiDungBang
 *   (DLL Convert.ToInt64(ma) vo ca XML3).
 * Sua loi DLL [AX]:607: thieu danh muc doi tuong cua dong => nem LoiDungBang (DLL NullReference mat ca XML3).
 * Sua loi DLL [AX]:617 (phu): hau to _GT tinh mot lan roi moi them _TB (DLL kiem _GT lan hai cho loai 7/22 sau
 *   khi da them _TB nen ra "X_GT_TB_GT").
 * Sua loi DLL [AX]:622: MATERIAL_TT_THAU rong ("" hoac khoang trang) coi nhu null => ghep cac truong thau.
 * Sua loi DLL [AX]:623: TT_THAU ghep cac phan khac rong bang ';', khong du ';' cuoi khi BID_YEAR rong.
 * Sua loi DLL [AX]:624: bo trung theo sere_serv.id (giu dong dau).
 * Moi key cau hinh doc qua HisDanhMuc::config(). MA_MAY ban 3176 khong phu thuoc MA_MAY.HIEN_THI_NGUON_CU_THE.
 */
class Xml3Dung implements DungBang
{
    const LOAI = 'XML3';

    /** TDL_HEIN_SERVICE_TYPE_ID DVKT (listHeinServiceType) */
    const LOAI_DVKT = [1, 2, 3, 4, 5, 7, 8, 13, 18, 19, 20, 22];
    /** VTYT (listHeinServiceTypeMaterial) */
    const LOAI_VAT_TU = [14, 15, 16, 17];
    /** DV khong thuc hien van xuat (hau to _TB) */
    const LOAI_KHONG_TH = [1, 2, 7, 8, 13, 18, 22];

    const KEY_LAY_GIA_0 = 'HIS.QD_130_BYT.LAY_CA_DVU_0_DONG';
    const KEY_BED_TIME = 'HIS.QD_130_BYT.BED_TIME_OPTION';
    const KEY_NHOM_EKIP = 'HIS.QD_130_BYT.NGUOI_THUC_HIEN_OPTION';
    const KEY_MA_BENH = 'HIS.QD_130_BYT.XML3.MA_BENH_OPTION';
    const KEY_NGAY_TH_YL = 'HIS.QD_130_BYT.XML3.NGAY_TH_YL_OPTION';
    const KEY_NGAY_KQ = 'HIS.QD_130_BYT.XML3.NGAY_KQ_OPTION';
    const KEY_GAN_LAI = 'HIS.QD_130_BYT.XML3.GAN_LAI_TIEN_BNTT_BNCCT_OPTION';
    const KEY_BAC_SI_KHAM = 'HIS.QD_130_BYT.XML3.MA_BAC_SI_EXAM_OPTION';

    public function loai(): string
    {
        return self::LOAI;
    }

    /**
     * Dieu kien can (khong phu thuoc cau hinh): co dong DVKT/VTYT da thuc hien hoac DV khong thuc hien loai _TB.
     * Gia 0 va KHONG_LAY_DVU_KHONG_THUC_HIEN can cau hinh nen chi xet trong dung(); dung() co the tra [].
     */
    public function coTrongHoSo(DuLieuHoSo $d): bool
    {
        return count($this->chonDong($d->dichVu, '1', '')) > 0;
    }

    public function dung(DuLieuHoSo $d, HisDanhMuc $dm, array $daDung): array
    {
        $ch = [
            'lay_gia_0' => (string) $dm->config(self::KEY_LAY_GIA_0),
            'khong_lay_khong_th' => (string) $dm->config(Xml2Dung::KEY_KHONG_LAY_KHONG_TH),
            'bed_time' => (string) $dm->config(self::KEY_BED_TIME),
            'nhom_ekip' => (string) $dm->config(self::KEY_NHOM_EKIP),
            'ma_benh' => (string) $dm->config(self::KEY_MA_BENH),
            'ngay_th_yl' => (string) $dm->config(self::KEY_NGAY_TH_YL),
            'ngay_kq' => (string) $dm->config(self::KEY_NGAY_KQ),
            'gan_lai' => (string) $dm->config(self::KEY_GAN_LAI) === '1',
            'bac_si_kham' => (string) $dm->config(self::KEY_BAC_SI_KHAM),
            'gay_te' => (string) $dm->config(Xml2Dung::KEY_GAY_TE),
        ];

        $chon = GiaTriBang::sapOnDinh($this->chonDong($d->dichVu, $ch['lay_gia_0'], $ch['khong_lay_khong_th']), function ($a, $b) {
            return GiaTriBang::soSanh((float) $a->intruction_time, (float) $b->intruction_time);
        });
        if (count($chon) === 0) {
            return [];
        }
        $idBhyt = $dm->idDoiTuongBhyt();
        if ($idBhyt === null) {
            throw new LoiDungBang(self::LOAI, 'Không xác định được đối tượng BHYT trong danh mục');
        }

        // DV cha hop le cho VTYT: nam trong danh sach da chon va co con trong danh sach do (DLL "source")
        $coCon = [];
        foreach ($chon as $ss) {
            if (GiaTriBang::co($ss->parent_id)) {
                $coCon[(string) (int) $ss->parent_id] = true;
            }
        }
        $cha = [];
        foreach ($chon as $ss) {
            if (isset($coCon[(string) (int) $ss->id])) {
                $cha[(string) (int) $ss->id] = $ss;
            }
        }
        $pttt = [];
        foreach ($d->pttt as $p) {
            if (GiaTriBang::co($p->sere_serv_id) && !isset($pttt[(int) $p->sere_serv_id])) {
                $pttt[(int) $p->sere_serv_id] = $p;
            }
        }
        $tein = [];
        foreach ($d->ketQuaCls as $t) {
            if (GiaTriBang::co($t->sere_serv_id)) {
                $tein[(int) $t->sere_serv_id][] = $t;
            }
        }

        $goi = [];
        $ra = [];
        foreach ($chon as $i => $ss) {
            if (!GiaTriBang::co($ss->patient_type_id) || $dm->doiTuong($ss->patient_type_id) === null) {
                throw new LoiDungBang(self::LOAI, 'Không có danh mục đối tượng thanh toán ID ' . $ss->patient_type_id
                    . ' (dịch vụ ID ' . $ss->id . ')');
            }
            $chiDinh = ThoiGianHis::chuoi($ss->intruction_time);
            if ($chiDinh === null || strlen($chiDinh) < 12) {
                throw new LoiDungBang(self::LOAI, 'Dịch vụ ID ' . $ss->id . ' không có thời gian chỉ định hợp lệ');
            }
            $ptDong = isset($pttt[(int) $ss->id]) ? $pttt[(int) $ss->id] : null;
            $chaDong = GiaTriBang::co($ss->parent_id) && isset($cha[(string) (int) $ss->parent_id]) ? $cha[(string) (int) $ss->parent_id] : null;
            $ra[] = $this->dong($d, $dm, $ss, $i + 1, $chon, $chaDong, $ptDong,
                isset($tein[(int) $ss->id]) ? $tein[(int) $ss->id] : [], $goi, $ch, (int) $ss->patient_type_id === $idBhyt);
        }

        return $ra;
    }

    /** Chon dong ([AX]:450-467 + sua [AX]:605, 624). */
    private function chonDong(array $dichVu, $layGia0, $khongLayKhongTh)
    {
        $ra = [];
        foreach ($dichVu as $dv) {
            if (!GiaTriBang::co($dv->tdl_hein_service_type_id)) {
                continue;
            }
            $loai = (int) $dv->tdl_hein_service_type_id;
            if ((int) $dv->is_no_execute === 1) {
                $lay = in_array($loai, self::LOAI_KHONG_TH, true) && $khongLayKhongTh !== '1';
            } else {
                $lay = (in_array($loai, self::LOAI_DVKT, true) || in_array($loai, self::LOAI_VAT_TU, true))
                    && ((float) $dv->price != 0 || $layGia0 === '1');
            }
            $k = (string) $dv->id;
            if ($lay && !isset($ra[$k])) {
                $ra[$k] = $dv;
            }
        }

        return array_values($ra);
    }

    private function dong(DuLieuHoSo $d, HisDanhMuc $dm, $ss, $stt, array $chon, $cha, $pt, array $tein, array &$goi, array $ch, $laBhyt)
    {
        $loai = (int) $ss->tdl_hein_service_type_id;
        $laVatTu = in_array($loai, self::LOAI_VAT_TU, true);
        $tien = TienXml3::tinh($ss, $laVatTu, $laBhyt, $ch['gan_lai']);

        $maDichVu = '';
        $goiVt = '';
        $maKhoa = GiaTriBang::chu($ss->request_bhyt_code);
        $gayTe = (int) $ss->medicine_is_anaesthesia === 1 || ($pt !== null && (int) $pt->is_anaesthesia === 1 && $ch['gay_te'] === '1');
        if ($laVatTu) {
            if ($cha !== null) {
                $maKhoa = GiaTriBang::chu($cha->request_bhyt_code);
                $maDichVu = GiaTriBang::chu($cha->tdl_hein_service_bhyt_code);
                if ($gayTe && substr($maDichVu, -3) !== '_GT') {
                    $maDichVu .= '_GT';
                }
                $k = (string) (int) $ss->parent_id;
                if (!isset($goi[$k])) {
                    $goi[$k] = 'G' . (count($goi) + 1);
                }
                $goiVt = $goi[$k];
            }
        } else {
            $maDichVu = GiaTriBang::chu($ss->tdl_hein_service_bhyt_code);
            if ($gayTe && substr($maDichVu, -3) !== '_GT') {
                $maDichVu .= '_GT';
            }
        }
        if ((int) $ss->is_no_execute === 1 && substr($maDichVu, -3) !== '_TB') {
            $maDichVu .= '_TB';
        }

        $maNhom = GiaTriBang::chu($ss->hst_bhyt_code);
        if (GiaTriBang::co($ss->hst_bhyt_code_in_time) && (float) $d->hoSo->in_time < (float) $ss->hst_bhyt_code_in_time) {
            $maNhom = GiaTriBang::co($ss->old_hst_bhyt_code) ? (string) $ss->old_hst_bhyt_code : $maNhom;
        }

        $ngayYl = substr(ThoiGianHis::chuoi($loai === 5 && GiaTriBang::co($ss->start_time) ? $ss->start_time : $ss->intruction_time), 0, 12);
        $ngayThYl = $this->ngayThYl($ss, $ch['ngay_th_yl']);
        $loaiVt = $laVatTu && (int) $ss->service_id > 0 ? $dm->loaiVatTu($ss->service_id) : null;
        $maXangDau = $dm->maXangDau($ss->service_id);

        return [
            'MA_LK' => GiaTriBang::chu($d->hoSo->treatment_code),
            'STT' => (string) $stt,
            'MA_DICH_VU' => $maDichVu,
            'MA_PTTT_QT' => $pt !== null ? GiaTriBang::noi([$pt->icd_cm_code, $pt->icd_cm_sub_code]) : '',
            'MA_VAT_TU' => $laVatTu ? GiaTriBang::chu($ss->tdl_hein_service_bhyt_code) : '',
            'MA_NHOM' => $maNhom,
            'GOI_VTYT' => $goiVt,
            'TEN_VAT_TU' => $laVatTu ? GiaTriBang::chu($ss->tdl_hein_service_bhyt_name) : '',
            'TEN_DICH_VU' => $laVatTu ? '' : GiaTriBang::chu($ss->tdl_hein_service_bhyt_name),
            'MA_XANG_DAU' => $maXangDau === null ? '' : $maXangDau,
            'DON_VI_TINH' => GiaTriBang::chu($ss->service_unit_name),
            'PHAM_VI' => $tien['ty_le_bh'] > 0 ? '1' : '2',
            'SO_LUONG' => GiaTriBang::so($tien['sl'], 3),
            'DON_GIA_BV' => GiaTriBang::so($tien['dg_bv'], 3),
            'DON_GIA_BH' => GiaTriBang::so($tien['dg_bh'], 3),
            'TT_THAU' => $laVatTu ? $this->ttThau($ss) : '',
            'TYLE_TT_DV' => GiaTriBang::so($tien['ty_le_dv'], 0),
            'TYLE_TT_BH' => GiaTriBang::so($tien['ty_le_bh'], 0),
            'THANH_TIEN_BV' => GiaTriBang::tien($tien['bv']),
            'THANH_TIEN_BH' => GiaTriBang::tien($tien['bh']),
            'T_TRANTT' => $tien['tran'] === null ? '' : GiaTriBang::tien($tien['tran']),
            'MUC_HUONG' => GiaTriBang::co($ss->hein_ratio) ? GiaTriBang::so((float) $ss->hein_ratio * 100, 6) : '0',
            'T_NGUONKHAC_NSNN' => GiaTriBang::tien($tien['nk_nsnn']),
            'T_NGUONKHAC_VTNN' => GiaTriBang::tien($tien['nk_vtnn']),
            'T_NGUONKHAC_VTTN' => GiaTriBang::tien($tien['nk_vttn']),
            'T_NGUONKHAC_CL' => GiaTriBang::tien($tien['nk_cl']),
            'T_NGUONKHAC' => GiaTriBang::tien($tien['nk']),
            'T_BHTT' => GiaTriBang::tien($tien['bhtt']),
            'T_BNTT' => GiaTriBang::tien($tien['bntt']),
            'T_BNCCT' => GiaTriBang::tien($tien['bncct']),
            'MA_KHOA' => $maKhoa,
            'MA_GIUONG' => !$laVatTu && NgayKqXml3::laGiuong($ss) ? NgayKqXml3::maGiuong($ss, $d->giuong) : '',
            'MA_BAC_SI' => NguoiXml3::maBacSi($d, $dm, $ss, $ch['bac_si_kham']),
            'NGUOI_THUC_HIEN' => NguoiXml3::nguoiThucHien($d, $dm, $ss, $laVatTu, $ch['nhom_ekip']),
            'MA_BENH' => $this->maBenh($ss->icd_code, $ss->icd_sub_code, $d->hoSo->icd_code, in_array($ch['ma_benh'], ['1', '3'], true)),
            'MA_BENH_YHCT' => $this->maBenh($ss->traditional_icd_code, $ss->traditional_icd_sub_code,
                $d->hoSo->traditional_icd_code, in_array($ch['ma_benh'], ['2', '3'], true)),
            'NGAY_YL' => $ngayYl,
            'NGAY_TH_YL' => $ngayThYl,
            'NGAY_KQ' => NgayKqXml3::ngayKq($d, $ss, $ngayThYl, $chon, $ch, $laVatTu),
            'MA_PTTT' => '1',
            'VET_THUONG_TP' => '',
            'PP_VO_CAM' => $this->ppVoCam($loai, $pt),
            'VI_TRI_TH_DVKT' => '',
            'MA_MAY' => $this->maMay($ss, $tein),
            'MA_HIEU_SP' => $laVatTu ? (GiaTriBang::chu($ss->material_serial_number) !== ''
                ? (string) $ss->material_serial_number : GiaTriBang::chu($ss->model_code)) : '',
            'TAI_SU_DUNG' => $loaiVt !== null && (int) $loaiVt->is_reusable === 1 ? '1' : '',
            'DU_PHONG' => '',
        ];
    }

    /** NGAY_TH_YL #38: USED_TIME -> BEGIN_TIME -> theo NGAY_TH_YL_OPTION. */
    private function ngayThYl($ss, $tuyChon)
    {
        $chon = function (array $ds) {
            foreach ($ds as $v) {
                if (GiaTriBang::co($v)) {
                    return $v;
                }
            }

            return null;
        };
        if ($tuyChon === '1') {
            $sau = $chon([$ss->sample_time, $ss->start_time, $ss->intruction_time]);
        } elseif ($tuyChon === '2') {
            $sau = $chon([$ss->receive_sample_time, $ss->start_time, $ss->intruction_time]);
        } else {
            $sau = $chon([$ss->start_time, $ss->use_time, $ss->intruction_time]);
        }
        $s = ThoiGianHis::chuoi($chon([$ss->used_time, $ss->begin_time, $sau]));

        return $s === null ? '' : substr($s, 0, 12);
    }

    /** TT_THAU VTYT: MATERIAL_TT_THAU, khong co thi ghep theo MATERIAL_INFORMATION_BID (3, 4, khac). */
    private function ttThau($ss)
    {
        if ($ss->material_tt_thau !== null && trim((string) $ss->material_tt_thau) !== '') { // [AX]:622
            return (string) $ss->material_tt_thau;
        }
        $kieu = GiaTriBang::co($ss->material_information_bid) ? (int) $ss->material_information_bid : null;
        if ($kieu === 3) {
            $phan = [$ss->material_bid_extra_code, $ss->material_bid_year];
        } elseif ($kieu === 4) {
            $phan = [$ss->material_bid_extra_code, $ss->material_bid_package_code, $ss->material_bid_year];
        } else {
            $phan = [$ss->material_bid_extra_code, $ss->material_bid_package_code, $ss->material_bid_group_code,
                $ss->material_bid_year];
        }

        return GiaTriBang::noi($phan); // [AX]:623
    }

    /**
     * MA_BENH / MA_BENH_YHCT #35-36 (giu nguyen DLL [AX]:621: khong kem thi go MOT lan ICD cua ho so,
     * ke ca khi trung ICD cua chinh dich vu).
     */
    private function maBenh($icd, $icdPhu, $icdHoSo, $kemHoSo)
    {
        $ds = [];
        if (trim(GiaTriBang::chu($icd)) !== '') {
            $ds[] = (string) $icd;
        }
        if (trim(GiaTriBang::chu($icdPhu)) !== '') {
            foreach (explode(';', trim((string) $icdPhu, ';')) as $p) {
                $ds[] = $p;
            }
        }
        if ($kemHoSo && trim(GiaTriBang::chu($icdHoSo)) !== '') {
            $ds[] = (string) $icdHoSo;
        }
        if (!$kemHoSo) {
            $vt = array_search(GiaTriBang::chu($icdHoSo), $ds, true);
            if ($vt !== false) {
                array_splice($ds, $vt, 1);
            }
        }
        if (count($ds) === 1) {
            return $ds[0];
        }
        $ra = [];
        foreach ($ds as $m) {
            if (trim($m) !== '' && !in_array($m, $ra, true)) {
                $ra[] = $m;
            }
        }

        return implode(';', $ra);
    }

    /** PP_VO_CAM #42: loai 7/22 co PTTT: EMME_HEIN_CODE -> EMME_SECOND_HEIN_CODE -> "4". */
    private function ppVoCam($loai, $pt)
    {
        if (($loai !== 7 && $loai !== 22) || $pt === null) {
            return '';
        }
        if (GiaTriBang::chu($pt->emme_hein_code) !== '') {
            return (string) $pt->emme_hein_code;
        }

        return GiaTriBang::chu($pt->emme_second_hein_code) !== '' ? (string) $pt->emme_second_hein_code : '4';
    }

    /**
     * MA_MAY #44 ban QD3176: MACHINE_GROUP_CODE.SOURCE_CODE[SOURCE_NAME khi SOURCE_CODE="3"].SERIAL_NUMBER.
     * Co TEIN: ban ghi dau du ba truong, khong co thi ""; khong co TEIN: cac truong tren dong dich vu.
     */
    private function maMay($ss, array $tein)
    {
        $du = function ($o) {
            return GiaTriBang::chu($o->machine_group_code) !== '' && GiaTriBang::chu($o->source_code) !== ''
                && GiaTriBang::chu($o->serial_number) !== '';
        };
        $nguon = null;
        if (count($tein) > 0) {
            foreach ($tein as $t) {
                if ($du($t)) {
                    $nguon = $t;
                    break;
                }
            }
        } elseif ($du($ss)) {
            $nguon = $ss;
        }
        if ($nguon === null) {
            return '';
        }
        $ten = (string) $nguon->source_code === '3' && GiaTriBang::chu($nguon->source_name) !== ''
            ? '[' . $nguon->source_name . ']' : '';

        return $nguon->machine_group_code . '.' . $nguon->source_code . $ten . '.' . $nguon->serial_number;
    }
}

<?php

namespace App\Services\SinhXml3176\Bang;

use App\Services\SinhXml3176\DuLieuHoSo;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;

/**
 * XML2 - chi tiet thuoc. Port Xml2Processor.GenerateXml2ADO (DLL QD130/3176, ban do [AX]:350-445),
 * ban IS_3176. Chi sua cac loi DLL duoi day; con lai giu nguyen hanh vi DLL.
 *
 * Sua loi DLL [AX]:586: ID doi tuong BHYT lay qua HisDanhMuc::idDoiTuongBhyt() (DLL lay MA lam ID).
 * Sua loi DLL [AX]:588: thieu danh muc doi tuong cua dong thuoc => nem LoiDungBang (DLL NullReference mat ca XML2).
 * Sua loi DLL [AX]:589: co quy doi don vi thi SO_LUONG, DON_GIA lam tron 3 chu so (DLL khong lam tron).
 * Sua loi DLL [AX]:590: tach dong theo lan dung + quy doi thi SO_LUONG = used.AMOUNT * CONVERT_RATIO.
 * Sua loi DLL [AX]:591: THANH_TIEN_BV/BH tinh tu SO_LUONG, DON_GIA da lam tron (khop gia tri xuat ra).
 * Sua loi DLL [AX]:594: TYLE_TT_BH lam tron so nguyen HALF-UP (DLL round banker); moi phep tinh tien dung dung
 *   ty le xuat ra nay de tep tu nhat quan (THANH_TIEN_BH = SL*DG*TYLE_TT_BH/100).
 * Sua loi DLL [AX]:596: buoc can doi khong de T_NGUONKHAC am; am thi NK=0 va T_BNCCT bu phan chenh.
 * Sua loi DLL [AX]:598: moi key cau hinh doc qua HisDanhMuc::config() (DLL doc 5 key tu cache may tram).
 * Sua loi DLL [AX]:602: MEDICINE_TT_THAU rong ("" hoac khoang trang) coi nhu null => ghep cac truong thau.
 */
class Xml2Dung implements DungBang
{
    const LOAI = 'XML2';

    /** TDL_HEIN_SERVICE_TYPE_ID thuoc (MA_THUOC = ma hoat chat) */
    const LOAI_THUOC = [9, 10, 11, 12];
    /** mau / che pham mau (MA_THUOC = ma dich vu BHYT) */
    const LOAI_MAU = [6, 21];

    const KEY_HUONG_DAN = 'HIS.Desktop.Plugins.AssignPrescription.TutorialFormat';
    const KEY_QUY_NGHEO = 'MOS.POOR_FUND.IS_APPLIED';
    const KEY_TINH_LAI_NK = 'HIS.QD_130_BYT.XML2.TINH_LAI_TIEN_BNTT_BNCCT_NGUON_KHAC_OPTION';
    const KEY_TACH_LAN_DUNG = 'HIS.QD_130_BYT.XML2.NGAY_TH_YL_CUA_THUOC_OPTION';
    const KEY_NGAY_TH_YL = 'HIS.QD_130_BYT.XML2.NGAY_TH_YL_OPTION';
    const KEY_KHONG_LAY_KHONG_TH = 'HIS.QD_130_BYT.XML3.KHONG_LAY_DVU_KHONG_THUC_HIEN';
    const KEY_GAY_TE = 'XML.EXPORT.4210.GAY_TE_OPTION';
    const KEY_AN_NGAY_TH_YL = 'HIS.QD_130_BYT.XML2.DISPLAY_NGAY_TH_YL_OPTION';

    public function loai(): string
    {
        return self::LOAI;
    }

    /**
     * Dieu kien can (khong phu thuoc danh muc): co dong thuoc/mau da thuc hien, gia > 0.
     * Dieu kien tien BHYT va bo dong theo DV cha can danh muc nen chi xet trong dung(); dung() co the tra [].
     */
    public function coTrongHoSo(DuLieuHoSo $d): bool
    {
        return count($this->locSoBo($d->dichVu)) > 0;
    }

    public function dung(DuLieuHoSo $d, HisDanhMuc $dm, array $daDung): array
    {
        $ung = $this->locSoBo($d->dichVu);
        if (count($ung) === 0) {
            return [];
        }
        $idBhyt = $dm->idDoiTuongBhyt();
        if ($idBhyt === null) {
            throw new LoiDungBang(self::LOAI, 'Không xác định được đối tượng BHYT trong danh mục');
        }
        $chon = GiaTriBang::sapOnDinh($this->chonDong($ung, $idBhyt), function ($a, $b) {
            return GiaTriBang::soSanh((float) $a->intruction_time, (float) $b->intruction_time);
        });

        $ch = [
            'ma_bhyt' => trim((string) $dm->config(HisDanhMuc::KEY_MA_DOI_TUONG_BHYT)),
            'huong_dan' => (string) $dm->config(self::KEY_HUONG_DAN),
            'quy_ngheo' => (string) $dm->config(self::KEY_QUY_NGHEO),
            'tinh_lai_nk' => (string) $dm->config(self::KEY_TINH_LAI_NK),
            'tach' => (string) $dm->config(self::KEY_TACH_LAN_DUNG),
            'ngay_th_yl' => (string) $dm->config(self::KEY_NGAY_TH_YL),
            'khong_lay_khong_th' => (string) $dm->config(self::KEY_KHONG_LAY_KHONG_TH),
            'gay_te' => (string) $dm->config(self::KEY_GAY_TE),
            'an_ngay_th_yl' => (string) $dm->config(self::KEY_AN_NGAY_TH_YL),
        ];

        $theoId = [];
        foreach ($d->dichVu as $dv) {
            $k = (string) $dv->id;
            if (!isset($theoId[$k])) {
                $theoId[$k] = $dv;
            }
        }
        $ptttTheoDv = [];
        foreach ($d->pttt as $p) {
            if ((int) $p->sere_serv_id > 0 && !isset($ptttTheoDv[(int) $p->sere_serv_id])) {
                $ptttTheoDv[(int) $p->sere_serv_id] = $p;
            }
        }

        $ra = [];
        $stt = 1;
        foreach ($chon as $ss) {
            $dt = GiaTriBang::co($ss->patient_type_id) && (int) $ss->patient_type_id > 0 ? $dm->doiTuong($ss->patient_type_id) : null;
            if ($dt === null) {
                throw new LoiDungBang(self::LOAI, 'Không có danh mục đối tượng thanh toán ID ' . $ss->patient_type_id
                    . ' (dịch vụ ID ' . $ss->id . ')');
            }
            $ngayYl = ThoiGianHis::chuoi($ss->intruction_time);
            if ($ngayYl === null || strlen($ngayYl) < 12) {
                throw new LoiDungBang(self::LOAI, 'Dịch vụ ID ' . $ss->id . ' không có thời gian chỉ định hợp lệ');
            }

            $maDichVu = '';
            $cha = GiaTriBang::co($ss->parent_id) && isset($theoId[(string) $ss->parent_id]) ? $theoId[(string) $ss->parent_id] : null;
            if ($cha !== null) {
                if ($ch['khong_lay_khong_th'] === '1' && (int) $cha->is_no_execute === 1) {
                    continue; // L:738-744, STT khong tang
                }
                $maDichVu = GiaTriBang::chu($cha->tdl_hein_service_bhyt_code);
                if ($maDichVu === '') {
                    $maDichVu = GiaTriBang::chu($ss->tdl_hein_service_bhyt_code);
                }
                if (count($d->pttt) > 0) { // DLL: can danh sach PTTT khac null ([AX]:599, giu nguyen)
                    $pt = isset($ptttTheoDv[(int) $cha->id]) ? $ptttTheoDv[(int) $cha->id] : null;
                    if ((($pt !== null && (int) $pt->is_anaesthesia === 1) || (int) $ss->medicine_is_anaesthesia === 1)
                        && $ch['gay_te'] === '1' && substr($maDichVu, -3) !== '_GT') {
                        $maDichVu .= '_GT';
                    }
                }
            }

            $lanDung = ($ch['tach'] === '1' && GiaTriBang::co($ss->medicine_id)) ? $this->lanDung($d->suDungThuoc, $ss) : [];
            if (count($lanDung) === 0) {
                $lanDung = [null];
            }

            foreach ($lanDung as $lan) {
                $ra[] = $this->dong($d, $dm, $ss, $dt, $lan, $ch, $stt, $maDichVu, substr($ngayYl, 0, 12));
                $stt++;
            }
        }

        return $ra;
    }

    /** L:350-400 dieu kien 1-3: loai thuoc/mau, da thuc hien, PRICE > 0. */
    private function locSoBo(array $dichVu)
    {
        $ra = [];
        foreach ($dichVu as $dv) {
            if (!GiaTriBang::co($dv->tdl_hein_service_type_id)) {
                continue;
            }
            $loai = (int) $dv->tdl_hein_service_type_id;
            if ((in_array($loai, self::LOAI_THUOC, true) || in_array($loai, self::LOAI_MAU, true))
                && (int) $dv->is_no_execute !== 1 && (float) $dv->price > 0) {
                $ra[] = $dv;
            }
        }

        return $ra;
    }

    /** Dieu kien 4 (doi tuong BHYT can tien BH hoac nguon khac) + bo trung theo ID (giu dong dau). */
    private function chonDong(array $ung, $idBhyt)
    {
        $ra = [];
        foreach ($ung as $dv) {
            if ((int) $dv->patient_type_id === $idBhyt) {
                $hein = $dv->vir_total_hein_price;
                $lay = (GiaTriBang::co($hein) && (float) $hein > 0)
                    || (GiaTriBang::co($hein) && (float) $hein == 0 && GiaTriBang::co($dv->other_source_price) && (float) $dv->other_source_price > 0);
                if (!$lay) {
                    continue;
                }
            }
            $k = (string) $dv->id;
            if (!isset($ra[$k])) {
                $ra[$k] = $dv;
            }
        }

        return array_values($ra);
    }

    /** GetMedimateUsedInfos: khop exp_mest_medicine_id -> exp_mest_material_id; USE_TIME > 0, AMOUNT > 0. */
    private function lanDung(array $suDung, $ss)
    {
        $ra = [];
        foreach ($suDung as $u) {
            if (GiaTriBang::co($ss->exp_mest_medicine_id) && GiaTriBang::co($u->exp_mest_medicine_id)) {
                $khop = (int) $u->exp_mest_medicine_id === (int) $ss->exp_mest_medicine_id;
            } elseif (GiaTriBang::co($ss->exp_mest_material_id) && GiaTriBang::co($u->exp_mest_material_id)) {
                $khop = (int) $u->exp_mest_material_id === (int) $ss->exp_mest_material_id;
            } else {
                $khop = false;
            }
            if (!$khop || !GiaTriBang::co($u->use_time) || (float) $u->use_time <= 0
                || !GiaTriBang::co($u->amount) || (float) $u->amount <= 0) {
                continue;
            }
            $ra[] = [
                'use_time' => ThoiGianHis::chuoi($u->use_time),
                'amount' => (float) $u->amount,
                'session' => GiaTriBang::co($u->medication_session) ? (int) $u->medication_session : 0,
            ];
        }

        return GiaTriBang::sapOnDinh($ra, function ($a, $b) {
            return $a['session'] !== $b['session']
                ? GiaTriBang::soSanh($a['session'], $b['session'])
                : GiaTriBang::soSanh((float) $a['use_time'], (float) $b['use_time']);
        });
    }

    /** Mot dong XML2 (L:440-834). $lan = null khi khong tach theo lan dung. */
    private function dong(DuLieuHoSo $d, HisDanhMuc $dm, $ss, $dt, $lan, array $ch, $stt, $maDichVu, $ngayYl)
    {
        $loai = (int) $ss->tdl_hein_service_type_id;
        $luong = $lan === null ? (float) $ss->amount : $lan['amount'];
        $tien = $this->tien($d, $ss, $dt, $lan, $luong, $ch);

        $nhanVien = GiaTriBang::co($ss->request_loginname) ? $dm->nhanVien($ss->request_loginname) : null;

        return [
            'MA_LK' => GiaTriBang::chu($d->hoSo->treatment_code),
            'STT' => (string) $stt,
            'MA_THUOC' => (in_array($loai, self::LOAI_MAU, true) || !GiaTriBang::co($ss->medicine_id))
                ? GiaTriBang::chu($ss->tdl_hein_service_bhyt_code) : GiaTriBang::chu($ss->active_ingr_bhyt_code),
            'MA_PP_CHEBIEN' => GiaTriBang::noi([$ss->preprocessing_code, $ss->processing_code]),
            'MA_CSKCB_THUOC' => GiaTriBang::chu($ss->transfer_medi_org_code),
            'MA_NHOM' => GiaTriBang::chu($ss->hst_bhyt_code),
            'TEN_THUOC' => GiaTriBang::chu($ss->tdl_hein_service_bhyt_name),
            'DON_VI_TINH' => $tien['quy_doi'] ? GiaTriBang::chu($ss->convert_unit_name) : GiaTriBang::chu($ss->service_unit_name),
            'HAM_LUONG' => GiaTriBang::chu($ss->concentra),
            'DUONG_DUNG' => GiaTriBang::chu($ss->medicine_use_form_code),
            'DANG_BAO_CHE' => GiaTriBang::chu($ss->dosage_form),
            'LIEU_DUNG' => $this->lieuDung($ss, $ch['huong_dan']),
            'CACH_DUNG' => GiaTriBang::chu($ss->medicine_htu_text) !== '' ? GiaTriBang::chu($ss->medicine_htu_text) : GiaTriBang::chu($ss->advise),
            'SO_DANG_KY' => GiaTriBang::chu($ss->medicine_register_number),
            'TT_THAU' => $this->ttThau($ss),
            'PHAM_VI' => $this->phamVi($ss, $tien['ty_le'], $loai),
            'TYLE_TT_BH' => GiaTriBang::so($tien['ty_le'], 0),
            'SO_LUONG' => GiaTriBang::so($tien['sl'], 3),
            'DON_GIA' => GiaTriBang::so($tien['dg'], 3),
            'THANH_TIEN_BV' => GiaTriBang::tien($tien['bv']),
            'THANH_TIEN_BH' => GiaTriBang::tien($tien['bh']),
            'T_NGUONKHAC_NSNN' => GiaTriBang::tien($tien['nguon'] === 1 ? $tien['nk'] : 0),
            'T_NGUONKHAC_VTNN' => GiaTriBang::tien($tien['nguon'] === 2 ? $tien['nk'] : 0),
            'T_NGUONKHAC_VTTN' => GiaTriBang::tien($tien['nguon'] === 3 ? $tien['nk'] : 0),
            'T_NGUONKHAC_CL' => GiaTriBang::tien($tien['cl']),
            'T_NGUONKHAC' => GiaTriBang::tien($tien['nk']),
            'MUC_HUONG' => GiaTriBang::co($ss->hein_ratio) ? GiaTriBang::so((float) $ss->hein_ratio * 100, 6) : '0',
            'T_BHTT' => GiaTriBang::tien($tien['bhtt']),
            'T_BNCCT' => GiaTriBang::tien($tien['bncct']),
            'T_BNTT' => GiaTriBang::tien($tien['bntt']),
            'MA_KHOA' => GiaTriBang::chu($ss->request_bhyt_code),
            'MA_BAC_SI' => $nhanVien !== null ? GiaTriBang::chu($nhanVien->diploma) : '',
            'MA_DICH_VU' => $maDichVu,
            'NGAY_YL' => $ngayYl,
            'NGAY_TH_YL' => $this->ngayThYl($d, $ss, $lan, $ch),
            'MA_PTTT' => '1',
            'NGUON_CTRA' => $tien['bhtt'] > 0 ? '1'
                : (in_array($tien['nguon'], [2, 3], true) ? '2' : ($tien['nguon'] === 1 ? '3' : '4')),
            'VET_THUONG_TP' => '',
            'DU_PHONG' => '',
        ];
    }

    /**
     * Tien L:577-728 ([AX]:421-428). Tra so (float) da lam tron 2 chu so; ty_le la so nguyen da xuat.
     */
    private function tien(DuLieuHoSo $d, $ss, $dt, $lan, $luong, array $ch)
    {
        $goc = (float) $ss->original_price;
        $vat = GiaTriBang::co($ss->vat_ratio) ? (float) $ss->vat_ratio : 0.0;

        $tyLe = 0.0;
        if (trim((string) $dt->patient_type_code) === $ch['ma_bhyt'] && $goc > 0) {
            $tyLe = GiaTriBang::co($ss->hein_limit_price)
                ? (float) $ss->hein_limit_price / ($goc * (1 + $vat)) * 100
                : (float) $ss->price / $goc * 100;
            $tyLe = GiaTriBang::tyLeNguyen($tyLe); // [AX]:594
        }

        $sl = round($luong, 3);
        $dg = round($goc * (1 + $vat), 3);
        $quyDoi = GiaTriBang::co($ss->convert_ratio) && (float) $ss->convert_ratio != 0 && (int) $ss->use_original_unit_for_pres !== 1;
        if ($quyDoi) {
            $tyLeQd = (float) $ss->convert_ratio;
            $sl = round($luong * $tyLeQd, 3);
            $dg = round($goc * (1 + $vat) / $tyLeQd, 3);
        }

        $bv = $bh = $bhtt = $nk = 0.0;
        if ($sl != 0 && $dg != 0) {
            $bv = round($sl * $dg, 2);
            if ($tyLe != 0) {
                $bh = round($sl * $dg * $tyLe / 100, 2);
            }
        }
        if ($bh > 0 && GiaTriBang::co($ss->hein_ratio)) {
            $bhtt = round($bh * (float) $ss->hein_ratio, 2);
        }
        if (GiaTriBang::co($ss->other_source_price)) {
            $nk = round(round((float) $ss->other_source_price, 3) * round($luong, 3), 2);
        }

        $bncct = max(0.0, round($bh - $bhtt - $nk, 2));
        $bntt = round($bv - $bh - $nk, 2) <= 0 ? 0.0 : round($bv - $bh, 2);

        // Buoc 4: NK > 0, VIR_TOTAL_PATIENT_PRICE_BHYT = 0, BNTT = 0: BNCCT < 1 dong => 0 (chenh dung 1 thi giu)
        $virBn = round((float) $ss->vir_total_patient_price_bhyt, 2);
        if ($nk > 0 && $virBn == 0 && $bntt == 0) {
            $r = round($bncct, 2);
            if (abs($r - $virBn) > 1) {
                $bncct = $r;
            } elseif (abs($virBn - $r) < 1) {
                $bncct = $virBn;
            }
        }

        // Buoc 5: can doi BNTT + BNCCT + BHTT + NK = BV
        if ($nk > 0 && abs(round($bntt + $bncct + $bhtt + $nk, 2) - $bv) >= 0.005) {
            $nk = round($bv - $bntt - $bncct - $bhtt, 2);
            if ($nk < 0) { // [AX]:596
                $nk = 0.0;
                $bncct = max(0.0, round($bv - $bhtt - $bntt, 2));
            }
        }

        $nguon = GiaTriBang::co($ss->hein_pay_source_type_id) ? (int) $ss->hein_pay_source_type_id : null;
        $cl = in_array($nguon, [1, 2, 3], true) ? 0.0 : $nk;

        // Buoc 6: tinh lai BNCCT/BNTT theo nguon khac con lai
        $the = (string) $ss->hein_card_number;
        $quyNgheo = $ch['quy_ngheo'] === '1' && $the !== ''
            && (strpos($the, 'CN') === 0 || (strpos($the, 'GD') === 0 && $this->coHnCode($ss->json_patient_type_alter)));
        if (($ch['tinh_lai_nk'] === '1' || $quyNgheo) && $cl > 0 && $bntt > 0) {
            $bncct = round($bncct + $cl, 2);
            $bntt = max(0.0, round($bntt - $cl, 2));
        }

        return [
            'ty_le' => $tyLe, 'quy_doi' => $quyDoi, 'sl' => $sl, 'dg' => $dg,
            'bv' => $bv, 'bh' => $bh, 'bhtt' => $bhtt, 'nk' => $nk, 'cl' => $cl, 'nguon' => $nguon,
            'bncct' => $bncct, 'bntt' => $bntt,
        ];
    }

    private function coHnCode($json)
    {
        if ($json === null || trim((string) $json) === '') {
            return false;
        }
        $o = json_decode((string) $json, true);
        if (!is_array($o) || !array_key_exists('HNCODE', $o) || $o['HNCODE'] === null) {
            return false;
        }
        $v = is_scalar($o['HNCODE']) ? (string) $o['HNCODE'] : json_encode($o['HNCODE']);

        return trim($v) !== '';
    }

    /** PHAM_VI dua tren ty le so nguyen da xuat (cung gia tri dung de tinh THANH_TIEN_BH). */
    private function phamVi($ss, $tyLe, $loai)
    {
        if ($tyLe > 0) {
            $dau = substr((string) $ss->hein_card_number, 0, 2);
            if (in_array($dau, ['CA', 'CY', 'QN'], true) && GiaTriBang::co($ss->medicine_id) && $loai === 9) {
                return '3';
            }

            return '1';
        }

        return $tyLe == 0 ? '2' : '';
    }

    private function ttThau($ss)
    {
        $tt = $ss->medicine_tt_thau;
        if ($tt !== null && trim((string) $tt) !== '') { // [AX]:602
            return (string) $tt;
        }
        $phan = [];
        foreach ([$ss->medicine_bid_extra_code, $ss->medicine_bid_package_code, $ss->medicine_bid_group_code, $ss->medicine_bid_year] as $p) {
            if ($p !== null && trim((string) $p) !== '') {
                $phan[] = (string) $p;
            }
        }

        return implode(';', $phan);
    }

    /** LIEU_DUNG L:483-530 ([AX]:412-420). */
    private function lieuDung($ss, $dinhDang)
    {
        $tut = $ss->tutorial === null ? '' : (string) $ss->tutorial;
        if (trim($tut) === '') {
            return '';
        }
        if (GiaTriBang::co($ss->remedy_count) && (float) $ss->remedy_count != 0) {
            return $tut;
        }
        $hd = GiaTriBang::chu($ss->medicine_tutorial);
        if ($dinhDang === '6' && $hd !== '') {
            return $hd;
        }

        $ld = $this->xuLyHuongDan($tut, $dinhDang, $hd);
        $donVi = GiaTriBang::chu($ss->service_unit_name);
        $den = ThoiGianHis::ngay(GiaTriBang::co($ss->use_time_to) ? $ss->use_time_to : 0);
        $tu = GiaTriBang::co($ss->use_time) ? ThoiGianHis::ngay($ss->use_time) : null;
        $chiDinh = ThoiGianHis::ngay($ss->tdl_intruction_date);
        if ($den !== null && ($tu !== null || $chiDinh !== null)) {
            // co USE_TIME: chia 3 chu so; khong co: tinh tu ngay chi dinh, chia 1 chu so (giu nhu DLL)
            $batDau = $tu !== null ? $tu : $chiDinh;
            $soNgay = (int) $this->ngayCuaThoiGian($batDau)->diff($this->ngayCuaThoiGian($den))->format('%r%a') + 1;
            if ($soNgay <= 0) {
                $soNgay = 1;
            }
            $cs = $tu !== null ? 3 : 1;
            $moiNgay = GiaTriBang::so(round((float) $ss->amount / $soNgay, $cs), $cs);
            $ld .= ' * ' . $soNgay . ' ngày [' . $moiNgay . ' ' . $donVi . '/ngày]';
        }

        return $ld;
    }

    private function ngayCuaThoiGian(\DateTime $t)
    {
        $c = clone $t;
        $c->setTime(0, 0, 0);

        return $c;
    }

    /** ProcessDataTutorial: dinh dang 1/3/6 (6 khi khong co MEDICINE_TUTORIAL) boc cac cum (...). */
    private function xuLyHuongDan($tut, $dinhDang, $hd)
    {
        $text = $tut;
        if (!($dinhDang === '3' || $dinhDang === '1' || ($dinhDang === '6' && $hd === ''))) {
            return $text;
        }
        $cum = [];
        while (strpos($text, '(') !== false && strpos($text, ')') !== false) {
            $a = strrpos($text, '(');
            $b = strpos($text, ')', $a + 1);
            if ($b === false) {
                break;
            }
            $cum[] = substr($text, $a, $b - $a + 1);
            $text = substr($text, 0, $a) . substr($text, $b + 1);
        }
        $text = str_replace(['(', ')'], '', $text);
        if (trim($text) === '' && count($cum) > 0) {
            $dai = $cum[0];
            foreach ($cum as $c) {
                if (mb_strlen($c, 'UTF-8') > mb_strlen($dai, 'UTF-8')) {
                    $dai = $c;
                }
            }
            $text = substr($dai, 1, strlen($dai) - 2);
        }

        return $text;
    }

    /** NGAY_TH_YL [AX]:429-431, ban 3176 (DISPLAY_NGAY_TH_YL_OPTION). */
    private function ngayThYl(DuLieuHoSo $d, $ss, $lan, array $ch)
    {
        if ($ch['an_ngay_th_yl'] === '1' && (int) $d->hoSo->tdl_treatment_type_id !== 3) {
            return '';
        }
        if ($lan !== null) {
            $t = ThoiGianHis::ngay($lan['use_time']);
            if ($t !== null && $lan['session'] >= 1 && $lan['session'] <= 4 && substr($lan['use_time'], 8) === '000000') {
                $t->setTime([1 => 7, 2 => 11, 3 => 15, 4 => 19][$lan['session']], 0, 0);
            }

            return ThoiGianHis::phut($t);
        }

        return $this->ngayThYlDichVu($ss, $ch['ngay_th_yl']);
    }

    /** CalcNgayThYlForSereServ */
    private function ngayThYlDichVu($ss, $phutCong)
    {
        $daDung = ThoiGianHis::ngay($ss->used_time);
        if ($daDung !== null) {
            return ThoiGianHis::phut($daDung);
        }
        $dung = ThoiGianHis::ngay($ss->use_time);
        $ngayCd = ThoiGianHis::ngay($ss->tdl_intruction_date);
        $chiDinh = ThoiGianHis::ngay($ss->intruction_time);
        if ($phutCong !== '') {
            if ($dung !== null && $ngayCd !== null && $dung->format('Ymd') > $ngayCd->format('Ymd')) {
                return $dung->format('Ymd') . ($chiDinh !== null ? $chiDinh->format('Hi') : '');
            }
            if ($chiDinh !== null) {
                if (is_numeric($phutCong)) {
                    $chiDinh->modify(sprintf('%+d minutes', (int) round((float) $phutCong)));
                }

                return ThoiGianHis::phut($chiDinh);
            }

            return '';
        }
        $batDau = ThoiGianHis::ngay($ss->start_time);
        if ($batDau !== null) {
            return ThoiGianHis::phut($batDau);
        }
        $ketThuc = ThoiGianHis::ngay($ss->finish_time);
        if ($ketThuc !== null) {
            return ThoiGianHis::phut($chiDinh !== null && $ketThuc < $chiDinh ? $chiDinh : $ketThuc);
        }

        return ThoiGianHis::phut($chiDinh);
    }
}

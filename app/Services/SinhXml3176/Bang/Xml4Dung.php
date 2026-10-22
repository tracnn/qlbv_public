<?php

namespace App\Services\SinhXml3176\Bang;

use App\Services\SinhXml3176\DuLieuHoSo;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;

/**
 * XML4 - chi tieu chi tiet dich vu can lam sang. Port Xml4Processor.GenerateXml4ADO (DLL XML 130.XML4,
 * ban do [AX]:647-681). XML4 khong co co IS_3176 ([AX]:690): QD 130 va QD3176 xuat giong nhau.
 * Dau vao la list2 (DichVuTongHop::loc), roi loc buoc 1; sinh ba khoi A (Tein), B (khong chi so), C (Suin),
 * STT tang lien tuc A -> B -> C.
 *
 * Sua loi DLL [AX]:695: SUBCLINICAL_RESULT_LOGINNAME la chuoi rong coi nhu null, roi ve EXECUTE_LOGINNAME de tra DIPLOMA.
 * Sua loi DLL [AX]:699: danh sach dong null/rong khong nem loi (DLL MapADOToXml dung && => NullReference); tra [].
 * Giu nhu DLL (can xac nhan): [AX]:694 MA_BS_DOC_KQ roi ve login name tho; [AX]:696 ma/ten chi so khoi A chon
 *   doc lap (co the lech cap); [AX]:697 NGAY_KQ khoi A/C theo FINISH_TIME, khoi B theo END_TIME.
 * Key HIS.QD_130_BYT.XML4.LAY_DVU_PTTT: DLL doc cau hinh toan cuc may tram, module doc HIS_CONFIG qua HisDanhMuc::config().
 */
class Xml4Dung implements DungBang
{
    const LOAI = 'XML4';

    const KEY_LAY_DVU_PTTT = 'HIS.QD_130_BYT.XML4.LAY_DVU_PTTT';

    /** CDHA, TDCN, XN (baseHeinTypes) */
    const LOAI_CLS = [1, 8, 18];
    /** PTTT, TT - them khi LAY_DVU_PTTT 1/2 */
    const LOAI_PTTT = [7, 22];
    /** CDHA, TDCN - NGAY_KQ uu tien RESULT_READ_TIME (GetNgayKetQua) */
    const LOAI_HINH_ANH = [1, 8];

    /** Do dai toi da MO_TA/KET_LUAN (GlobalConfigStore.MAX_LENGTH, theo ky tu) */
    const DAI_TOI_DA = 1024;

    public function loai(): string
    {
        return self::LOAI;
    }

    /** Dieu kien can (khong phu thuoc cau hinh, list2): co dong da thuc hien loai CLS/PTTT/TT. */
    public function coTrongHoSo(DuLieuHoSo $d): bool
    {
        foreach ($d->dichVu as $ss) {
            if ($this->thoaBuoc1($ss, 2)) {
                return true;
            }
        }

        return false;
    }

    public function dung(DuLieuHoSo $d, HisDanhMuc $dm, array $daDung): array
    {
        $layPttt = $this->soNguyen($dm->config(self::KEY_LAY_DVU_PTTT));

        $chon = [];
        foreach (DichVuTongHop::loc($d, $dm) as $ss) {
            if ($this->thoaBuoc1($ss, $layPttt)) {
                $chon[] = $ss;
            }
        }
        if (count($chon) === 0) {
            return [];
        }
        $theoId = [];
        foreach ($chon as $ss) {
            $k = (string) (int) $ss->id;
            if (!isset($theoId[$k])) { // FirstOrDefault
                $theoId[$k] = $ss;
            }
        }

        $maLk = GiaTriBang::chu($d->hoSo->treatment_code);
        $ra = [];
        $stt = 1;

        // Khoi A: Tein sap theo ID, Tein thuoc dich vu da loc
        $tein = GiaTriBang::sapOnDinh($d->ketQuaCls, function ($a, $b) {
            return GiaTriBang::soSanh((float) $a->id, (float) $b->id);
        });
        $coTein = [];
        foreach ($tein as $t) {
            if (GiaTriBang::co($t->sere_serv_id)) {
                $coTein[(string) (int) $t->sere_serv_id] = true;
            }
            $k = GiaTriBang::co($t->sere_serv_id) ? (string) (int) $t->sere_serv_id : null;
            if ($k === null || !isset($theoId[$k])) {
                continue;
            }
            $ss = $theoId[$k];
            $ra[] = $this->dong($maLk, $stt++, $ss, $dm,
                $this->dauTienCo([$t->bhyt_code, $t->antibiotic_resistance_code]),
                $this->dauTienCo([$t->bhyt_name, $t->antibiotic_resistance_name]),
                $t->value, $t->test_index_unit_name, $t->result_description, $ss->finish_time);
        }

        $coSuin = [];
        foreach ($d->chiSoCdha as $s) {
            if (GiaTriBang::co($s->sere_serv_id)) {
                $coSuin[(string) (int) $s->sere_serv_id][] = $s;
            }
        }

        // Khoi B: dich vu khong co Tein va khong co Suin, sap theo INTRUCTION_TIME
        $khongChiSo = [];
        foreach ($chon as $ss) {
            $k = (string) (int) $ss->id;
            if (!isset($coTein[$k]) && !isset($coSuin[$k])) {
                $khongChiSo[] = $ss;
            }
        }
        $khongChiSo = GiaTriBang::sapOnDinh($khongChiSo, function ($a, $b) {
            return GiaTriBang::soSanh((float) $a->intruction_time, (float) $b->intruction_time);
        });
        foreach ($khongChiSo as $ss) {
            $dmDv = $dm->dichVuDanhMuc($ss->service_id);
            $ra[] = $this->dong($maLk, $stt++, $ss, $dm,
                $dmDv !== null ? GiaTriBang::chu($dmDv->suim_index_code) : '',
                $dmDv !== null ? GiaTriBang::chu($dmDv->suim_index_name) : '',
                '', '', $ss->description, $ss->end_time);
        }

        // Khoi C: duyet dich vu da loc theo thu tu dau vao, moi Suin mot dong
        foreach ($chon as $ss) {
            $k = (string) (int) $ss->id;
            if (!isset($coSuin[$k])) {
                continue;
            }
            foreach ($coSuin[$k] as $s) {
                $ma = GiaTriBang::chu($s->suim_index_code);
                $ten = GiaTriBang::chu($s->suim_index_name);
                if ($ma === '' || $ten === '') {
                    $dmDv = $dm->dichVuDanhMuc($ss->service_id);
                    $ma = $dmDv !== null ? GiaTriBang::chu($dmDv->suim_index_code) : '';
                    $ten = $dmDv !== null ? GiaTriBang::chu($dmDv->suim_index_name) : '';
                }
                $ra[] = $this->dong($maLk, $stt++, $ss, $dm, $ma, $ten, $s->value, $s->suim_index_unit_name,
                    $s->description, $ss->finish_time);
            }
        }

        return $ra;
    }

    /** Buoc loc 1 ([AX]:654-657). */
    private function thoaBuoc1($ss, $layPttt)
    {
        if ((int) $ss->is_no_execute === 1 || !GiaTriBang::co($ss->tdl_hein_service_type_id)) {
            return false;
        }
        $loai = (int) $ss->tdl_hein_service_type_id;
        if (in_array($loai, self::LOAI_CLS, true)) {
            return true;
        }
        if (!in_array($loai, self::LOAI_PTTT, true)) {
            return false;
        }

        return $layPttt === 2 || ($layPttt === 1 && !GiaTriBang::co($ss->tracking_id));
    }

    private function dong($maLk, $stt, $ss, HisDanhMuc $dm, $ma, $ten, $giaTri, $donVi, $moTa, $thoiDiem)
    {
        return [
            'MA_LK' => $maLk,
            'STT' => (string) $stt,
            'MA_DICH_VU' => GiaTriBang::chu($ss->tdl_hein_service_bhyt_code),
            'MA_CHI_SO' => (string) $ma,
            'TEN_CHI_SO' => (string) $ten,
            'GIA_TRI' => GiaTriBang::chu($giaTri),
            'DON_VI_DO' => GiaTriBang::chu($donVi),
            'MO_TA' => GiaTriBang::catChu(GiaTriBang::chu($moTa), self::DAI_TOI_DA),
            'KET_LUAN' => GiaTriBang::catChu(GiaTriBang::chu($ss->conclude), self::DAI_TOI_DA),
            'NGAY_KQ' => $this->ngayKq($ss, $thoiDiem),
            'MA_BS_DOC_KQ' => $this->maBsDocKq($ss, $dm),
            'DU_PHONG' => '',
        ];
    }

    /** GetNgayKetQua: CDHA/TDCN RESULT_READ_TIME ?? END_TIME ?? FINISH_TIME ?? START_TIME ?? INTRUCTION_TIME. */
    private function ngayKq($ss, $thoiDiem)
    {
        if (in_array((int) $ss->tdl_hein_service_type_id, self::LOAI_HINH_ANH, true)) {
            $thoiDiem = null;
            foreach ([$ss->result_read_time, $ss->end_time, $ss->finish_time, $ss->start_time, $ss->intruction_time] as $t) {
                if (GiaTriBang::co($t)) {
                    $thoiDiem = $t;
                    break;
                }
            }
        }
        $s = ThoiGianHis::chuoi($thoiDiem);

        return $s === null ? '' : substr($s, 0, 12);
    }

    /**
     * Mac dinh EXECUTE_LOGINNAME tho; tach (SUBCLINICAL_RESULT_LOGINNAME ?? EXECUTE_LOGINNAME) theo ';',
     * lay DIPLOMA cua nguoi dau tien co DIPLOMA khac rong.
     * Sua loi DLL [AX]:695: SUBCLINICAL_RESULT_LOGINNAME rong ("") coi nhu null.
     */
    private function maBsDocKq($ss, HisDanhMuc $dm)
    {
        $ma = GiaTriBang::chu($ss->execute_loginname);
        $nguon = GiaTriBang::co($ss->subclinical_result_loginname) ? $ss->subclinical_result_loginname : $ss->execute_loginname;
        foreach (GiaTriBang::tachMa($nguon) as $login) {
            $nv = $dm->nhanVien($login);
            if ($nv !== null && GiaTriBang::chu($nv->diploma) !== '') {
                return (string) $nv->diploma;
            }
        }

        return $ma;
    }

    /** Chuoi dau tien khac null/"" (string.IsNullOrEmpty, khong trim); khong co => "". */
    private function dauTienCo(array $phan)
    {
        foreach ($phan as $p) {
            if (GiaTriBang::co($p)) {
                return (string) $p;
            }
        }

        return '';
    }

    /** int.TryParse: rong/khong phai so nguyen => 0. */
    private function soNguyen($v)
    {
        $v = trim((string) $v);

        return preg_match('/^[+-]?\d+$/', $v) ? (int) $v : 0;
    }
}

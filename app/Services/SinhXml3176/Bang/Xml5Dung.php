<?php

namespace App\Services\SinhXml3176\Bang;

use App\Services\SinhXml3176\DuLieuHoSo;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;

/**
 * XML5 - chi tieu chi tiet dien bien lam sang. Port Xml5Processor.GenerateXml5ADO + ProcessDataExam
 * (DLL XML130.XML5, ban do [AX]:705-764), luon theo nhanh IS_3176.
 * Dau vao ListSereServ = list2 (DichVuTongHop::loc). Nhanh 1: co to dieu tri => moi to co CONTENT mot dong,
 * sap theo TRACKING_TIME. Nhanh 2: khong co to dieu tri nao, co kham chinh => mot dong tu du lieu kham.
 *
 * Sua loi DLL [AX]:759: nhan vien LOGINNAME null khong lam vo (DLL o.LOGINNAME.Equals => NullReference, mat ca XML5);
 *   tra nhan vien qua HisDanhMuc::nhanVien (bo dong loginname rong).
 * Sua loi DLL [AX]:761: PHAU_THUAT bo DESCRIPTION null/rong, khong co ';' thua.
 * Sua loi DLL [AX]:764: danh sach null/rong khong nem loi; tra [].
 * Sua loi DLL [AX]:725 (Xml5Processor.cs:135): HOI_CHAN bo CONCLUSION null/rong, khong co "; " thua.
 * Giu nhu DLL (can xac nhan): [AX]:755-756 co to dieu tri ma moi to rong CONTENT => 0 dong (khong roi ve kham);
 *   [AX]:757 NGUOI_THUC_HIEN uu tien MODIFIER truoc CREATOR; [AX]:758 DIPLOMA ?? IDENTIFICATION_NUMBER
 *   (chuoi rong khong roi); [AX]:760 NGAY_YL_OPTION "2" va START_TIME null => rong (DLL null); [AX]:762
 *   TDL_IS_MAIN_EXAM khong xet IS_NO_EXECUTE.
 */
class Xml5Dung implements DungBang
{
    const LOAI = 'XML5';

    const KEY_NGAY_YL_OPTION = 'XML.EXPORT.4210.XML3.NGAY_YL_OPTION';

    /** TDL_HEIN_SERVICE_TYPE_ID lay vao PHAU_THUAT (DLL: 7, 22, 2) */
    const LOAI_PHAU_THUAT = [7, 22, 2];

    /** GlobalConfigStore.MAX_LENGTH, theo ky tu */
    const DAI_TOI_DA = 1024;

    public function loai(): string
    {
        return self::LOAI;
    }

    /** Dieu kien can (tren $d->dichVu, khong qua list2). */
    public function coTrongHoSo(DuLieuHoSo $d): bool
    {
        if (count($d->theoDoi) > 0) {
            foreach ($d->theoDoi as $t) {
                if (GiaTriBang::co($t->content)) {
                    return true;
                }
            }

            return false;
        }

        return $this->khamChinh($d->dichVu) !== null;
    }

    public function dung(DuLieuHoSo $d, HisDanhMuc $dm, array $daDung): array
    {
        $dichVu = DichVuTongHop::loc($d, $dm);
        $kham = $this->khamChinh($dichVu);
        $dhstKham = null;
        if ($kham !== null && GiaTriBang::co($kham->dhst_id)) {
            foreach ($d->dhst as $x) {
                if ((string) (int) $x->id === (string) (int) $kham->dhst_id) {
                    $dhstKham = $x;
                    break;
                }
            }
        }
        $maLk = GiaTriBang::chu($d->hoSo->treatment_code);

        if (count($d->theoDoi) > 0) {
            return $this->nhanhTheoDoi($d, $dm, $dichVu, $maLk);
        }
        if ($kham === null) {
            return [];
        }

        return [$this->dong($maLk, 1, GiaTriBang::catChu($this->dienBienKham($kham, $dhstKham), self::DAI_TOI_DA),
            '', '', '', $this->thoiDiemKham($kham, $dm->config(self::KEY_NGAY_YL_OPTION)), $this->nguoiKham($kham, $dm))];
    }

    private function nhanhTheoDoi(DuLieuHoSo $d, HisDanhMuc $dm, array $dichVu, $maLk)
    {
        $to = array_values(array_filter($d->theoDoi, function ($t) {
            return GiaTriBang::co($t->content);
        }));
        $to = GiaTriBang::sapOnDinh($to, function ($a, $b) {
            return GiaTriBang::soSanh((float) $a->tracking_time, (float) $b->tracking_time);
        });

        // DHST theo TRACKING_ID: ban ghi dau cua nhom
        $dhstTheoTo = [];
        foreach ($d->dhst as $x) {
            if (GiaTriBang::co($x->tracking_id)) {
                $k = (string) (int) $x->tracking_id;
                if (!isset($dhstTheoTo[$k])) {
                    $dhstTheoTo[$k] = $x;
                }
            }
        }
        $coPttt = [];
        foreach ($d->pttt as $p) {
            if (GiaTriBang::co($p->sere_serv_id)) {
                $coPttt[(string) (int) $p->sere_serv_id] = true;
            }
        }

        $ra = [];
        $stt = 1;
        foreach ($to as $t) {
            $id = (string) (int) $t->id;
            $phan = ['Diễn biến: ' . self::trimCs($t->content)];
            $csst = isset($dhstTheoTo[$id]) ? $this->csst($dhstTheoTo[$id]) : '';
            if ($csst !== '') {
                $phan[] = $csst;
            }
            if (GiaTriBang::co($t->care_instruction)) {
                $phan[] = 'Theo dõi - chăm sóc: ' . self::trimCs($t->care_instruction);
            }

            $ra[] = $this->dong($maLk, $stt++, GiaTriBang::catChu(implode('; ', $phan), self::DAI_TOI_DA),
                GiaTriBang::chu($t->disease_stage), $this->hoiChan($d->hoiChan, $id),
                $this->phauThuat($dichVu, $coPttt, $id), $this->phut($t->tracking_time), $this->nguoiTheoDoi($t, $dm));
        }

        return $ra;
    }

    private function dong($maLk, $stt, $dienBien, $giaiDoan, $hoiChan, $phauThuat, $thoiDiem, $nguoi)
    {
        return [
            'MA_LK' => $maLk,
            'STT' => (string) $stt,
            'DIEN_BIEN_LS' => (string) $dienBien,
            'GIAI_DOAN_BENH' => (string) $giaiDoan,
            'HOI_CHAN' => (string) $hoiChan,
            'PHAU_THUAT' => (string) $phauThuat,
            'THOI_DIEM_DBLS' => (string) $thoiDiem,
            'NGUOI_THUC_HIEN' => (string) $nguoi,
            'DU_PHONG' => '',
        ];
    }

    /**
     * Dong dau tien co TDL_IS_MAIN_EXAM == 1 (khong xet IS_NO_EXECUTE, [AX]:762); khong co => dich vu kham
     * (TDL_SERVICE_TYPE_ID 1, IS_NO_EXECUTE != 1) som nhat theo TDL_INTRUCTION_TIME (OrderBy on dinh, null dau).
     */
    private function khamChinh(array $dichVu)
    {
        foreach ($dichVu as $ss) {
            if (GiaTriBang::co($ss->tdl_is_main_exam) && (int) $ss->tdl_is_main_exam === 1) {
                return $ss;
            }
        }
        $ung = array_values(array_filter($dichVu, function ($ss) {
            return GiaTriBang::co($ss->tdl_service_type_id) && (int) $ss->tdl_service_type_id === 1
                && (int) $ss->is_no_execute !== 1;
        }));
        $ung = GiaTriBang::sapOnDinh($ung, function ($a, $b) {
            $ca = GiaTriBang::co($a->tdl_intruction_time);
            $cb = GiaTriBang::co($b->tdl_intruction_time);
            if (!$ca || !$cb) {
                return $ca === $cb ? 0 : ($ca ? 1 : -1);
            }

            return GiaTriBang::soSanh((float) $a->tdl_intruction_time, (float) $b->tdl_intruction_time);
        });

        return count($ung) > 0 ? $ung[0] : null;
    }

    /** ProcessDataExam: cac muc IsNullOrWhiteSpace thi bo, gia tri khong trim; noi "; ". */
    private function dienBienKham($kham, $dhst)
    {
        $muc = [
            ['Lý do khám: ', $kham->hospitalization_reason],
            ['Quá trình bệnh lý: ', $kham->pathological_process],
            ['Tiểu sử bệnh của bệnh nhân: ', $kham->pathological_history],
            ['Tiểu sử bệnh của gia đình: ', $kham->pathological_history_family],
            ['Khám toàn thân: ', $kham->full_exam],
            ['Khám bộ phận: ', $kham->part_exam],
        ];
        $phan = [];
        foreach ($muc as $m) {
            if (!self::rongHoacTrang($m[1])) {
                $phan[] = $m[0] . $m[1];
            }
        }
        $csst = $dhst !== null ? $this->csst($dhst) : '';
        if ($csst !== '') {
            $phan[] = $csst;
        }
        foreach ([['Tóm tắt kết quả cls: ', $kham->subclinical], ['Phương pháp điều trị: ', $kham->treatment_instruction]] as $m) {
            if (!self::rongHoacTrang($m[1])) {
                $phan[] = $m[0] . $m[1];
            }
        }

        return implode('; ', $phan);
    }

    /** "CSST<M.._T.._NT.._SP.._W.._H.._B.._C.._U.._HAmax/min_CBG..>"; khong chi so nao => "". */
    private function csst($x)
    {
        $phan = [];
        foreach (['M' => 'pulse', 'T' => 'temperature', 'NT' => 'breath_rate', 'SP' => 'spo2', 'W' => 'weight',
            'H' => 'height', 'B' => 'belly', 'C' => 'chest', 'U' => 'urine'] as $tienTo => $cot) {
            if (GiaTriBang::co($x->$cot)) {
                $phan[] = $tienTo . self::so($x->$cot);
            }
            if ($cot === 'urine' && (GiaTriBang::co($x->blood_pressure_min) || GiaTriBang::co($x->blood_pressure_max))) {
                // C# noi null thanh "": "HA" + MAX + "/" + MIN
                $phan[] = 'HA' . self::so($x->blood_pressure_max) . '/' . self::so($x->blood_pressure_min);
            }
        }
        if (GiaTriBang::co($x->capillary_blood_glucose)) {
            $phan[] = 'CBG' . self::so($x->capillary_blood_glucose);
        }

        return count($phan) > 0 ? 'CSST<' . implode('_', $phan) . '>' : '';
    }

    /**
     * string.Join("; ", CONCLUSION.Distinct()) theo TRACKING_ID; IsNullOrWhiteSpace => "".
     * Sua loi DLL [AX]:725 (Xml5Processor.cs:135): bo CONCLUSION null/rong truoc khi noi (DLL ra "; X" thua dau phan cach).
     */
    private function hoiChan(array $hoiChan, $idTo)
    {
        $ket = [];
        foreach ($hoiChan as $h) {
            if (GiaTriBang::co($h->tracking_id) && (string) (int) $h->tracking_id === $idTo
                && GiaTriBang::co($h->conclusion)) {
                $c = (string) $h->conclusion;
                if (!in_array($c, $ket, true)) {
                    $ket[] = $c;
                }
            }
        }
        $s = implode('; ', $ket);

        return self::rongHoacTrang($s) ? '' : GiaTriBang::catChu($s, self::DAI_TOI_DA);
    }

    /**
     * DESCRIPTION cac dich vu cung to, loai 7/22/2, co ban ghi V_HIS_SERE_SERV_PTTT; noi ';'.
     * Sua loi DLL [AX]:761: bo DESCRIPTION null/rong (DLL de ';' thua o dau).
     */
    private function phauThuat(array $dichVu, array $coPttt, $idTo)
    {
        $mo = [];
        foreach ($dichVu as $ss) {
            if (GiaTriBang::co($ss->tracking_id) && (string) (int) $ss->tracking_id === $idTo
                && GiaTriBang::co($ss->tdl_hein_service_type_id)
                && in_array((int) $ss->tdl_hein_service_type_id, self::LOAI_PHAU_THUAT, true)
                && isset($coPttt[(string) (int) $ss->id])) {
                $mo[] = $ss->description;
            }
        }

        return GiaTriBang::catChu(GiaTriBang::noi($mo), self::DAI_TOI_DA);
    }

    /** DIPLOMA (khac rong) cua MODIFIER, khong co thi cua CREATOR, khong co => "". */
    private function nguoiTheoDoi($t, HisDanhMuc $dm)
    {
        foreach ([$t->modifier, $t->creator] as $login) {
            if (!GiaTriBang::co($login)) {
                continue;
            }
            $nv = $dm->nhanVien($login);
            if ($nv !== null && GiaTriBang::co($nv->diploma)) {
                return (string) $nv->diploma;
            }
        }

        return '';
    }

    /**
     * IS_3176: DIPLOMA ?? IDENTIFICATION_NUMBER cua nhan vien EXECUTE_LOGINNAME (?? khong bat chuoi rong).
     * Sua loi DLL [AX]:759: nhan vien LOGINNAME null khong lam vo.
     */
    private function nguoiKham($kham, HisDanhMuc $dm)
    {
        $nv = GiaTriBang::co($kham->execute_loginname) ? $dm->nhanVien($kham->execute_loginname) : null;
        if ($nv === null) {
            return '';
        }

        return GiaTriBang::chu($nv->diploma !== null ? $nv->diploma : $nv->identification_number);
    }

    /** NGAY_YL_OPTION "2": START_TIME (null => rong); "3": START_TIME roi TDL_INTRUCTION_TIME; khac: TDL_INTRUCTION_TIME. */
    private function thoiDiemKham($kham, $option)
    {
        $option = trim((string) $option);
        if ($option === '2') {
            return $this->phut($kham->start_time);
        }
        if ($option === '3' && GiaTriBang::co($kham->start_time)) {
            return $this->phut($kham->start_time);
        }

        return $this->phut($kham->tdl_intruction_time);
    }

    /** 12 ky tu dau cua so thoi gian HIS; null => "". */
    private function phut($v)
    {
        $s = ThoiGianHis::chuoi($v);

        return $s === null ? '' : substr($s, 0, 12);
    }

    /** string.IsNullOrWhiteSpace. */
    private static function rongHoacTrang($v)
    {
        return $v === null || self::trimCs($v) === '';
    }

    /** String.Trim() cua C#: bo khoang trang Unicode hai dau. */
    private static function trimCs($v)
    {
        // /u tra null khi chuoi khong phai UTF-8 hop le: giu nguyen chuoi goc, khong lam mat noi dung
        $r = preg_replace('/^[\s\p{Z}]+|[\s\p{Z}]+$/u', '', (string) $v);

        return $r === null ? (string) $v : $r;
    }

    /**
     * So DHST nhu C# noi decimal/long vao chuoi: dang ngan nhat, khong 0 thua cuoi (Oracle NUMBER khong giu scale).
     * Oracle/oci8 co the tra ".5" => "0.5". null => "".
     */
    private static function so($v)
    {
        if ($v === null || $v === '') {
            return '';
        }
        if (is_int($v)) {
            return (string) $v;
        }
        $s = is_float($v) ? sprintf('%.10F', $v) : trim((string) $v);
        if (!preg_match('/^-?\d*(\.\d*)?$/', $s) || $s === '' || $s === '-') {
            return (string) $v;
        }
        $s = preg_replace('/^(-?)\./', '${1}0.', $s);
        if (strpos($s, '.') !== false) {
            $s = rtrim(rtrim($s, '0'), '.');
        }

        return $s === '-0' ? '0' : $s;
    }
}

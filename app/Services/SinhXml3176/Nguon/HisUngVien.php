<?php

namespace App\Services\SinhXml3176\Nguon;

use App\Services\SinhXml3176\CauHinhSinhXml3176;
use Illuminate\Support\Facades\DB;

/**
 * Chon ho so ung vien tu HIS (chi doc) theo moc quet + bo loc cau hinh.
 * Ma cau hinh (co so, doi tuong, dien dieu tri) duoc doi sang ID mot lan moi luot bang doiMa().
 */
class HisUngVien
{
    private $cauHinh;
    private $daDoiMa = false;
    private $idCoSo = [];
    private $idDoiTuongBn = [];
    private $idDoiTuongTt = [];
    private $idDienDieuTri = [];

    public function __construct(CauHinhSinhXml3176 $cauHinh)
    {
        $this->cauHinh = $cauHinh;
    }

    /** Doi ma sang ID; ma khong ton tai trong HIS thi nem loi cau hinh. */
    public function doiMa()
    {
        $this->idCoSo = $this->tra('co_so', $this->cauHinh->coSo(), 'his_branch', 'hein_medi_org_code');
        $this->idDoiTuongBn = $this->tra('doi_tuong_bn', $this->cauHinh->doiTuongBn(), 'his_patient_type', 'patient_type_code');
        $this->idDoiTuongTt = $this->tra('doi_tuong_tt', $this->cauHinh->doiTuongTt(), 'his_patient_type', 'patient_type_code');
        $this->idDienDieuTri = $this->tra('dien_dieu_tri', $this->cauHinh->dienDieuTri(), 'his_treatment_type', 'treatment_type_code');
        $this->daDoiMa = true;
    }

    public function idDoiTuongTt()
    {
        $this->canDaDoiMa();

        return $this->idDoiTuongTt;
    }

    /** Tra ve mang stdClass{id, treatment_code, fee_lock_time, out_time}. */
    public function layLo($loaiBan, $tuMoc, $gioiHan)
    {
        $this->canDaDoiMa();

        $q = DB::connection($this->cauHinh->ketNoiHis())->table('his_treatment')
            ->select('id', 'treatment_code', 'fee_lock_time', 'out_time')
            ->where('is_delete', 0);

        if ($loaiBan === CauHinhSinhXml3176::THONG_TUYEN) {
            $q->where('out_time', '>', $tuMoc)
                ->whereNull('fee_lock_time')
                ->orderBy('out_time')->orderBy('id');
        } else {
            $q->where('fee_lock_time', '>', $tuMoc)
                ->whereNotNull('out_time')
                ->orderBy('fee_lock_time')->orderBy('id');
        }

        $this->locIn($q, 'branch_id', $this->idCoSo);
        $this->locIn($q, 'tdl_patient_type_id', $this->idDoiTuongBn);
        $this->locIn($q, 'tdl_treatment_type_id', $this->idDienDieuTri);

        return $q->limit((int) $gioiHan)->get()->all();
    }

    private function locIn($q, $cot, array $ids)
    {
        if (!empty($ids)) {
            $q->whereIn($cot, $ids);
        }
    }

    private function canDaDoiMa()
    {
        if (!$this->daDoiMa) {
            throw new \LogicException('Phai goi doiMa() truoc khi dung HisUngVien');
        }
    }

    private function tra($khoa, array $maDs, $bang, $cotMa)
    {
        if (empty($maDs)) {
            return [];
        }

        $hang = DB::connection($this->cauHinh->ketNoiHis())->table($bang)
            ->whereIn($cotMa, $maDs)
            ->select('id', $cotMa . ' as ma')
            ->get();

        $theoMa = [];
        foreach ($hang as $h) {
            $theoMa[trim((string) $h->ma)] = (int) $h->id;
        }

        foreach ($maDs as $ma) {
            if (!isset($theoMa[$ma])) {
                throw new \InvalidArgumentException('Ma cau hinh khong ton tai trong HIS: ' . $khoa . '=' . $ma);
            }
        }

        return array_values(array_unique(array_values($theoMa)));
    }
}

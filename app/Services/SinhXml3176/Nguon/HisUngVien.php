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
    private $maCoSo = [];

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
        $this->maCoSo = [];
        foreach (DB::connection($this->cauHinh->ketNoiHis())->table('his_branch')->select('id', 'hein_medi_org_code')->get() as $cs) {
            $this->maCoSo[(int) $cs->id] = trim((string) $cs->hein_medi_org_code);
        }
        $this->daDoiMa = true;
    }

    public function idDoiTuongTt()
    {
        $this->canDaDoiMa();

        return $this->idDoiTuongTt;
    }

    /**
     * Mot trang ung vien theo keyset (moc, id): moc > tuMoc HOAC (moc = tuMoc VA id > sauId), sap (moc, id).
     * Moc la fee_lock_time (chinh thuc) hoac out_time (thong tuyen). sauId = 0 => lay ca dong dung bang tuMoc.
     *
     * @param int|null $denMoc chan tren (gom ca bang), null = khong chan
     * @return \stdClass[] {id, treatment_code, fee_lock_time, out_time}
     */
    public function layLo($loaiBan, $tuMoc, $gioiHan, $sauId = 0, $denMoc = null)
    {
        $cot = self::cotMoc($loaiBan);
        $tuMoc = (int) $tuMoc;
        $sauId = (int) $sauId;

        $q = $this->truyVan($loaiBan)
            ->where(function ($w) use ($cot, $tuMoc, $sauId) {
                $w->where($cot, '>', $tuMoc)
                    ->orWhere(function ($x) use ($cot, $tuMoc, $sauId) {
                        $x->where($cot, '=', $tuMoc)->where('id', '>', $sauId);
                    });
            })
            ->orderBy($cot)->orderBy('id');

        if ($denMoc !== null) {
            $q->where($cot, '<=', (int) $denMoc);
        }

        return $q->limit((int) $gioiHan)->get()->all();
    }

    /**
     * Ung vien theo TREATMENT_CODE (chay bu), van ap bo loc cau hinh va dieu kien loai ban.
     * Ma khong co trong HIS (hoac bi loc) thi khong co trong ket qua.
     */
    public function layTheoMa($loaiBan, array $maDs)
    {
        $maDs = array_values(array_unique(array_filter(array_map(function ($m) {
            return trim((string) $m);
        }, $maDs), function ($m) {
            return $m !== '';
        })));

        $ra = [];
        foreach (array_chunk($maDs, 1000) as $nhom) {
            $q = $this->truyVan($loaiBan)->whereIn('treatment_code', $nhom)->orderBy('id');
            foreach ($q->get() as $dong) {
                $ra[] = $dong;
            }
        }

        return $ra;
    }

    /** MACSKCB (his_branch.hein_medi_org_code) cua co so; khong co => null. Nap trong doiMa(). */
    public function maCskcb($branchId)
    {
        $this->canDaDoiMa();
        if ($branchId === null || $branchId === '') {
            return null;
        }
        $k = (int) $branchId;

        return isset($this->maCoSo[$k]) && $this->maCoSo[$k] !== '' ? $this->maCoSo[$k] : null;
    }

    /** Cot moc cua loai ban. */
    public static function cotMoc($loaiBan)
    {
        return $loaiBan === CauHinhSinhXml3176::THONG_TUYEN ? 'out_time' : 'fee_lock_time';
    }

    /** Truy van goc: chua xoa + dieu kien loai ban + bo loc cau hinh. */
    private function truyVan($loaiBan)
    {
        $this->canDaDoiMa();

        $q = DB::connection($this->cauHinh->ketNoiHis())->table('his_treatment')
            ->select('id', 'treatment_code', 'fee_lock_time', 'out_time')
            ->where('is_delete', 0);

        if ($loaiBan === CauHinhSinhXml3176::THONG_TUYEN) {
            $q->whereNull('fee_lock_time')->whereNotNull('out_time');
        } else {
            $q->whereNotNull('fee_lock_time')->whereNotNull('out_time');
        }

        $this->locIn($q, 'branch_id', $this->idCoSo);
        $this->locIn($q, 'tdl_patient_type_id', $this->idDoiTuongBn);
        $this->locIn($q, 'tdl_treatment_type_id', $this->idDienDieuTri);

        return $q;
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

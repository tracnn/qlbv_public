<?php

namespace App\Services\BHYT;

use DB;
use Illuminate\Support\Facades\Log;

/**
 * Tra khoa dieu tri CUOI (his_treatment.last_department_id) cua ho so trong HIS theo ma_lk
 * (= his_treatment.treatment_code).
 *
 * Tra THEO LO: Oracle gioi han IN 1000 phan tu, va tra tung dong thi 50 nghin dong xuat la
 * 50 nghin truy van.
 *
 * NHO ma da tra (ke ca ma khong co trong HIS): file loi XML3176 dung chung mot instance cho
 * 17 sheet, mot ho so loi o XML1, XML3, XML4 chi tra HIS mot lan.
 */
class KhoaDieuTriHis
{
    const LO = 1000;

    /** Gia tri o khoa khi tra HIS loi - de trong se trong nhu ho so "khong co khoa". */
    const LOI = 'Lỗi tra HIS';

    /** @var array ma_lk => ['ma_khoa', 'ten_khoa'] hoac null (khong co trong HIS) */
    protected $daTra = [];

    /**
     * @return array [ma_lk => ['ma_khoa' => ..., 'ten_khoa' => ...]] - ma khong co trong HIS
     *               thi khong co khoa trong mang.
     * @throws \Throwable loi ket noi/truy van HIS; lo loi KHONG duoc nho, lan sau tra lai.
     */
    public function theoMaLk(array $maLk)
    {
        $maLk = $this->chuanHoa($maLk);

        $chuaTra = array_values(array_filter($maLk, function ($m) {
            return !array_key_exists($m, $this->daTra);
        }));

        foreach (array_chunk($chuaTra, self::LO) as $lo) {
            $kq = $this->traLo($lo);

            foreach ($lo as $m) {
                $this->daTra[$m] = isset($kq[$m]) ? $kq[$m] : null;
            }
        }

        $ra = [];

        foreach ($maLk as $m) {
            if ($this->daTra[$m] !== null) {
                $ra[$m] = $this->daTra[$m];
            }
        }

        return $ra;
    }

    /**
     * Dang dung de do thang vao o Excel: moi ma deu co khoa, khong co trong HIS thi null.
     * Mat ket noi HIS KHONG chan xuat - moi o ghi LOI va ghi log.
     *
     * @return array [ma_lk => ['ma_khoa' => ..., 'ten_khoa' => ...]]
     */
    public function khoaChoXuat(array $maLk)
    {
        $maLk = $this->chuanHoa($maLk);

        try {
            $co = $this->theoMaLk($maLk);
            $rong = ['ma_khoa' => null, 'ten_khoa' => null];
        } catch (\Throwable $e) {
            Log::warning('Khong tra duoc khoa HIS khi xuat Excel - ' . $e->getMessage());
            $co = [];
            $rong = ['ma_khoa' => self::LOI, 'ten_khoa' => self::LOI];
        }

        $ra = [];

        foreach ($maLk as $m) {
            $ra[$m] = isset($co[$m]) ? $co[$m] : $rong;
        }

        return $ra;
    }

    protected function chuanHoa(array $maLk)
    {
        return array_values(array_unique(array_filter(array_map(function ($m) {
            return trim((string) $m);
        }, $maLk), 'strlen')));
    }

    protected function traLo(array $lo)
    {
        return DB::connection('HISPro')->table('his_treatment')
            ->leftJoin('his_department', 'his_department.id', '=', 'his_treatment.last_department_id')
            ->whereIn('his_treatment.treatment_code', $lo)
            ->get(['his_treatment.treatment_code', 'his_department.department_code',
                   'his_department.department_name'])
            ->mapWithKeys(function ($r) {
                return [$r->treatment_code => [
                    'ma_khoa' => $r->department_code,
                    'ten_khoa' => $r->department_name,
                ]];
            })
            ->all();
    }
}

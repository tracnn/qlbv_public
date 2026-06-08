<?php
namespace App\Services;

use Illuminate\Http\Request;
use Carbon\Carbon;

class OnTimeResultService
{
    /**
     * Phân loại 1 dòng kết quả: chua_tra | bat_thuong | dung_hen | tre_hen
     */
    public function classify($row)
    {
        if (empty($row->finish_time)) {
            return 'chua_tra';
        }
        if ($row->actual_minutes < 0) {
            return 'bat_thuong';
        }
        return ($row->actual_minutes <= $row->estimate_duration) ? 'dung_hen' : 'tre_hen';
    }

    public function summarize($rows)
    {
        $dung = $tre = $chua = $bat = 0;
        $sumActual = 0;
        foreach ($rows as $r) {
            switch ($this->classify($r)) {
                case 'dung_hen': $dung++; $sumActual += $r->actual_minutes; break;
                case 'tre_hen':  $tre++;  $sumActual += $r->actual_minutes; break;
                case 'chua_tra': $chua++; break;
                case 'bat_thuong': $bat++; break;
            }
        }
        $hopLe = $dung + $tre;
        $kpi = [
            'tong_co_hen'   => count($rows),
            'da_tra_hop_le' => $hopLe,
            'dung_hen'      => $dung,
            'tre_hen'       => $tre,
            'chua_tra'      => $chua,
            'bat_thuong'    => $bat,
            'pct_dung_hen'  => $hopLe > 0 ? round($dung / $hopLe * 100, 1) : 0,
            'pct_tre_hen'   => $hopLe > 0 ? round($tre / $hopLe * 100, 1) : 0,
            'tg_tra_tb'     => $hopLe > 0 ? round($sumActual / $hopLe) : 0,
        ];
        return [
            'kpi' => $kpi,
            'breakdown_loai_dich_vu' => [],
            'breakdown_phong'        => [],
            'breakdown_dich_vu'      => [],
            'trend_theo_ngay'        => [],
        ];
    }
}

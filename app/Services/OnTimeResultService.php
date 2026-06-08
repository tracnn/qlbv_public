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
            'breakdown_loai_dich_vu' => $this->groupBy($rows, 'service_type_id', 'service_type_name'),
            'breakdown_phong'        => $this->groupBy($rows, 'execute_room_id', 'execute_room_name'),
            'breakdown_dich_vu'      => $this->groupBy($rows, 'service_id', 'service_name'),
            'trend_theo_ngay'        => $this->groupBy($rows, 'day_val', 'day_val'),
        ];
    }

    public function groupBy($rows, $idField, $nameField)
    {
        $groups = [];
        foreach ($rows as $r) {
            $key = $r->$idField;
            if (!isset($groups[$key])) {
                $groups[$key] = ['id'=>$r->$idField,'name'=>$r->$nameField,'tong'=>0,'dung_hen'=>0,'tre_hen'=>0,'chua_tra'=>0,'bat_thuong'=>0,'_sumActual'=>0];
            }
            $g =& $groups[$key];
            $g['tong']++;
            $cls = $this->classify($r);
            $g[$cls]++;
            if ($cls === 'dung_hen' || $cls === 'tre_hen') {
                $g['_sumActual'] += $r->actual_minutes;
            }
            unset($g);
        }
        $result = [];
        foreach ($groups as $g) {
            $hopLe = $g['dung_hen'] + $g['tre_hen'];
            $g['pct_dung_hen'] = $hopLe > 0 ? round($g['dung_hen']/$hopLe*100, 1) : 0;
            $g['pct_tre_hen']  = $hopLe > 0 ? round($g['tre_hen']/$hopLe*100, 1) : 0;
            $g['tg_tra_tb']    = $hopLe > 0 ? round($g['_sumActual']/$hopLe) : 0;
            unset($g['_sumActual']);
            $result[] = $g;
        }
        usort($result, function($a, $b) {
            return $b['pct_tre_hen'] <=> $a['pct_tre_hen'];
        });
        return $result;
    }
}

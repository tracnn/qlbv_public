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

    /**
     * Tổng hợp KPI và breakdown cho danh sách rows kết quả.
     *
     * @param  array|\Traversable $rows  Danh sách dòng dữ liệu (stdClass hoặc tương đương)
     * @return array{
     *   kpi: array{
     *     tong_co_hen: int,
     *     da_tra_hop_le: int,
     *     dung_hen: int,
     *     tre_hen: int,
     *     chua_tra: int,
     *     bat_thuong: int,
     *     pct_dung_hen: float,
     *     pct_tre_hen: float,
     *     tg_tra_tb: int
     *   },
     *   breakdown_loai_dich_vu: array,
     *   breakdown_phong: array,
     *   breakdown_dich_vu: array,
     *   trend_theo_ngay: array
     * }
     */
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
            'breakdown_loai_dich_vu' => $this->buildBreakdown($rows, 'service_type_id', 'service_type_name'),
            'breakdown_phong'        => $this->buildBreakdown($rows, 'execute_room_id', 'execute_room_name'),
            'breakdown_dich_vu'      => $this->buildBreakdown($rows, 'service_id', 'service_name'),
            'trend_theo_ngay'        => $this->buildBreakdown($rows, 'day_val', 'day_val'),
        ];
    }

    /**
     * Nhóm các rows theo một trường định danh và tổng hợp số liệu cho từng nhóm.
     *
     * @param  array|\Traversable $rows       Danh sách dòng dữ liệu
     * @param  string             $idField    Tên trường dùng làm khóa nhóm (vd: 'service_type_id')
     * @param  string             $nameField  Tên trường hiển thị tên nhóm (vd: 'service_type_name')
     * @return array  Mảng các nhóm, mỗi nhóm gồm:
     *                id, name, tong, dung_hen, tre_hen, chua_tra, bat_thuong,
     *                pct_dung_hen, pct_tre_hen, tg_tra_tb.
     *                Sắp xếp giảm dần theo pct_tre_hen.
     */
    public function buildBreakdown($rows, $idField, $nameField)
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
            unset($g); // break the reference so the next iteration doesn't overwrite the last group
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

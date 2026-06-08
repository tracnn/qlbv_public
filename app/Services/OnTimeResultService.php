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
}

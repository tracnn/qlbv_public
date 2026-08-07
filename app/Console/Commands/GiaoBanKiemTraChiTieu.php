<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GiaoBan\GiaoBanDeptConfig;
use App\Services\GiaoBan\MetricValidator;

/**
 * Quet toan bo giaoban_dept_configs, in ra cau hinh chi tieu khong dat schema.
 * Chay TRUOC khi bat siet validate o GiaoBanConfigController.
 */
class GiaoBanKiemTraChiTieu extends Command
{
    protected $signature = 'giaoban:kiem-tra-chi-tieu';
    protected $description = 'Kiem tra cau hinh chi tieu giao ban co dat MetricSchema khong';

    public function handle()
    {
        $configs = GiaoBanDeptConfig::orderBy('sort_order')->get();
        $soSai = 0;

        foreach ($configs as $cfg) {
            $loi = MetricValidator::validateJson($cfg->metrics, $cfg->block_type);
            if (empty($loi)) continue;

            $soSai++;
            $this->error(sprintf('#%d %s (khối %s) — %d lỗi',
                $cfg->id, $cfg->display_name, $cfg->block_type, count($loi)));
            foreach ($loi as $l) {
                $viTri = $l['index'] === -1 ? 'toàn danh sách' : ('tiêu chí thứ ' . ($l['index'] + 1));
                $this->line(sprintf('    - %s / %s: %s', $viTri, $l['field'], $l['message']));
            }
        }

        $this->info(sprintf('Đã kiểm %d cấu hình, %d cấu hình không đạt.', count($configs), $soSai));
        return $soSai > 0 ? 1 : 0;
    }
}

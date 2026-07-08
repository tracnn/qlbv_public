<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\GiaoBan\GiaoBanMetricService;

class GiaoBanPreview extends Command
{
    protected $signature = 'giaoban:preview {--from=} {--to=}';
    protected $description = 'In so lieu giao ban tu HIS de doi chieu (khong ghi DB)';

    public function handle()
    {
        $svc = new GiaoBanMetricService();
        $to = $this->option('to') ?: date('Y-m-d 07:00:00');
        $from = $this->option('from') ?: date('Y-m-d 07:00:00', strtotime('-1 day', strtotime($to)));
        $this->info("Ky so lieu: $from -> $to");

        $censusFrom = $svc->normalizeRows($this->runHis($svc->buildCensusSql($from)));
        $censusTo = $svc->normalizeRows($this->runHis($svc->buildCensusSql($to)));
        $moveIn = $svc->normalizeRows($this->runHis($svc->buildMovementInSql($from, $to)));
        $moveOut = $svc->normalizeRows($this->runHis($svc->buildMovementOutSql($from, $to)));
        $ends = $svc->normalizeRows($this->runHis($svc->buildEndTypeSql($from, $to)));

        $depts = collect($svc->normalizeRows(DB::connection('HISPro')
            ->select('SELECT id, department_name FROM his_department WHERE is_delete = 0')))
            ->pluck('department_name', 'id');

        $rows = [];
        $ids = collect($censusFrom)->pluck('department_id')
            ->merge(collect($censusTo)->pluck('department_id'))
            ->merge(collect($moveIn)->pluck('department_id'))
            ->unique()->values();
        foreach ($ids as $id) {
            $find = function ($rows, $col) use ($id) {
                foreach ($rows as $r) {
                    if ((int) $r->department_id === (int) $id) {
                        return (int) $r->{$col};
                    }
                }
                return 0;
            };
            $raVien = $chuyenVien = $tuVong = 0;
            foreach ($ends as $r) {
                if ((int) $r->department_id !== (int) $id) {
                    continue;
                }
                if ($r->end_code === 'CV') {
                    $chuyenVien += (int) $r->so_bn;
                } elseif ($r->end_code === 'TV') {
                    $tuVong += (int) $r->so_bn;
                } else {
                    $raVien += (int) $r->so_bn;
                }
            }
            $cu = $find($censusFrom, 'so_bn');
            $vao = $find($moveIn, 'bn_vao');
            $den = $find($moveIn, 'bn_chuyen_den');
            $di = $find($moveOut, 'bn_chuyen_khoa');
            $hienCo = $find($censusTo, 'so_bn');
            $rows[] = [
                'khoa' => isset($depts[$id]) ? $depts[$id] : $id,
                'cu' => $cu, 'vao' => $vao, 'den' => $den,
                'ra' => $raVien, 'cv' => $chuyenVien, 'tv' => $tuVong, 'di' => $di,
                'hien_co' => $hienCo,
                'can_doi' => ($cu + $vao + $den - $raVien - $chuyenVien - $tuVong - $di) - $hienCo,
            ];
        }
        $this->table(['Khoa', 'Cũ', 'Vào', 'Đến', 'Ra', 'CV', 'TV', 'Đi', 'Hiện có', 'Lệch'], $rows);
        return 0;
    }

    /**
     * @param array $sqlAndBinds [$sql, $binds]
     * @return array raw rows from Oracle
     */
    protected function runHis($sqlAndBinds)
    {
        list($sql, $binds) = $sqlAndBinds;
        return DB::connection('HISPro')->select($sql, $binds);
    }
}

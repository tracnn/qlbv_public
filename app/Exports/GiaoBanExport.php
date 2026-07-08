<?php

namespace App\Exports;

use App\Models\GiaoBan\GiaoBanReport;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GiaoBanExport implements FromArray, WithStyles, WithColumnWidths
{
    protected $report;
    protected $configs;
    protected $boldRows = [];
    protected $italicRows = [];

    /** @param \Illuminate\Support\Collection $configs GiaoBanDeptConfig active theo sort_order */
    public function __construct(GiaoBanReport $report, $configs)
    {
        $this->report = $report;
        $this->configs = $configs;
    }

    public function array(): array
    {
        $cells = [];
        foreach ($this->report->cells as $c) {
            $cells[$c->dept_config_id . '|' . $c->metric_code] = $c;
        }
        $display = function ($deptId, $code) use ($cells) {
            $key = $deptId . '|' . $code;
            if (!isset($cells[$key])) return null;
            return $cells[$key]->displayValue();
        };

        $rows = [];
        $title = 'BÁO CÁO GIAO BAN NGÀY ' . date('d/m/Y', strtotime($this->report->report_date));
        if ($this->report->status !== 'final') $title .= ' (BẢN NHÁP)';
        $rows[] = [$title];
        $rows[] = ['Số liệu từ ' . $this->report->from_time . ' đến ' . $this->report->to_time];
        $rows[] = [];

        $i = 1;
        foreach ($this->configs as $cfg) {
            $this->boldRows[] = count($rows) + 1;
            $rows[] = [$i . '. ' . mb_strtoupper($cfg->display_name)];
            $line = []; $vals = [];
            foreach ($cfg->metricList() as $m) {
                $line[] = $m['name'];
                $v = $display($cfg->id, $m['code']);
                $vals[] = $v === null ? '' : (float) $v;
            }
            $rows[] = $line;
            $rows[] = $vals;
            $noteKey = $cfg->id . '|note';
            if (isset($cells[$noteKey]) && trim((string) $cells[$noteKey]->note) !== '') {
                $this->italicRows[] = count($rows) + 1;
                $rows[] = ['* ' . trim(html_entity_decode(strip_tags((string) $cells[$noteKey]->note)))];
            }
            $rows[] = [];
            $i++;
        }

        if (trim((string) $this->report->general_note) !== '') {
            $this->boldRows[] = count($rows) + 1;
            $rows[] = ['GHI CHÚ CHUNG'];
            $this->italicRows[] = count($rows) + 1;
            $gn = trim(html_entity_decode(strip_tags((string) $this->report->general_note)));
            // Chống formula injection Excel: ký tự đầu =,+,-,@ -> ép về text bằng dấu nháy đơn
            if ($gn !== '' && strpos('=+-@', $gn[0]) !== false) {
                $gn = "'" . $gn;
            }
            $rows[] = [$gn];
        }
        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [1 => ['font' => ['bold' => true, 'size' => 14]]];
        foreach ($this->boldRows as $r) $styles[$r] = ['font' => ['bold' => true]];
        foreach ($this->italicRows as $r) $styles[$r] = ['font' => ['italic' => true]];
        return $styles;
    }

    public function columnWidths(): array
    {
        return ['A' => 18, 'B' => 14, 'C' => 14, 'D' => 14, 'E' => 14, 'F' => 14,
                'G' => 14, 'H' => 14, 'I' => 14, 'J' => 14, 'K' => 14, 'L' => 14];
    }
}

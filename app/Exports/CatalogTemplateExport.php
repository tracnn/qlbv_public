<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class CatalogTemplateExport implements FromArray, WithHeadings, WithEvents
{
    protected $type;
    protected $config;

    public function __construct($type)
    {
        $this->type = $type;
        $this->config = config("catalog_import_mapping.{$type}", []);
    }

    /** Header = alias đầu mỗi field + chèn các detect_key còn thiếu (để file tự nhận diện khi import). */
    public function headers(): array
    {
        $mapping = $this->config['mapping'] ?? [];
        $headers = [];
        foreach ($mapping as $field => $aliases) {
            if (!empty($aliases)) {
                $headers[] = $aliases[0];
            }
        }
        foreach (($this->config['detect_keys'] ?? []) as $key) {
            if (!in_array($key, $headers, true)) {
                $headers[] = $key;
            }
        }
        return $headers;
    }

    /** Tên header (first alias) của các cột bắt buộc — để tô màu. */
    public function requiredHeaders(): array
    {
        $mapping = $this->config['mapping'] ?? [];
        $out = [];
        foreach (($this->config['required_fields'] ?? []) as $field) {
            if (!empty($mapping[$field])) {
                $out[] = $mapping[$field][0];
            }
        }
        return $out;
    }

    public function headings(): array
    {
        return $this->headers();
    }

    /** Không có dòng dữ liệu — chỉ header cho người dùng điền. */
    public function array(): array
    {
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $headers = $this->headers();
                $required = $this->requiredHeaders();

                foreach ($headers as $i => $name) {
                    $col = Coordinate::stringFromColumnIndex($i + 1);
                    $cell = $col . '1';
                    $sheet->getStyle($cell)->getFont()->setBold(true);
                    // Đặt độ rộng cột tường minh (rộng hơn độ dài chữ) để không bị che header.
                    $sheet->getColumnDimension($col)->setWidth(max(16, mb_strlen((string) $name) + 6));
                    if (in_array($name, $required, true)) {
                        $sheet->getStyle($cell)->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('FFF2CC');
                    }
                }
            },
        ];
    }
}

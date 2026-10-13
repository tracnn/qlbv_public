<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

/**
 * Xuat bang tra vat pham -> DVKT me de nguoi dung duyet. Moi o ghi dang CHUOI: ma thuoc
 * '40.540' ma de Excel doan kieu se thanh so 40.54 va khong khop lai khi nap.
 * Tieu de cot chinh la khoa ma lenh nap doc (WithHeadingRow).
 */
class VatPhamKemDvktExport extends StringValueBinder implements FromArray, WithHeadings, WithCustomValueBinder
{
    const COT = ['loai', 'ma_vat_pham', 'ten_vat_pham', 'ma_dvkt', 'ten_dvkt', 'nguon', 'hieu_luc', 'so_lan_gan', 'ty_le_gan', 'ghi_chu'];

    /** @var array */
    private $dong;

    public function __construct(array $dong)
    {
        $this->dong = $dong;
    }

    public function headings(): array
    {
        return self::COT;
    }

    public function array(): array
    {
        return array_map(function ($d) {
            $d = is_array($d) ? $d : $d->toArray();

            return array_map(function ($c) use ($d) {
                $v = $d[$c] ?? null;
                if ($c === 'hieu_luc') {
                    return ($v === null || $v) ? '1' : '0';
                }

                return $v === null ? '' : (string) $v;
            }, self::COT);
        }, $this->dong);
    }
}

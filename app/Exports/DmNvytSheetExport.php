<?php

namespace App\Exports;

use App\Models\BHYT\MedicalStaff;
use App\Services\BHYT\LocCoSo;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet danh muc nhan vien y te trong file xuat loi XML3176.
 *
 * Cung ly do voi DmKhoaGiuongSheetExport: xuat toan bo, khong loc theo ngay hay ho so,
 * moi o la chuoi (so dinh danh, ma BHXH, ma co so co so 0 dung dau).
 */
class DmNvytSheetExport extends StringValueBinder implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle, WithCustomValueBinder
{
    /** Cot nghiep vu theo thu tu xuat. Bo id, created_at, updated_at. */
    const COT = [
        'ma_cskcb', 'ma_loai_kcb', 'ma_khoa', 'ten_khoa', 'ma_bhxh', 'ho_ten', 'gioi_tinh',
        'so_dinh_danh', 'chucdanh_nn', 'vi_tri', 'macchn', 'ngaycap_cchn', 'noicap_cchn',
        'phamvi_cm', 'phamvi_cmbs', 'dvkt_khac', 'vb_phancong', 'thoigian_dk',
        'thoigian_ngay', 'thoigian_tuan', 'cskcb_khac', 'cskcb_cgkt', 'qd_cgkt',
        'tu_ngay', 'den_ngay',
    ];

    protected $maCskcb;
    protected $danhSachCoSo;

    public function __construct($maCskcb, array $danhSachCoSo = [])
    {
        $this->maCskcb = $maCskcb;
        $this->danhSachCoSo = $danhSachCoSo;
    }

    /**
     * MedicalStaff khong co scopeCuaCoSo nen ap cung quy uoc ngay tai day: dong co
     * ma_cskcb rong dung chung cho moi co so. Khop DUNG BANG (LocCoSo::ap) se lam mat
     * cac dong do.
     */
    public function query()
    {
        $query = MedicalStaff::query()->select(self::COT);

        $ma = LocCoSo::maHopLe($this->maCskcb, $this->danhSachCoSo);

        if ($ma !== '') {
            $query->where(function ($w) use ($ma) {
                $w->whereNull('ma_cskcb')
                  ->orWhere('ma_cskcb', '')
                  ->orWhere('ma_cskcb', $ma);
            });
        }

        return $query
            ->orderBy('ma_cskcb')
            ->orderBy('ma_khoa')
            ->orderBy('ho_ten');
    }

    public function headings(): array
    {
        return array_map('strtoupper', self::COT);
    }

    public function map($data): array
    {
        $dong = [];

        foreach (self::COT as $cot) {
            $dong[] = $data->{$cot};
        }

        return $dong;
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    public function title(): string
    {
        return 'DM NVYT';
    }
}

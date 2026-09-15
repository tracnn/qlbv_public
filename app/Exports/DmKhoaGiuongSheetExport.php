<?php

namespace App\Exports;

use App\Models\BHYT\DepartmentBedCatalog;
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
 * Sheet danh muc khoa-giuong trong file xuat loi XML3176.
 *
 * Bang tra cuu de nguoi dung do ten khoa tu ma khoa tren cac sheet loi. Xuat TOAN BO,
 * khong loc theo ngay hay theo ho so: chi giu ma co trong sheet loi thi khong do ra duoc
 * ma SAI, ma ma sai chinh la thu can tim. Giu ca dong het hieu luc; cot tu_ngay/den_ngay
 * cho thay hieu luc.
 *
 * Ke thua StringValueBinder: ma co so 01929 va cac ma khac co so 0 dung dau, bo gan gia
 * tri mac dinh doi chung thanh so va MAT so 0.
 */
class DmKhoaGiuongSheetExport extends StringValueBinder implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle, WithCustomValueBinder
{
    /** Cot nghiep vu theo thu tu xuat. Bo id, created_at, updated_at. */
    const COT = [
        'ma_cskcb', 'ma_loai_kcb', 'ma_khoa', 'ten_khoa', 'ban_kham', 'giuong_pd',
        'giuong_2015', 'giuong_tk', 'giuong_hstc', 'giuong_hscc', 'ldlk', 'lien_khoa',
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
     * Loc co so theo scopeCuaCoSo: dong co ma_cskcb rong dung chung cho moi co so.
     * Ma khong hop le thi LocCoSo::maHopLe tra '' va scope bo qua loc.
     */
    public function query()
    {
        return DepartmentBedCatalog::query()
            ->cuaCoSo(LocCoSo::maHopLe($this->maCskcb, $this->danhSachCoSo))
            ->select(self::COT)
            ->orderBy('ma_cskcb')
            ->orderBy('ma_khoa')
            ->orderBy('tu_ngay');
    }

    public function headings(): array
    {
        // Ten truong viet hoa theo chuan: nguoi dung doi chieu voi tep danh muc gui cong BHXH.
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
        return 'DM khoa-giường';
    }
}

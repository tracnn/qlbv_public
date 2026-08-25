<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use App\Services\Tt12\Tt12DanhSach;

/**
 * FromQuery chu khong FromCollection: Maatwebsite se phan trang truy van thay vi nap ca
 * bang vao bo nho. May chu dat PHP 128 MB.
 */
class Tt12DanhSachExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    private $loc;

    public function __construct(array $loc)
    {
        $this->loc = $loc;
    }

    public function query()
    {
        return Tt12DanhSach::truyVan($this->loc);
    }

    public function headings(): array
    {
        return array('Mã hồ sơ', 'Mẫu', 'Tên tệp', 'Mã CSKCB', 'Số dòng', 'Số lỗi',
            'Đã kiểm', 'Đã ký', 'Mã giao dịch', 'Mã kết quả', 'Thời gian tiếp nhận',
            'Đã đồng bộ', 'Thời điểm nạp');
    }

    public function map($hoSo): array
    {
        return array(
            $hoSo->ma_ho_so,
            $hoSo->mau,
            $hoSo->ten_tep,
            $hoSo->ma_cskcb,
            (int) $hoSo->so_dong,
            (int) $hoSo->so_loi,
            $hoSo->checked_at ? 'x' : '',
            $hoSo->is_signed ? 'x' : '',
            $hoSo->ma_gd,
            $hoSo->ma_ket_qua,
            $hoSo->thoi_gian_tiep_nhan,
            $hoSo->dong_bo_at ? 'x' : '',
            $hoSo->imported_at ? $hoSo->imported_at->format('d/m/Y H:i') : '',
        );
    }
}

<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use App\Models\BHYT\Tt12\Tt12Loi;
use App\Models\BHYT\Tt12\Tt12HoSo;

class Tt12LoiExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    private $maHoSo;

    public function __construct($maHoSo = null)
    {
        $this->maHoSo = $maHoSo;
    }

    public function query()
    {
        $q = Tt12Loi::query()->orderBy('ho_so_id')->orderBy('stt_dong')->orderBy('id');

        if (!empty($this->maHoSo)) {
            $id = Tt12HoSo::where('ma_ho_so', $this->maHoSo)->value('id');

            // Ma ho so khong ton tai thi tra ve TEP RONG, khong tra ve toan bo loi cua
            // moi ho so. Bo dieu kien khi khong tim thay la mot cach im lang de xuat
            // nham hang chuc nghin dong.
            $q->where('ho_so_id', $id === null ? -1 : $id);
        }

        return $q;
    }

    public function headings(): array
    {
        return array('Mã hồ sơ', 'Dòng', 'Cột', 'Mã lỗi', 'Mức độ', 'Mô tả');
    }

    public function map($loi): array
    {
        return array(
            $loi->hoSo ? $loi->hoSo->ma_ho_so : '',
            $loi->stt_dong,
            $loi->cot,
            $loi->ma_loi,
            $loi->muc_do === 'loi' ? 'Lỗi' : 'Cảnh báo',
            $loi->mo_ta,
        );
    }
}

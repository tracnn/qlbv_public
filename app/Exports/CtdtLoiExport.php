<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Mot dong = mot loi. Bang nay duoc in ra dua cho NGUOI NHAP LIEU di sua, tach hoan toan
 * khoi man hinh - nen moi dong phai tu du thong tin de tim lai ho so, khong duoc dua vao
 * viec nguoi doc dang mo man danh sach.
 *
 * Nhan truy van cua ctdt_ho_so (da loc theo man hinh) chu khong truy van ctdt_loi: bo loc
 * cua man hinh la bo loc theo HO SO (ngay nap, dich vu, co so). Loc thang tren ctdt_loi se
 * khong ap dung duoc nhung dieu kien do.
 *
 * XUAT CA muc chan LAN canh bao: chi xuat muc chan la giau mat nua cong viec - canh bao hom
 * nay la loi chan cua dot siet sau.
 */
class CtdtLoiExport implements FromQuery, WithHeadings, ShouldAutoSize, WithMapping, WithTitle
{
    /** @var \Illuminate\Database\Eloquent\Builder truy van ctdt_ho_so da loc */
    protected $truyVan;

    protected $stt = 0;

    public function __construct($truyVan)
    {
        $this->truyVan = $truyVan;
    }

    public function query()
    {
        // Chi lay ho so CO loi: mot bang loi day nhung dong "khong loi" la bang khong ai doc.
        return $this->truyVan
            ->where('so_loi', '>', 0)
            ->with(['loi', 'chungTu' => function ($q) {
                $q->select('id', 'ho_so_id', 'ma_the', 'ho_ten', 'loai_ho_so')->orderBy('id');
            }])
            ->orderByDesc('imported_at');
    }

    public function title(): string
    {
        return 'Loi';
    }

    public function headings(): array
    {
        return [
            'STT',
            'Mã hồ sơ',
            'Mã CSKCB',
            'Họ tên',
            'Số thẻ',
            'Loại chứng từ',
            'Mã lỗi',
            'Trường',
            'Mức độ',
            'Mô tả',
        ];
    }

    /**
     * Mot HO SO cho ra NHIEU dong - mot dong moi loi. WithMapping cho phep tra ve mang cua
     * mang de sinh nhieu dong tu mot ban ghi.
     */
    public function map($hoSo): array
    {
        $dau = $hoSo->relationLoaded('chungTu') ? $hoSo->chungTu->first() : null;
        $dong = [];

        foreach ($hoSo->loi as $loi) {
            $this->stt++;

            $dong[] = [
                $this->stt,
                (string) $hoSo->ma_ho_so,
                (string) $hoSo->macskcb,
                $dau ? (string) $dau->ho_ten : '',
                $dau ? (string) $dau->ma_the : '',
                $dau ? (string) $dau->loai_ho_so : '',
                (string) $loi->ma_loi,
                (string) $loi->ten_truong,
                // Giu nguyen ma 'chan'/'canh_bao' cua CSDL nhung doc duoc: nguoi nhap lieu
                // tu quyet uu tien, khong phai lop nay quyet ho.
                $loi->muc_do === 'chan' ? 'Chặn' : 'Cảnh báo',
                (string) $loi->mo_ta,
            ];
        }

        return $dong;
    }
}

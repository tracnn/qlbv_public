<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use App\Models\BHYT\Ctdt\CtdtLichSuGui;

/**
 * Mot dong = mot lan goi cong BHXH. Dung khi can dung lai chuyen da xay ra.
 *
 * KHONG doc cot van ban ctdt_ho_so.lich_su_gui: cot do do HAI noi cung ghi voi HAI dinh
 * dang khac nhau (CtdtLuuHoSo::noiLichSu ghi khi nap de, SubmitCtdtJob::noiLichSu ghi khi
 * gui), va boc tach van ban tu do la dung mot nguon su that thu hai tren nen cat.
 *
 * BUOC phai co khoang ngay: bang nay chi tang, khong bao gio giam. Xuat toan bang tren may
 * chu gioi han PHP 128MB la cach chac chan de het bo nho.
 */
class CtdtNhatKyGuiExport implements FromQuery, WithHeadings, ShouldAutoSize, WithMapping, WithTitle
{
    protected $tuNgay;
    protected $denNgay;
    protected $stt = 0;

    /**
     * @param string $tuNgay  'Y-m-d'
     * @param string $denNgay 'Y-m-d'
     */
    public function __construct($tuNgay, $denNgay)
    {
        $this->tuNgay = $tuNgay;
        $this->denNgay = $denNgay;
    }

    public function query()
    {
        return CtdtLichSuGui::query()
            ->whereBetween('created_at', [
                $this->tuNgay . ' 00:00:00',
                $this->denNgay . ' 23:59:59',
            ])
            ->orderBy('created_at');
    }

    public function title(): string
    {
        return 'Nhat ky gui';
    }

    public function headings(): array
    {
        return [
            'STT',
            'Thời điểm',
            'Mã hồ sơ',
            'Nguồn',
            'Người gửi',
            'Kết quả',
            'Mã kết quả',
            'Mã giao dịch',
            'Thời gian tiếp nhận',
            'Phản hồi của cổng',
        ];
    }

    public function map($dong): array
    {
        $this->stt++;

        return [
            $this->stt,
            (string) $dong->created_at,
            (string) $dong->ma_ho_so,
            // Cau hoi van hanh dau tien khi co su co: "dem qua LENH NEN gui bao nhieu".
            $dong->nguon === CtdtLichSuGui::NGUON_CONSOLE ? 'Lệnh nền' : 'Người bấm',
            (string) $dong->nguoi_gui,
            $dong->thanh_cong ? 'Cổng nhận' : 'Cổng từ chối',
            (string) $dong->ma_ket_qua,
            (string) $dong->ma_gd,
            (string) $dong->thoi_gian_tiep_nhan,
            (string) $dong->thong_diep,
        ];
    }
}

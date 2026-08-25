<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use App\Models\BHYT\Tt12\Tt12LichSuGui;
use App\Services\Tt12\Tt12DanhSach;

/**
 * Mot dong = mot lan goi cong BHXH cho mot ho so TT12. Dung khi can dung lai chuyen da
 * xay ra - cac cot trang thai tren tt12_ho_so bi GHI DE o moi lan gui, nen sau lan gui
 * thu hai dau vet cua lan thu nhat chi con o day.
 *
 * BUOC phai co khoang ngay, VA khoang do bi chan tran 90 ngay: bang nay chi tang, khong
 * bao gio giam. FromQuery chi giam bo nho HYDRATE Eloquent - PhpSpreadsheet van giu toan
 * bo sheet trong RAM truoc khi ghi. Tren may chu gioi han PHP 128MB mot khoang mot nam
 * la chet giua chung, khong phai mot tep lon.
 *
 * Khuon theo CtdtNhatKyGuiExport.
 */
class Tt12NhatKyGuiExport implements FromQuery, WithHeadings, ShouldAutoSize, WithMapping, WithTitle
{
    protected $tuNgay;
    protected $denNgay;
    protected $stt = 0;

    /** Tran do rong khoang ngay, tinh bang ngay */
    const TRAN_SO_NGAY = 90;

    /**
     * Chuan hoa NGAY TRONG HAM DUNG, khong phai trong query(): moc da chuan roi thi phep
     * kiem tran ben duoi so sanh duoc, va query() chi con mot viec la ghep dieu kien.
     *
     * @param string $tuNgay  'Y-m-d' hoac 'Y-m-d H:i:s'
     * @param string $denNgay 'Y-m-d' hoac 'Y-m-d H:i:s'
     * @throws \InvalidArgumentException khi khoang rong qua TRAN_SO_NGAY
     */
    public function __construct($tuNgay, $denNgay)
    {
        // Dung lai Tt12DanhSach::mocDau/mocCuoi chu KHONG tu chuan hoa: man hinh danh
        // sach TT12 luon gui dang 'YYYY-MM-DD HH:mm:ss', va cu noi them ' 00:00:00' vao
        // do cho ra '2026-08-01 00:00:00 00:00:00' - MySQL doc khong ra va tra ve RONG.
        // Viet ban chuan hoa thu hai o day la de hai ban lech nhau.
        $this->tuNgay = Tt12DanhSach::mocDau($tuNgay);
        $this->denNgay = Tt12DanhSach::mocCuoi($denNgay);

        $soNgay = Carbon::parse($this->tuNgay)->diffInDays(Carbon::parse($this->denNgay));

        if ($soNgay > self::TRAN_SO_NGAY) {
            throw new \InvalidArgumentException(
                'Khoảng ngày của nhật ký gửi tối đa ' . self::TRAN_SO_NGAY
                . ' ngày, đang chọn ' . $soNgay . ' ngày. Vui lòng chia nhỏ khoảng ngày.'
            );
        }
    }

    public function query()
    {
        set_time_limit(1800); // Tang thoi gian thuc thi len 1800 giay (30 phut)
        ini_set('memory_limit', '4096M'); // Noi gioi han bo nho, giong CtdtNhatKyGuiExport

        return Tt12LichSuGui::query()
            ->whereBetween('gui_luc', [$this->tuNgay, $this->denNgay])
            // orderBy('id') la KHOA PHA HOA, khong phai trang tri: Sheet::fromQuery()
            // duyet bang chunk(100) tuc LIMIT/OFFSET, ma mot lo gui hang loat co the sinh
            // nhieu dong cung mot giay. Thu tu cac dong trung giua hai trang khong duoc
            // bao dam neu chi sap theo gui_luc.
            ->orderBy('gui_luc')
            ->orderBy('id');
    }

    public function title(): string
    {
        return 'Nhật ký gửi';
    }

    public function headings(): array
    {
        return [
            'STT',
            'Thời điểm gửi',
            'Mã hồ sơ',
            'Người gửi',
            'Mã kết quả',
            'Mã giao dịch',
            'Thời gian tiếp nhận',
            'Thông điệp',
            'Lỗi nguyên văn',
        ];
    }

    public function map($dong): array
    {
        $this->stt++;

        return [
            $this->stt,
            (string) $dong->gui_luc,
            (string) $dong->ma_ho_so,
            (string) $dong->gui_boi,
            (string) $dong->ma_ket_qua,
            (string) $dong->ma_gd,
            (string) $dong->thoi_gian_tiep_nhan,
            (string) $dong->thong_diep,
            (string) $dong->loi,
        ];
    }
}

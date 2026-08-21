<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use App\Models\BHYT\Ctdt\CtdtLichSuGui;
use App\Services\Ctdt\CtdtDanhSach;

/**
 * Mot dong = mot lan goi cong BHXH. Dung khi can dung lai chuyen da xay ra.
 *
 * KHONG doc cot van ban ctdt_ho_so.lich_su_gui: cot do do HAI noi cung ghi voi HAI dinh
 * dang khac nhau (CtdtLuuHoSo::noiLichSu ghi khi nap de, SubmitCtdtJob::noiLichSu ghi khi
 * gui), va boc tach van ban tu do la dung mot nguon su that thu hai tren nen cat.
 *
 * BUOC phai co khoang ngay, VA khoang do bi chan tran 90 ngay: bang nay chi tang, khong
 * bao gio giam. FromQuery chi giam bo nho HYDRATE Eloquent - PhpSpreadsheet van giu toan bo
 * sheet trong RAM truoc khi ghi, va ShouldAutoSize con do be rong tung o. Tren may chu gioi
 * han PHP 128MB mot khoang mot nam la chet giua chung, khong phai mot tep lon.
 */
class CtdtNhatKyGuiExport implements FromQuery, WithHeadings, ShouldAutoSize, WithMapping, WithTitle
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
        // Dung lai CtdtDanhSach::mocDau/mocCuoi chu KHONG tu chuan hoa: man hinh luon gui
        // dang 'YYYY-MM-DD HH:mm:ss', va cu noi them ' 00:00:00' vao do cho ra
        // '2026-08-01 00:00:00 00:00:00' - MySQL doc khong ra va tra ve RONG. Nguoi van
        // hanh mo tep chi thay dong tieu de roi ket luan "dem qua khong gui gi", trong khi
        // bang day du lieu. Chinh CtdtDanhSach da vap va da giai ca nay; viet ban chuan hoa
        // thu hai o day la de hai ban lech nhau.
        $this->tuNgay = CtdtDanhSach::mocDau($tuNgay);
        $this->denNgay = CtdtDanhSach::mocCuoi($denNgay);

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
        ini_set('memory_limit', '4096M'); // Noi gioi han bo nho, giong 15 lop Export con lai

        return CtdtLichSuGui::query()
            ->whereBetween('created_at', [$this->tuNgay, $this->denNgay])
            // orderBy('id') la KHOA PHA HOA, khong phai trang tri: Sheet::fromQuery() duyet
            // bang chunk(100) tuc LIMIT/OFFSET, ma mot lo gui nen sinh hang chuc dong cung
            // mot giay. Thu tu cac dong trung giua hai trang khong duoc bao dam - dong se
            // lap o trang sau hoac mat han, ma tep xuat trong van binh thuong.
            ->orderBy('created_at')
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

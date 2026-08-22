<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use App\Services\Ctdt\CtdtTrangThaiGui;

/**
 * Xuat danh sach ho so chung tu dien tu theo DUNG bo loc dang chon tren man hinh.
 *
 * Nhan thang doi tuong truy van da loc tu controller, khong tu dung lai dieu kien: neu moi
 * ben tu dung thi them mot bo loc ma quen ben kia se lam tep xuat khac han man hinh, va
 * khong co dau hieu gi cho toi luc ai do ngoi doi chieu tung dong voi ban cua BHXH.
 *
 * FromQuery giam bo nho HYDRATE Eloquent - va chi the thoi. PhpSpreadsheet van dung toan bo
 * sheet trong RAM truoc khi ghi ra tep, va ShouldAutoSize con do be rong tung o cua 18 cot x
 * N dong. Vi vay query() con phai noi set_time_limit / memory_limit nhu 15 lop Export con
 * lai; chi FromQuery khong du de song tren may chu gioi han PHP 128MB/120s.
 */
class CtdtDanhSachExport implements FromQuery, WithHeadings, ShouldAutoSize, WithMapping, WithTitle
{
    /** @var \Illuminate\Database\Eloquent\Builder */
    protected $truyVan;

    protected $stt = 0;

    public function __construct($truyVan)
    {
        $this->truyVan = $truyVan;
    }

    public function query()
    {
        set_time_limit(1800); // Tang thoi gian thuc thi len 1800 giay (30 phut)
        ini_set('memory_limit', '4096M'); // Noi gioi han bo nho, giong 15 lop Export con lai

        // Nap kem chung tu dau tien: cot Ho ten / So the lay tu do. Khong nap kem thi moi
        // dong la mot truy van rieng - 5000 dong thanh 5001 truy van.
        return $this->truyVan
            ->with(['chungTu' => function ($q) {
                $q->select('id', 'ho_so_id', 'ma_the', 'so_cccd', 'ma_bhxh', 'ho_ten')
                    ->orderBy('id');
            }])
            // orderBy('id') la KHOA PHA HOA: Sheet::fromQuery() duyet bang chunk(100) tuc
            // LIMIT/OFFSET, ma mot tep nap ra hang tram ho so trung imported_at den tung
            // giay. Thu tu cac dong trung giua hai trang khong duoc bao dam - dong se lap o
            // trang sau hoac mat han, ma tep xuat trong van binh thuong.
            ->orderByDesc('imported_at')
            ->orderBy('id');
    }

    public function title(): string
    {
        return 'Hồ sơ';
    }

    public function headings(): array
    {
        return [
            'STT',
            'Mã hồ sơ',
            'Dịch vụ',
            'Loại HS',
            'Mã CSKCB',
            'Họ tên',
            'Số thẻ',
            'Số CCCD',
            'Mã BHXH',
            'Số chứng từ',
            'Số lỗi',
            'Trạng thái gửi',
            'Mã giao dịch',
            'Mã kết quả',
            'Thời gian tiếp nhận',
            'Lỗi ký số',
            'Lỗi gửi',
            'Người nạp',
            'Thời điểm nạp',
            'Thời điểm gửi',
        ];
    }

    public function map($hoSo): array
    {
        $this->stt++;

        $dau = $hoSo->relationLoaded('chungTu') ? $hoSo->chungTu->first() : null;

        return [
            $this->stt,
            (string) $hoSo->ma_ho_so,
            (string) $hoSo->dich_vu,
            (string) $hoSo->loai_hs,
            (string) $hoSo->macskcb,
            $dau ? (string) $dau->ho_ten : '',
            $dau ? (string) $dau->ma_the : '',
            $dau ? (string) $dau->so_cccd : '',
            $dau ? (string) $dau->ma_bhxh : '',
            (int) $hoSo->so_chung_tu,
            (int) $hoSo->so_loi,
            // Nhan lay tu CtdtTrangThaiGui: go lai chuoi o day nghia la doi nhan tren man
            // hinh se khong doi nhan trong tep xuat.
            CtdtTrangThaiGui::nhan(CtdtTrangThaiGui::cua($hoSo)),
            (string) $hoSo->ma_gd,
            (string) $hoSo->ma_ket_qua,
            (string) $hoSo->thoi_gian_tiep_nhan,
            (string) $hoSo->signed_error,
            (string) $hoSo->submit_error,
            (string) $hoSo->imported_by,
            (string) $hoSo->imported_at,
            (string) $hoSo->submitted_at,
        ];
    }
}

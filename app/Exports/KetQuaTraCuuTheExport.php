<?php

namespace App\Exports;

use App\Services\BHYT\NhanMaThe;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Xuat ket qua tra cuu the BHYT theo DUNG bo loc dang chon tren man hinh.
 *
 * Nhan thang doi tuong truy van da loc tu controller, khong tu dung lai dieu kien: neu moi
 * ben tu dung thi them mot bo loc ma quen ben kia se lam tep xuat khac han man hinh, va
 * khong co dau hieu gi cho toi luc ai do ngoi doi chieu tung dong.
 *
 * KHAC HeinCardErrorExport: lop do la mot sheet trong bo xuat loi XML, dung quy tac "loi" cua
 * job (qd130xml.hein_card_invalid). Lop nay khong mang danh "loi" - no xuat dung thu bo loc
 * dang chon, ke ca dong hop le.
 *
 * FromQuery de Laravel Excel duyet THEO LO: bang nay phinh theo thoi gian, moi ho so mot dong.
 */
class KetQuaTraCuuTheExport implements FromQuery, WithHeadings, ShouldAutoSize, WithMapping, WithTitle
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
        return $this->truyVan->orderByDesc('updated_at');
    }

    public function headings(): array
    {
        return [
            'STT',
            'Mã hồ sơ',
            'Cơ sở KCB',
            'Mã tra cứu',
            'Mã kiểm tra',
            'Mã kết quả',
            'Ghi chú',
            'Số thẻ',
            'Họ tên',
            'Ngày sinh',
            'Giới tính',
            'Địa chỉ',
            'Thẻ cũ',
            'Thẻ mới',
            'Nơi ĐKBĐ',
            'Nơi ĐKBĐ mới',
            'Tên nơi ĐKBĐ mới',
            'Cơ quan BHXH',
            'Thẻ giá trị từ',
            'Thẻ giá trị đến',
            'Thẻ mới giá trị từ',
            'Thẻ mới giá trị đến',
            'Mã khu vực',
            'Ngày đủ 5 năm',
            'Mã số BHXH',
            'Thời gian tra cứu',
        ];
    }

    public function map($r): array
    {
        $this->stt++;

        return [
            $this->stt,
            $r->ma_lk,
            $r->ma_cskcb,
            // Nhan tieng Viet qua NhanMaThe: ma tran khong noi gi, va ham do tra ma tran khi
            // gap ma la thay vi nem "Undefined offset".
            NhanMaThe::traCuu($r->ma_tracuu),
            NhanMaThe::kiemTra($r->ma_kiemtra),
            NhanMaThe::traCuu($r->ma_ketqua),
            $r->ghi_chu,
            $r->ma_the,
            $r->ho_ten,
            $r->ngay_sinh,
            $r->gioi_tinh,
            $r->dia_chi,
            $r->ma_the_cu,
            $r->ma_the_moi,
            $r->ma_dkbd,
            $r->ma_dkbd_moi,
            $r->ten_dkbd_moi,
            $r->cq_bhxh,
            $r->gt_the_tu,
            $r->gt_the_den,
            $r->gt_the_tumoi,
            $r->gt_the_denmoi,
            $r->ma_kv,
            $r->ngay_du5nam,
            $r->maso_bhxh,
            (string) $r->updated_at,
        ];
    }

    public function title(): string
    {
        return 'Kết quả tra cứu thẻ';
    }
}

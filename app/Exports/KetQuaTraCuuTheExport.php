<?php

namespace App\Exports;

use App\Services\BHYT\KhoaDieuTriHis;
use App\Services\BHYT\NhanMaThe;
use Illuminate\Support\Facades\Log;
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

    const LOI_HIS = 'Lỗi tra HIS';

    /** @var KhoaDieuTriHis */
    protected $khoaHis;

    /** Khoa cua lo dang xuat: [ma_lk => ['ma_khoa' => ..., 'ten_khoa' => ...]]. */
    protected $khoaTheoMaLk = [];

    /** Lo dang xuat tra HIS bi loi. */
    protected $loiHis = false;

    public function __construct($truyVan, KhoaDieuTriHis $khoaHis = null)
    {
        $this->truyVan = $truyVan;
        $this->khoaHis = $khoaHis ?: new KhoaDieuTriHis();
    }

    /**
     * Laravel Excel goi truoc map() cho MOI LO (chunk_size dong): tra khoa HIS mot lan cho ca
     * lo thay vi moi dong mot truy van Oracle.
     *
     * Mat ket noi HIS van cho xuat - phan con lai cua tep van dung - nhung o khoa ghi ro loi:
     * de trong se trong nhu ho so "khong co khoa".
     */
    public function prepareRows($rows)
    {
        $this->loiHis = false;

        try {
            $this->khoaTheoMaLk = $this->khoaHis->theoMaLk(collect($rows)->pluck('ma_lk')->all());
        } catch (\Throwable $e) {
            Log::warning('Xuat ket qua tra cuu the: khong tra duoc khoa HIS - ' . $e->getMessage());
            $this->khoaTheoMaLk = [];
            $this->loiHis = true;
        }

        return $rows;
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
            // Gia tri DA GUI len cong - them CUOI de khong xe dich cot nguoi dung da quen.
            'Số thẻ đã gửi',
            'Họ tên đã gửi',
            'Ngày sinh đã gửi',
            'Nơi ĐKBĐ đã gửi',
            // Khoa dieu tri cuoi trong HIS (his_treatment.last_department_id).
            'Mã khoa (HIS)',
            'Khoa điều trị (HIS)',
        ];
    }

    public function map($r): array
    {
        $this->stt++;

        $khoa = $this->khoaTheoMaLk[trim((string) $r->ma_lk)] ?? null;

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
            $r->ma_the_gui,
            $r->ho_ten_gui,
            $r->ngay_sinh_gui,
            $r->ma_dkbd_gui,
            $this->loiHis ? self::LOI_HIS : ($khoa['ma_khoa'] ?? null),
            $this->loiHis ? self::LOI_HIS : ($khoa['ten_khoa'] ?? null),
        ];
    }

    public function title(): string
    {
        return 'Kết quả tra cứu thẻ';
    }
}

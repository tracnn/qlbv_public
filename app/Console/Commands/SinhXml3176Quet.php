<?php

namespace App\Console\Commands;

use App\Services\SinhXml3176\Bang\DangKyBang;
use App\Services\SinhXml3176\CauHinhSinhXml3176;
use App\Services\SinhXml3176\KiemDieuKien;
use App\Services\SinhXml3176\LuotQuet;
use App\Services\SinhXml3176\Nguon\HisNapHoSo;
use App\Services\SinhXml3176\Nguon\HisUngVien;
use App\Services\SinhXml3176\TheoDoiSinh;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Sinh XML3176 truc tiep tu HIS (chi doc) vao thu muc ma tien giam dinh quet.
 * Mac dinh ghi vao thu muc thu (thu_muc_thu); chi ghi thu muc that khi ghi_thu_muc_that === true
 * VA da du 14 bang.
 */
class SinhXml3176Quet extends Command
{
    protected $signature = 'sinhxml3176:quet
        {--lien-tuc : Chay nen lien tuc, tu thoat sau --so-vong vong}
        {--nghi= : So giay nghi giua hai vong khi --lien-tuc, mac dinh sinh_xml3176.nghi_giay}
        {--so-vong=1000 : Tran so vong khi --lien-tuc, lenh tu thoat sau khi chay du}
        {--ma-dieu-tri=* : Chay bu cho cac TREATMENT_CODE (bo qua theo doi da sinh, khong dung moc)}
        {--tu-ngay= : Chay bu tu ngay (Y-m-d, Ymd hoac YmdHis) theo moc cua loai ban, khong dung moc}
        {--den-ngay= : Chay bu den ngay (gom ca ngay), mac dinh bang --tu-ngay}
        {--go-khoa : CAP CUU: go khoa luot vo dieu kien roi thoat. CHI dung khi CHAC CHAN khong con tien trinh sinhxml3176:quet nao dang chay}';

    protected $description = 'Sinh XML3176 truc tiep tu HIS vao thu muc tien giam dinh quet';

    const KHOA_LUOT = 'sinhxml3176:quet:dang-chay';

    public function handle()
    {
        if ($this->option('go-khoa')) {
            Cache::forget(self::KHOA_LUOT);
            $this->warn('CAP CUU: da go khoa ' . self::KHOA_LUOT . ' VO DIEU KIEN.');
            $this->warn('Neu mot tien trinh --lien-tuc THAT SU dang chay, lenh ke tiep se sinh trung tep.'
                . ' Kiem bang tasklist /FI "IMAGENAME eq php.exe" /V hoac dich vu nssm "QLBV SinhXml3176".');
            $this->info('Khong quet gi ca trong lan chay nay.');

            return 0;
        }

        $cauHinh = CauHinhSinhXml3176::tuConfig();
        if (!$cauHinh->batBat()) {
            $this->info('Module sinh XML3176 dang tat');

            return 0;
        }

        $dangKy = DangKyBang::macDinh();
        if ($cauHinh->ghiThuMucThat() && !$dangKy->daDu()) {
            $this->error('Tu choi ghi vao thu muc that: chua du 14 bang. Thieu: ' . implode(', ', $dangKy->thieu()));
            $this->line('Dat ghi_thu_muc_that = false de ghi vao thu_muc_thu.');

            return 1;
        }

        $khoang = null;
        if ($this->option('tu-ngay') !== null || $this->option('den-ngay') !== null) {
            $khoang = $this->khoangNgay();
            if ($khoang === null) {
                return 1;
            }
        }
        $maDs = array_values(array_filter((array) $this->option('ma-dieu-tri'), function ($m) {
            return trim((string) $m) !== '';
        }));

        if (!Cache::add(self::KHOA_LUOT, true, max(1, $cauHinh->khoaLuotPhut()))) {
            $this->warn('Mot luot sinhxml3176:quet khac dang chay. Bo qua luot nay.');

            return 0;
        }

        try {
            $luot = $this->taoLuot($cauHinh, $dangKy);

            if (!empty($maDs) || $khoang !== null) {
                return $this->motVong($cauHinh, function ($loai) use ($luot, $maDs, $khoang) {
                    if (!empty($maDs)) {
                        return $luot->chayChoMa($maDs, $loai);
                    }

                    return $luot->chayChoKhoangNgay($khoang[0], $khoang[1], $loai);
                });
            }

            $chay = function ($loai) use ($luot) {
                return $luot->chay($loai);
            };

            if (!$this->option('lien-tuc')) {
                return $this->motVong($cauHinh, $chay);
            }

            return $this->vongLap($cauHinh, $chay);
        } finally {
            Cache::forget(self::KHOA_LUOT);
        }
    }

    protected function taoLuot(CauHinhSinhXml3176 $cauHinh, DangKyBang $dangKy)
    {
        $ungVien = new HisUngVien($cauHinh);

        return new LuotQuet($cauHinh, $ungVien, new HisNapHoSo($cauHinh, $ungVien), new TheoDoiSinh(),
            $dangKy, new KiemDieuKien());
    }

    /** Chay chinh thuc, them thong tuyen neu bat. Loi luot (cau hinh, mat ket noi) => 1. */
    private function motVong(CauHinhSinhXml3176 $cauHinh, callable $chay)
    {
        $cacBan = [CauHinhSinhXml3176::CHINH_THUC];
        if ($cauHinh->thongTuyen()) {
            $cacBan[] = CauHinhSinhXml3176::THONG_TUYEN;
        }

        $ma = 0;
        foreach ($cacBan as $loai) {
            try {
                $kq = $chay($loai);
            } catch (\Throwable $e) {
                Log::error('sinhxml3176:quet ' . $loai . ' hong: ' . $e->getMessage());
                $this->error($loai . ': ' . $e->getMessage());
                $ma = 1;
                continue;
            }
            $this->line($loai . ': ung_vien=' . $kq['ung_vien'] . ' da_ghi=' . $kq['da_ghi']
                . ' bi_loai=' . $kq['bi_loai'] . ' loi=' . $kq['loi'] . ' bo_qua=' . $kq['bo_qua']);
            if (!empty($kq['khong_tim_thay'])) {
                $this->warn($loai . ': khong tim thay (hoac khong qua bo loc): ' . implode(', ', $kq['khong_tim_thay']));
            }
        }

        return $ma;
    }

    private function vongLap(CauHinhSinhXml3176 $cauHinh, callable $chay)
    {
        $soVong = max(1, (int) $this->option('so-vong'));
        $nghi = $this->option('nghi');
        $nghi = $nghi === null || $nghi === '' ? $cauHinh->nghiGiay() : (int) $nghi;
        $nghi = max(0, $nghi);

        $this->info('Chay lien tuc: nghi ' . $nghi . ' giay giua hai vong, tu thoat sau ' . $soVong . ' vong.');

        $soVongHong = 0;
        for ($i = 1; $i <= $soVong; $i++) {
            if ($this->motVong($cauHinh, $chay) !== 0) {
                $soVongHong++;
            }
            if ($i < $soVong && $nghi > 0) {
                sleep($nghi);
            }
        }

        if ($soVongHong > 0) {
            $this->error('Da chay du ' . $soVong . ' vong, ' . $soVongHong . ' vong loi - xem log o tren.');

            return 1;
        }

        $this->info('Da chay du ' . $soVong . ' vong, thoat de nssm dung lai tien trinh sach.');

        return 0;
    }

    /** [tu, den] dang yyyyMMddHHmmss; sai dinh dang => in loi, tra null. */
    private function khoangNgay()
    {
        $tuChu = $this->option('tu-ngay');
        $denChu = $this->option('den-ngay');
        if ($tuChu === null || $tuChu === '') {
            $tuChu = $denChu;
        }
        if ($denChu === null || $denChu === '') {
            $denChu = $tuChu;
        }

        $tu = self::docNgay($tuChu, false);
        if ($tu === null) {
            $this->error('--tu-ngay khong hop le: ' . $tuChu . ' (dung Y-m-d, Ymd hoac YmdHis)');

            return null;
        }
        $den = self::docNgay($denChu, true);
        if ($den === null) {
            $this->error('--den-ngay khong hop le: ' . $denChu . ' (dung Y-m-d, Ymd hoac YmdHis)');

            return null;
        }
        if ($tu > $den) {
            $this->error('--tu-ngay phai nho hon hoac bang --den-ngay');

            return null;
        }

        return [$tu, $den];
    }

    private static function docNgay($chu, $cuoiNgay)
    {
        $chu = trim((string) $chu);
        if (preg_match('/^\d{14}$/', $chu)) {
            $t = \DateTime::createFromFormat('YmdHis', $chu);

            return $t !== false && $t->format('YmdHis') === $chu ? (int) $chu : null;
        }
        foreach (['Y-m-d', 'Ymd'] as $mau) {
            $t = \DateTime::createFromFormat('!' . $mau, $chu);
            if ($t !== false && $t->format($mau) === $chu) {
                return (int) ($t->format('Ymd') . ($cuoiNgay ? '235959' : '000000'));
            }
        }

        return null;
    }
}

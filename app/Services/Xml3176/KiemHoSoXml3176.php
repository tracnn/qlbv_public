<?php

namespace App\Services\Xml3176;

use App\Models\BHYT\Xml3176ErrorResult;
use App\Services\Xml3176CompleteChecker;
use App\Services\Xml3176ErrorService;

/**
 * Kiem MOT ho so XML3176: tung loai XML co checker roi kiem tong the.
 *
 * Phan loi dung chung cua KiemHoSoXml3176Job (hang doi) va lenh xml3176:do-kiem (do/so sanh)
 * de lenh do dung dung duong kiem chay tren prod.
 *
 * Moi loai lam DUNG nhu CheckXml3176TypeJob::handle cu: xoa loi cua rieng loai -> batDauGom ->
 * nap dong (Xml3176CheckTypes::truyVanDong, lo 500, kem XML1) -> ketThucGom trong finally.
 * Kiem tong the (tru khi organization.xml_3176_not_check): xoa loi XMLComplete cu -> gom ->
 * DuLieuHoSo::nap + Xml3176CompleteChecker::checkErrors -> ketThucGom trong finally.
 *
 * NGUOI CANH ($conHieuLuc): goi TRUOC moi lan xoa/ghi loi (moi loai va kiem tong the). Tra
 * false -> dung ngay, khong xoa/ghi tiep, tra false. Job truyen phep so chain_token; lenh do
 * truyen phep luon dung.
 *
 * Khong dong dau checked_at - job lam (co dieu kien token).
 */
class KiemHoSoXml3176
{
    /** So dong nap moi lan, nhu CheckXml3176TypeJob::CO_LO. */
    const CO_LO = 500;

    const TONG_THE = 'XMLComplete';

    /**
     * @param string        $maLk
     * @param array         $loaiXml    cac loai can kiem (loai khong co checker bi bo qua); chay
     *                                  theo thu tu Xml3176CheckTypes::LOAI, moi loai mot lan
     * @param callable      $conHieuLuc nguoi canh, xem chu thich lop
     * @param bool          $ghi        true (job): xoa loi cu, gom roi ghi tung buoc.
     *                                  false (lenh do): khong xoa, khong ketThucGom - bo dem gom
     *                                  de nguyen cho lenh doc; luon kiem tong the (nhu lenh do cu)
     * @param callable|null $boc        function ($muc, callable $viec): boc tung buoc (lenh do do
     *                                  thoi gian va bat ngoai le tung muc); null = goi thang
     * @param callable|null $moiDong    function ($muc): goi truoc moi dong duoc kiem (lenh do dem)
     * @return bool false neu dung vi nguoi canh tra false
     */
    public function chay(string $maLk, array $loaiXml, callable $conHieuLuc, bool $ghi = true, callable $boc = null, callable $moiDong = null): bool
    {
        $boc = $boc ?: function ($muc, callable $viec) {
            $viec();
        };
        $loi = app(Xml3176ErrorService::class);

        if ($ghi) {
            // Danh muc sua qua ung dung (tien trinh khac) -> xoa bo dem tinh truoc khi kiem.
            Xml3176PhienBanDanhMuc::dongBoBoDem();
        } elseif (!$loi->dangGom()) {
            // Lenh do: khong bao gio ghi. batDauGom xoa bo dem nen chi bat khi chua gom.
            $loi->batDauGom();
        }

        foreach (self::theoThuTu($loaiXml) as $loai) {
            if (!$conHieuLuc()) {
                return false;
            }

            $boc($loai, function () use ($maLk, $loai, $ghi, $loi, $moiDong) {
                $this->kiemLoai($maLk, $loai, $ghi, $loi, $moiDong);
            });
        }

        // Lenh do luon kiem tong the nhu ban cu cua no (so sanh voi moc truoc).
        if ($ghi && config('organization.xml_3176_not_check', false)) {
            return true;
        }

        if (!$conHieuLuc()) {
            return false;
        }

        $boc(self::TONG_THE, function () use ($maLk, $ghi, $loi, $moiDong) {
            $this->kiemTongThe($maLk, $ghi, $loi, $moiDong);
        });

        return true;
    }

    /** Loai co checker trong danh sach, khu trung, theo thu tu Xml3176CheckTypes::LOAI. */
    public static function theoThuTu(array $loaiXml): array
    {
        $co = array_flip(array_filter($loaiXml, 'is_string'));

        return array_values(array_filter(array_keys(Xml3176CheckTypes::LOAI), function ($loai) use ($co) {
            return isset($co[$loai]);
        }));
    }

    private function kiemLoai($maLk, $loai, $ghi, Xml3176ErrorService $loi, callable $moiDong = null)
    {
        $cauHinh = Xml3176CheckTypes::cauHinh($loai);

        if ($ghi) {
            // Xoa loi CUA RIENG LOAI NAY (khong dung deleteErrors - xoa toan ho so).
            Xml3176ErrorResult::where('ma_lk', $maLk)
                ->where('xml', $loai)
                ->delete();
        }

        $checker = app($cauHinh['checker']);

        if ($ghi) {
            $loi->batDauGom();
        }

        try {
            // Nap kem XML1 (neu loai co quan he) - mot truy van moi lo thay vi moi dong.
            Xml3176CheckTypes::truyVanDong($loai, $maLk)
                ->chunk(self::CO_LO, function ($dong) use ($checker, $loai, $moiDong) {
                    foreach ($dong as $d) {
                        if ($moiDong) {
                            $moiDong($loai);
                        }
                        $checker->checkErrors($d);
                    }
                });
        } finally {
            // Hong giua chung thi phan da tim duoc van ghi, va bo dem khong ro sang buoc sau.
            if ($ghi) {
                $loi->ketThucGom();
            }
        }
    }

    private function kiemTongThe($maLk, $ghi, Xml3176ErrorService $loi, callable $moiDong = null)
    {
        if ($ghi) {
            // Xoa XMLComplete cu truoc khi ghi: job tong the cu khong xoa, retry co the nhan doi.
            Xml3176ErrorResult::where('ma_lk', $maLk)
                ->where('xml', self::TONG_THE)
                ->delete();
            $loi->batDauGom();
        }

        try {
            if ($moiDong) {
                $moiDong(self::TONG_THE);
            }
            app(Xml3176CompleteChecker::class)->checkErrors($maLk, DuLieuHoSo::nap((string) $maLk));
        } finally {
            if ($ghi) {
                $loi->ketThucGom();
            }
        }
    }
}

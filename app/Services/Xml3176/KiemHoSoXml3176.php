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
 * NGUOI CANH ($conHieuLuc): goi TRUOC moi lan xoa loi va NGAY TRUOC moi lan ghi bo dem
 * (ketThucGom) cua tung loai va kiem tong the. Tra false -> bo bo dem chua ghi, dung ngay, khong
 * xoa/ghi tiep, tra false. Lan xoa DAU TIEN khong goi: nguoi goi phai so ngay truoc khi goi
 * chay() (job da so o dau handle()). Job truyen phep so chain_token; lenh do truyen luon dung.
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

        // Nguoi goi vua so token ngay truoc khi goi chay(): bo lan so dau tien.
        $vuaSo = true;

        foreach (self::theoThuTu($loaiXml) as $loai) {
            if (!$vuaSo && !$conHieuLuc()) {
                return false;
            }
            $vuaSo = false;

            $con = true;
            $boc($loai, function () use ($maLk, $loai, $ghi, $loi, $conHieuLuc, $moiDong, &$con) {
                $con = $this->kiemLoai($maLk, $loai, $ghi, $loi, $conHieuLuc, $moiDong);
            });
            if (!$con) {
                return false;
            }
        }

        // Lenh do luon kiem tong the nhu ban cu cua no (so sanh voi moc truoc).
        if ($ghi && config('organization.xml_3176_not_check', false)) {
            return true;
        }

        if (!$vuaSo && !$conHieuLuc()) {
            return false;
        }

        $con = true;
        $boc(self::TONG_THE, function () use ($maLk, $ghi, $loi, $conHieuLuc, $moiDong, &$con) {
            $con = $this->kiemTongThe($maLk, $ghi, $loi, $conHieuLuc, $moiDong);
        });

        return $con;
    }

    /** Loai co checker trong danh sach, khu trung, theo thu tu Xml3176CheckTypes::LOAI. */
    public static function theoThuTu(array $loaiXml): array
    {
        $co = array_flip(array_filter($loaiXml, 'is_string'));

        return array_values(array_filter(array_keys(Xml3176CheckTypes::LOAI), function ($loai) use ($co) {
            return isset($co[$loai]);
        }));
    }

    /** @return bool false neu nguoi canh tra false ngay truoc khi ghi (bo dem da bo) */
    private function kiemLoai($maLk, $loai, $ghi, Xml3176ErrorService $loi, callable $conHieuLuc, callable $moiDong = null): bool
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

        $con = true;
        $dangNem = true;
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
            $dangNem = false;
        } finally {
            // Hong giua chung thi phan da tim duoc van ghi (neu con hieu luc), va bo dem khong
            // ro sang buoc sau.
            if ($ghi) {
                $con = $this->ghiBoDem($loi, $conHieuLuc, $dangNem);
            }
        }

        return $con;
    }

    /** @return bool false neu nguoi canh tra false ngay truoc khi ghi (bo dem da bo) */
    private function kiemTongThe($maLk, $ghi, Xml3176ErrorService $loi, callable $conHieuLuc, callable $moiDong = null): bool
    {
        if ($ghi) {
            // Xoa XMLComplete cu truoc khi ghi: job tong the cu khong xoa, retry co the nhan doi.
            Xml3176ErrorResult::where('ma_lk', $maLk)
                ->where('xml', self::TONG_THE)
                ->delete();
            $loi->batDauGom();
        }

        $con = true;
        $dangNem = true;
        try {
            if ($moiDong) {
                $moiDong(self::TONG_THE);
            }
            app(Xml3176CompleteChecker::class)->checkErrors($maLk, DuLieuHoSo::nap((string) $maLk));
            $dangNem = false;
        } finally {
            if ($ghi) {
                $con = $this->ghiBoDem($loi, $conHieuLuc, $dangNem);
            }
        }

        return $con;
    }

    /**
     * So token NGAY TRUOC khi ghi bo dem: lech -> bo bo dem (ket qua cua du lieu cu) roi moi tat
     * gom. Luon ketThucGom de bo dem khong ro sang buoc/job sau.
     *
     * Dang co ngoai le lan truyen ($dangNem) thi khong de loi cua nguoi canh che mat no: nguoi
     * canh nem -> bo bo dem, nuot loi cua nguoi canh, ngoai le goc tiep tuc lan truyen.
     *
     * @return bool true neu da ghi (con hieu luc)
     */
    private function ghiBoDem(Xml3176ErrorService $loi, callable $conHieuLuc, bool $dangNem): bool
    {
        try {
            $con = (bool) $conHieuLuc();
        } catch (\Throwable $e) {
            $loi->xoaBoDemGom();
            $loi->ketThucGom();
            if ($dangNem) {
                return false;
            }
            throw $e;
        }

        if (!$con) {
            $loi->xoaBoDemGom();
        }
        $loi->ketThucGom();

        return $con;
    }
}

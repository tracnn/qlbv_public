<?php

namespace App\Services\Ctdt;

use Illuminate\Support\Facades\DB;
use App\Jobs\SignCtdtJob;
use App\Jobs\SubmitCtdtJob;
use App\Models\BHYT\Ctdt\CtdtLichSuGui;

/**
 * Dat khoa chong xu ly trung roi xep chuoi ky so - gui len cong.
 *
 * VI SAO LOP RIENG: man chi tiet (nut "Ky va gui"), nut gui HANG LOAT va lenh Console
 * ctdt:import deu can dung mot viec nay. De moi ben tu viet thi cac ban se lech nhau - dung
 * dieu da xay ra that voi XML3176, va ghi chu trong CtdtImporter::nhapTuTep() da canh bao
 * truoc.
 *
 * KHONG kiem dieu kien gui o day: noi goi phai tu hoi CtdtDuDieuKienGui truoc, vi cac noi
 * goi bao loi cho nhung doi tuong khac nhau - man hinh tra JSON cho nguoi bam, Console in ra
 * man hinh va ghi log.
 */
class CtdtXepHangKyGui
{
    /** Ten bang khoa. Xem chu thich dai o migration create_ctdt_khoa_xu_ly_table. */
    const BANG_KHOA = 'ctdt_khoa_xu_ly';

    /**
     * Thoi han khoa, tinh bang PHUT.
     *
     * 30 phut phai LON HON tong ngan sach thu lai cua ca chuoi: SignCtdtJob co tries = 2,
     * timeout = 120; SubmitCtdtJob co tries = 3, timeout = 90; queue.connections.database
     * .retry_after = 300. Khoa ngan hon la mo cua cho lan bam thu hai trong khi chuoi thu
     * nhat con dang chay.
     */
    const KHOA_PHUT = 30;

    /**
     * @param  string      $maHoSo
     * @param  string|null $nguoiGui loginname nguoi bam, co the null ca khi nguoi do bam tay
     * @param  string      $nguon    CtdtLichSuGui::NGUON_MAN_HINH hoac NGUON_CONSOLE
     * @return bool false khi ho so dang co luot xu ly khac
     */
    public static function xep($maHoSo, $nguoiGui = null, $nguon = CtdtLichSuGui::NGUON_MAN_HINH)
    {
        if (!self::giuKhoa($maHoSo, $nguon)) {
            return false;
        }

        // Chuoi chu khong hai lan dispatch roi rac: job gui co the chay truoc job ky va luon
        // thay is_signed = false.
        SignCtdtJob::withChain([
            (new SubmitCtdtJob($maHoSo, $nguoiGui, $nguon))->onQueue(CtdtHangDoi::gui()),
        ])
        ->dispatch($maHoSo)
        ->onQueue(CtdtHangDoi::ky());

        return true;
    }

    /**
     * Giu khoa cho MOT ho so. Nguyen tu that, khac han Cache::add() tren FileStore.
     *
     * @param  string $maHoSo
     * @param  string $nguon
     * @return bool false khi ho so da co khoa con hieu luc
     */
    public static function giuKhoa($maHoSo, $nguon = CtdtLichSuGui::NGUON_MAN_HINH)
    {
        // Don khoa het han TRUOC khi thu chen. Khong don thi mot tien trinh bi giet giua
        // chuoi de lai khoa mo coi, va ho so do khong bao gio gui duoc nua.
        //
        // Don TOAN BANG chu khong chi dong cua $maHoSo: bang nay chi co dong cho nhung ho so
        // DANG chay nen no rat nho, va don ca bang thi khong can mot tien trinh don rac rieng.
        self::donKhoaHetHan();

        try {
            DB::table(self::BANG_KHOA)->insert([
                'ma_ho_so'    => $maHoSo,
                'het_han_luc' => now()->addMinutes(self::KHOA_PHUT),
                'nguon'       => $nguon,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // CHI coi la "da co nguoi giu" khi that su trung rang buoc duy nhat (SQLSTATE
            // 23000). Bat QueryException chung chung la bien MOI loi CSDL - thieu bang, mat
            // ket noi - thanh "ho so dang xu ly": nut bam bao mot cau vo hai, khong dong log
            // nao, va khong ho so nao gui duoc nua.
            if ((string) $e->getCode() !== '23000') {
                throw $e;
            }

            return false;
        }

        return true;
    }

    /**
     * Chi HOI, khong dat khoa.
     *
     * giuKhoa() vua hoi vua dat, nen dung no lam phep tham do se lam chinh nguoi hoi chiem
     * mat khoa - va lan xep hang that ngay sau do bi tu choi boi chinh minh.
     *
     * @param  string $maHoSo
     * @return bool
     */
    public static function dangXuLy($maHoSo)
    {
        return DB::table(self::BANG_KHOA)
            ->where('ma_ho_so', $maHoSo)
            ->where('het_han_luc', '>', now())
            ->exists();
    }

    /**
     * Go khoa cua MOT ho so. Dung khi can mo lai duong gui ma khong doi het 30 phut.
     *
     * @param  string $maHoSo
     * @return int so dong da xoa
     */
    public static function goKhoa($maHoSo)
    {
        return DB::table(self::BANG_KHOA)->where('ma_ho_so', $maHoSo)->delete();
    }

    /** @return int so khoa het han da don */
    public static function donKhoaHetHan()
    {
        return DB::table(self::BANG_KHOA)->where('het_han_luc', '<=', now())->delete();
    }
}

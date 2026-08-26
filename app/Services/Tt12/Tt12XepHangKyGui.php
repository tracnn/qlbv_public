<?php

namespace App\Services\Tt12;

use Illuminate\Support\Facades\DB;
use App\Jobs\SignTt12Job;
use App\Jobs\SubmitTt12Job;

/**
 * Dat khoa chong xu ly trung roi xep chuoi ky so - gui len cong.
 *
 * VI SAO LOP RIENG: man chi tiet (nut "Ky va gui") va nut gui HANG LOAT deu can dung mot
 * viec nay. De moi ben tu viet thi cac ban se lech nhau - dung dieu da xay ra that voi
 * XML3176, va chinh vi the CtdtXepHangKyGui moi ra doi.
 *
 * TRUOC DAY nut "Ky va gui" cua TT12 moi lan bam chi lam MOT buoc: chua ky thi day job ky
 * roi dung, phai bam lan hai moi gui. Ten nut noi mot dang, hanh vi mot neo - va o duong gui
 * hang loat thi thong diep "Da day 30 ho so vao hang doi ky va 0 ho so vao hang doi gui" rat
 * de doc luot thanh "da gui xong 30 ho so".
 *
 * KHONG kiem dieu kien gui o day: noi goi phai tu hoi Tt12QuyetDinhGui truoc, vi cac noi goi
 * bao loi cho nhung doi tuong khac nhau.
 */
class Tt12XepHangKyGui
{
    /** Ten bang khoa. Xem chu thich dai o migration create_tt12_khoa_xu_ly_table. */
    const BANG_KHOA = 'tt12_khoa_xu_ly';

    /**
     * Thoi han khoa, tinh bang PHUT.
     *
     * PHAI LON HON tong ngan sach thu lai cua ca chuoi: SignTt12Job va SubmitTt12Job moi job
     * co tries x timeout rieng. Khoa ngan hon la mo cua cho lan bam thu hai trong khi chuoi
     * thu nhat con dang chay. Tt12XepHangKyGuiTest khoa phep tinh nay lai.
     */
    const KHOA_PHUT = 40;

    const NGUON_MAN_HINH = 'man_hinh';
    const NGUON_HANG_LOAT = 'hang_loat';

    /**
     * @param  string      $maHoSo
     * @param  string|null $nguoiGui loginname nguoi bam
     * @param  string      $nguon
     * @return bool false khi ho so dang co luot xu ly khac
     */
    public static function xep($maHoSo, $nguoiGui = null, $nguon = self::NGUON_MAN_HINH)
    {
        if (!self::giuKhoa($maHoSo, $nguon)) {
            return false;
        }

        // CHUOI chu khong hai lan dispatch roi rac: job gui co the chay TRUOC job ky va luon
        // thay is_signed = false.
        //
        // Chuoi KHONG TU NO du an toan: SignTt12Job khi ky hong thi ghi loi roi return binh
        // thuong chu khong nem, nen chuoi van chay tiep. Chot that nam o SubmitTt12Job - no
        // tu hoi lai Tt12QuyetDinhGui::nenGui() truoc khi gui.
        SignTt12Job::withChain(array(
            (new SubmitTt12Job($maHoSo, $nguoiGui))->onQueue(Tt12HangDoi::gui()),
        ))
        ->dispatch($maHoSo)
        ->onQueue(Tt12HangDoi::ky());

        return true;
    }

    /**
     * Giu khoa cho MOT ho so. Nguyen tu that, khac han Cache::add() tren FileStore.
     *
     * @param  string $maHoSo
     * @param  string $nguon
     * @return bool false khi ho so da co khoa con hieu luc
     */
    public static function giuKhoa($maHoSo, $nguon = self::NGUON_MAN_HINH)
    {
        // Don khoa het han TRUOC khi thu chen. Khong don thi mot tien trinh bi giet giua
        // chuoi de lai khoa mo coi, va ho so do khong bao gio gui duoc nua.
        //
        // Don TOAN BANG chu khong chi dong cua $maHoSo: bang nay chi co dong cho nhung ho so
        // DANG chay nen no rat nho, va don ca bang thi khong can mot tien trinh don rac rieng.
        self::donKhoaHetHan();

        try {
            DB::table(self::BANG_KHOA)->insert(array(
                'ma_ho_so'    => $maHoSo,
                'het_han_luc' => now()->addMinutes(self::KHOA_PHUT),
                'nguon'       => $nguon,
                'created_at'  => now(),
                'updated_at'  => now(),
            ));
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
     * Go khoa cua MOT ho so. Dung khi can mo lai duong gui ma khong doi het han.
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

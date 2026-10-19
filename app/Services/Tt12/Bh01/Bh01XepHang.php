<?php

namespace App\Services\Tt12\Bh01;

use Illuminate\Support\Facades\DB;

use App\Jobs\SignBh01Job;
use App\Jobs\SubmitBh01Job;
use App\Models\BHYT\Tt12\Bh01Ban;
use App\Services\Tt12\Tt12HangDoi;
use App\Services\Tt12\Tt12QuyetDinhGui;

/**
 * Xep ky / xep gui mot ban 01/BH, va khoa chong gui trung.
 *
 * VI SAO MOT NOI DUY NHAT: spec muc 5.3 "khong co duong nao khac xep SubmitBh01Job". Cong la
 * cong THAT, gui roi khong rut lai duoc; hai noi tu xep job gui thi chi can mot noi quen giu
 * khoa la hai lan bam thanh hai ho so tren cong.
 *
 * KHOA NAM TREN CHINH DONG bh01_ban (cot dang_xu_ly_tu) chu khong o bang khoa rieng nhu
 * Tt12XepHangKyGui: mot UPDATE co dieu kien la nguyen tu tren ca MySQL lan SQLite, va
 * khong co dong khoa mo coi nao phai don.
 */
class Bh01XepHang
{
    /**
     * Thoi han khoa gui, tinh bang PHUT.
     *
     * PHAI >= tries x timeout cua SubmitBh01Job (3 x 300s = 15 phut) cong bien: khoa het han
     * giua chung la mo cua cho lan bam thu hai trong khi lan thu nhat con dang thu lai.
     * Bh01XepHangTest khoa phep tinh nay lai.
     */
    const KHOA_PHUT = 20;

    /**
     * @param  string      $maBan
     * @param  string|null $guiBoi loginname nguoi bam
     * @return bool false khi ban dang co luot gui khac (hoac khong ton tai)
     */
    public static function xepGui($maBan, $guiBoi = null)
    {
        $bayGio = now();

        // MOT cau UPDATE co dieu kien, dem so dong bi doi: hai yeu cau den cung luc thi CSDL
        // chi cho mot ben doi duoc dong. Doc truoc roi ghi sau (SELECT roi UPDATE) se de ca
        // hai ben cung thay "chua khoa" va cung xep job.
        $soDong = DB::table('bh01_ban')
            ->where('ma_ban', $maBan)
            ->where(function ($q) use ($bayGio) {
                $q->whereNull('dang_xu_ly_tu')
                  ->orWhere('dang_xu_ly_tu', '<', $bayGio->copy()->subMinutes(self::KHOA_PHUT));
            })
            ->update(array('dang_xu_ly_tu' => $bayGio, 'updated_at' => $bayGio));

        if ($soDong !== 1) {
            return false;
        }

        try {
            SubmitBh01Job::dispatch($maBan, $guiBoi)->onQueue(Tt12HangDoi::gui());
        } catch (\Throwable $e) {
            // Xep hang hong (mat ket noi hang doi) thi khong job nao chay de nha khoa: nha
            // ngay, khong thi ban bi chan gui suot KHOA_PHUT ma khong ai biet vi sao.
            self::nhaKhoa($maBan);

            throw $e;
        }

        return true;
    }

    /**
     * Xep ky (hoac ky lai) mot ban.
     *
     * Khong giu khoa: ky lai dung tu cung bh01_dong va cung id_ds_chitiet nen cung noi dung
     * truoc chu ky. NHUNG tu choi ban da gui hoac dang gui: tep da ky cua ban do la dau vet
     * doi soat (ban tai ve phai dung la ban cong da nhan), va ky de giua luc job gui dang
     * doc tep se lam lech sha256.
     *
     * @param  string $maBan
     * @return bool false khi ban khong ton tai, da gui, hoac dang gui
     */
    public static function xepKy($maBan)
    {
        $soDong = DB::table('bh01_ban')
            ->where('ma_ban', $maBan)
            ->whereNull('submitted_at')
            ->where(function ($q) {
                $q->whereNull('dang_xu_ly_tu')
                  ->orWhere('dang_xu_ly_tu', '<', now()->subMinutes(self::KHOA_PHUT));
            })
            ->update(array(
                'trang_thai_ky' => Bh01Ban::KY_CHO,
                'signed_error'  => null,
                'updated_at'    => now(),
            ));

        if ($soDong !== 1) {
            return false;
        }

        SignBh01Job::dispatch($maBan)->onQueue(Tt12HangDoi::ky());

        return true;
    }

    /**
     * Mo lai duong gui. Job gui goi o cuoi moi luot va trong failed().
     *
     * @param  string $maBan
     * @return int so dong da doi
     */
    public static function nhaKhoa($maBan)
    {
        return DB::table('bh01_ban')
            ->where('ma_ban', $maBan)
            ->update(array('dang_xu_ly_tu' => null, 'updated_at' => now()));
    }

    /**
     * Ban MOI NHAT (so_ban lon nhat) cua ky da duoc cong tiep nhan - de man hinh hoi xac nhan
     * truoc khi gui them mot ban cho ky do (spec muc 5.4).
     *
     * @param  string     $maCskcb
     * @param  string|int $nam
     * @param  string|int $thang
     * @return Bh01Ban|null
     */
    public static function daDuocTiepNhan($maCskcb, $nam, $thang)
    {
        // Chuan hoa ve chuoi '09': thang_qt luu dang chuoi co so 0 dau, so sanh chuoi.
        $cacBan = Bh01Ban::where('ma_cskcb', (string) $maCskcb)
            ->where('nam_qt', (string) (int) $nam)
            ->where('thang_qt', sprintf('%02d', (int) $thang))
            ->whereNotNull('ma_ket_qua')
            ->orderBy('so_ban', 'desc')
            ->get();

        // Hoi Tt12QuyetDinhGui chu khong so '200' tai cho: bang ma thanh cong nam o mot noi.
        return $cacBan->first(function ($ban) {
            return Tt12QuyetDinhGui::daTiepNhan($ban->ma_ket_qua);
        });
    }
}

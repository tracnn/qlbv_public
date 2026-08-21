<?php

namespace App\Services\Ctdt;

use Illuminate\Support\Facades\Cache;
use App\Jobs\SignCtdtJob;
use App\Jobs\SubmitCtdtJob;
use App\Models\BHYT\Ctdt\CtdtLichSuGui;

/**
 * Dat khoa chong xu ly trung roi xep chuoi ky so - gui len cong.
 *
 * VI SAO LOP RIENG: man chi tiet (nut "Ky va gui") va lenh Console ctdt:import deu can dung
 * mot viec nay. De moi ben tu viet thi hai ban se lech nhau - dung dieu da xay ra that voi
 * XML3176, va ghi chu trong CtdtImporter::nhapTuTep() da canh bao truoc.
 *
 * KHONG kiem dieu kien gui o day: noi goi phai tu hoi CtdtQuyetDinhGui truoc, vi hai noi goi
 * bao loi cho hai doi tuong khac nhau - man hinh tra JSON cho nguoi bam, Console in ra man
 * hinh va ghi log.
 */
class CtdtXepHangKyGui
{
    /** Tien to khoa cache, ghep them ma ho so */
    const KHOA = 'ctdt:dang-xu-ly:';

    /**
     * Thoi han khoa, tinh bang PHUT - Cache::add() cua Laravel 5.5 nhan phut.
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
        // Cache::add() tra false khi khoa DA ton tai - do chinh la phep thu "da co ai xep
        // chua". Khoa theo TUNG ma ho so: mot khoa chung se khoa ca he thong lai chi vi mot
        // ho so dang chay, va lenh Console xu 200 ho so mot luot se chi xep duoc dung mot.
        if (!Cache::add(self::KHOA . $maHoSo, true, self::KHOA_PHUT)) {
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
     * Chi HOI, khong dat khoa.
     *
     * Cache::add() vua hoi vua dat, nen dung no lam phep tham do se lam chinh nguoi hoi
     * chiem mat khoa - va lan xep hang that ngay sau do bi tu choi boi chinh minh.
     *
     * @param  string $maHoSo
     * @return bool
     */
    public static function dangXuLy($maHoSo)
    {
        return Cache::has(self::KHOA . $maHoSo);
    }
}

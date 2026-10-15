<?php

namespace App\Services\Xml3176;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Phien ban danh muc: sua danh muc qua ung dung co hieu luc ngay trong worker hang doi.
 *
 * - Moi duong GHI danh muc goi tang(): +1 nguyen tu trong bang xml3176_phien_ban_danh_muc
 *   (dong id = 1) va xoa bo dem cua chinh tien trinh dang ghi.
 * - Moi job kiem goi dongBoBoDem() luc bat dau (1 truy van): phien ban khac lan truoc thay
 *   trong tien trinh nay thi xoa toan bo bo dem tinh.
 * - TTL 600 giay cua Xml3176BoDem van giu lam luoi an toan (vd sua tay bang SQL).
 *
 * Bang chua co (chua chay migrate) hay loi CSDL: KHONG bao gio nem - chi ghi log. Lenh chi
 * doc xml3176:do-kiem co the chay tren CSDL chua migrate.
 */
class Xml3176PhienBanDanhMuc
{
    const BANG = 'xml3176_phien_ban_danh_muc';

    /** @var int|null phien ban thay lan cuoi trong tien trinh nay; null = chua doc */
    private static $daThay = null;

    /** @var bool da ghi canh bao doc loi chua (chi mot lan moi tien trinh) */
    private static $daCanhBao = false;

    /** Tang phien ban sau khi ghi danh muc. Khong bao gio nem. */
    public static function tang(string $lyDo): void
    {
        try {
            $du = ['ly_do' => mb_substr($lyDo, 0, 255, 'UTF-8'), 'updated_at' => Carbon::now()];
            $so = DB::table(self::BANG)->where('id', 1)
                ->update(['phien_ban' => DB::raw('phien_ban + 1')] + $du);

            if ($so === 0) {
                // Mat dong id = 1 (bi xoa tay): tao lai.
                DB::table(self::BANG)->insert(['id' => 1, 'phien_ban' => 1] + $du);
            }
        } catch (\Throwable $e) {
            Log::warning('Khong tang duoc phien ban danh muc XML3176 (' . $lyDo . '): ' . $e->getMessage());
        }

        // Tien trinh vua ghi (web/lenh) cung phai thay danh muc moi ngay.
        self::xoaBoDemTatCa();
    }

    /**
     * Doc phien ban; doi so voi lan truoc (hoac $batBuoc) thi xoa bo dem tinh.
     * Doc loi / chua co bang: coi nhu khong biet, khong lam gi.
     */
    public static function dongBoBoDem(bool $batBuoc = false): void
    {
        try {
            $phienBan = DB::table(self::BANG)->where('id', 1)->value('phien_ban');
        } catch (\Throwable $e) {
            if (!self::$daCanhBao) {
                self::$daCanhBao = true;
                Log::warning('Khong doc duoc phien ban danh muc XML3176 (chua migrate?): ' . $e->getMessage());
            }

            return;
        }

        if ($phienBan === null) {
            return;
        }

        $phienBan = (int) $phienBan;
        if ($batBuoc || $phienBan !== self::$daThay) {
            self::xoaBoDemTatCa();
            self::$daThay = $phienBan;
        }
    }

    public static function xoaBoDemTatCa(): void
    {
        Xml3176BoDem::xoaTatCa();
        NvytLookup::xoaBoNho();
        VatPhamKemDvktLookup::xoaBoNho();
    }

    /** Cho test: quen phien ban da thay, nhu tien trinh moi. */
    public static function quenPhienBan(): void
    {
        self::$daThay = null;
        self::$daCanhBao = false;
    }
}

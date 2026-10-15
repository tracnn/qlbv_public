<?php

namespace App\Services\Xml3176;

/**
 * Bo dem tinh dung chung cho kiem XML3176: song qua nhieu job trong cung worker.
 *
 * - TTL_GIAY tinh tu luc tao bo dem theo ten; het han thi bo dem do bi bo va tinh lai.
 * - Moi bo dem theo khoa toi da TRAN_MUC muc (TRAN_MUC_MODEL cho bo dem chua model/Collection);
 *   vuot tran thi xoa bo dem do roi nho tiep.
 * - Truoc khi nho muc moi, neu bo nho thuc dung > NGUONG_BO_NHO thi xoa TAT CA bo dem (worker
 *   chay dai, memory_limit co the chi 128M).
 * - Ngoai le tu ham tinh KHONG duoc nho (loi CSDL khong thanh ket qua), duoc nem lai.
 */
class Xml3176BoDem
{
    const TTL_GIAY = 600;
    const TRAN_MUC = 20000;
    /** Tran cho bo dem chua Eloquent model / Collection (nang hon nhieu so voi scalar/bool). */
    const TRAN_MUC_MODEL = 5000;
    /** Neu memory_get_usage(true) vuot nguong nay (byte) thi xoa tat ca bo dem truoc khi nho them. */
    const NGUONG_BO_NHO = 67108864; // 64 MB

    /** Ten cac bo dem chua model/Collection. */
    const BO_DEM_MODEL = [
        'danh_muc.dvkt_hieu_luc',
        'danh_muc.thuoc_khop',
        'danh_muc.vtyt',
        'danh_muc.thuoc_ham_luong_duyet',
        'cvs.icdyhct_dong',
    ];

    /** @var callable|null Doc bo nho cho test: tra so byte dang dung. Test tu dat lai (xoaTatCa khong dat lai). */
    public static $docBoNho = null;

    /** @var callable|null Dong ho gia lap cho test: tra so giay hien tai. */
    public static $bayGio = null;

    /** @var array ten => ['luc' => int, 'muc' => array khoa => gia tri] */
    private static $theoKhoa = [];

    /** @var array ten => ['luc' => int, 'bang' => array] */
    private static $bangDay = [];

    /**
     * Tra gia tri da nho cho (ten, khoa) hoac goi $tinh() roi nho (ke ca false/null).
     */
    public static function nho(string $ten, string $khoa, callable $tinh)
    {
        $bay = self::hetHan(self::$theoKhoa, $ten);
        if (!$bay && array_key_exists($khoa, self::$theoKhoa[$ten]['muc'])) {
            return self::$theoKhoa[$ten]['muc'][$khoa];
        }

        $giaTri = $tinh();
        self::ghi($ten, $khoa, $giaTri);

        return $giaTri;
    }

    /**
     * Nap ca bang mot lan cho $ten; lan sau (con han) tra lai ban da nap.
     */
    public static function bang(string $ten, callable $nap): array
    {
        if (!self::hetHan(self::$bangDay, $ten)) {
            return self::$bangDay[$ten]['bang'];
        }

        $bang = $nap();
        self::$bangDay[$ten] = ['luc' => self::bayGio(), 'bang' => $bang];

        return $bang;
    }

    /**
     * Dat gia tri cho mot khoa cua bang da nap. Bang chua nap hoac da het han thi khong lam gi
     * (lan nap sau se doc CSDL moi).
     */
    public static function dat(string $ten, string $khoa, $giaTri): void
    {
        if (self::hetHan(self::$bangDay, $ten)) {
            return;
        }

        self::$bangDay[$ten]['bang'][$khoa] = $giaTri;
    }

    /** Co khoa trong bang da nap (con han) hay khong. */
    public static function coTrongBang(string $ten, string $khoa): bool
    {
        return !self::hetHan(self::$bangDay, $ten) && array_key_exists($khoa, self::$bangDay[$ten]['bang']);
    }

    public static function xoaTatCa(): void
    {
        self::$theoKhoa = [];
        self::$bangDay = [];
        self::$bayGio = null;
        // $docBoNho la moc test, khong dat lai o day (xoaTatCa cung duoc goi khi vuot nguong bo nho).
    }

    private static function tranMuc(string $ten): int
    {
        return in_array($ten, self::BO_DEM_MODEL, true) ? self::TRAN_MUC_MODEL : self::TRAN_MUC;
    }

    private static function boNhoDangDung(): int
    {
        return self::$docBoNho ? (int) call_user_func(self::$docBoNho) : memory_get_usage(true);
    }

    private static function ghi(string $ten, string $khoa, $giaTri): void
    {
        if (self::boNhoDangDung() > self::NGUONG_BO_NHO) {
            $bayGio = self::$bayGio;
            self::xoaTatCa();
            self::$bayGio = $bayGio;
        }

        if (self::hetHan(self::$theoKhoa, $ten)) {
            self::$theoKhoa[$ten] = ['luc' => self::bayGio(), 'muc' => []];
        } elseif (count(self::$theoKhoa[$ten]['muc']) >= self::tranMuc($ten)
            && !array_key_exists($khoa, self::$theoKhoa[$ten]['muc'])) {
            self::$theoKhoa[$ten] = ['luc' => self::bayGio(), 'muc' => []];
        }

        self::$theoKhoa[$ten]['muc'][$khoa] = $giaTri;
    }

    /** Tra true neu chua co hoac da het han (va bo muc het han di). */
    private static function hetHan(array &$kho, string $ten): bool
    {
        if (!isset($kho[$ten])) {
            return true;
        }
        if (self::bayGio() - $kho[$ten]['luc'] > self::TTL_GIAY) {
            unset($kho[$ten]);

            return true;
        }

        return false;
    }

    private static function bayGio(): int
    {
        return self::$bayGio ? (int) call_user_func(self::$bayGio) : time();
    }
}

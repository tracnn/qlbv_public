<?php

namespace App\Services\Xml3176;

/**
 * Bo dem tinh dung chung cho kiem XML3176: song qua nhieu job trong cung worker.
 *
 * - TTL_GIAY tinh tu luc tao bo dem theo ten; het han thi bo dem do bi bo va tinh lai.
 * - Moi bo dem theo khoa toi da TRAN_MUC muc; vuot tran thi xoa bo dem do roi nho tiep.
 * - Ngoai le tu ham tinh KHONG duoc nho (loi CSDL khong thanh ket qua), duoc nem lai.
 */
class Xml3176BoDem
{
    const TTL_GIAY = 600;
    const TRAN_MUC = 20000;

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
     * Dat gia tri cho mot khoa. Neu $ten la bang da nap thi sua ngay trong bang do
     * (bang chua nap thi khong lam gi, lan nap sau se doc CSDL moi).
     */
    public static function dat(string $ten, string $khoa, $giaTri): void
    {
        if (isset(self::$bangDay[$ten])) {
            self::$bangDay[$ten]['bang'][$khoa] = $giaTri;

            return;
        }

        self::ghi($ten, $khoa, $giaTri);
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
    }

    private static function ghi(string $ten, string $khoa, $giaTri): void
    {
        if (self::hetHan(self::$theoKhoa, $ten)) {
            self::$theoKhoa[$ten] = ['luc' => self::bayGio(), 'muc' => []];
        } elseif (count(self::$theoKhoa[$ten]['muc']) >= self::TRAN_MUC
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

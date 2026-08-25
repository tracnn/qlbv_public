<?php

namespace App\Services\Tt12\Mau;

/**
 * Hanh vi chung cua mot dac ta mau TT12.
 *
 * Lop con chi khai DU LIEU (ten the, danh sach cot). Moi hanh vi suy ra tu du lieu do
 * nam o day, viet MOT LAN. Sau ban cai rieng la sau ban se lech nhau - dung dieu da
 * xay ra voi cac checker cua XML3176.
 */
abstract class MauCoSo
{
    /** @return string ma mau, vi du 'MAU_01' */
    abstract public static function ma();

    /** @return string ten hien thi cho nguoi dung */
    abstract public static function ten();

    /** @return string ten the bao ngoai danh sach, vi du 'DANHSACH_DMBOPHANCHUYENMON' */
    abstract public static function theDanhSach();

    /** @return string ten the mot dong, vi du 'DMBOPHANCHUYENMON' */
    abstract public static function theDong();

    /** @return string khoa danh muc trong config/catalog_import_mapping.php */
    abstract public static function danhMuc();

    /** @return string ten bang danh muc dich */
    abstract public static function bangDanhMuc();

    /**
     * Dac ta cot, DUNG THU TU the trong XML va thu tu cot trong Excel.
     *
     * @return array moi phan tu ['the' => string, 'kieu' => 'so'|'chuoi'|'ngay8',
     *                            'max' => int|null, 'bat_buoc' => bool]
     */
    abstract public static function cot();

    /** @return array dac ta cot bang con; rong neu mau khong co bang con */
    public static function cotCon()
    {
        return array();
    }

    /** @return string|null the bao ngoai danh sach con */
    public static function theDanhSachCon()
    {
        return null;
    }

    /** @return string|null the mot dong con */
    public static function theDongCon()
    {
        return null;
    }

    /**
     * Tien to cot bang con trong tep Excel. MAU_05 dat 12 the con thanh 12 cot phang
     * mang tien to THUOCPX_ - khong co tien to thi TEN_THUOC cua bang con dung ten voi
     * cot cha o mau khac.
     *
     * @return string
     */
    public static function tienToCon()
    {
        return 'THUOCPX_';
    }

    /** @return array chi ten the, dung thu tu */
    public static function tenThe()
    {
        $ten = array();

        foreach (static::cot() as $cot) {
            $ten[] = $cot['the'];
        }

        return $ten;
    }

    /**
     * The dung lam cot 'ma' rut ra tren man hinh danh sach.
     *
     * Mac dinh la the thu HAI (the dau luon la STT). MAU_06 lech quy uoc nay - the thu hai
     * cua no la TEN_TB - nen Mau06 ghi de. Hoi lop dac ta chu de noi goi doan theo vi tri
     * la de mot mau lech lam sai cot loc ma khong ai biet.
     *
     * @return string
     */
    public static function theMa()
    {
        $ten = static::tenThe();

        return $ten[1];
    }

    /** @return string the dung lam cot 'ten' rut ra tren man hinh danh sach */
    public static function theTen()
    {
        $ten = static::tenThe();

        return $ten[2];
    }

    /** @return array chi ten the cua bang con, dung thu tu */
    public static function tenTheCon()
    {
        $ten = array();

        foreach (static::cotCon() as $cot) {
            $ten[] = $cot['the'];
        }

        return $ten;
    }

    /** @return array ten cot Excel cua bang con: tien to + ten the */
    public static function tenCotExcelCon()
    {
        $ten = array();

        foreach (static::tenTheCon() as $the) {
            $ten[] = static::tienToCon() . $the;
        }

        return $ten;
    }

    /**
     * Anh xa the XML -> cot bang danh muc.
     *
     * Quy tac dung cho CA SAU MAU: ten cot = chu thuong cua ten the. STT khong anh xa -
     * no la so thu tu trong mot lan gui, khong phai thuoc tinh cua ban ghi danh muc.
     * Task 3 co test khang dinh moi cot suy ra duoc that su ton tai trong bang dich.
     *
     * @return array [TEN_THE => ten_cot]
     */
    public static function cotDanhMuc()
    {
        $anhXa = array();

        foreach (static::tenThe() as $the) {
            if ($the === 'STT') {
                continue;
            }

            $anhXa[$the] = strtolower($the);
        }

        return $anhXa;
    }

    /** @return array dac ta cua mot the, hoac null */
    public static function cotCua($the)
    {
        foreach (static::cot() as $cot) {
            if ($cot['the'] === $the) {
                return $cot;
            }
        }

        return null;
    }

    /** @return string gia tri loaiHs lay tu config */
    public static function loaiHs()
    {
        return (string) config('tt12.mau.' . static::ma() . '.loai_hs');
    }

    /** @return string url gui lay tu config */
    public static function url()
    {
        return (string) config('tt12.mau.' . static::ma() . '.url');
    }
}

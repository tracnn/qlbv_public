<?php

namespace App\Services\Ctdt\Kiem;

/**
 * Bang KIEU TRUONG, khoa theo ten the, dung chung cho ca chin loai chung tu.
 *
 * VI SAO MOT BANG CHUNG: ten the lap lai rat nhieu giua chin loai (NGAY_SINH, GIOI_TINH,
 * LOAI_GIAYTO... co mat o gan het). Khai rieng cho tung loai la chep cung mot quy tac chin
 * lan, va chin ban se lech nhau. Giong cach CtdtNhanTruong da lam voi nhan hien thi.
 *
 * VI SAO LIET KE TUONG MINH chu khong doan theo ten: 'SO_NGAY_NGHIDUONGTHAI' chua chu
 * NGAY nhung la MOT SO DEM, khong phai ngay. Doan theo ten se bat no phai co dang
 * 'YYYYMMDD' va sinh loi gia cho moi ho so nghi duong thai.
 */
class CtdtQuyTac
{
    /** @var array TEN THE => kieu truong */
    const BANG = [
        // ── Truong ngay ────────────────────────────────────────────────────────
        // Do dai KHONG dong nhat giua cac loai: NGAY_VAO cua CT03 la 12 ky tu con cua
        // CT06 la 8. Nen quy tac chi doi 8/12/14 chu so VA la mot ngay co that.
        'NGAY_SINH'               => 'ngay',
        'NGAY_VAO'                => 'ngay',
        'NGAY_RA'                 => 'ngay',
        'NGAY_CT'                 => 'ngay',
        'NGAY_CHUNG_TU'           => 'ngay',
        'NGAY_KCB'                => 'ngay',
        'NGAYCAP_CCCD'            => 'ngay',
        'NGOAITRU_TUNGAY'         => 'ngay',
        'NGOAITRU_DENNGAY'        => 'ngay',
        'NGAY_SINHCON'            => 'ngay',
        'NGAY_CHETCON'            => 'ngay',
        'TU_NGAY'                 => 'ngay',
        'DEN_NGAY'                => 'ngay',
        'NGAY_DINH_CHI_THAINGHEN' => 'ngay',
        'NGAY_CAP'                => 'ngay',
        'NGAYGIO_VV'              => 'ngay',
        'NGAY_TV'                 => 'ngay',
        'NGAY_CAPGIAYBT'          => 'ngay',
        'NGAY_SINH_CON'           => 'ngay',
        'NGAYSINH_NND'            => 'ngay',
        'NGAYCAP_CCCD_NND'        => 'ngay',
        'NGAYSINH_MTH'            => 'ngay',
        'NGAYCAP_CCCD_MTH'        => 'ngay',
        'NGAYSINH_CHA_MTH'        => 'ngay',
        'NGAYCAP_CCCD_CHA_MTH'    => 'ngay',
        'NGAYSINH_CHA_NND'        => 'ngay',
        'NGAYCAP_CCCD_CHA_NND'    => 'ngay',

        // ── Gioi tinh: 1 Nam, 2 Nu, 3 chua xac dinh ────────────────────────────
        'GIOI_TINH'     => 'gioi_tinh',
        'GIOI_TINH_CON' => 'gioi_tinh',

        // ── Loai giay to: 0 khong giay to, 1 CCCD, 2 CMND, 3 ho chieu, 4 dinh danh ──
        'LOAI_GIAYTO'          => 'loai_giayto',
        'LOAI_GIAYTO_NND'      => 'loai_giayto',
        'LOAI_GIAYTO_MTH'      => 'loai_giayto',
        'LOAI_GIAYTO_CHA_MTH'  => 'loai_giayto',
        'LOAI_GIAYTO_CHA_NND'  => 'loai_giayto',

        // ── Truong co: chi 0 hoac 1 ────────────────────────────────────────────
        'TEKT'                       => 'co_khong',
        'DINH_CHI_THAI_NGHEN'        => 'co_khong',
        'IS_NOI_KHOA'                => 'co_khong',
        'IS_PHAU_THUAT_THU_THUAT'    => 'co_khong',
        'IS_LAO_GIAI_DOAN_NANG'      => 'co_khong',
        'IS_XO_GAN_GIAI_DOAN_MAT_BU' => 'co_khong',
        'IS_NGHIDUONGTHAI'           => 'co_khong',
        'SINHCON_PHAUTHUAT'          => 'co_khong',
        'SINHCON_DUOI32TUAN'         => 'co_khong',
        'CAP_LAN_DAU'                => 'co_khong',
    ];

    /**
     * Cac cap ngay bat dau - ket thuc, cho quy tac CTDT006.
     *
     * So sanh tren TAM ky tu dau (phan ngay), vi hai the trong cung mot cap co the khac
     * do dai: NGAYGIO_VV la 12 ky tu con NGAY_TV cung 12, nhung NGAY_VAO/NGAY_RA cua CT06
     * lai la 8. So sanh ca chuoi se cho ket qua sai khi do dai lech.
     *
     * @var array THE BAT DAU => THE KET THUC
     */
    const CAP_NGAY = [
        'NGAY_VAO'   => 'NGAY_RA',
        'TU_NGAY'    => 'DEN_NGAY',
        'NGAYGIO_VV' => 'NGAY_TV',
    ];

    /** @return string|null 'ngay' | 'gioi_tinh' | 'loai_giayto' | 'co_khong' | null */
    public static function kieuCua($tenThe)
    {
        $tenThe = (string) $tenThe;

        return isset(self::BANG[$tenThe]) ? self::BANG[$tenThe] : null;
    }
}

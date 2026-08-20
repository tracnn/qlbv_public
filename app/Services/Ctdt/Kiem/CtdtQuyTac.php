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
     * Cac truong ngay CHAP NHAN dang chi co nam (yyyy), ngoai 8/12/14 chu so.
     *
     * Cong van 2076/BHXH-CNTT PL02 ghi ro o bang truong CT03/CT04/CT07:
     *   "NGAY_SINH ... dinh dang yyyyMMdd HOAC yyyy, voi yyyy la nam sinh"
     * va tuong tu cho NGAYSINH_NND, NGAY_SINHCON, NGAY_CHETCON, NGAYCAP_CMND_NND.
     * Nam sinh khong ro ngay thang la quy uoc quen thuoc voi nguoi cao tuoi - tren du lieu
     * that co 41 truong hop dang 1950 / 1948 / 1945 / 1939 tung bi bat nham.
     *
     * DANH SACH HEP CO CHU DICH. Cac truong ngay khac KHONG duoc noi long: NGAY_VAO/NGAY_RA
     * la yyyyMMddHHmm, TU_NGAY/DEN_NGAY la yyyyMMdd. Cho yyyy qua o do la de lot
     * 'NGAY_RA = 2026' - mot ho so ra vien vao "nam nao do".
     *
     * Bon truong dau duoc tai lieu neu DICH DANH. Cac truong con lai (ngay sinh cua me,
     * cua cha, va ngay cap giay to) la SUY RA tu cung mot ngu nghia: cung la ngay sinh /
     * ngay cap giay to cua mot nguoi, noi ma ngay thang co the khong ro. Neu doi chieu voi
     * cong cho thay suy luan nay sai, thu hep lai danh sach nay chu dung sua ngayHopLe().
     *
     * @var string[]
     */
    const CHI_NAM = [
        // Duoc tai lieu neu dich danh
        'NGAY_SINH',
        'NGAYSINH_NND',
        'NGAY_SINHCON',
        'NGAY_CHETCON',

        // Suy ra tu cung ngu nghia
        'NGAYSINH_MTH',
        'NGAYSINH_CHA_MTH',
        'NGAYSINH_CHA_NND',
        'NGAYCAP_CCCD',
        'NGAYCAP_CCCD_NND',
        'NGAYCAP_CCCD_MTH',
        'NGAYCAP_CCCD_CHA_MTH',
        'NGAYCAP_CCCD_CHA_NND',
    ];

    /** @return bool The nay co chap nhan dang chi co nam khong */
    public static function choPhepChiNam($tenThe)
    {
        return in_array((string) $tenThe, self::CHI_NAM, true);
    }

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

<?php

namespace App\Services\Ctdt\Kiem;

/**
 * Truong bat buoc va truong khuyen nghi, theo tung loai chung tu.
 *
 * VI SAO KHONG LAY TU DAC TA: PL02 chi danh dau cot "Bat buoc" cho THAM SO API (muc 2),
 * con cac bang mo ta the cua CT03/CT04/... (muc 9.2, 10.2...) khong co cot do. Danh sach
 * duoi day la quyet dinh nghiep vu, da duoc chu du an duyet.
 *
 * NGUYEN TAC: chi dua vao muc BAT BUOC nhung truong ma thieu la ho so vo nghia hoac cong
 * chac chan tu choi. Bat buoc rong tay se sinh mot bien lo, va nguoi van hanh se hoc cach
 * bo qua ca cot so loi.
 *
 * MA_THE nam o KHUYEN NGHI chu khong phai bat buoc: PL02 co the TEKT (tre em khong the)
 * voi gia tri 1 la hop le. Dat o muc bat buoc se chan nham moi ho so tre so sinh - dung
 * nhom ma giay chung sinh phuc vu.
 */
class CtdtTruongBatBuoc
{
    /** @var array LOAIHOSO => danh sach the bat buoc (muc chan) */
    const BAT_BUOC = [
        'CT03'              => ['MA_YTE', 'HO_TEN', 'NGAY_SINH', 'NGAY_VAO', 'NGAY_RA'],
        'CT04'              => ['HO_TEN', 'NGAY_SINH', 'NGAY_VAO', 'NGAY_RA'],
        'CT06'              => ['HO_TEN', 'NGAY_SINH', 'NGAY_VAO', 'NGAY_RA'],
        'CT07'              => ['HO_TEN', 'NGAY_SINH', 'TU_NGAY', 'DEN_NGAY'],
        'GIAYDIEUTRINOITRU' => ['MA_YTE', 'HO_TEN', 'NGAY_SINH', 'NGAY_VAO', 'NGAY_RA'],
        'GIAYDIEUTRIVOSINH' => ['MA_YTE', 'HO_TEN', 'NGAY_SINH', 'NGAY_VAO', 'NGAY_RA'],
        'GIAYSUCKHOEME'     => ['MA_YTE', 'HO_TEN', 'NGAY_SINH', 'NGAY_VAO', 'NGAY_RA'],
        'GIAYBAOTU'         => ['MA_GBT', 'HO_TEN', 'NGAY_SINH', 'NGAY_TV'],
        'GIAYCHUNGSINH'     => ['MA_GCS', 'HOTEN_NND', 'NGAYSINH_NND', 'NGAY_SINH_CON'],
    ];

    /** @var array LOAIHOSO => danh sach the khuyen nghi (muc canh bao) */
    const KHUYEN_NGHI = [
        'CT03'              => ['MA_THE'],
        'CT04'              => ['MA_THE'],
        'CT06'              => ['MA_THE'],
        'CT07'              => ['MA_THE'],
        'GIAYDIEUTRINOITRU' => ['MA_THE'],
        'GIAYDIEUTRIVOSINH' => ['MA_THE'],
        'GIAYSUCKHOEME'     => ['MA_THE'],
        'GIAYBAOTU'         => ['MA_THE'],
        'GIAYCHUNGSINH'     => ['MA_THE_NND'],
    ];

    /** @return array Mang rong voi loai la - loai do do CtdtChecker bo qua, khong nem */
    public static function cua($loaiHoSo)
    {
        return isset(self::BAT_BUOC[$loaiHoSo]) ? self::BAT_BUOC[$loaiHoSo] : [];
    }

    /** @return array */
    public static function khuyenNghi($loaiHoSo)
    {
        return isset(self::KHUYEN_NGHI[$loaiHoSo]) ? self::KHUYEN_NGHI[$loaiHoSo] : [];
    }
}

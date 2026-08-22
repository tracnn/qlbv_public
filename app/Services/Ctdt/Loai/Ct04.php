<?php

namespace App\Services\Ctdt\Loai;

use App\Models\BHYT\Ctdt\CtdtCt04;

/**
 * CT04 - Ban tom tat ho so benh an (Mau so 03 - TT25).
 *
 * KHONG co MA_YTE: mot HOSO chi gom CT04 se khong co khoa nghiep vu va phai lui ve
 * Id GUID cua THONGTINHOSO - luc do khong ghi de duoc. Day la han che DA BIET.
 */
class Ct04 implements LoaiChungTu
{
    public static function maLoaiHoSo()
    {
        return 'CT04';
    }

    public static function theGoc()
    {
        return 'CT04';
    }

    public static function dichVu()
    {
        return 'CT2025';
    }

    public static function bang()
    {
        return 'ctdt_ct04';
    }

    public static function model()
    {
        return CtdtCt04::class;
    }

    public static function tenTab()
    {
        return 'Tóm tắt hồ sơ bệnh án';
    }

    public static function truong()
    {
        return [
            'MA_CT'                      => 'ma_ct',
            'SO_SERI'                    => 'so_seri',
            'MA_BHXH'                    => 'ma_bhxh',
            'MA_THE'                     => 'ma_the',
            'HO_TEN'                     => 'ho_ten',
            'NGAY_SINH'                  => 'ngay_sinh',
            'GIOI_TINH'                  => 'gioi_tinh',
            'MA_DANTOC'                  => 'ma_dantoc',
            'DIA_CHI'                    => 'dia_chi',
            'NGHE_NGHIEP'                => 'nghe_nghiep',
            'HO_TEN_CHA'                 => 'ho_ten_cha',
            'HO_TEN_ME'                  => 'ho_ten_me',
            'NGUOI_GIAM_HO'              => 'nguoi_giam_ho',
            'TEN_DONVI'                  => 'ten_donvi',
            'NGUOI_DAI_DIEN'             => 'nguoi_dai_dien',
            'NGAY_CT'                    => 'ngay_ct',
            'NGAY_VAO'                   => 'ngay_vao',
            'NGAY_RA'                    => 'ngay_ra',
            'CHAN_DOAN_VAO'              => 'chan_doan_vao',
            'CHAN_DOAN_RA'               => 'chan_doan_ra',
            'QT_BENHLY'                  => 'qt_benhly',
            'TOMTAT_KQ'                  => 'tomtat_kq',
            'PP_DIEUTRI'                 => 'pp_dieutri',
            'NGAY_SINHCON'               => 'ngay_sinhcon',
            'NGAY_CHETCON'               => 'ngay_chetcon',
            'SO_CONCHET'                 => 'so_conchet',
            'TT_RAVIEN'                  => 'tt_ravien',
            'GHI_CHU'                    => 'ghi_chu',
            'TEKT'                       => 'tekt',
            'LOAI_GIAYTO'                => 'loai_giayto',
            'SO_CCCD'                    => 'so_cccd',
            'NGAYCAP_CCCD'               => 'ngaycap_cccd',
            'NOICAP_CCCD'                => 'noicap_cccd',
            'LYDO_VVIEN'                 => 'lydo_vvien',
            'TIEN_SU_BENH'               => 'tien_su_benh',
            'DAU_HIEU_LAM_SANG'          => 'dau_hieu_lam_sang',
            'NOI_KHOA'                   => 'noi_khoa',
            'IS_NOI_KHOA'                => 'is_noi_khoa',
            'PHAU_THUAT_THU_THUAT'       => 'phau_thuat_thu_thuat',
            'IS_PHAU_THUAT_THU_THUAT'    => 'is_phau_thuat_thu_thuat',
            'HUONG_DIEU_TRI'             => 'huong_dieu_tri',
            'BENH_ICD10_ID'              => 'benh_icd10_id',
            'BENH_ICD10_TEN'             => 'benh_icd10_ten',
            'IS_LAO_GIAI_DOAN_NANG'      => 'is_lao_giai_doan_nang',
            'IS_XO_GAN_GIAI_DOAN_MAT_BU' => 'is_xo_gan_giai_doan_mat_bu',
        ];
    }

    public static function maChungTu(\SimpleXMLElement $xml)
    {
        // CT04 khong co MA_YTE. Tra null thay vi bia tu MA_CT / SO_SERI: bia khoa co
        // rui ro nang hon - hai ho so khac nhau bi coi la mot va mat du lieu im lang.
        return null;
    }

    public static function rutGon(\SimpleXMLElement $xml)
    {
        return [
            'ma_the'    => DocThe::chuoi($xml, 'MA_THE'),
            'ho_ten'    => DocThe::chuoi($xml, 'HO_TEN'),
            'ngay_sinh' => DocThe::chuoi($xml, 'NGAY_SINH'),
            'ngay_vao'  => DocThe::chuoi($xml, 'NGAY_VAO'),
            'ngay_ra'   => DocThe::chuoi($xml, 'NGAY_RA'),
            'so_cccd'   => DocThe::chuoi($xml, 'SO_CCCD'),
            'ma_bhxh'   => DocThe::chuoi($xml, 'MA_BHXH'),
        ];
    }
}

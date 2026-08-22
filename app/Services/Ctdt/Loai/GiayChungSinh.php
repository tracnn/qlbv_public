<?php

namespace App\Services\Ctdt\Loai;

use App\Models\BHYT\Ctdt\CtdtGiayChungSinh;

/**
 * Giay chung sinh - dich vu rieng, loaiHs=61, goi HSDLGCS (TT22/2025).
 *
 * BA nhom nguoi dung hau to khac nhau:
 *   _NND     nguoi de   |   _MTH  me thay the   |   _CHA_MTH  cha cua me thay the
 *   _CHA_NND cha cua nguoi de
 *
 * Cot rut gon lay thong tin NGUOI ME (_NND) chu khong phai dua con: man danh sach tra
 * cuu theo nguoi co the BHYT.
 */
class GiayChungSinh implements LoaiChungTu
{
    public static function maLoaiHoSo()
    {
        return 'GIAYCHUNGSINH';
    }

    public static function theGoc()
    {
        return 'GIAYCHUNGSINH';
    }

    public static function dichVu()
    {
        return 'GCS';
    }

    public static function bang()
    {
        return 'ctdt_giay_chung_sinh';
    }

    public static function model()
    {
        return CtdtGiayChungSinh::class;
    }

    public static function tenTab()
    {
        return 'Giấy chứng sinh';
    }

    public static function truong()
    {
        return [
            // Dinh danh va nguoi de
            'MA_GCS'                => 'ma_gcs',
            'MA_BN'                 => 'ma_bn',
            'MA_CT'                 => 'ma_ct',
            'SO_SERI'               => 'so_seri',
            'MA_BHXH_NND'           => 'ma_bhxh_nnd',
            'MA_THE_NND'            => 'ma_the_nnd',
            'HOTEN_NND'             => 'hoten_nnd',
            'NGAYSINH_NND'          => 'ngaysinh_nnd',
            'MA_DANTOC_NND'         => 'ma_dantoc_nnd',
            'MA_QUOCTICH_NND'       => 'ma_quoctich_nnd',
            'LOAI_GIAYTO_NND'       => 'loai_giayto_nnd',
            'SO_CCCD_NND'           => 'so_cccd_nnd',
            'NGAYCAP_CCCD_NND'      => 'ngaycap_cccd_nnd',
            'NOICAP_CCCD_NND'       => 'noicap_cccd_nnd',
            'NOI_CU_TRU_NND'        => 'noi_cu_tru_nnd',
            'MATINH_CU_TRU'         => 'matinh_cu_tru',
            'MAHUYEN_CU_TRU'        => 'mahuyen_cu_tru',
            'MAXA_CU_TRU'           => 'maxa_cu_tru',
            'HO_TEN_CHA'            => 'ho_ten_cha',
            'MA_THE_TAM'            => 'ma_the_tam',
            // Thong tin con
            'TEN_CON'               => 'ten_con',
            'GIOI_TINH_CON'         => 'gioi_tinh_con',
            'SO_CON'                => 'so_con',
            'LAN_SINH'              => 'lan_sinh',
            'SO_CON_SONG'           => 'so_con_song',
            'CAN_NANG_CON'          => 'can_nang_con',
            'NGAY_SINH_CON'         => 'ngay_sinh_con',
            'NOI_SINH_CON'          => 'noi_sinh_con',
            'TINH_TRANG_CON'        => 'tinh_trang_con',
            'SINHCON_PHAUTHUAT'     => 'sinhcon_phauthuat',
            'SINHCON_DUOI32TUAN'    => 'sinhcon_duoi32tuan',
            'GHI_CHU'               => 'ghi_chu',
            // Nguoi lap phieu va don vi
            'NGUOI_DO_DE'           => 'nguoi_do_de',
            'NGUOI_GHI_PHIEU'       => 'nguoi_ghi_phieu',
            'MA_TTDV'               => 'ma_ttdv',
            'THU_TRUONG_DVI'        => 'thu_truong_dvi',
            'NGAY_CT'               => 'ngay_ct',
            'SO'                    => 'so',
            'QUYEN_SO'              => 'quyen_so',
            // Me thay the
            'MA_BHXH_MTH'           => 'ma_bhxh_mth',
            'MA_THE_MTH'            => 'ma_the_mth',
            'HOTEN_MTH'             => 'hoten_mth',
            'NGAYSINH_MTH'          => 'ngaysinh_mth',
            'MA_DANTOC_MTH'         => 'ma_dantoc_mth',
            'MA_QUOCTICH_MTH'       => 'ma_quoctich_mth',
            'LOAI_GIAYTO_MTH'       => 'loai_giayto_mth',
            'SO_CCCD_MTH'           => 'so_cccd_mth',
            'NGAYCAP_CCCD_MTH'      => 'ngaycap_cccd_mth',
            'NOICAP_CCCD_MTH'       => 'noicap_cccd_mth',
            'NOI_CU_TRU_MTH'        => 'noi_cu_tru_mth',
            'MATINH_CU_TRU_MTH'     => 'matinh_cu_tru_mth',
            'MAXA_CU_TRU_MTH'       => 'maxa_cu_tru_mth',
            'HO_TEN_CHA_MTH'        => 'ho_ten_cha_mth',
            'NGAYSINH_CHA_MTH'      => 'ngaysinh_cha_mth',
            'MA_DANTOC_CHA_MTH'     => 'ma_dantoc_cha_mth',
            'NOI_CU_TRU_CHA_MTH'    => 'noi_cu_tru_cha_mth',
            'MATINH_CU_TRU_CHA_MTH' => 'matinh_cu_tru_cha_mth',
            'MAXA_CU_TRU_CHA_MTH'   => 'maxa_cu_tru_cha_mth',
            'LOAI_GIAYTO_CHA_MTH'   => 'loai_giayto_cha_mth',
            'SO_CCCD_CHA_MTH'       => 'so_cccd_cha_mth',
            'NGAYCAP_CCCD_CHA_MTH'  => 'ngaycap_cccd_cha_mth',
            'NOICAP_CCCD_CHA_MTH'   => 'noicap_cccd_cha_mth',
            // Cha cua nguoi de
            'NGAYSINH_CHA_NND'      => 'ngaysinh_cha_nnd',
            'MA_DANTOC_CHA_NND'     => 'ma_dantoc_cha_nnd',
            'LOAI_GIAYTO_CHA_NND'   => 'loai_giayto_cha_nnd',
            'SO_CCCD_CHA_NND'       => 'so_cccd_cha_nnd',
            'NGAYCAP_CCCD_CHA_NND'  => 'ngaycap_cccd_cha_nnd',
            'NOICAP_CCCD_CHA_NND'   => 'noicap_cccd_cha_nnd',
            'CAP_LAN_DAU'           => 'cap_lan_dau',
        ];
    }

    public static function maChungTu(\SimpleXMLElement $xml)
    {
        return DocThe::chuoi($xml, 'MA_GCS');
    }

    public static function rutGon(\SimpleXMLElement $xml)
    {
        return [
            'ma_the'    => DocThe::chuoi($xml, 'MA_THE_NND'),
            'ho_ten'    => DocThe::chuoi($xml, 'HOTEN_NND'),
            'ngay_sinh' => DocThe::chuoi($xml, 'NGAYSINH_NND'),
            'ngay_vao'  => DocThe::chuoi($xml, 'NGAY_SINH_CON'),
            'ngay_ra'   => DocThe::chuoi($xml, 'NGAY_CT'),
            'so_cccd'   => DocThe::chuoi($xml, 'SO_CCCD_NND'),
            'ma_bhxh'   => DocThe::chuoi($xml, 'MA_BHXH_NND'),
        ];
    }
}

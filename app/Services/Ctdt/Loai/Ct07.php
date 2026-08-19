<?php

namespace App\Services\Ctdt\Loai;

use App\Models\BHYT\Ctdt\CtdtCt07;

/**
 * CT07 - Giay chung nhan nghi viec huong BHXH (Mau so 07 - TT25). KHONG co MA_YTE.
 *
 * Khong co NGAY_VAO/NGAY_RA ma dung TU_NGAY/DEN_NGAY. Cot rut gon van dien vao
 * ngay_vao/ngay_ra de man danh sach chi phai loc theo MOT bo cot cho ca chin loai.
 */
class Ct07 implements LoaiChungTu
{
    public static function maLoaiHoSo()
    {
        return 'CT07';
    }

    public static function theGoc()
    {
        return 'CT07';
    }

    public static function dichVu()
    {
        return 'CT2025';
    }

    public static function bang()
    {
        return 'ctdt_ct07';
    }

    public static function model()
    {
        return CtdtCt07::class;
    }

    public static function tenTab()
    {
        return 'Nghỉ việc hưởng BHXH';
    }

    public static function truong()
    {
        return [
            'MA_CT'                => 'ma_ct',
            'MAU_SO'               => 'mau_so',
            'SO_SERI'              => 'so_seri',
            'SO_KCB'               => 'so_kcb',
            'MA_BHXH'              => 'ma_bhxh',
            'MA_THE'               => 'ma_the',
            'HO_TEN'               => 'ho_ten',
            'NGAY_SINH'            => 'ngay_sinh',
            'GIOI_TINH'            => 'gioi_tinh',
            'DON_VI'               => 'don_vi',
            'CHANDOAN_DIEUTRI'     => 'chandoan_dieutri',
            'TU_NGAY'              => 'tu_ngay',
            'DEN_NGAY'             => 'den_ngay',
            'HO_TEN_CHA'           => 'ho_ten_cha',
            'HO_TEN_ME'            => 'ho_ten_me',
            'THU_TRUONG_DV'        => 'thu_truong_dv',
            'MA_CCHN'              => 'ma_cchn',
            'TEN_NGUOI_HANH_NGHE'  => 'ten_nguoi_hanh_nghe',
            'NGAY_CHUNG_TU'        => 'ngay_chung_tu',
            'TEKT'                 => 'tekt',
            'LOAI_GIAYTO'          => 'loai_giayto',
            'SO_CCCD'              => 'so_cccd',
            'NGAYCAP_CCCD'         => 'ngaycap_cccd',
            'NOICAP_CCCD'          => 'noicap_cccd',
            'NGAY_KCB'             => 'ngay_kcb',
            'BENH_ICD10_ID'        => 'benh_icd10_id',
            'BENH_ICD10_TEN'       => 'benh_icd10_ten',
        ];
    }

    public static function maChungTu(\SimpleXMLElement $xml)
    {
        return null;
    }

    public static function rutGon(\SimpleXMLElement $xml)
    {
        return [
            'ma_the'    => DocThe::chuoi($xml, 'MA_THE'),
            'ho_ten'    => DocThe::chuoi($xml, 'HO_TEN'),
            'ngay_sinh' => DocThe::chuoi($xml, 'NGAY_SINH'),
            'ngay_vao'  => DocThe::chuoi($xml, 'TU_NGAY'),
            'ngay_ra'   => DocThe::chuoi($xml, 'DEN_NGAY'),
        ];
    }
}

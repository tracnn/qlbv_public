<?php

namespace App\Services;

class XmlStructures
{
    public static $expectedStructures130 = [
        'XML1' => [
            'MA_LK', 'STT', 'MA_BN', 'HO_TEN', 'SO_CCCD', 'NGAY_SINH', 'GIOI_TINH', 'NHOM_MAU', 'MA_QUOCTICH',
            'MA_DANTOC', 'MA_NGHE_NGHIEP', 'DIA_CHI', 'MATINH_CU_TRU', 'MAHUYEN_CU_TRU', 'MAXA_CU_TRU',
            'DIEN_THOAI', 'MA_THE_BHYT', 'MA_DKBD', 'GT_THE_TU', 'GT_THE_DEN', 'NGAY_MIEN_CCT', 'LY_DO_VV',
            'LY_DO_VNT', 'MA_LY_DO_VNT', 'CHAN_DOAN_VAO', 'CHAN_DOAN_RV', 'MA_BENH_CHINH', 'MA_BENH_KT',
            'MA_BENH_YHCT', 'MA_PTTT_QT', 'MA_DOITUONG_KCB', 'MA_NOI_DI', 'MA_NOI_DEN', 'MA_TAI_NAN', 'NGAY_VAO',
            'NGAY_VAO_NOI_TRU', 'NGAY_RA', 'GIAY_CHUYEN_TUYEN', 'SO_NGAY_DTRI', 'PP_DIEU_TRI', 'KET_QUA_DTRI',
            'MA_LOAI_RV', 'GHI_CHU', 'NGAY_TTOAN', 'T_THUOC', 'T_VTYT', 'T_TONGCHI_BV', 'T_TONGCHI_BH',
            'T_BNTT', 'T_BNCCT', 'T_BHTT', 'T_NGUONKHAC', 'T_BHTT_GDV', 'NAM_QT', 'THANG_QT', 'MA_LOAI_KCB',
            'MA_KHOA', 'MA_CSKCB', 'MA_KHUVUC', 'CAN_NANG', 'CAN_NANG_CON', 'NAM_NAM_LIEN_TUC', 'NGAY_TAI_KHAM',
            'MA_HSBA', 'MA_TTDV', 'DU_PHONG'
        ],
        'XML2' => [
            'MA_LK', 'STT', 'MA_THUOC', 'MA_PP_CHEBIEN', 'MA_CSKCB_THUOC', 'MA_NHOM', 'TEN_THUOC',
            'DON_VI_TINH', 'HAM_LUONG', 'DUONG_DUNG', 'DANG_BAO_CHE', 'LIEU_DUNG', 'CACH_DUNG',
            'SO_DANG_KY', 'TT_THAU', 'PHAM_VI', 'TYLE_TT_BH', 'SO_LUONG', 'DON_GIA', 'THANH_TIEN_BV',
            'THANH_TIEN_BH', 'T_NGUONKHAC_NSNN', 'T_NGUONKHAC_VTNN', 'T_NGUONKHAC_VTTN', 'T_NGUONKHAC_CL',
            'T_NGUONKHAC', 'MUC_HUONG', 'T_BHTT', 'T_BNCCT', 'T_BNTT', 'MA_KHOA', 'MA_BAC_SI', 'MA_DICH_VU',
            'NGAY_YL', 'NGAY_TH_YL', 'MA_PTTT', 'NGUON_CTRA', 'VET_THUONG_TP', 'DU_PHONG'
        ],
        'XML3' => [
            'MA_LK', 'STT', 'MA_DICH_VU', 'MA_PTTT_QT', 'MA_VAT_TU', 'MA_NHOM', 'GOI_VTYT', 'TEN_VAT_TU',
            'TEN_DICH_VU', 'MA_XANG_DAU', 'DON_VI_TINH', 'PHAM_VI', 'SO_LUONG', 'DON_GIA_BV', 'DON_GIA_BH',
            'TT_THAU', 'TYLE_TT_DV', 'TYLE_TT_BH', 'THANH_TIEN_BV', 'THANH_TIEN_BH', 'T_TRANTT', 'MUC_HUONG',
            'T_NGUONKHAC_NSNN', 'T_NGUONKHAC_VTNN', 'T_NGUONKHAC_VTTN', 'T_NGUONKHAC_CL', 'T_NGUONKHAC',
            'T_BHTT', 'T_BNTT', 'T_BNCCT', 'MA_KHOA', 'MA_GIUONG', 'MA_BAC_SI', 'NGUOI_THUC_HIEN', 'MA_BENH',
            'MA_BENH_YHCT', 'NGAY_YL', 'NGAY_TH_YL', 'NGAY_KQ', 'MA_PTTT', 'VET_THUONG_TP', 'PP_VO_CAM',
            'VI_TRI_TH_DVKT', 'MA_MAY', 'MA_HIEU_SP', 'TAI_SU_DUNG', 'DU_PHONG'
        ],
        'XML4' => [
            'MA_LK', 'STT', 'MA_DICH_VU', 'MA_CHI_SO', 'TEN_CHI_SO', 'GIA_TRI', 'DON_VI_DO', 'MO_TA', 'KET_LUAN',
            'NGAY_KQ', 'MA_BS_DOC_KQ', 'DU_PHONG'
        ],
        'XML5' => [
            'MA_LK', 'STT', 'DIEN_BIEN_LS', 'GIAI_DOAN_BENH', 'HOI_CHAN', 'PHAU_THUAT', 'THOI_DIEM_DBLS',
            'NGUOI_THUC_HIEN', 'DU_PHONG'
        ],
        'XML6' => [
            'MA_LK', 'MA_THE_BHYT', 'SO_CCCD', 'NGAY_SINH', 'GIOI_TINH', 'DIA_CHI', 'MATINH_CU_TRU', 'MAHUYEN_CU_TRU', 'MAXA_CU_TRU',
            'NGAYKD_HIV', 'NOI_LAY_MAU_XN', 'NOI_XN_KD', 'NOI_BDDT_ARV', 'BDDT_ARV', 'MA_PHAC_DO_DIEU_TRI_BD',
            'MA_BAC_PHAC_DO_BD', 'MA_LYDO_DTRI', 'LOAI_DTRI_LAO', 'SANG_LOC_LAO', 'PHACDO_DTRI_LAO', 'NGAYBD_DTRI_LAO',
            'NGAYKT_DTRI_LAO', 'KQ_DTRI_LAO', 'MA_LYDO_XNTL_VR', 'NGAY_XN_TLVR', 'KQ_XNTL_VR', 'NGAY_KQ_XN_TLVR',
            'MA_LOAI_BN', 'GIAI_DOAN_LAM_SANG', 'NHOM_DOI_TUONG', 'MA_TINH_TRANG_DK', 'LAN_XN_PCR', 'NGAY_XN_PCR',
            'NGAY_KQ_XN_PCR', 'MA_KQ_XN_PCR', 'NGAY_NHAN_TT_MANG_THAI', 'NGAY_BAT_DAU_DT_CTX', 'MA_XU_TRI',
            'NGAY_BAT_DAU_XU_TRI', 'NGAY_KET_THUC_XU_TRI', 'MA_PHAC_DO_DIEU_TRI', 'MA_BAC_PHAC_DO',
            'SO_NGAY_CAP_THUOC_ARV', 'NGAY_CHUYEN_PHAC_DO', 'LY_DO_CHUYEN_PHAC_DO', 'MA_CSKCB', 'DU_PHONG'
        ],
        'XML7' => [
            'MA_LK', 'SO_LUU_TRU', 'MA_YTE', 'MA_KHOA_RV', 'NGAY_VAO', 'NGAY_RA', 'MA_DINH_CHI_THAI',
            'NGUYENNHAN_DINHCHI', 'THOIGIAN_DINHCHI', 'TUOI_THAI', 'CHAN_DOAN_RV', 'PP_DIEUTRI', 'GHI_CHU',
            'MA_TTDV', 'MA_BS', 'TEN_BS', 'NGAY_CT', 'MA_CHA', 'MA_ME', 'MA_THE_TAM', 'HO_TEN_CHA', 'HO_TEN_ME',
            'SO_NGAY_NGHI', 'NGOAITRU_TUNGAY', 'NGOAITRU_DENNGAY', 'DU_PHONG'
        ],
        'XML8' => [
            'MA_LK', 'MA_LOAI_KCB', 'HO_TEN_CHA', 'HO_TEN_ME', 'NGUOI_GIAM_HO', 'DON_VI', 'NGAY_VAO', 'NGAY_RA',
            'CHAN_DOAN_VAO', 'CHAN_DOAN_RV', 'QT_BENHLY', 'TOMTAT_KQ', 'PP_DIEUTRI', 'NGAY_SINHCON',
            'NGAY_CONCHET', 'SO_CONCHET', 'KET_QUA_DTRI', 'GHI_CHU', 'MA_TTDV', 'NGAY_CT', 'MA_THE_TAM', 'DU_PHONG'
        ],
        'XML9' => [
            'MA_LK', 'MA_BHXH_NND', 'MA_THE_NND', 'HO_TEN_NND', 'NGAYSINH_NND', 'MA_DANTOC_NND', 'SO_CCCD_NND',
            'NGAYCAP_CCCD_NND', 'NOICAP_CCCD_NND', 'NOI_CU_TRU_NND', 'MA_QUOCTICH', 'MATINH_CU_TRU', 'MAHUYEN_CU_TRU',
            'MAXA_CU_TRU', 'HO_TEN_CHA', 'MA_THE_TAM', 'HO_TEN_CON', 'GIOI_TINH_CON', 'SO_CON', 'LAN_SINH', 'SO_CON_SONG',
            'CAN_NANG_CON', 'NGAY_SINH_CON', 'NOI_SINH_CON', 'TINH_TRANG_CON', 'SINHCON_PHAUTHUAT', 'SINHCON_DUOI32TUAN',
            'GHI_CHU', 'NGUOI_DO_DE', 'NGUOI_GHI_PHIEU', 'NGAY_CT', 'SO', 'QUYEN_SO', 'MA_TTDV', 'DU_PHONG'
        ],
        'XML10' => [
            'MA_LK', 'SO_SERI', 'SO_CT', 'SO_NGAY', 'DON_VI', 'CHAN_DOAN_RV', 'TU_NGAY', 'DEN_NGAY', 'MA_TTDV',
            'TEN_BS', 'MA_BS', 'NGAY_CT', 'DU_PHONG'
        ],
        'XML11' => [
            'MA_LK', 'SO_CT', 'SO_SERI', 'SO_KCB', 'DON_VI', 'MA_BHXH', 'MA_THE_BHYT', 'CHAN_DOAN_RV', 'PP_DIEUTRI',
            'MA_DINH_CHI_THAI', 'NGUYENNHAN_DINHCHI', 'TUOI_THAI', 'SO_NGAY_NGHI', 'TU_NGAY', 'DEN_NGAY', 'HO_TEN_CHA',
            'HO_TEN_ME', 'MA_TTDV', 'MA_BS', 'NGAY_CT', 'MA_THE_TAM', 'MAU_SO', 'DU_PHONG'
        ],
        'XML13' => [
            'MA_LK', 'SO_HOSO', 'SO_CHUYENTUYEN', 'GIAY_CHUYEN_TUYEN', 'MA_CSKCB', 'MA_NOI_DI', 'MA_NOI_DEN',
            'HO_TEN', 'NGAY_SINH', 'GIOI_TINH', 'MA_QUOCTICH', 'MA_DANTOC', 'MA_NGHE_NGHIEP', 'DIA_CHI',
            'MA_THE_BHYT', 'GT_THE_DEN', 'NGAY_VAO', 'NGAY_VAO_NOI_TRU', 'NGAY_RA', 'DAU_HIEU_LS', 'CHAN_DOAN_RV',
            'QT_BENHLY', 'TOMTAT_KQ', 'PP_DIEUTRI', 'MA_BENH_CHINH', 'MA_BENH_KT', 'MA_BENH_YHCT', 'TEN_DICH_VU',
            'TEN_THUOC', 'PP_DIEU_TRI', 'MA_LOAI_RV', 'MA_LYDO_CT', 'HUONG_DIEU_TRI', 'PHUONGTIEN_VC',
            'HOTEN_NGUOI_HT', 'CHUCDANH_NGUOI_HT', 'MA_BAC_SI', 'MA_TTDV', 'DU_PHONG'
        ],
        'XML14' => [
            'MA_LK', 'SO_GIAYHEN_KL', 'MA_CSKCB', 'HO_TEN', 'NGAY_SINH', 'GIOI_TINH', 'DIA_CHI', 'MA_THE_BHYT',
            'GT_THE_DEN', 'NGAY_VAO', 'NGAY_VAO_NOI_TRU', 'NGAY_RA', 'NGAY_HEN_KL', 'CHAN_DOAN_RV', 'MA_BENH_CHINH',
            'MA_BENH_KT', 'MA_BENH_YHCT', 'MA_DOITUONG_KCB', 'MA_BAC_SI', 'MA_TTDV', 'NGAY_CT', 'DU_PHONG'
        ],
        'XML15' => [
            'MA_LK', 'STT', 'MA_BN', 'HO_TEN', 'SO_CCCD', 'PHANLOAI_LAO_VITRI', 'PHANLOAI_LAO_TS', 'PHANLOAI_LAO_HIV',
            'PHANLOAI_LAO_VK', 'PHANLOAI_LAO_KT', 'LOAI_DTRI_LAO', 'NGAYBD_DTRI_LAO', 'PHACDO_DTRI_LAO',
            'NGAYKT_DTRI_LAO', 'KET_QUA_DTRI_LAO', 'MA_CSKCB', 'NGAYKD_HIV', 'BDDT_ARV', 'NGAY_BAT_DAU_DT_CTX',
            'DU_PHONG'
        ]
    ];
}
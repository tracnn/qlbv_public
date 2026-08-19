<?php

namespace App\Services\Ctdt;

/**
 * Tu dien nhan tieng Viet cho ten the PL02.
 *
 * VI SAO MOT TU DIEN CHUNG chu khong phai nhan rieng cho tung loai: ten the lap lai rat
 * nhieu giua chin loai (MA_THE, HO_TEN, NGAY_SINH, NGAY_VAO... co mat o gan het). Khai
 * rieng cho tung loai la chep lai cung mot nhan chin lan, va chin ban se lech nhau.
 *
 * LUI VE CHINH TEN THE khi chua co trong tu dien: BHXH them the moi truoc khi ta kip cap
 * nhat la chuyen se xay ra, va hien "SO_NGAY_MOI" con hon hien mot o trong.
 */
class CtdtNhanTruong
{
    const TU_DIEN = [
        // Dinh danh ho so
        'SO_LUU_TRU'      => 'Số lưu trữ',
        'MA_YTE'          => 'Mã y tế',
        'MA_BHXH'         => 'Mã số BHXH',
        'MA_THE'          => 'Mã thẻ BHYT',
        'MA_CT'           => 'Mã chứng từ',
        'SO_SERI'         => 'Số seri',
        'MA_KHOA'         => 'Mã khoa',
        'MA_BN'           => 'Mã bệnh nhân',
        'MA_HSBA'         => 'Mã hồ sơ bệnh án',
        'MACSKCB'         => 'Mã cơ sở KCB',
        'DIACHI_CSKCB'    => 'Địa chỉ cơ sở KCB',
        'SO_KCB'          => 'Số khám chữa bệnh',
        'MAU_SO'          => 'Mẫu số',

        // Nhan than
        'HO_TEN'          => 'Họ tên',
        'NGAY_SINH'       => 'Ngày sinh',
        'GIOI_TINH'       => 'Giới tính',
        'MA_DANTOC'       => 'Mã dân tộc',
        'MA_DAN_TOC'      => 'Mã dân tộc',
        'TEN_DAN_TOC'     => 'Tên dân tộc',
        'MA_QUOCTICH'     => 'Mã quốc tịch',
        'NGHE_NGHIEP'     => 'Nghề nghiệp',
        'DIA_CHI'         => 'Địa chỉ',
        'HO_TEN_CHA'      => 'Họ tên cha',
        'HO_TEN_ME'       => 'Họ tên mẹ',
        'NGUOI_GIAM_HO'   => 'Người giám hộ',
        'NGUOI_THANTHICH' => 'Người thân thích',

        // Giay to tuy than
        'LOAI_GIAYTO'     => 'Loại giấy tờ',
        'SO_CCCD'         => 'Số giấy tờ',
        'SO_GIAYTO'       => 'Số giấy tờ',
        'NGAYCAP_CCCD'    => 'Ngày cấp giấy tờ',
        'NGAY_CAP'        => 'Ngày cấp',
        'NOICAP_CCCD'     => 'Nơi cấp giấy tờ',
        'NOI_CAP'         => 'Nơi cấp',

        // Cu tru
        'NOI_CU_TRU_NND'      => 'Nơi cư trú',
        'MATINH_CU_TRU'       => 'Mã tỉnh cư trú',
        'MAHUYEN_CU_TRU'      => 'Mã huyện cư trú',
        'MAXA_CU_TRU'         => 'Mã xã cư trú',
        'MA_TINHCUTRU'        => 'Mã tỉnh cư trú',
        'MA_XACUTRU'          => 'Mã xã cư trú',
        'DCHI_THUONGTRU'      => 'Địa chỉ thường trú',
        'MATINH_THUONGTRU'    => 'Mã tỉnh thường trú',
        'MAHUYEN_THUONGTRU'   => 'Mã huyện thường trú',
        'MAXA_THUONGTRU'      => 'Mã xã thường trú',
        'DCHI_HIENTAI'        => 'Địa chỉ hiện tại',
        'MATINH_HIENTAI'      => 'Mã tỉnh hiện tại',
        'MAHUYEN_HIENTAI'     => 'Mã huyện hiện tại',
        'MAXA_HIENTAI'        => 'Mã xã hiện tại',

        // Dot dieu tri
        'NGAY_VAO'            => 'Ngày vào',
        'NGAY_RA'             => 'Ngày ra',
        'NGAYGIO_VV'          => 'Ngày giờ vào viện',
        'TU_NGAY'             => 'Từ ngày',
        'DEN_NGAY'            => 'Đến ngày',
        'NGAY_KCB'            => 'Ngày khám chữa bệnh',
        'NGOAITRU_TUNGAY'     => 'Ngoại trú từ ngày',
        'NGOAITRU_DENNGAY'    => 'Ngoại trú đến ngày',
        'CHAN_DOAN'           => 'Chẩn đoán',
        'CHAN_DOAN_VAO'       => 'Chẩn đoán vào',
        'CHAN_DOAN_RA'        => 'Chẩn đoán ra',
        'CHANDOAN_DIEUTRI'    => 'Chẩn đoán điều trị',
        'PP_DIEUTRI'          => 'Phương pháp điều trị',
        'QT_BENHLY'           => 'Quá trình bệnh lý',
        'TOMTAT_KQ'           => 'Tóm tắt kết quả',
        'MO_TA'               => 'Mô tả',
        'KET_LUAN'            => 'Kết luận',
        'GHI_CHU'             => 'Ghi chú',
        'TT_RAVIEN'           => 'Tình trạng ra viện',
        'LYDO_VVIEN'          => 'Lý do vào viện',
        'TIEN_SU_BENH'        => 'Tiền sử bệnh',
        'DAU_HIEU_LAM_SANG'   => 'Dấu hiệu lâm sàng',
        'HUONG_DIEU_TRI'      => 'Hướng điều trị',
        'NOI_KHOA'            => 'Điều trị nội khoa',
        'IS_NOI_KHOA'         => 'Có điều trị nội khoa',
        'PHAU_THUAT_THU_THUAT'    => 'Phẫu thuật, thủ thuật',
        'IS_PHAU_THUAT_THU_THUAT' => 'Có phẫu thuật, thủ thuật',
        'TINHTRANGBENHHIENTAI'    => 'Tình trạng bệnh hiện tại',

        // Chan doan ICD
        'BENH_ICD10_ID'   => 'Mã bệnh ICD10',
        'BENH_ICD10_MA'   => 'Mã bệnh ICD10',
        'BENH_ICD10_TEN'  => 'Tên bệnh ICD10',
        'BENHICD10_ID'    => 'Mã bệnh ICD10',
        'TENBENHNICD10'   => 'Tên bệnh ICD10',

        // Thai san
        'DINH_CHI_THAI_NGHEN'     => 'Đình chỉ thai nghén',
        'TUOI_THAI'               => 'Tuổi thai',
        'NGAY_SINHCON'            => 'Ngày sinh con',
        'NGAY_CHETCON'            => 'Ngày chết con',
        'SO_CONCHET'              => 'Số con chết',
        'IS_NGHIDUONGTHAI'        => 'Có nghỉ dưỡng thai',
        'SO_NGAY_NGHIDUONGTHAI'   => 'Số ngày nghỉ dưỡng thai',
        'NGAY_DINH_CHI_THAINGHEN' => 'Ngày đình chỉ thai nghén',
        'LOAI_PHUONG_PHAP'        => 'Loại phương pháp',
        'LOAI_PP_DIEU_TRI_VOSINH' => 'Loại phương pháp điều trị vô sinh',

        // Nguoi hanh nghe, don vi
        'THU_TRUONG_DVI'      => 'Thủ trưởng đơn vị',
        'THU_TRUONG_DV'       => 'Thủ trưởng đơn vị',
        'TTRUONG_DVI'         => 'Thủ trưởng đơn vị',
        'DAI_DIEN_DVI'        => 'Đại diện đơn vị',
        'NGUOI_DAI_DIEN'      => 'Người đại diện',
        'TEN_DONVI'           => 'Tên đơn vị',
        'TEN_DVI'             => 'Tên đơn vị',
        'DON_VI'              => 'Đơn vị',
        'MA_CCHN'             => 'Mã chứng chỉ hành nghề',
        'MA_CCHN_BS'          => 'Mã chứng chỉ hành nghề bác sĩ',
        'MA_CCHN_TRUONGKHOA'  => 'Mã chứng chỉ hành nghề trưởng khoa',
        'TEN_TRUONGKHOA'      => 'Tên trưởng khoa',
        'TEN_NGUOI_HANH_NGHE' => 'Tên người hành nghề',
        'MA_BS'               => 'Mã bác sĩ',
        'TEN_BS'              => 'Tên bác sĩ',
        'NGUOI_GHIGIAY'       => 'Người ghi giấy',
        'NGUOI_GHI_PHIEU'     => 'Người ghi phiếu',
        'NGUOI_DO_DE'         => 'Người đỡ đẻ',
        'MA_TTDV'             => 'Mã định danh thủ trưởng cơ sở',

        // Chung tu
        'NGAY_CT'             => 'Ngày chứng từ',
        'NGAY_CHUNG_TU'       => 'Ngày chứng từ',
        'TEKT'                => 'Trẻ em không thẻ',
        'IS_LAO_GIAI_DOAN_NANG'      => 'Lao giai đoạn nặng',
        'IS_XO_GAN_GIAI_DOAN_MAT_BU' => 'Xơ gan giai đoạn mất bù',

        // Giay bao tu
        'MA_GBT'          => 'Mã giấy báo tử',
        'NGAY_TV'         => 'Ngày tử vong',
        'TINH_TRANG_TV'   => 'Tình trạng tử vong',
        'NGUYENNHAN_TV'   => 'Nguyên nhân tử vong',
        'SO_BAOTU'        => 'Số báo tử',
        'QUYEN_SO'        => 'Quyển số',
        'NGAY_CAPGIAYBT'  => 'Ngày cấp giấy báo tử',
        'SO_BAOTU_BD'     => 'Số báo tử ban đầu',
        'QUYEN_SO_BD'     => 'Quyển số ban đầu',

        // Giay chung sinh
        'MA_GCS'            => 'Mã giấy chứng sinh',
        'TEN_CON'           => 'Tên con',
        'GIOI_TINH_CON'     => 'Giới tính con',
        'SO_CON'            => 'Số con',
        'LAN_SINH'          => 'Lần sinh',
        'SO_CON_SONG'       => 'Số con sống',
        'CAN_NANG_CON'      => 'Cân nặng con (gam)',
        'NGAY_SINH_CON'     => 'Ngày sinh con',
        'NOI_SINH_CON'      => 'Nơi sinh con',
        'TINH_TRANG_CON'    => 'Tình trạng con',
        'SINHCON_PHAUTHUAT' => 'Sinh con phẫu thuật',
        'SINHCON_DUOI32TUAN'=> 'Sinh con dưới 32 tuần',
        'MA_THE_TAM'        => 'Mã thẻ tạm',
        'SO'                => 'Số',
        'CAP_LAN_DAU'       => 'Cấp lần đầu',
    ];

    /**
     * Hau to phan biet BON nhom nguoi trong giay chung sinh. Ghep vao sau nhan goc thay vi
     * khai rieng 60 dong: cung mot the SO_CCCD xuat hien o ca bon nhom.
     */
    const HAU_TO = [
        '_CHA_MTH' => ' (cha của mẹ thay thế)',
        '_CHA_NND' => ' (cha của người đẻ)',
        '_MTH'     => ' (mẹ thay thế)',
        '_NND'     => ' (người đẻ)',
    ];

    /** @return string */
    public static function cua($tenThe)
    {
        $tenThe = (string) $tenThe;

        if (isset(self::TU_DIEN[$tenThe])) {
            return self::TU_DIEN[$tenThe];
        }

        // Thu bo hau to nhom nguoi roi tra lai. Thu tu DUYET quan trong: '_CHA_MTH' phai
        // duoc thu TRUOC '_MTH', khong thi 'HO_TEN_CHA_MTH' se bi cat thanh 'HO_TEN_CHA'
        // va gan nhan cua nhom sai.
        foreach (self::HAU_TO as $hauTo => $themVao) {
            if (substr($tenThe, -strlen($hauTo)) === $hauTo) {
                $goc = substr($tenThe, 0, -strlen($hauTo));

                if (isset(self::TU_DIEN[$goc])) {
                    return self::TU_DIEN[$goc] . $themVao;
                }
            }
        }

        return $tenThe;
    }
}

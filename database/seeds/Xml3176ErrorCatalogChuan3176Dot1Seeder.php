<?php

use Illuminate\Database\Seeder;
use App\Models\BHYT\Xml3176ErrorCatalog;

/**
 * Nap 24 error_code cua Dot 1 - bo sung quy tac theo chuan du lieu dau ra
 * (QD 130/QD-BYT, sua doi theo QD 4750/QD-BYT).
 *
 * BAT BUOC chay TRUOC khi bat quy tac: thieu dong danh muc thi
 * getCriticalErrorStatus() tra mac dinh TRUE, va loi nghiem trong CHAN XUAT XML.
 *
 * Tat ca 24 ma o day KHONG chan xuat XML: cac quy tac nay chua tung chay tren
 * du lieu co tien, mot quy tac bao oan ma chan duong gui ho so thi lam te
 * liet viec gui ho so. Nguoi van hanh bat len qua man danh muc ma loi sau khi quan sat.
 *
 * Idempotent (updateOrCreate) - chay lai an toan.
 */
class Xml3176ErrorCatalogChuan3176Dot1Seeder extends Seeder
{
    public function run()
    {
        $danhMuc = [
            // Nhom A - cau truc ma dich vu
            ['XML3', 'XML3_MA_DICH_VU_TB_CO_DON_GIA', 'DVKT không thực hiện được nhưng vẫn có đơn giá', 'Mã có hậu tố _TB thì DON_GIA_BH và DON_GIA_BV phải bằng 0 (khoản 3 Điều 7 TT 39/2018/TT-BYT)'],
            ['XML3', 'XML3_MA_DICH_VU_CHUA_CO_GIA_CO_DON_GIA_BH', 'DVKT chưa có mức giá nhưng vẫn có đơn giá BH', 'Mã kết thúc bằng 0000 thì DON_GIA_BH phải bằng 0'],
            ['XML3', 'XML3_MA_DICH_VU_HAU_TO_LA', 'Hậu tố mã dịch vụ không hợp lệ', 'Chuẩn chỉ quy định hai hậu tố _TB và _GT'],
            ['XML3', 'XML3_MA_DICH_VU_VAN_CHUYEN_THIEU_XANG_DAU', 'Vận chuyển người bệnh nhưng thiếu mã xăng dầu', 'Mã VC.XXXXX phải kèm MA_XANG_DAU để tính chi phí vận chuyển'],
            ['XML3', 'XML3_MA_DICH_VU_VAN_CHUYEN_CSKCB_NOT_FOUND', 'Mã cơ sở nơi chuyển đến không có trong danh mục', 'XXXXX trong VC.XXXXX phải là mã cơ sở KBCB hợp lệ'],
            ['XML3', 'XML3_MA_DICH_VU_CHUYEN_MAU_CSKCB_NOT_FOUND', 'Mã cơ sở nơi thực hiện cận lâm sàng không có trong danh mục', 'WWWWW trong XX.YYYY.ZZZZ.K.WWWWW phải là mã cơ sở KBCB hợp lệ (TT 09/2019/TT-BYT)'],

            // Nhom B-a - cong thuc tien tung dong
            ['XML3', 'XML3_THANH_TIEN_BV_SAI_CONG_THUC', 'Thành tiền BV không đúng công thức', 'THANH_TIEN_BV = SO_LUONG x DON_GIA_BV x TYLE_TT_DV/100'],
            ['XML3', 'XML3_THANH_TIEN_BH_SAI_CONG_THUC', 'Thành tiền BH không đúng công thức', 'THANH_TIEN_BH = SO_LUONG x DON_GIA_BH x TYLE_TT_DV/100 x TYLE_TT_BH/100'],
            ['XML3', 'XML3_T_NGUONKHAC_SAI_TONG', 'Tiền nguồn khác không bằng tổng bốn nguồn thành phần', 'T_NGUONKHAC = NSNN + VTNN + VTTN + CL'],
            ['XML3', 'XML3_T_BHTT_SAI_CONG_THUC', 'Tiền BHYT thanh toán không đúng công thức', 'T_BHTT = THANH_TIEN_BH x MUC_HUONG/100 (khi không có nguồn khác và không có trần thanh toán)'],
            ['XML2', 'XML2_THANH_TIEN_BV_SAI_CONG_THUC', 'Thành tiền BV không đúng công thức', 'THANH_TIEN_BV = SO_LUONG x DON_GIA'],
            ['XML2', 'XML2_THANH_TIEN_BH_SAI_CONG_THUC', 'Thành tiền BH không đúng công thức', 'THANH_TIEN_BH = SO_LUONG x DON_GIA x TYLE_TT_BH/100'],
            ['XML2', 'XML2_T_NGUONKHAC_SAI_TONG', 'Tiền nguồn khác không bằng tổng bốn nguồn thành phần', 'T_NGUONKHAC = NSNN + VTNN + VTTN + CL'],
            ['XML2', 'XML2_T_BHTT_SAI_CONG_THUC', 'Tiền BHYT thanh toán không đúng công thức', 'T_BHTT = THANH_TIEN_BH x MUC_HUONG/100 (khi không có nguồn khác)'],

            // Nhom B-b - tap gia tri hop le
            ['XML3', 'XML3_PHAM_VI_NGOAI_TAP_GIA_TRI', 'Phạm vi ngoài tập giá trị hợp lệ', 'PHAM_VI chỉ được là 1, 2 hoặc 3'],
            ['XML3', 'XML3_PHAM_VI_TU_TRA_MA_BH_TRA', 'Người bệnh tự trả nhưng quỹ BHYT vẫn thanh toán', 'PHAM_VI = 2 nghĩa là người bệnh tự trả (QĐ 4750) nên THANH_TIEN_BH và T_BHTT phải bằng 0'],
            ['XML3', 'XML3_TAI_SU_DUNG_INVALID', 'Mã tái sử dụng không hợp lệ', 'TAI_SU_DUNG chỉ được ghi 1, không tái sử dụng thì để trống'],
            ['XML3', 'XML3_TAI_SU_DUNG_DON_GIA_LECH', 'VTYT tái sử dụng nhưng hai đơn giá lệch nhau', 'VTYT tái sử dụng phải có DON_GIA_BV = DON_GIA_BH'],
            ['XML2', 'XML2_NGUON_CTRA_INVALID', 'Nguồn chi trả thuốc không hợp lệ', 'NGUON_CTRA chỉ được là 1, 2, 3 hoặc 4'],
            ['XML2', 'XML2_NGUON_CTRA_NGOAI_QUY_MA_BH_TRA', 'Thuốc không do quỹ BHYT chi trả nhưng vẫn đề nghị BHYT thanh toán', 'NGUON_CTRA khác 1 (dự án, chương trình mục tiêu, nguồn khác) thì T_BHTT phải bằng 0'],
            ['XML1', 'XML1_ADMIN_INFO_ERROR_MA_KHUVUC', 'Mã khu vực không hợp lệ', 'MA_KHUVUC chỉ được là K1, K2 hoặc K3'],

            // Nhom C - lien bang
            ['XMLComplete', 'XMLComplete_NGAY_TAI_KHAM_SAI_DINH_DANG', 'Ngày tái khám sai định dạng', 'Mỗi ngày tái khám gồm 8 ký tự yyyymmdd, nhiều ngày ngăn bởi dấu chấm phẩy'],
            ['XMLComplete', 'XMLComplete_NGAY_TAI_KHAM_KHONG_KHOP_XML14', 'Ngày tái khám không có giấy hẹn khám lại tương ứng', 'Mỗi ngày trong NGAY_TAI_KHAM phải có một dòng XML14 cùng MA_LK có NGAY_HEN_KL trùng khớp'],
            ['XMLComplete', 'XMLComplete_CAN_NANG_CON_THIEU_XML9', 'Có cân nặng con nhưng thiếu giấy chứng sinh', 'CAN_NANG_CON chỉ ghi khi sinh con nên hồ sơ phải có XML9'],
        ];

        foreach ($danhMuc as $dong) {
            list($xml, $maLoi, $ten, $moTa) = $dong;

            Xml3176ErrorCatalog::updateOrCreate(
                ['xml' => $xml, 'error_code' => $maLoi],
                [
                    'error_name'     => $ten,
                    'description'    => $moTa,
                    'critical_error' => false,
                    'is_check'       => true,
                ]
            );
        }
    }
}

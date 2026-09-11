<?php

use Illuminate\Database\Seeder;
use App\Models\BHYT\Xml3176ErrorCatalog;

/**
 * Nap 10 error_code cua bo quy tac ma doi tuong den KCB (Phu luc 1 do Bo Y te ban hanh).
 *
 * BAT BUOC chay TRUOC khi bat quy tac: thieu dong danh muc thi
 * getCriticalErrorStatus() tra mac dinh TRUE, quy tac no lan dau se TU GHI dong danh muc
 * o muc nghiem trong va chan xuat XML ca lo - chay seeder sau do cung khong go duoc cac
 * dong loi da ghi. Vi vay co mot migration goi seeder nay.
 *
 * Muc nghiem trong duoc dat khong chan xuat XML cho ca 10 ma: dot nay do duoc 3 ho so vi
 * pham tren 1.213, ba quy tac o XMLComplete chua co ho so nao de chay. Nguoi van hanh tu
 * bat len qua man danh muc ma loi sau khi quan sat.
 *
 * Da bo XML1_DOI_TUONG_KCB_KHONG_BHYT_CO_THE (10 -> 9 ma): ho so ma 9 bi chan tu diem
 * phat job boi cong xml3176.ma_doituong_kcb_khong_kiem (Xml3176Importer::canKiemLoi()),
 * nen Xml3176Xml1Checker khong bao gio chay tren chung - quy tac la ma chet, dong danh
 * muc la dong rac.
 *
 * Idempotent (updateOrCreate) - chay lai an toan.
 */
class Xml3176ErrorCatalogDoiTuongKcbSeeder extends Seeder
{
    public function run()
    {
        $danhMuc = [
            ['XML1', 'XML1_DOI_TUONG_KCB_NGOAI_DANH_MUC', 'Mã đối tượng KCB ngoài danh mục', 'Mã đối tượng KCB phải thuộc danh mục 27 mã do Bộ Y tế ban hành'],
            ['XML1', 'XML1_DOI_TUONG_KCB_THIEU_NOI_DI', 'Đến KCB có phiếu chuyển nhưng thiếu mã nơi chuyển đi', 'Mã 1.3 là đến KCB có phiếu chuyển cơ sở nên MA_NOI_DI không được để trống'],
            ['XML1', 'XML1_DOI_TUONG_KCB_TU_DEN_CO_NOI_DI', 'Người bệnh tự đến nhưng lại có mã nơi chuyển đi', 'Các mã tự đến (1.11-1.18, 3.1, 3.2, 3.3, 3.6) thì MA_NOI_DI phải để trống'],
            ['XML1', 'XML1_DOI_TUONG_KCB_THIEU_THE_BHYT', 'Đề nghị quỹ BHYT thanh toán nhưng không có mã thẻ', 'Chỉ báo khi T_BHTT > 0; cấp cứu chưa xuất trình thẻ là ngoại lệ đã biết'],
            ['XML1', 'XML1_DOI_TUONG_KCB_THIEU_GIAY_CHUYEN_TUYEN', 'Thiếu số phiếu chuyển cơ sở KCB hoặc số phiếu hẹn khám lại', 'Mã 1.3 và 1.5 khai sẵn là đến kèm một tờ phiếu nên GIAY_CHUYEN_TUYEN phải ghi số phiếu đó'],
            ['XML1', 'XML1_DOI_TUONG_KCB_DUNG_DKBD_SAI_MA', 'Đến đúng nơi đăng ký ban đầu nhưng khai mã đối tượng khẳng định đến từ nơi khác', 'MA_CSKCB nằm trong MA_DKBD, mã không có dung_dkbd, và mã có tu_den hoặc can_noi_di'],
            ['XML1', 'XML1_DOI_TUONG_KCB_31_NGOAI_TRU_CO_BHTT', 'Đối tượng này khám ngoại trú không được quỹ BHYT thanh toán', 'Mã 3.1 hưởng 40% nội trú và 0% ngoại trú, và MA_LOAI_KCB khác rỗng'],
            ['XMLComplete', 'XMLComplete_DOI_TUONG_KCB_MUC_HUONG_CO_DINH', 'Mức hưởng không đúng quy định của mã đối tượng', 'Mã 1.2 hưởng 100% không phụ thuộc mức hưởng trên thẻ BHYT'],
            ['XMLComplete', 'XMLComplete_DOI_TUONG_KCB_MUC_HUONG_THEO_MOC', 'Mức hưởng không đúng mốc thời gian của mã đối tượng', 'Mã 1.13, 1.14, 1.18: không được hưởng đến 30/6/2026, hưởng 50% từ 01/7/2026'],
            ['XMLComplete', 'XMLComplete_DOI_TUONG_KCB_LINH_THUOC_CO_TIEN_KHAM', 'Chỉ lĩnh thuốc nhưng vẫn có tiền công khám', 'Mã 7, 7.2, 7.3, 7.4, 10 là chỉ lĩnh thuốc, không khám bệnh'],
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

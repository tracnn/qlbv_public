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
 * Muc nghiem trong duoc dat khong chan xuat XML cho ca 10 ma: dot nay do duoc 5 ho so vi
 * pham tren 1.213, ba quy tac o XMLComplete chua co ho so nao de chay. Nguoi van hanh tu
 * bat len qua man danh muc ma loi sau khi quan sat.
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
            ['XML1', 'XML1_DOI_TUONG_KCB_KHONG_BHYT_CO_THE', 'Khai không KCB BHYT nhưng vẫn có mã thẻ', 'Mã 9 là người bệnh không KCB BHYT nên MA_THE_BHYT phải để trống'],
            ['XML1', 'XML1_DOI_TUONG_KCB_DUNG_DKBD_SAI_MA', 'Đến đúng nơi đăng ký ban đầu nhưng khai mã đối tượng khác', 'MA_CSKCB nằm trong MA_DKBD thì mã đối tượng phải là 1.1 hoặc 1.2'],
            ['XML1', 'XML1_DOI_TUONG_KCB_31_NGOAI_TRU_CO_BHTT', 'Đối tượng này khám ngoại trú không được quỹ BHYT thanh toán', 'Mã 3.1 hưởng 40% nội trú và 0% ngoại trú'],
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

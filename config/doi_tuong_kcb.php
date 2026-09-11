<?php

/**
 * Danh muc ma doi tuong den kham benh, chua benh - Phu luc 1 do Bo truong Bo Y te ban
 * hanh (2025). Nguon: "1. Danh muc ma doi tuong KCB.signed.pdf".
 *
 * 27 MA, khong phai 28: so thu tu trong van ban chay 1->28 nhung NHAY QUA STT 22 (dong
 * 21 la ma '7', dong ke tiep la 23 voi ma '7.2'). Da kiem bang ca trich van ban lan
 * trich bang. KHONG tu them ma '7.1'.
 *
 * Khoa vang nghia la thuoc tinh do khong ap dung cho ma nay. Y nghia tung thuoc tinh:
 *   tu_den                 - nguoi benh tu den, khong qua chuyen co so
 *   can_noi_di             - phai co MA_NOI_DI (co so noi chuyen nguoi benh di)
 *   dung_dkbd              - den dung noi dang ky ban dau
 *   khong_bhyt             - khong KCB BHYT, khong duoc de nghi quy thanh toan
 *   muc_huong_co_dinh      - MUC_HUONG bat buoc, khong phu thuoc muc huong tren the
 *   ngoai_tru_khong_huong  - ngoai tru thi khong duoc huong BHYT
 *   muc_huong_theo_moc     - muc huong doi theo moc thoi gian
 *   linh_thuoc_khong_kham  - chi linh thuoc, khong kham benh
 *
 * Sua tep nay phai chay lai 'php artisan config:clear'.
 */
return [
    '1.1'  => ['ten' => 'Đến KCB đúng cơ sở nơi đăng ký KCB BHYT ban đầu', 'dung_dkbd' => true],
    '1.2'  => ['ten' => 'Đi KCB tại cơ sở KCB cấp ban đầu', 'muc_huong_co_dinh' => 100],
    '1.3'  => ['ten' => 'Đến KCB có phiếu chuyển cơ sở KCB', 'can_noi_di' => true],
    '1.4'  => ['ten' => 'KCB khi thay đổi nơi lưu trú, nơi cư trú'],
    '1.5'  => ['ten' => 'Đến KCB theo phiếu hẹn khám lại'],
    '1.6'  => ['ten' => 'Người đã hiến bộ phận cơ thể phải điều trị ngay sau khi hiến'],
    '1.7'  => ['ten' => 'Trẻ sơ sinh phải điều trị ngay sau khi sinh ra'],
    '1.11' => ['ten' => 'Tự đến KCB tại cơ sở KCB cấp ban đầu còn lại', 'tu_den' => true],
    '1.12' => ['ten' => 'Tự đến KCB ngoại trú tại cơ sở cấp cơ bản dưới 50 điểm', 'tu_den' => true],
    '1.13' => ['ten' => 'Tự đến KCB ngoại trú tại cơ sở cấp cơ bản 50-70 điểm', 'tu_den' => true,
               'muc_huong_theo_moc' => ['moc' => '2026-07-01', 'truoc_moc' => 0, 'tu_moc' => 50]],
    '1.14' => ['ten' => 'Tự đến KCB ngoại trú tại cơ sở cấp cơ bản trước đây là tuyến tỉnh hoặc trung ương', 'tu_den' => true,
               'muc_huong_theo_moc' => ['moc' => '2026-07-01', 'truoc_moc' => 0, 'tu_moc' => 50]],
    '1.15' => ['ten' => 'Tự đến KCB nội trú tại cơ sở KCB cấp cơ bản', 'tu_den' => true],
    '1.16' => ['ten' => 'Tự đến KCB tại cơ sở cấp cơ bản với bệnh thuộc Phụ lục II TT 01/2025', 'tu_den' => true],
    '1.17' => ['ten' => 'Tự đến KCB tại cơ sở cấp chuyên sâu với bệnh thuộc Phụ lục I TT 01/2025', 'tu_den' => true],
    '1.18' => ['ten' => 'Tự đến KCB ngoại trú tại cơ sở cấp chuyên sâu trước đây là tuyến tỉnh', 'tu_den' => true,
               'muc_huong_theo_moc' => ['moc' => '2026-07-01', 'truoc_moc' => 0, 'tu_moc' => 50]],
    '2'    => ['ten' => 'Cấp cứu'],
    '3.1'  => ['ten' => 'Tự đến KCB tại cơ sở cấp chuyên sâu trước đây là tuyến trung ương', 'tu_den' => true,
               'ngoai_tru_khong_huong' => true],
    '3.2'  => ['ten' => 'Tự đến KCB nội trú tại cơ sở cấp chuyên sâu trước đây là tuyến tỉnh', 'tu_den' => true],
    '3.3'  => ['ten' => 'Tự đến KCB tại cơ sở cấp cơ bản, cấp chuyên sâu trước đây là tuyến huyện', 'tu_den' => true],
    '3.6'  => ['ten' => 'Dân tộc thiểu số, hộ nghèo vùng khó khăn đến KCB nội trú tại cơ sở cấp chuyên sâu', 'tu_den' => true],
    '7'    => ['ten' => 'Lĩnh thuốc theo giấy hẹn trong dịch bệnh nhóm A hoặc bất khả kháng', 'linh_thuoc_khong_kham' => true],
    '7.2'  => ['ten' => 'Người bệnh uỷ quyền cho người khác đến lĩnh thuốc', 'linh_thuoc_khong_kham' => true],
    '7.3'  => ['ten' => 'Người bệnh lĩnh thuốc tại cơ sở KCB khác', 'linh_thuoc_khong_kham' => true],
    '7.4'  => ['ten' => 'Cơ sở KCB chuyển thuốc đến cho người bệnh', 'linh_thuoc_khong_kham' => true],
    '8'    => ['ten' => 'Thu hồi đề nghị thanh toán'],
    '9'    => ['ten' => 'Người bệnh không KCB BHYT', 'khong_bhyt' => true],
    '10'   => ['ten' => 'Đến lĩnh thuốc theo giấy hẹn (chỉ lĩnh thuốc, không khám bệnh)', 'linh_thuoc_khong_kham' => true],
];

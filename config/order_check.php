<?php

return [
    // Connection đọc HIS (chỉ SELECT)
    'his_connection' => 'HISPro',

    // Số phiếu chỉ định tối đa xử lý mỗi lần quét
    'batch_size' => 500,

    // Khoảng nghỉ giữa 2 lần quét khi chạy dạng service nssm (giây)
    'scan_sleep_interval' => (int) env('ORDER_CHECK_SCAN_SLEEP', 60),

    // Bỏ qua các loại điều trị không áp dụng (vd loại test), CSV id; rỗng = không loại
    'exclude_treatment_type_ids' => env('ORDER_CHECK_EXCLUDE_TREATMENT_TYPES', ''),

    // Doi tuong benh nhan duoc coi la BHYT, CSV id trong HIS_PATIENT_TYPE.
    // Mac dinh 1 = ma '01' BHYT. RONG = KHONG loc (hanh vi truoc 2026-07-28).
    //
    // LUU Y: loc phai o muc DONG DICH VU (his_sere_serv.patient_type_id), khong phai muc
    // ho so - do tren 7 ngay that thi hai cach lech 44.927 dong (30,17%), lon nhat la
    // 43.264 dong Vien phi nam trong ho so BHYT.
    'bhyt_patient_type_ids' => env('ORDER_CHECK_BHYT_PATIENT_TYPES', '1'),

    // Loại phiếu chỉ định KHÔNG áp luật A_MISSING_DIAGNOSIS (vd Khám=1: chẩn đoán có SAU khám).
    // CSV id loại phiếu (HIS_SERVICE_REQ_TYPE). Rỗng = áp cho mọi loại.
    'missing_diagnosis_exclude_type_ids' => env('ORDER_CHECK_MISSING_DIAG_EXCLUDE_TYPES', '1'),

    // Loai phieu KHONG ap luat kiem CCHN nguoi thuc hien, CSV id (HIS_SERVICE_REQ_TYPE).
    // Mac dinh 6 Don phong kham, 14 Don tu truc, 15 Don dieu tri: nguoi thuc hien cua cac
    // phieu nay la duoc si / dieu duong cap phat, khong phai nguoi can CCHN theo nghia cua
    // luat. RONG = khong loai tru loai nao.
    //
    // Ap cho ca B_DOCTOR_NO_PRACTICE_CERT lan nua "nguoi thuc hien" cua
    // A_STAFF_CERT_NOT_IN_CATALOG; nua "bac si chi dinh" van xet o moi loai phieu.
    'practice_cert_exclude_type_ids' => env('ORDER_CHECK_PRACTICE_CERT_EXCLUDE_TYPES', '6,14,15'),

    // ===== Thông báo email digest =====
    // Bật/tắt gửi email (mặc định TẮT cho an toàn, bật khi đã cấu hình người nhận)
    'notify_enabled' => (bool) env('ORDER_CHECK_NOTIFY_ENABLED', false),

    // Ngưỡng mức độ tối thiểu được thông báo: info | warning | critical
    'notify_min_severity' => env('ORDER_CHECK_NOTIFY_MIN_SEVERITY', 'warning'),

    // Khoảng nghỉ giữa 2 lần gửi digest khi chạy service nssm (giây). Mặc định 1 giờ.
    'notify_sleep_interval' => (int) env('ORDER_CHECK_NOTIFY_SLEEP', 3600),
];

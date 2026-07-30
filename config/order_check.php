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

    // Bo qua han cac LOAI PHIEU khong thuoc pham vi kiem tra y lenh, CSV id trong
    // HIS_SERVICE_REQ_TYPE; rong = khong loai.
    //
    // Mac dinh 11 Khac, 16 Don mau, 17 Suat an, 18 Ngoai kham chua benh.
    //
    // Loc tai NGUON (HisOrderSource::fetchServiceRequests) chu khong tai tung quy tac: dat
    // o mot cho thi khong the sot quy tac nao, va quy tac them sau nay cung tu dong duoc
    // loai tru.
    //
    // Do tren 7 ngay that truoc khi bat: Suat an 89.931 phieu ma chua tung sinh mot vi pham
    // nao, Khac 13.787, Don mau 1.824, Ngoai KCB 0 — tong 105.542/917.663 y lenh (11,5%).
    'exclude_service_req_type_ids' => env('ORDER_CHECK_EXCLUDE_SERVICE_REQ_TYPES', '11,16,17,18'),

    // Do rong cua so quet theo id. 0 nghia la KHONG chan (hanh vi cu).
    //
    // Laravel sinh SQL dang "select * from (select rownum rn, t1.* from (... order by id) t1)
    // where rn <= 500": truy van trong cung khong co gioi han nen Oracle sap xep MOI dong
    // sau moc roi moi cat. Do tren production, limit giu nguyen 500:
    //   ton    10.000 ->     68 ms      ton 1.000.000 ->  4.849 ms
    //   ton   100.000 ->    582 ms      ton 5.000.000 -> 21.356 ms
    // Chan tren lam thoi gian moi luot thanh hang so. 50.000 roi vao khoang ~300 ms.
    'scan_id_window' => (int) env('ORDER_CHECK_SCAN_ID_WINDOW', 50000),

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
    // CHI ap cho B_DOCTOR_NO_PRACTICE_CERT. A_STAFF_CERT_NOT_IN_CATALOG van xet ca hai vai
    // tro o moi loai phieu - nguoi dung chot ngay 2026-07-28.
    'practice_cert_exclude_type_ids' => env('ORDER_CHECK_PRACTICE_CERT_EXCLUDE_TYPES', '6,14,15'),

    // ===== Thông báo email digest =====
    // Bật/tắt gửi email (mặc định TẮT cho an toàn, bật khi đã cấu hình người nhận)
    'notify_enabled' => (bool) env('ORDER_CHECK_NOTIFY_ENABLED', false),

    // Ngưỡng mức độ tối thiểu được thông báo: info | warning | critical
    'notify_min_severity' => env('ORDER_CHECK_NOTIFY_MIN_SEVERITY', 'warning'),

    // Khoảng nghỉ giữa 2 lần gửi digest khi chạy service nssm (giây). Mặc định 1 giờ.
    'notify_sleep_interval' => (int) env('ORDER_CHECK_NOTIFY_SLEEP', 3600),
];

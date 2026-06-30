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

    // Loại phiếu chỉ định KHÔNG áp luật A_MISSING_DIAGNOSIS (vd Khám=1: chẩn đoán có SAU khám).
    // CSV id loại phiếu (HIS_SERVICE_REQ_TYPE). Rỗng = áp cho mọi loại.
    'missing_diagnosis_exclude_type_ids' => env('ORDER_CHECK_MISSING_DIAG_EXCLUDE_TYPES', '1'),

    // ===== Thông báo email digest =====
    // Bật/tắt gửi email (mặc định TẮT cho an toàn, bật khi đã cấu hình người nhận)
    'notify_enabled' => (bool) env('ORDER_CHECK_NOTIFY_ENABLED', false),

    // Ngưỡng mức độ tối thiểu được thông báo: info | warning | critical
    'notify_min_severity' => env('ORDER_CHECK_NOTIFY_MIN_SEVERITY', 'warning'),

    // Khoảng nghỉ giữa 2 lần gửi digest khi chạy service nssm (giây). Mặc định 1 giờ.
    'notify_sleep_interval' => (int) env('ORDER_CHECK_NOTIFY_SLEEP', 3600),
];

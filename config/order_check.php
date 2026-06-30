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
];

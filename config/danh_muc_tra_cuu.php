<?php

/**
 * So dang ky cac danh muc xem duoc o khu "Danh muc tra cuu".
 *
 * Day la NGUON SU THAT DUY NHAT cho ca man hinh lan menu: them mot muc vao day thi man
 * hinh, route va muc menu deu tu co, khong phai sua controller/view/route.
 *
 * Moi muc khai:
 *   ten     - ten hien thi tren menu va tieu de man hinh
 *   model   - lop Eloquent de truy van
 *   cot     - ten cot trong bang => nhan hien thi. CHI nhung cot khai o day duoc doc len,
 *             nen danh muc co cot nhay cam se khong bi lo vi nguoi khai quen giau.
 *   cot_tim - cac cot cho o tim kiem (phai nam trong 'cot')
 *   sap_xep - [ten cot, 'asc'|'desc']
 *
 * Khoa mang chinh la SLUG tren URL: /danh-muc-tra-cuu/<khoa>
 */
return [
    'dvkt_can_ma_may' => [
        'ten'     => 'DVKT cần mã máy',
        'model'   => App\Models\BHYT\DvktCanMaMay::class,
        'cot'     => [
            'ma_dvkt'   => 'Mã DVKT',
            'ten_dvkt'  => 'Tên DVKT',
            'is_active' => 'Đang dùng',
        ],
        'cot_tim' => ['ma_dvkt', 'ten_dvkt'],
        'sap_xep' => ['ma_dvkt', 'asc'],
    ],
];

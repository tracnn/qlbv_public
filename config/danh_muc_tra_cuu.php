<?php

/**
 * So dang ky cac danh muc xem duoc o khu "Danh muc tra cuu".
 *
 * Day la NGUON SU THAT DUY NHAT cho ca man hinh lan menu: them mot muc vao day thi man
 * hinh, route va muc menu deu tu co, khong phai sua controller/view/route.
 *
 * LUU Y KHI TRIEN KHAI: may chu chay `php artisan config:cache` thi phai chay
 * `php artisan config:clear` (hoac cache lai) sau khi sua tep nay, neu khong ca menu lan
 * route deu khong thay danh muc moi.
 *
 * Moi muc khai:
 *   ten          - ten hien thi tren menu va tieu de man hinh
 *   model        - lop Eloquent de truy van. Model KHONG duoc co $appends: accessor trong
 *                  $appends lot vao JSON bat ke select(), tuc lo cot khong khai. Co test
 *                  canh dieu nay.
 *   cot          - ten cot trong bang => nhan hien thi. Client chi duoc sap xep/tim kiem
 *                  tren dung cac cot nay (whitelist trong controller).
 *   cot_co_khong - (tuy chon) cac cot dang co/khong, hien thi thanh chu thay vi 0/1
 *   khoa_chinh   - (tuy chon) ten cot khoa chinh; mac dinh lay tu model
 *   sap_xep      - (tuy chon) [ten cot, 'asc'|'desc'], chi ap khi nguoi dung chua tu sap xep
 *
 * Khoa mang chinh la SLUG tren URL: /danh-muc-tra-cuu/<khoa>
 */
return [
    'dvkt_can_ma_may' => [
        'ten'          => 'DVKT cần mã máy',
        'model'        => App\Models\BHYT\DvktCanMaMay::class,
        'cot'          => [
            'ma_dvkt'   => 'Mã DVKT',
            'ten_dvkt'  => 'Tên DVKT',
            'is_active' => 'Đang dùng',
        ],
        'cot_co_khong' => ['is_active'],
        'sap_xep'      => ['ma_dvkt', 'asc'],
    ],

    'benh_pl1_cap_chuyen_sau' => [
        'ten'          => 'Bệnh PL1 TT01 cấp chuyên sâu',
        'model'        => App\Models\BHYT\BenhPl1CapChuyenSau::class,
        'cot'          => [
            'stt'       => 'STT',
            'ma_icd'    => 'Mã ICD',
            'loai'      => 'Loại',
            'tuoi_duoi' => 'Dưới tuổi',
            'ten_benh'  => 'Tên bệnh',
            'dieu_kien' => 'Điều kiện',
            'is_active' => 'Đang dùng',
        ],
        'cot_co_khong' => ['is_active'],
        'sap_xep'      => ['stt', 'asc'],
    ],

    'khoa_phong' => [
        'ten'          => 'DM Khoa phòng',
        'model'        => App\Models\BHYT\department::class,
        'khoa_chinh'   => 'ID',
        'cot'          => [
            'MA_KHOA'    => 'Mã khoa',
            'TEN_KHOA'   => 'Tên khoa',
            'ACTIVE'     => 'Kích hoạt',
            'created_at' => 'Ngày tạo',
            'updated_at' => 'Ngày sửa',
        ],
        'cot_co_khong' => ['ACTIVE'],
        'sap_xep'      => ['MA_KHOA', 'asc'],
    ],
];

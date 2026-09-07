<?php

/*
 * Tham so dich vu tra cuu tien cung chi tra (MCCT) tren cong BHXH.
 *
 * KHONG khai host o day. Host nam o organization.BHYT.base_url (tep rieng cua tung may,
 * trong .gitignore) va duoc ghep bang CongBhxh::url(). Khai URL day du o day nghia la mot
 * may doi $bhxhBaseUrl sang daotaoegw nhung rieng MCCT van goi cong THAT.
 */
return [
    // Hang so giao thuc do BHXH quy dinh, giong nhau o moi noi cai dat.
    'duong_dan' => '/api/TraCuuCCT/TraCuuTienMCCT',

    // Nguong mien cung chi tra = 6 thang luong co so (NĐ 188/2025/NĐ-CP).
    'so_thang_luong_co_so' => 6,

    /*
     * Luong co so theo MOC HIEU LUC, khong phai mot so.
     *
     * Khai mot so tran thi lan tang luong tiep theo se lang le tinh sai nguong cho toan bo
     * du lieu cu. Sap tang dan theo ngay.
     */
    'luong_co_so' => [
        '2023-07-01' => 1800000,
        '2024-07-01' => 2340000,
        '2026-07-01' => 2530000,
    ],

    /*
     * Timeout goi cong, tinh bang giay.
     *
     * timeout_tong nang tu 30 len 60 ngay 07/9/2026: cong that su tra ve "cURL error 28:
     * Operation timed out after 30006 ms with 0 bytes received" - tuc het 30 giay ma chua
     * nhan duoc byte nao, khong phai loi mang.
     *
     * TRAN CUNG la max_execution_time cua PHP (dang la 120 giay). Nang timeout_tong vuot qua
     * nua so do la tu chuoc lay rui ro: luong 401 co the goi cong HAI lan, va PHP se chet
     * giua chung voi mot loi khong noi duoc gi ve nguyen nhan that.
     *
     * Doi so o day thi javascript cua modal tu bam theo - xem TIMEOUT_MS trong
     * check-card/search.blade.php.
     */
    'timeout_ket_noi' => 15,
    'timeout_tong' => 60,
];

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
    ],

    'timeout_ket_noi' => 10,
    'timeout_tong' => 30,
];

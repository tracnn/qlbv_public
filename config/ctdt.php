<?php

return [
    // Hang doi rieng cho tung viec: ky so cham (USB token) khong duoc chan viec kiem loi.
    'queue_name'        => env('CTDT_QUEUE', 'JobCtdt'),
    'sign_queue_name'   => env('CTDT_SIGN_QUEUE', 'JobSignCtdt'),
    'submit_queue_name' => env('CTDT_SUBMIT_QUEUE', 'JobSubmitCtdt'),

    'import_enabled' => true,
    'sign_enabled'   => true,

    // MAC DINH TAT. Cong that cua BHXH nhan la nhan that; chi bat sau khi da chay thu
    // va doi chieu.
    'submit_enabled' => env('CTDT_SUBMIT_ENABLED', false),

    'import_path' => env('CTDT_IMPORT_PATH', 'D:\XML\ChungTuDienTu\inbox'),

    'token_url' => 'https://egw.baohiemxahoi.gov.vn/api/token/take',

    // Ba dich vu gui cua PL02. Khac nhau DUY NHAT o the goc, loai_hs va url.
    // loai_hs de kieu CHUOI: '39' khac 39 khi so sanh nghiem ngat trong ma.
    'dich_vu' => [
        'CT2025' => [
            'ten'     => 'Chứng từ TT25/2025',
            'the_goc' => 'HSCHUNGTU',
            'loai_hs' => '39',
            'url'     => 'https://egw.baohiemxahoi.gov.vn/api/chungtugw/GuiHoSoChungTu2025',
        ],
        'GBT' => [
            'ten'     => 'Giấy báo tử',
            'the_goc' => 'HSDLGBT',
            'loai_hs' => '60',
            'url'     => 'https://egw.baohiemxahoi.gov.vn/api/hososuckhoe/guiGiayToDienTu',
        ],
        'GCS' => [
            'ten'     => 'Giấy chứng sinh',
            'the_goc' => 'HSDLGCS',
            'loai_hs' => '61',
            'url'     => 'https://egw.baohiemxahoi.gov.vn/api/hososuckhoe/guiGiayToDienTu',
        ],
    ],

    // Bang ma ket qua muc 4 cua PL02, dung chung cho ca ba dich vu gui.
    'ma_ket_qua' => [
        '200'  => 'Thành công',
        '205'  => 'fileBase64Str không hợp lệ',
        '401'  => 'Lỗi xác thực tài khoản',
        '500'  => 'Lỗi server',
        '1001' => 'File size quá dài',
    ],

    // Ma ket qua lay token (muc I) - khac bang tren, dung rieng cho duong dang nhap.
    'ma_ket_qua_token' => [
        '200' => 'Lấy token thành công',
        '401' => 'Tài khoản không tồn tại',
        '402' => 'Mã cơ sở KCB không đúng',
        '403' => 'Tài khoản đã bị khóa',
        '500' => 'Lỗi hệ thống',
    ],
];

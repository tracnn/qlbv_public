<?php

/*
 * Hang so GIAO THUC cua Phu luc 02 - giong nhau o moi co so KCB, khong ai chinh khi
 * trien khai. URL, loai_hs va bang ma ket qua do BHXH quy dinh.
 *
 * Tham so THEO TUNG CO SO (bat/tat gui, duong dan thu muc nap, ten hang doi) KHONG nam
 * o day ma o config/organization.php khoa 'chung_tu_dien_tu' - dung cho ma nguon khong
 * phai la noi khai bao thu thay doi theo noi cai dat, va organization.php la tep .gitignore
 * rieng cua tung may.
 */
return [
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
    // CANH BAO KHOA MANG: PHP ep khoa mang dang chuoi so thanh int, vd '200' => int(200).
    // array_key_exists('200', ...) va $mang['200'] van chay dung (PHP tu ep chuoi truy
    // van thanh int), NHUNG foreach ($cfg as $ma => $mo) if ($ma === $maKetQuaTuCong) se
    // LUON trUOT vi 200 === '200' la false. Tra bang array_key_exists() hoac so sanh
    // long (==), tuyet doi KHONG dung === voi chuoi. Cung mot bay ma loai_hs o tren da
    // canh bao.
    'ma_ket_qua' => [
        '200'  => 'Thành công',
        '205'  => 'fileBase64Str không hợp lệ',
        '401'  => 'Lỗi xác thực tài khoản',
        '500'  => 'Lỗi server',
        '1001' => 'File size quá dài',
    ],

    // Ma ket qua lay token (muc I) - khac bang tren, dung rieng cho duong dang nhap.
    // CUNG BAY KHOA INT NHU 'ma_ket_qua' O TREN: tra bang array_key_exists()/so sanh
    // long, khong dung === voi chuoi.
    'ma_ket_qua_token' => [
        '200' => 'Lấy token thành công',
        '401' => 'Tài khoản không tồn tại',
        '402' => 'Mã cơ sở KCB không đúng',
        '403' => 'Tài khoản đã bị khóa',
        '500' => 'Lỗi hệ thống',
    ],

    // Danh muc ma loi cua bo kiem noi dung.
    //
    // VI SAO O CONFIG chu khong phai mot bang danh muc nhu xml3176_error_catalogs: ma loi
    // o day do TA tu dinh nghia tu dac ta PL02, khong phai do BHXH ban hanh va cap nhat
    // dinh ky. Mot bang danh muc chi co nghia khi co nguoi ngoai doi noi dung cua no.
    //
    // muc_do 'chan'    -> tinh vao ctdt_ho_so.so_loi, ho so khong duoc gui
    // muc_do 'canh_bao' -> hien cho nguoi doc, KHONG chan gui
    'ma_loi' => [
        'CTDT001' => ['mo_ta' => 'Thiếu trường bắt buộc',                    'muc_do' => 'chan'],
        'CTDT002' => ['mo_ta' => 'Trường ngày sai định dạng',                'muc_do' => 'chan'],
        'CTDT003' => ['mo_ta' => 'Giới tính ngoài giá trị cho phép',         'muc_do' => 'chan'],
        'CTDT004' => ['mo_ta' => 'Loại giấy tờ ngoài giá trị cho phép',      'muc_do' => 'chan'],
        'CTDT005' => ['mo_ta' => 'Trường cờ ngoài giá trị 0/1',              'muc_do' => 'canh_bao'],
        'CTDT006' => ['mo_ta' => 'Ngày kết thúc sớm hơn ngày bắt đầu',       'muc_do' => 'chan'],
        'CTDT007' => ['mo_ta' => 'Mã cơ sở trong chứng từ lệch với hồ sơ',   'muc_do' => 'chan'],
        'CTDT008' => ['mo_ta' => 'Thiếu mã thẻ BHYT',                        'muc_do' => 'canh_bao'],
    ],
];

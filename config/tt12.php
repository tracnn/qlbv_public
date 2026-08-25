<?php

/*
 * Hang so GIAO THUC cua TT12/2026/BTC - giong nhau o moi co so KCB, khong ai chinh khi
 * trien khai. URL, loai_hs va bang ma ket qua do BHXH quy dinh.
 *
 * Tham so THEO TUNG CO SO (bat/tat gui, ten hang doi) KHONG nam o day ma o
 * config/organization.php khoa 'tt12'.
 */
return [
    // Sau dich vu gui danh muc. Khac nhau DUY NHAT o loai_hs va url; ten the khai trong
    // lop Mau0x vi chung di lien voi dac ta cot.
    // loai_hs de kieu CHUOI: '10' khac 10 khi so sanh nghiem ngat trong ma.
    'mau' => [
        'MAU_01' => ['loai_hs' => '70', 'url' => 'https://egw.baohiemxahoi.gov.vn/api/DanhMucGW/GuiDanhMuc01_BPCMKBCB'],
        'MAU_02' => ['loai_hs' => '71', 'url' => 'https://egw.baohiemxahoi.gov.vn/api/DanhMucGW/GuiDanhMuc02_NLKCB'],
        'MAU_03' => ['loai_hs' => '10', 'url' => 'https://egw.baohiemxahoi.gov.vn/api/DanhMucGW/GuiDanhMuc03_DMTHUOC'],
        'MAU_04' => ['loai_hs' => '11', 'url' => 'https://egw.baohiemxahoi.gov.vn/api/DanhMucGW/GuiDanhMuc04_DMVTYT'],
        'MAU_05' => ['loai_hs' => '12', 'url' => 'https://egw.baohiemxahoi.gov.vn/api/DanhMucGW/GuiDanhMuc05_DVKT'],
        'MAU_06' => ['loai_hs' => '72', 'url' => 'https://egw.baohiemxahoi.gov.vn/api/DanhMucGW/GuiDanhMuc06_DMTBYT'],
    ],

    // TEN TRUONG BODY nam o day chu khong go cung trong ma. Tep PDF cua BHXH bi loi
    // tim-thay-the hang loat: cum "CSKCB" bi thay bang "Co so KCB" ngay ca ben trong ten
    // tham so, nen vi du curl viet 'maCo so KCB' con bang dac ta viet 'maCskcb'. Chua co
    // cach nao xac minh ngoai gui thu. De o day de doi duoc ma khong sua ma nguon.
    'truong_body' => [
        'username'   => 'username',
        'loai_hs'    => 'loaiHs',
        'ma_tinh'    => 'maTinh',
        'ma_cskcb'   => 'maCSKCB',
        'file_base64' => 'fileHsBase64',
    ],

    // Bang ma ket qua muc I.4 cua tai lieu, dung chung cho ca sau dich vu.
    // CANH BAO KHOA MANG: PHP ep khoa mang dang chuoi so thanh int, vd '200' => int(200).
    // array_key_exists('200', ...) van chay dung, NHUNG
    // foreach ($cfg as $ma => $mo) if ($ma === $maTuCong) se LUON truot vi 200 === '200'
    // la false. Tra bang array_key_exists() hoac so sanh long (==), tuyet doi KHONG ===.
    'ma_ket_qua' => [
        '200' => 'Tiếp nhận thành công',
        '123' => 'Lỗi nội dung file XML',
        '124' => 'Lỗi nội dung file XML',
        '125' => 'Lỗi nội dung file XML',
        '202' => 'Lỗi nội dung file XML',
        '204' => 'Lỗi nội dung file XML',
        '205' => 'Lỗi nội dung file XML',
        '401' => 'Mã cơ sở KCB chưa đúng, tài khoản hoặc token hết hạn, hoặc không có quyền',
        '402' => 'Mã cơ sở KCB chưa đúng, tài khoản hoặc token hết hạn, hoặc không có quyền',
        '403' => 'Mã cơ sở KCB chưa đúng, tài khoản hoặc token hết hạn, hoặc không có quyền',
        '500' => 'Lỗi hệ thống',
    ],

    // Ma coi la THANH CONG. De thanh mang de khong phai rai '200' khap noi.
    'ma_thanh_cong' => ['200'],
];

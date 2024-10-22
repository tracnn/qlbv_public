<?php

return [
    'correct_facility_code' => ['01013'], //Mã nơi đkbd đúng tuyến
    'exclude_department' => ['K02'], //Mã khoa không cần kiểm tra chỉ định giường trong Xml7450
    'BHYT' => [
        'username' => '',
        'password' => '',
        'login_url' => 'https://egw.baohiemxahoi.gov.vn/api/token/take',
        'check_card_url' => 'https://egw.baohiemxahoi.gov.vn/api/egw/NhanLichSuKCB2018', 
        'enableCheck' => false,
        'check_card_url_2024' => 'https://egw.baohiemxahoi.gov.vn/api/egw/KQNhanLichSuKCB2024',
        'hoTenCb' => '',
        'cccdCb' => '', 
        'check_by_user' => true,
        //https://egw.baohiemxahoi.gov.vn
    ],
    'base_url' => '',
];
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
    ],
    'base_url' => '',
    'base_pacs_url' => '',
    'pacs_url_suffix' => '', //''&service_id=',
    'organization_name' => 'Bệnh viện Đa khoa',
    'is_bieudo_dieutringoaitru' => true,
    'ftp_emr_config' => [
        'host' => '192.168.7.216',
        'port' => 21,
        'username' => 'emruser',
        'password' => 'ec5509674951b76d5dec601224f923ea',
        'ssl' => true,
        'ftp_pasv' => true,
    ],
    'xml_4750_not_check' => false,
    'q_his_plus_url' => '',
];
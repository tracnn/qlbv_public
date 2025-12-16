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
        'submit_xml_url' => 'https://egw.baohiemxahoi.gov.vn/api/qd130/guiHoSoXmlQD4750',
        'ma_tinh' => '01', // Mã tỉnh (2 ký tự đầu của mã CSKCB)
        'ma_cskcb' => '01013', // Mã cơ sở khám chữa bệnh (có thể lấy từ correct_facility_code)
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
    'patient' => [
        'emr_document_type_result_ids' => [6, 22, 160, 3, 28, 4, 14, 25, 26, 27],
    ],
    'api' => [
        'access_token' => '8f14e45fceea167a5a36dedd4bea2543',
        'token_name' => 'HIS-API-Token',
        'description' => 'Token for Ministry of Health API access',
        'created_at' => '2024-01-15',
        'expires_at' => null,
        'permissions' => ['read:all'],
        'rate_limit' => 60,
        'organization' => '01013'
    ],
    'export_xml_not_check' => false, //Không kiểm tra lỗi trước khi xuất xml
    'login_acs' => [
        'username' => 'tracnn',
        'password' => 'tracnn',
        'application_code' => 'HIS',
        'login_url' => 'http://192.168.7.200:1401/api/Token/Login',
    ],
    'xml_sign' => [
        'endpoint' => 'http://192.168.7.239:1415/api/EmrSign/SignXmlBhyt',
        'application_code' => 'EMR',
        'hsm_type' => '2',
        'hsm_user_code' => '35c891bdb41d4745b1e4b62a3af33ecf',
        'password' => '',
        'secret_key' => '',
        'identity_number' => '',
        'hsm_serial_number' => 'YBb0gYS0HBsUQFRD7SGHIbTV4ZXrbtkMxM/E524+',
        'tag_store_signature_value' => 'CHUKYDONVI',
        'enabled' => false,
    ],
];
<?php

// Host cong tiep nhan du lieu BHXH. Doi DUNG dong nay la chuyen ca he thong giua moi
// truong thu nghiem va chinh thuc: nam URL cua khoi BHYT duoi day ghep tu no, con ba
// dich vu CTDT (config/ctdt.php) va sau mau TT12 (config/tt12.php) ghep luc doc qua
// App\Services\BHYT\CongBhxh. Duong dan tung dich vu la hang so giao thuc do BHXH quy
// dinh nen chung nam trong kho ma; chi host la thu doi theo tung noi cai dat.
$bhxhBaseUrl = 'https://egw.baohiemxahoi.gov.vn';

return [
    'correct_facility_code' => [''], //Mã nơi đkbd đúng tuyến
    'exclude_department' => ['K02'], //Mã khoa không cần kiểm tra chỉ định giường trong Xml7450
    'BHYT' => [
        'username' => '',
        'password' => '',
        // BAT BUOC. Khong khai thi App\Services\BHYT\CongBhxh se NEM - co y the, thay vi
        // roi ve host that, de mot may thu nghiem quen khai khong gui ho so that len cong
        // that. Luu y day la organization.BHYT.base_url, KHONG phai khoa 'base_url' o cap
        // ngoai cung cuoi tep (do la dia chi web cua chinh ung dung, viec khac han).
        'base_url' => $bhxhBaseUrl,
        'login_url' => $bhxhBaseUrl . '/api/token/take',
        'check_card_url' => $bhxhBaseUrl . '/api/egw/NhanLichSuKCB2018',
        'enableCheck' => false, //Bật/tắt kiểm tra thẻ BHXH bởi cổng BHXH
        'check_card_url_2024' => $bhxhBaseUrl . '/api/egw/KQNhanLichSuKCB2024',
        'hoTenCb' => '',
        'cccdCb' => '',
        'check_by_user' => true, //Bật/tắt kiểm tra thẻ BHXH bởi user
        'submit_xml_enabled' => false, //Bật/tắt chức năng gửi XML lên cổng BHXH
        'submit_xml_url' => $bhxhBaseUrl . '/api/qd130/guiHoSoXmlQD4750',
        'loai_ho_so_4750' => '130',
        'ma_tinh' => '01', // Mã tỉnh (2 ký tự đầu của mã CSKCB)
        'ma_cskcb' => '', // Mã cơ sở khám chữa bệnh (có thể lấy từ correct_facility_code)
        'submit_xml_3176_enabled' => false, //Bật/tắt chức năng gửi XML lên cổng BHXH
        'submit_xml_3176_url' => $bhxhBaseUrl . '/api/qd130/guiHoSoXmlQD3176',
        'loai_ho_so_3176' => '130',
    ],
    'BHYT_CO_SO' => [
        '01013' => [
            'username' => '',
            'password' => '',
            'ho_ten_cb' => '',
            'cccd_cb' => '',
        ],
    ],
    // Danh muc TT12/2026/BTC (module tt12). Xem docs/superpowers/specs/2026-08-25-tt12-danh-muc-design.md.
    // Cung ly do tach nhu khoi chung_tu_dien_tu duoi day: day la tham so THEO TUNG CO SO,
    // con config/tt12.php giu hang so giao thuc TT12 (duong dan, loaiHs, ten the, ma ket qua).
    'tt12' => [
        // CA HAI MAC DINH TAT. Cong that cua BHXH nhan la nhan that, khong co duong rut lai.
        // Chi bat sau khi da ky thu mot ho so mot dong va doi chieu tay ket qua.
        'sign_enabled'   => false,
        'submit_enabled' => false,

        // Hang doi rieng cho ba job kiem/ky/gui cua TT12.
        'hang_doi' => 'tt12',

        // BA hang doi RIENG: ky so hong vi ly do CUC BO (rut USB token, HSM khong phan hoi)
        // con gui hong vi MANG. Gop chung thi mot lan mang chap keo theo ky lai - thao tac
        // ton thoi gian nhat trong chuoi, va mot ho so TT12 co the la hang nghin dong.
        // Ten phai KHOP dong --queue trong update.bat / install_service.bat.
        'hang_doi_ky'  => 'tt12-ky',
        'hang_doi_gui' => 'tt12-gui',
    ],

    // Chung tu dien tu BHXH theo Phu luc 02 (module ctdt). Xem docs/chung-tu-dien-tu-pl02.md.
    // Dat o day chu khong o config/ctdt.php: day la tham so THEO TUNG CO SO (bat/tat, duong
    // dan thu muc, ten hang doi), con config/ctdt.php chi giu hang so giao thuc PL02 dung
    // chung cho moi co so (URL, loai_hs, bang ma ket qua).
    'chung_tu_dien_tu' => [
        // MAC DINH TAT. Cong that cua BHXH nhan la nhan that, khong co duong rut lai.
        // Chi bat sau khi da chay thu va doi chieu tay mot ho so.
        'submit_enabled' => false,

        'import_enabled' => true,
        'sign_enabled'   => true,

        // Tran so TEP lenh ctdt:import xu ly moi luot. Khong co tran thi mot thu muc do nham
        // 3000 tep thanh 3000 lan POST that len cong trong MOT luot, khong ai kip dung lai.
        'import_gioi_han' => 200,

        // Lenh ctdt:import co duoc GUI len cong khong. TACH RIENG khoi submit_enabled: bat
        // submit_enabled la cho phep NGUOI bam nut gui; bat khoa nay la cho phep MAY gui khi
        // khong co ai nhin. Hai muc do tin cay khac nhau thi phai hai cong tac khac nhau.
        'import_tu_dong_gui' => false,

        // Ba hang doi RIENG: ky so hong vi ly do cuc bo (USB token bi rut, HSM khong phan
        // hoi) con gui hong vi mang. Gop chung thi mot lan mang chap keo theo ba lan ky lai
        // - thao tac ton thoi gian nhat trong chuoi.
        'queue_name'        => 'JobCtdt',
        'sign_queue_name'   => 'JobSignCtdt',
        'submit_queue_name' => 'JobSubmitCtdt',
    ],
    'base_url' => '',
    'base_pacs_url' => '',
    'pacs_url_suffix' => '', //''&service_id=',
    'organization_name' => 'Bệnh viện Đa khoa',
    'organization_logo' => env('ORGANIZATION_LOGO', ''), // URL/đường dẫn logo riêng của đơn vị; trống thì dùng images/logo.png
    'is_bieudo_dieutringoaitru' => true,
    'ftp_emr_config' => [
        'host' => '192.168.7.216',
        'port' => 21,
        'username' => 'emruser',
        'password' => 'ec5509674951b76d5dec601224f923ea',
        'ssl' => true,
        'ftp_pasv' => true,
    ],
    'xml_4750_not_check' => true, //Không kiểm tra lỗi trước khi xuất xml
    'q_his_plus_url' => '',
    'patient' => [
        'emr_document_type_result_ids' => [6, 22, 160, 3, 28, 4, 14, 25, 26, 27],
        'emr_document_group_ids' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 21, 22, 61],
    ],
    'api' => [
        'access_token' => '',
        'token_name' => 'HIS-API-Token',
        'description' => 'Token for Ministry of Health API access',
        'created_at' => '2024-01-15',
        'expires_at' => null,
        'permissions' => ['read:all'],
        'rate_limit' => 60,
        'organization' => '01013'
    ],
    'export_xml_not_check' => true,
    'login_acs' => [
        'username' => '',
        'password' => '',
        'application_code' => 'HIS',
        'login_url' => 'http://x.x.x.x:1401/api/Token/Login',
    ],
    'xml_sign' => [
        'endpoint' => 'http://x.x.x.x:1415/api/EmrSign/SignXmlBhyt',
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
    'usb_token_sign' => [
        'endpoint' => 'http://127.0.0.1:18081/api/EmrSign/SignXmlBhyt',
        'enabled' => false,
        'service_token' => '',
        'tag_store_signature_value' => 'CHUKYDONVI',
        'timeout' => 30,
    ],
    'e_invoice' => [
        'bkav' => [
            'enabled' => false,
            'api_url' => '', // URL API BKAV (ví dụ: https://api.bkav.com.vn)
            'partner_guid' => '', // Partner GUID từ BKAV
            'partner_token' => '', // Partner Token format: Base64(Key):Base64(IV)
        ],
    ],
    // Phien ban cap nhat 20260120.1
    'xml_3176_not_check' => true, //Không kiểm tra lỗi trước khi xuất xml
    'truc_du_lieu_y_te' => [
        'username' => '', // Tài khoản được Trục dữ liệu Y Tế cấp
        'password' => '', // Mật khẩu tương ứng
        'code' => '', // Mã đơn vị (maCSKCB)
        'loai_ho_so' => '3176', // Loại hồ sơ
        'ma_tinh' => 'HN', // Mã tỉnh
        'environment' => 'production', // sandbox hoặc production/poc
        // Môi trường thử nghiệm (Sandbox)
        'login_url_sandbox' => 'https://sbauth-soyt.hanoi.gov.vn/api/auth/token/take',
        'submit_xml_url_sandbox' => 'https://sbaxis-soyt.hanoi.gov.vn/api/kcb/xml/qd3176/guiHoSoXml',
        'check_status_url_sandbox' => 'https://sbaxis-soyt.hanoi.gov.vn/api/kcb/tra-cuu-trang-thai',
        // Môi trường chính thức (POC)
        'login_url_production' => 'https://auth-soyt.hanoi.gov.vn/api/auth/token/take',
        'submit_xml_url_production' => 'https://axis-soyt.hanoi.gov.vn/api/kcb/xml/qd3176/guiHoSoXml',
        'check_status_url_production' => 'https://axis-soyt.hanoi.gov.vn/api/kcb/tra-cuu-trang-thai',
        'enabled' => false, // Bật/tắt chức năng gửi dữ liệu lên Trục
        'disk' => 'trucDuLieuYTe', // Tên disk trong filesystems config
        'scan_sleep_interval' => 300, // Thời gian sleep giữa các lần quét (giây)
    ],
    'cong_du_lieu_y_te_dien_bien' => [
        'username' => '', // Tài khoản được Sở Y tế tỉnh Điện Biên cung cấp
        'password' => '', // Mật khẩu (sẽ được hash MD5 khi gửi)
        'login_url' => 'https://api.congdulieuytedienbien.vn/api/token',
        'submit_xml_url' => 'https://api.congdulieuytedienbien.vn/api/Cong130/CheckIn',
        'enabled' => false, // Bật/tắt chức năng gửi dữ liệu lên Cổng dữ liệu Y tế tỉnh Điện Biên
        'disk' => 'congDuLieuYTeDienBien', // Tên disk trong filesystems config
        'scan_sleep_interval' => 300, // Thời gian sleep giữa các lần quét (giây)
    ],
    'fss_config' => [
        'baseUrl' => 'http://x.x.x.x:1435',
    ],
    'patient_type_code_evenue' => [
        '01',
        '02'
    ],
    'dashboard' => [
        // patient_type_code KHÔNG tính vào thống kê KCB của dashboard Home (KSK đoàn/từ thiện...)
        'exclude_patient_type_codes' => [], // 03 = KSK
    ],

    /*
     * Module sinh hồ sơ XML3176 trực tiếp từ HIS (nguồn dự phòng thay HIS desktop).
     * Chi tiết: docs/trien-khai-sinh-xml3176.md. Thiếu cả khối này thì module tắt.
     *
     * Mẫu dưới đây để CHẠY THỬ: ghi_thu_muc_that = false nên chỉ ghi vào thu_muc_thu,
     * tiền giám định không nạp gì. Lần đầu bật, module chỉ đặt mốc = thời điểm hiện tại;
     * hồ sơ cũ lấy bằng: php artisan sinhxml3176:quet --ma-dieu-tri=<ma> (hoặc --tu-ngay/--den-ngay).
     *
     * Bộ lọc ghi bằng MÃ (không phải ID HIS); mã không có trong HIS thì lệnh báo lỗi và dừng.
     * Mảng rỗng = không lọc. Khi triển khai thật phải khai GIỐNG màn "Thiết lập đồng bộ"
     * của plugin ExportXmlQD130 trên máy trạm HIS.
     */
    'sinh_xml3176' => [
        'enabled'          => true,   // false = tắt module
        'ghi_thu_muc_that' => false,  // CHỈ nhận đúng true; Đợt 1 (XML1-3) lệnh từ chối ghi thư mục thật dù có bật
        'thong_tuyen'      => false,  // true = sinh thêm bản thông tuyến (đã ra viện, chưa có ngày thanh toán)
        'ket_noi_his'      => 'HISPro', // tên connection Oracle HIS trong config/database.php, không được rỗng
        'co_so'            => [],     // mã cơ sở HIS_BRANCH.HEIN_MEDI_ORG_CODE, ví dụ ['01929']
        'doi_tuong_bn'     => [],     // MÃ đối tượng bệnh nhân (HIS_PATIENT_TYPE.PATIENT_TYPE_CODE), ví dụ ['01'] = BHYT; không ghi tên 'BHYT'
        'doi_tuong_tt'     => [],     // MÃ đối tượng thanh toán của từng dòng dịch vụ, ví dụ ['01']
        'dien_dieu_tri'    => [],     // mã diện điều trị (HIS_TREATMENT_TYPE.TREATMENT_TYPE_CODE)
        'thu_muc'          => 'D:\XML\3176',            // thư mục tiền giám định quét (bản chính thức)
        'thu_muc_tt'       => 'D:\XML\3176TT',          // thư mục tiền giám định quét (bản thông tuyến)
        'thu_muc_thu'      => 'D:\XML\SinhXml3176Thu',  // thư mục thử; PHẢI nằm ngoài thu_muc và thu_muc_tt
        'thu_muc_luu'      => 'D:\XML\SinhXml3176LuuTru',  // bản sao XML mỗi lần sinh (màn Theo dõi sinh XML3176), mỗi ngày một thư mục con; null = không lưu; PHẢI nằm ngoài thu_muc, thu_muc_tt, thu_muc_thu; không tự xoá
        'thu_muc_tam'      => null,   // null = <cha của thư mục đích>\.sinh_xml3176_tmp; phải cùng ổ đĩa, ngoài cây quét
    ],
];
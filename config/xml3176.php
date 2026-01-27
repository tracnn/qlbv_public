<?php

return [
    'export_to_directory_by_day' => false, //false: xuất xml ra thư mục chung, true: xuất thư mục theo từng ngày
    'type_xml11_doc' => ['CT07'], //Loại mẫu của xml11
    'bed_group_code' => [14, 15, 16], //Nhóm dịch vụ giường
    'invalid_treatment_result' => [3, 4, 5, 6], //Loại kết quả dùng để kiểm tra ngày giường
    'invalid_end_type_treatment' => [2, 3, 4], //Loại ra viện dùng để kiểm tra ngày giường
    'treatment_type_inpatient' => ['03', '04', '09'], //Loại hồ sơ điều trị nội trú
    'treatment_type_outpatient' => ['01', '02'], //Loại hồ sơ khám + ngoại trú
    'max_weight_patient' => 200, //Cân nặng tối đa
    'drug_group_code' => [4, 5], //Mã nhóm là thuốc
    'blood_group_code' => [7], //Mã nhóm là máu
    'drug_group_not_check' => ['05V', '05C', 'HD'], //Nhóm thuốc (Thành phần đầu của mã thuốc) không cần kiểm tra
    'drug_code_not_check' => ['40.17', '40.573'], //Mã thuốc không cần kiểm tra (Oxy/NO)
    'material_group_code' => [10, 11], //Nhóm VTYT
    'examination_group_code' => [13], //Nhóm dịch vụ khám
    'transport_group_code' => [12], //Nhóm dịch vụ vận chuyển
    'bed_code_pattern' => '/^[HTCK]\d{3}$/', //Mẫu mã giường bắt buộc
    'excluded_material_group_code' => [11], //Mã nhóm VTYT ngoài danh mục hoặc VTYT thay thế
    'group_code_with_executor' => [1,2,3,8,18], //Mã nhóm bắt buộc phải có người thực hiện
    'service_groups_requiring_anesthesia' => [8], //Mã nhóm bắt buộc phải có phương pháp vô cẳm
    'anesthesia_code' => [1, 2, 3, 4], //Mã phương pháp vô cảm
    'hein_card_temp_pattern' => '/^TE1xx\d{10}$/', //Mẫu mã thẻ tạm "xx" được thay bằng mã tỉnh cư trú trong hàm check
    'hein_card_temp_prefix_pattern' => '^TE1', //Khai báo phần đầu mã thẻ tạm
    'hein_card_temp_num_pattern' => '\d{10}$', //Khai báo phần mã BHXH của mã thẻ tạm
    'queue_name' => 'JobXml3176', //Job name chung cho các job 3176 (check lỗi, complete, v.v.)
    'export_queue_name' => 'JobExportXml3176', //Job name riêng cho việc export XML 3176
    'submit_queue_name' => 'JobSubmitXml3176', //Job name riêng cho việc submit XML 3176 lên cổng BHXH (tránh blocking các job khác)
    'export_xml3176_enabled' => true, //Có xuất Xml theo 3176 tự động không (Chỉ những hồ sơ không có lỗi critical mới xuất)
    'treatment_end_type_absconding' => [3], //Bổ sung loại ra viện là trốn viện để không kiểm tra giấy ra viện
    'hein_card_invalid' => [ //Bổ sung mã kiểm tra thẻ được coi là lỗi
        'check_code' => ['01', '02', '03', '04', '05', '06', '07', '08', '09', '11'],
        'result_code' => ['003', '010', '050', '051', '052', '053', '060', 
        '061', '070', '110', '120', '121' ,'122', '123', '124', '130'],
    ],
    'prefix_hein_card_exclude_t_bhtt_gdv' => ['CA', 'CY', 'QN'], //Bổ sung không check completeXml đối với những thẻ này
    'xml4' => [
        'xml3_ma_nhom_require_ket_luan' => [2], //Bổ sung mã nhóm bắt buộc phải có kết luận
    ],
    'exportable_tt' => true, //Sau khi import từ thư mục xml thông tuyến thì có xuất Xml hay không (true: có xuất; false: không xuất)
    /* Bổ sung key 2024.08.23 */
    'xml1' => [
        'ma_doituong_kcb_trai_tuyen' => ['3'], //Bổ sung mã đối tượng khám bệnh chỉ định trái tuyến
        'the_bhyt_cbcs_pattern' => ['CA', 'QN', 'CY'], //Bổ sung mã thẻ BHYT CBCS
        'ma_loai_kcb_khong_tinh_ngay_dieu_tri' => ['01', '07', '09'], //Bổ sung mã loại khám bệnh không tính ngày điều trị
    ],
    'xml2' => [
        'tt_thau' => [ // Bổ sung quy tắc định dạng tt_thau
            'goi_thau_pattern' => '/^G([1-9]|[1-9][0-9])$/', // Gói thầu G1 đến G99
            'nhom_thau_pattern' => '/^N([1-9]|[1-9][0-9])$/', // Nhóm thầu N1 đến N99
            'nam_thau_pattern' => '/^\d{4}$/',   // Định dạng năm 4 ký tự
        ],
    ],
    'xml3' => [
        'tt_thau' => [ // Bổ sung quy tắc định dạng tt_thau
            'goi_thau_pattern' => '/^G([0-9]|[1-9][0-9])$/', // Gói thầu G0 đến G99
            'nhom_thau_pattern' => '/^N([0-9]|[1-9][0-9])$/', // Nhóm thầu N0 đến N99
            'nam_thau_pattern' => '/^\d{4}$/',   // Định dạng năm 4 ký tự
        ],
        'service_groups_requiring_machine' => [1,2,3], //Bổ sung mã nhóm bắt buộc phải có máy
        'service_groups_pttt' => [8,18], //Bổ sung mã nhóm là pttt
    ],
    'general' => [
        'check_valid_department_req' => true, //Kiểm tra tính hợp lệ của khoa chỉ định; Tuyến TW/Tỉnh => true
        'ma_khoa_kkb' => ['K01'], //Bổ sung mã khoa khám bệnh
    ],
];

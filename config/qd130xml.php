<?php

/*
 *  Cấu hình cho Xml theo QD130/4750 
 */

return [
    'export_to_directory_by_day' => false, //false: xuất xml ra thư mục chung, true: xuất thư mục theo từng ngày
    'type_xml11_doc' => ['CT07'], //Loại mẫu của xml11
    'correct_facility_code' => ['01013', '01061'], //Mã nơi đkbd đúng tuyến
    'bed_group_code' => [14, 15, 16], //Nhóm dịch vụ giường
    'invalid_treatment_result' => [3, 4, 5, 6], //Loại kết quả dùng để kiểm tra ngày giường
    'invalid_end_type_treatment' => [2, 3, 4], //Loại ra viện dùng để kiểm tra ngày giường
    'treatment_type_inpatient' => ['03', '04', '09'], //Loại hồ sơ điều trị nội trú
    'treatment_type_outpatient' => ['01', '02'], //Loại hồ sơ khám + ngoại trú
    'max_weight_patient' => 200, //Cân nặng tối đa
    'drug_group_code' => [4, 5], //Mã nhóm là thuốc
    'blood_group_code' => [7], //Mã nhóm là máu
    'exclude_department' => ['K0249', 'K26'], //Mã khoa không cần kiểm tra chỉ định giường
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
    'hein_card_temp_prefix_pattern' => '/^TE1', //Khai báo phần đầu mã thẻ tạm
    'hein_card_temp_num_pattern' => '\d{10}$/', //Khai báo phần mã BHXH của mã thẻ tạm
    'queue_name' => 'JobQd130Xml', //Job name sử dụng cho Qd130/4750 Xml
    'export_qd130_xml_enabled' => true, //Có xuất Xml theo Qd130 tự động không (Chỉ những hồ sơ không có lỗi critical mới xuất)
    'treatment_end_type_absconding' => [3], //Bổ sung loại ra viện là trốn viện để không kiểm tra giấy ra viện
    'hein_card_invalid' => [ //Bổ sung mã kiểm tra thẻ được coi là lỗi
        'check_code' => ['01', '02', '03', '04', '06', '07', '08'],
        'result_code' => ['003', '010', '050', '051', '052', '053', '060', 
        '061', '070', '110', '120', '121' ,'122', '123', '124', '130'],
    ],
    'prefix_hein_card_exclude_t_bhtt_gdv' => ['CA', 'CY', 'QN'], //Bổ sung không check completeXml đối với những thẻ này
    'xml4' => [
        'xml3_ma_nhom_require_ket_luan' => [2],
    ],
    'exportable_tt' => false, //Xuất xml thông tuyến (true: có xuất; false: không xuất)
    'xml1' => [
        'ma_doituong_kcb_trai_tuyen' => ['3'],
    ],
];
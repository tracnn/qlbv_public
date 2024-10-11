<?php

return [
    'general' => 'Liên hệ quản trị hệ thống',

    //XML1
    'XML1_SPECIAL_INPATIENT_ERROR_LY_DO_VNT' => 'UPDATE 
            his_treatment 
        SET 
            hospitalize_reason_name = hospitalization_reason 
        WHERE 
            his_treatment.treatment_code = \'{ma_dieu_tri}\'
            AND hospitalize_reason_name IS NULL
            AND tdl_treatment_type_id = 3;
        COMMIT;',

    //XML3
    'XML3_INFO_ERROR_MA_MAY_NOT_FOUND' => '1. Bổ sung máy trong danh mục HIS; 2. Đẩy cổng BHXH phê duyệt; 3. Tải danh mục trang thiết bị trên cổng import vào phần mềm GĐBHYT',
    'XML3_INFO_ERROR_MA_BAC_SI_NOT_FOUND' => '1. Kiểm tra danh mục NVYT trên HIS; 2. Bổ sung danh mục và đẩy cổng BHXH duyệt; 3. Cập nhật danh mục vào phần mềm GĐBHYT',

    //XML4
    'XML4_INFO_ERROR_KET_LUAN_EMPTY' => 'UPDATE 
            his_sere_serv_ext 
        SET 
            conclude = description 
        WHERE 
            conclude IS NULL 
        AND 
            description is not null
        AND 
            sere_serv_id IN 
            (SELECT 
                his_sere_serv.id 
            FROM 
                his_sere_serv
            JOIN 
                his_treatment ON his_treatment.id = his_sere_serv.tdl_treatment_id
            WHERE 
                his_sere_serv.tdl_hein_service_type_id = 1 
            AND 
                his_sere_serv.patient_type_id = 1
            AND 
                his_treatment.treatment_code =\'{ma_dieu_tri}\');
        COMMIT;',
];
<?php

namespace App\Services\GiaoBan;

/**
 * Dich ID danh muc HIS <-> ten hien thi cho form builder chi tieu giao ban.
 * Nhom nho: tai tron goi + cache. Nhom lon (remote): tim theo q, hoac tra nguoc theo ids.
 */
class GiaoBanCatalogService
{
    const CONN = 'HISPro';

    /** key => bang HIS, cot dinh danh, cot ten, co phai danh muc lon hay khong */
    const CATALOGS = [
        'service_type'   => ['table' => 'his_service_type',       'id_col' => 'id', 'name_col' => 'service_type_name',       'remote' => false, 'label' => 'Loại dịch vụ'],
        'diim_type'      => ['table' => 'his_diim_type',          'id_col' => 'id', 'name_col' => 'diim_type_name',          'remote' => false, 'label' => 'Loại CĐHA'],
        'test_type'      => ['table' => 'his_test_type',          'id_col' => 'id', 'name_col' => 'test_type_name',          'remote' => false, 'label' => 'Loại xét nghiệm'],
        'patient_type'   => ['table' => 'his_patient_type',       'id_col' => 'id', 'name_col' => 'patient_type_name',       'remote' => false, 'label' => 'Đối tượng BN'],
        'treatment_type' => ['table' => 'his_treatment_type',     'id_col' => 'id', 'name_col' => 'treatment_type_name',     'remote' => false, 'label' => 'Loại điều trị'],
        // end_type dinh danh bang CODE ('RV','CV'...) vi metric luu code chu khong luu id
        'end_type'       => ['table' => 'his_treatment_end_type', 'id_col' => 'treatment_end_type_code', 'name_col' => 'treatment_end_type_name', 'remote' => false, 'label' => 'Loại kết thúc'],
        'service'        => ['table' => 'his_service',            'id_col' => 'id', 'name_col' => 'service_name',            'remote' => true,  'label' => 'Dịch vụ'],
        // bang his_room khong co cot ten (chi co code phu tro nhu bhyt_code, g_code...);
        // "phong thuc hien" thuc te nam o his_execute_room (execute_room_code / execute_room_name)
        'room'           => ['table' => 'his_execute_room',       'id_col' => 'id', 'name_col' => 'execute_room_name',       'remote' => true,  'label' => 'Phòng thực hiện'],
        'bed'            => ['table' => 'his_bed',                'id_col' => 'id', 'name_col' => 'bed_name',                'remote' => true,  'label' => 'Giường'],
    ];

    public static function allKeys()
    {
        return array_keys(self::CATALOGS);
    }

    /** Cac danh muc tai tron goi khi mo modal. */
    public static function smallKeys()
    {
        $out = [];
        foreach (self::CATALOGS as $k => $c) {
            if (!$c['remote']) $out[] = $k;
        }
        return $out;
    }

    public static function isRemote($key)
    {
        return isset(self::CATALOGS[$key]) && self::CATALOGS[$key]['remote'];
    }
}

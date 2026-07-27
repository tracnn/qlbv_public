<?php

namespace App\Services\GiaoBan;

/**
 * Dich ID danh muc HIS <-> ten hien thi cho form builder chi tieu giao ban.
 * Nhom nho: tai tron goi + cache. Nhom lon (remote): tim theo q, hoac tra nguoc theo ids.
 */
class GiaoBanCatalogService
{
    // Dung cho phan truy van (task sau) khi mo connection Oracle HIS.
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
        // tdl_execute_room_id (GiaoBanMetricService::buildServiceCountSql) tro toi his_room.id,
        // nhung his_room khong co cot ten. View v_his_room ghep san ten phong + ten khoa
        // (RevenueDeptRoomService::buildRoomDetailSqlAndBindings da dung view nay cho dung muc dich).
        'room'           => ['table' => 'v_his_room',              'id_col' => 'id', 'name_col' => 'room_name',               'remote' => true,  'label' => 'Phòng thực hiện'],
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
            if (!$c['remote']) {
                $out[] = $k;
            }
        }
        return $out;
    }

    public static function isRemote($key)
    {
        return isset(self::CATALOGS[$key]) && self::CATALOGS[$key]['remote'];
    }

    /**
     * SQL lay tron mot danh muc nho. Ham thuan (test duoc khong can Oracle).
     * Tra ve cot chuan: ma, ten.
     */
    public static function buildSmallSql($key)
    {
        if (!isset(self::CATALOGS[$key])) {
            throw new \InvalidArgumentException("Danh muc khong ton tai: $key");
        }
        $c = self::CATALOGS[$key];
        $sql = "SELECT {$c['id_col']} AS ma, {$c['name_col']} AS ten
                FROM {$c['table']}
                WHERE is_delete = 0 AND is_active = 1
                ORDER BY {$c['name_col']}";
        return [$sql, []];
    }

    /** Toan bo danh muc nho, cache 60 PHUT (Laravel 5.5 nhan phut). */
    public static function allSmall()
    {
        return \Illuminate\Support\Facades\Cache::remember('giaoban.catalogs', 60, function () {
            $out = [];
            foreach (self::smallKeys() as $key) {
                $out[$key] = self::layDanhMuc($key);
            }
            return $out;
        });
    }

    /** Chay 1 truy van danh muc tren HISPro. HIS loi -> mang rong, khong lam trang trang cau hinh. */
    protected static function layDanhMuc($key)
    {
        list($sql, $binds) = self::buildSmallSql($key);
        try {
            $rows = \Illuminate\Support\Facades\DB::connection(self::CONN)->select($sql, $binds);
        } catch (\Exception $e) {
            return [];
        }
        return self::chuanHoa($rows);
    }

    /** Oracle tra cot HOA -> ve dang [['id' =>, 'name' =>], ...] */
    protected static function chuanHoa($rows)
    {
        $out = [];
        foreach ($rows as $r) {
            $row = array_change_key_case((array) $r, CASE_LOWER);
            $out[] = ['id' => $row['ma'], 'name' => $row['ten']];
        }
        return $out;
    }

    /** Nhan hien thi cua tung danh muc, cho form builder dat label. */
    public static function labels()
    {
        $out = [];
        foreach (self::CATALOGS as $k => $c) $out[$k] = $c['label'];
        return $out;
    }
}

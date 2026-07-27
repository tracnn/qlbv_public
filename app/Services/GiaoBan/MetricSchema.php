<?php

namespace App\Services\GiaoBan;

/**
 * Nguon su that duy nhat ve cau truc mot chi tieu giao ban.
 * Ba noi tieu thu: MetricValidator (chan payload sai), form builder JS (render field dong),
 * MetricSchemaTest (doi chieu voi switch trong GiaoBanMetricService::computeAll).
 *
 * Them loai chi tieu moi: sua o day, form + validate tu co.
 */
class MetricSchema
{
    const BLOCKS = ['dieu_tri', 'kham', 'can_lam_sang'];

    const TYPES = [
        'census_from' => [
            'label' => 'BN cũ (đầu kỳ)', 'blocks' => ['dieu_tri'], 'fields' => [], 'filter' => [],
        ],
        'census_to' => [
            'label' => 'Hiện có (cuối kỳ)', 'blocks' => ['dieu_tri'], 'fields' => [], 'filter' => [],
        ],
        'movement_in' => [
            'label' => 'BN vào thẳng', 'blocks' => ['dieu_tri'], 'fields' => [], 'filter' => [],
        ],
        'movement_transfer_in' => [
            'label' => 'BN chuyển đến', 'blocks' => ['dieu_tri'], 'fields' => [], 'filter' => [],
        ],
        'movement_transfer_out' => [
            'label' => 'BN chuyển khoa (đi)', 'blocks' => ['dieu_tri'], 'fields' => [], 'filter' => [],
        ],
        'end_type' => [
            'label' => 'Kết thúc điều trị', 'blocks' => ['dieu_tri'],
            'fields' => [
                'end_codes' => [
                    'widget' => 'catalog_multi', 'catalog' => 'end_type', 'value' => 'string',
                    'required' => true, 'label' => 'Loại kết thúc',
                ],
            ],
            'filter' => [],
        ],
        'bed_count' => [
            'label' => 'Đếm BN trên giường chỉ định', 'blocks' => ['dieu_tri'],
            'fields' => [
                'bed_ids' => [
                    'widget' => 'catalog_multi', 'catalog' => 'bed', 'value' => 'int',
                    'required' => true, 'label' => 'Giường',
                ],
            ],
            'filter' => [],
        ],
        'exam_visit' => [
            'label' => 'Lượt khám', 'blocks' => ['kham'], 'fields' => [],
            'filter' => [
                'treatment_type_ids' => ['widget' => 'catalog_multi', 'catalog' => 'treatment_type', 'value' => 'int', 'label' => 'Loại điều trị'],
                'patient_type_ids'   => ['widget' => 'catalog_multi', 'catalog' => 'patient_type', 'value' => 'int', 'label' => 'Đối tượng BN'],
                'end_type_codes'     => ['widget' => 'catalog_multi', 'catalog' => 'end_type', 'value' => 'string', 'label' => 'Loại kết thúc'],
            ],
        ],
        'service_count' => [
            'label' => 'Đếm dịch vụ', 'blocks' => ['can_lam_sang'], 'fields' => [],
            'scope' => 'service_dept', // widget pham vi khoa rieng, xem MetricValidator::SCOPE_*
            'filter' => [
                'service_type_ids' => ['widget' => 'catalog_multi', 'catalog' => 'service_type', 'value' => 'int', 'label' => 'Loại dịch vụ'],
                'diim_type_ids'    => ['widget' => 'catalog_multi', 'catalog' => 'diim_type', 'value' => 'int', 'label' => 'Loại CĐHA', 'other_key' => 'diim_type_other_of'],
                'test_type_ids'    => ['widget' => 'catalog_multi', 'catalog' => 'test_type', 'value' => 'int', 'label' => 'Loại xét nghiệm', 'other_key' => 'test_type_other_of'],
                'service_ids'      => ['widget' => 'catalog_multi', 'catalog' => 'service', 'value' => 'int', 'label' => 'Dịch vụ cụ thể'],
                'execute_room_ids' => ['widget' => 'catalog_multi', 'catalog' => 'room', 'value' => 'int', 'label' => 'Phòng thực hiện'],
                'priority_min'     => ['widget' => 'int', 'label' => 'Ưu tiên từ'],
                'priority_max'     => ['widget' => 'int', 'label' => 'Ưu tiên đến'],
            ],
        ],
        'admission' => [
            'label' => 'BN nhập viện nội trú (toàn viện)', 'blocks' => ['kham'], 'fields' => [], 'filter' => [],
        ],
        'manual' => [
            'label' => 'Nhập tay', 'blocks' => ['dieu_tri', 'kham', 'can_lam_sang'],
            'group' => 'input', // toan bo thuoc tinh nam trong khoa con "input"
            'fields' => [
                'unit'       => ['widget' => 'text', 'label' => 'Đơn vị', 'max' => 20],
                'hint'       => ['widget' => 'text', 'label' => 'Giải thích cho khoa', 'max' => 255],
                'value_type' => ['widget' => 'select', 'label' => 'Kiểu giá trị', 'options' => ['int', 'decimal', 'percent'], 'default' => 'int'],
                'min'        => ['widget' => 'number', 'label' => 'Nhỏ nhất'],
                'max'        => ['widget' => 'number', 'label' => 'Lớn nhất'],
                'required'   => ['widget' => 'bool', 'label' => 'Bắt buộc nhập'],
                'default'    => ['widget' => 'number', 'label' => 'Giá trị mặc định'],
                'carry_over' => ['widget' => 'bool', 'label' => 'Kế thừa từ phiên trước'],
            ],
            'filter' => [],
        ],
    ];

    public static function typeKeys()
    {
        return array_keys(self::TYPES);
    }

    public static function has($type)
    {
        return isset(self::TYPES[$type]);
    }

    public static function get($type)
    {
        return isset(self::TYPES[$type]) ? self::TYPES[$type] : null;
    }

    /** Cac type dung duoc voi mot block_type. */
    public static function forBlock($blockType)
    {
        $out = [];
        foreach (self::TYPES as $k => $def) {
            if (in_array($blockType, $def['blocks'], true)) $out[] = $k;
        }
        return $out;
    }
}

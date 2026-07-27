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

    /** Cac type khong can khoa HIS de tinh duoc. */
    const KHONG_CAN_KHOA = ['admission', 'bed_count', 'manual'];

    /**
     * Canh bao cho mot chi tieu khi tinh thu.
     * Phan chieu guard $hasScope trong GiaoBanMetricService::computeAll (dong 412-431).
     * Neu logic guard ben do doi, sua ham nay theo.
     *
     * @return null|string 'manual' | 'no_dept' | 'no_scope'
     */
    public static function warningFor(array $metric, array $deptIds)
    {
        $type = isset($metric['type']) ? $metric['type'] : '';
        if ($type === 'manual') return 'manual';

        if ($type === 'service_count') {
            $f = isset($metric['filter']) ? $metric['filter'] : [];
            if (!empty($deptIds)) return null; // computeAll tu gan pham vi theo khoa cua config

            // computeAll ghi de execute_department_ids = $deptIds khi bat co self.
            // $deptIds rong -> khoa nay coi nhu rong, chi 5 khoa con lai moi cuu duoc pham vi.
            $khoaConLai = empty($f['execute_department_id_self'])
                ? ['execute_department_ids', 'execute_department_id', 'request_department_ids',
                   'request_department_id', 'execute_room_ids', 'service_ids']
                : ['execute_department_id', 'request_department_ids',
                   'request_department_id', 'execute_room_ids', 'service_ids'];

            foreach ($khoaConLai as $k) {
                if (!empty($f[$k])) return null;
            }
            return 'no_scope';
        }

        if (in_array($type, self::KHONG_CAN_KHOA, true)) return null;

        return empty($deptIds) ? 'no_dept' : null;
    }

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

    /**
     * Kiem gia tri nhap tay theo khai bao input cua chi tieu.
     * Chi ap dung cho type manual; chi tieu tu dong khong rang buoc.
     * @return null|string null = hop le, nguoc lai la thong bao loi
     */
    public static function kiemGiaTriNhapTay($metric, $value)
    {
        if (!is_array($metric) || !isset($metric['type']) || $metric['type'] !== 'manual') return null;
        if ($value === null || $value === '') return null;   // xoa o
        if (!is_numeric($value)) return 'Giá trị phải là số.';

        $in = isset($metric['input']) && is_array($metric['input']) ? $metric['input'] : [];
        $v = (float) $value;
        $kieu = isset($in['value_type']) ? $in['value_type'] : 'int';

        if ($kieu === 'int' && floor($v) != $v) {
            return 'Chỉ tiêu này chỉ nhận số nguyên.';
        }
        if (in_array($kieu, ['decimal', 'percent'], true) && round($v, 2) != $v) {
            return 'Chỉ tiêu này tối đa 2 chữ số thập phân.';
        }
        if ($kieu === 'percent' && ($v < 0 || $v > 100)) {
            return 'Giá trị phần trăm phải trong khoảng 0–100.';
        }
        if (isset($in['min']) && is_numeric($in['min']) && $v < (float) $in['min']) {
            return 'Giá trị nhỏ hơn mức tối thiểu (' . $in['min'] . ').';
        }
        if (isset($in['max']) && is_numeric($in['max']) && $v > (float) $in['max']) {
            return 'Giá trị lớn hơn mức tối đa (' . $in['max'] . ').';
        }
        return null;
    }
}

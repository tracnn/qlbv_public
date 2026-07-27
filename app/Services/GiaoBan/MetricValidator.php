<?php

namespace App\Services\GiaoBan;

/**
 * Kiem tra mang metrics theo MetricSchema. Ham thuan, khong cham DB.
 * Tra ve [['index' => vi tri chi tieu, 'field' => ten o, 'message' => ...]].
 * index = -1 la loi cap toan danh sach.
 */
class MetricValidator
{
    const CODE_PATTERN = '/^[a-z][a-z0-9_]{0,31}$/';

    /** Cac khoa chi dinh pham vi khoa cu the cua service_count (ngoai execute_department_id_self). */
    const SCOPE_KEYS = [
        'execute_department_id', 'execute_department_ids',
        'request_department_id', 'request_department_ids',
        'execute_room_ids', 'service_ids',
    ];

    public static function validateJson($jsonString, $blockType)
    {
        $data = json_decode($jsonString, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [self::loi(-1, 'metrics', 'Chỉ tiêu không phải JSON hợp lệ.')];
        }
        if (!is_array($data)) {
            return [self::loi(-1, 'metrics', 'Chỉ tiêu phải là một mảng.')];
        }
        return self::validate($data, $blockType);
    }

    public static function validate(array $metrics, $blockType)
    {
        $loi = [];

        if (empty($metrics)) {
            return [self::loi(-1, 'metrics', 'Phải có ít nhất một chỉ tiêu.')];
        }
        if (array_keys($metrics) !== range(0, count($metrics) - 1)) {
            return [self::loi(-1, 'metrics', 'Chỉ tiêu phải là mảng tuần tự, không phải object.')];
        }

        $typeHopLe = MetricSchema::forBlock($blockType);
        $daThayCode = [];

        foreach ($metrics as $i => $m) {
            if (!is_array($m)) {
                $loi[] = self::loi($i, 'metrics', 'Chỉ tiêu phải là object.');
                continue;
            }

            $code = isset($m['code']) ? (string) $m['code'] : '';
            if (!preg_match(self::CODE_PATTERN, $code)) {
                $loi[] = self::loi($i, 'code', 'Mã chỉ tiêu phải bắt đầu bằng chữ thường, chỉ gồm a-z 0-9 _, tối đa 32 ký tự.');
            } elseif (isset($daThayCode[$code])) {
                $loi[] = self::loi($i, 'code', "Mã chỉ tiêu '$code' trùng với chỉ tiêu thứ " . ($daThayCode[$code] + 1) . '.');
            } else {
                $daThayCode[$code] = $i;
            }

            $name = isset($m['name']) ? trim((string) $m['name']) : '';
            if ($name === '') {
                $loi[] = self::loi($i, 'name', 'Tên hiển thị không được để trống.');
            } elseif (mb_strlen($name) > 255) {
                $loi[] = self::loi($i, 'name', 'Tên hiển thị tối đa 255 ký tự.');
            }

            $type = isset($m['type']) ? (string) $m['type'] : '';
            if (!MetricSchema::has($type)) {
                $loi[] = self::loi($i, 'type', "Loại chỉ tiêu '$type' không tồn tại.");
                continue; // khong biet type thi khong kiem tiep duoc
            }
            if (!in_array($type, $typeHopLe, true)) {
                $loi[] = self::loi($i, 'type', "Loại '$type' không dùng được cho khối '$blockType'.");
                continue;
            }

            $def = MetricSchema::get($type);

            if ($type === 'manual') {
                $loi = array_merge($loi, self::kiemNhapTay($i, isset($m['input']) ? $m['input'] : [], $def['fields']));
            } else {
                $loi = array_merge($loi, self::kiemFields($i, $m, $def['fields']));
                $loi = array_merge($loi, self::kiemFilter($i, isset($m['filter']) ? $m['filter'] : [], $def));
            }
        }

        return $loi;
    }

    /** Field nam thang trong chi tieu: end_codes, bed_ids. */
    protected static function kiemFields($i, array $m, array $fields)
    {
        $loi = [];
        foreach ($fields as $ten => $meta) {
            $coGiaTri = isset($m[$ten]) && is_array($m[$ten]) && count($m[$ten]) > 0;
            if (!empty($meta['required']) && !$coGiaTri) {
                $loi[] = self::loi($i, $ten, "Phải chọn ít nhất một giá trị cho '{$meta['label']}'.");
                continue;
            }
            if ($coGiaTri) {
                $loi = array_merge($loi, self::kiemKieuMang($i, $ten, $m[$ten], $meta));
            }
        }
        return $loi;
    }

    protected static function kiemFilter($i, $filter, array $def)
    {
        $loi = [];
        if (!is_array($filter) || empty($filter)) return $loi;

        $whitelist = [];
        foreach ($def['filter'] as $ten => $meta) {
            $whitelist[$ten] = $meta;
            if (isset($meta['other_key'])) $whitelist[$meta['other_key']] = $meta;
        }
        $laServiceCount = isset($def['scope']) && $def['scope'] === 'service_dept';
        if ($laServiceCount) {
            foreach (array_merge(['execute_department_id_self'], self::SCOPE_KEYS) as $k) {
                if (!isset($whitelist[$k])) {
                    $whitelist[$k] = ['widget' => 'catalog_multi', 'value' => 'int', 'label' => 'Phạm vi khoa'];
                }
            }
        }

        foreach ($filter as $ten => $giaTri) {
            if (!isset($whitelist[$ten])) {
                $loi[] = self::loi($i, 'filter.' . $ten, "Khoá lọc '$ten' không dùng được cho loại này.");
                continue;
            }
            $meta = $whitelist[$ten];

            if ($ten === 'execute_department_id_self') {
                if (!is_bool($giaTri)) {
                    $loi[] = self::loi($i, 'filter.' . $ten, 'Phạm vi "khoa này thực hiện" phải là true/false.');
                }
                continue;
            }
            if (in_array($ten, ['priority_min', 'priority_max', 'execute_department_id', 'request_department_id'], true)) {
                if (!is_numeric($giaTri)) {
                    $loi[] = self::loi($i, 'filter.' . $ten, "'$ten' phải là số.");
                }
                continue;
            }
            if (!is_array($giaTri) || count($giaTri) === 0) {
                $loi[] = self::loi($i, 'filter.' . $ten, "'{$meta['label']}' phải là mảng không rỗng.");
                continue;
            }
            $loi = array_merge($loi, self::kiemKieuMang($i, 'filter.' . $ten, $giaTri, $meta));
        }

        // khong duoc vua khai ids cu the vua danh dau nhom "Khac"
        foreach ($def['filter'] as $ten => $meta) {
            if (!isset($meta['other_key'])) continue;
            if (!empty($filter[$ten]) && !empty($filter[$meta['other_key']])) {
                $loi[] = self::loi($i, 'filter.' . $ten,
                    "Không được vừa chọn '{$meta['label']}' cụ thể vừa đánh dấu nhóm Khác. Chọn một trong hai.");
            }
        }

        // pham vi khoa: "khoa nay thuc hien" va "chi dinh cu the" loai tru nhau
        if ($laServiceCount && !empty($filter['execute_department_id_self'])) {
            foreach (self::SCOPE_KEYS as $k) {
                if (!empty($filter[$k])) {
                    $loi[] = self::loi($i, 'filter.execute_department_id_self',
                        'Đã chọn phạm vi "khoa này thực hiện" thì không khai thêm khoa/phòng/dịch vụ cụ thể.');
                    break;
                }
            }
        }

        return $loi;
    }

    protected static function kiemKieuMang($i, $field, $giaTri, array $meta)
    {
        if (!is_array($giaTri)) {
            return [self::loi($i, $field, 'Giá trị phải là mảng.')];
        }
        $kieu = isset($meta['value']) ? $meta['value'] : 'int';
        foreach ($giaTri as $v) {
            if ($kieu === 'int' && !is_numeric($v)) {
                return [self::loi($i, $field, 'Mọi giá trị phải là số.')];
            }
            if ($kieu === 'string' && (!is_string($v) || trim($v) === '')) {
                return [self::loi($i, $field, 'Mọi giá trị phải là chuỗi không rỗng.')];
            }
        }
        return [];
    }

    protected static function kiemNhapTay($i, $input, array $fields)
    {
        $loi = [];
        if (!is_array($input)) {
            return [self::loi($i, 'input', 'Khai báo ô nhập tay phải là object.')];
        }

        foreach ($input as $ten => $v) {
            if (!isset($fields[$ten])) {
                $loi[] = self::loi($i, 'input.' . $ten, "Thuộc tính '$ten' không dùng được cho chỉ tiêu nhập tay.");
            }
        }

        $valueType = isset($input['value_type']) ? $input['value_type'] : 'int';
        if (!in_array($valueType, $fields['value_type']['options'], true)) {
            $loi[] = self::loi($i, 'input.value_type', 'Kiểu giá trị chỉ nhận: int, decimal, percent.');
            $valueType = 'int';
        }

        if (isset($input['unit']) && mb_strlen((string) $input['unit']) > 20) {
            $loi[] = self::loi($i, 'input.unit', 'Đơn vị tối đa 20 ký tự.');
        }
        if (isset($input['hint']) && mb_strlen((string) $input['hint']) > 255) {
            $loi[] = self::loi($i, 'input.hint', 'Giải thích tối đa 255 ký tự.');
        }
        foreach (['required', 'carry_over'] as $ten) {
            if (isset($input[$ten]) && !is_bool($input[$ten])) {
                $loi[] = self::loi($i, 'input.' . $ten, "'$ten' phải là true/false.");
            }
        }

        $min = isset($input['min']) && $input['min'] !== '' ? $input['min'] : null;
        $max = isset($input['max']) && $input['max'] !== '' ? $input['max'] : null;
        $mac = isset($input['default']) && $input['default'] !== '' ? $input['default'] : null;

        foreach (['min' => $min, 'max' => $max, 'default' => $mac] as $ten => $v) {
            if ($v !== null && !is_numeric($v)) {
                $loi[] = self::loi($i, 'input.' . $ten, "'$ten' phải là số.");
            }
        }
        if (is_numeric($min) && is_numeric($max) && $min > $max) {
            $loi[] = self::loi($i, 'input.min', 'Giá trị nhỏ nhất không được lớn hơn giá trị lớn nhất.');
        }
        if (is_numeric($mac)) {
            if (is_numeric($min) && $mac < $min) {
                $loi[] = self::loi($i, 'input.default', 'Giá trị mặc định nhỏ hơn giá trị nhỏ nhất.');
            }
            if (is_numeric($max) && $mac > $max) {
                $loi[] = self::loi($i, 'input.default', 'Giá trị mặc định lớn hơn giá trị lớn nhất.');
            }
        }
        if ($valueType === 'percent') {
            if (is_numeric($min) && $min < 0) $loi[] = self::loi($i, 'input.min', 'Kiểu phần trăm giới hạn 0–100.');
            if (is_numeric($max) && $max > 100) $loi[] = self::loi($i, 'input.max', 'Kiểu phần trăm giới hạn 0–100.');
        }

        return $loi;
    }

    protected static function loi($index, $field, $message)
    {
        return ['index' => $index, 'field' => $field, 'message' => $message];
    }

    /** Body cua phan hoi 422, de form builder to do dung card. */
    public static function toResponsePayload(array $loi)
    {
        $dau = $loi[0];
        $viTri = $dau['index'] === -1 ? '' : (' (chỉ tiêu thứ ' . ($dau['index'] + 1) . ')');
        return ['message' => $dau['message'] . $viTri, 'errors' => $loi];
    }
}

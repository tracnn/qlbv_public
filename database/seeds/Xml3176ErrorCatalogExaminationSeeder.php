<?php

use Illuminate\Database\Seeder;
use App\Models\BHYT\Xml3176ErrorCatalog;

/**
 * Nạp 2 error_code công khám ngoại trú (TT39/2024/TT-BYT).
 * critical_error = true: đây là các khoản BHXH THỰC XUẤT TOÁN (khác lỗi 440 cảnh báo).
 * Idempotent (updateOrCreate) — chạy lại an toàn.
 */
class Xml3176ErrorCatalogExaminationSeeder extends Seeder
{
    public function run()
    {
        $rules = [
            [
                'XMLComplete_DUPLICATE_EXAMINATION_SERVICE',
                'Trùng dịch vụ khám bệnh',
                'Một dịch vụ khám bệnh được tính nhiều hơn 1 lần trong cùng hồ sơ',
            ],
            [
                'XMLComplete_EXAMINATION_FEE_EXCEEDS_CAP',
                'Tiền khám vượt trần 2 lần mức giá một lần khám',
                'Tổng tiền khám vượt quá 2 lần mức giá của 1 lần khám bệnh (TT39/2024/TT-BYT)',
            ],
        ];

        foreach ($rules as $r) {
            list($code, $name, $desc) = $r;
            Xml3176ErrorCatalog::updateOrCreate(
                ['xml' => 'XMLComplete', 'error_code' => $code],
                [
                    'error_name'     => $name,
                    'description'    => $desc,
                    'critical_error' => true,
                    'is_check'       => true,
                ]
            );
        }
    }
}

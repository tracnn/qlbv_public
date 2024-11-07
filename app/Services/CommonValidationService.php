<?php

namespace App\Services;

use App\Models\BHYT\MedicalStaff;

class CommonValidationService
{
    /**
     * Kiểm tra tính hợp lệ của MedicalStaff dựa trên trường và giá trị.
     *
     * @param mixed $value
     * @param string $field
     * @return bool
     */
    public function isMedicalStaffValid($value, $field = 'macchn')
    {
        return MedicalStaff::where($field, $value)->exists();
    }

    // Thêm các phương thức kiểm tra khác ở đây
}
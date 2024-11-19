<?php

namespace App\Services;

use App\Models\BHYT\MedicalStaff;
use App\Models\BHYT\MedicalOrganization;
use App\Models\BHYT\Icd10Category;
use App\Models\BHYT\IcdYhctCategory;
use App\Models\BHYT\AdministrativeUnit;
use App\Models\BHYT\JobCategory;

class CommonValidationService
{
    public function isMedicalStaffValid($value)
    {
        return MedicalStaff::where('macchn', $value)
        ->orWhere('ma_bhxh', $value)
        ->exists();
    }

    public function isMedicalOrganizationValid($value)
    {
        return MedicalOrganization::where('ma_cskcb', $value)
        ->exists();
    }

    public function isIcd10CategoryValid($value)
    {
        return Icd10Category::where('icd_code', $value)
        ->where('is_active', true)
        ->exists();
    }

    public function isIcdYhctCategoryValid($value)
    {
        return IcdYhctCategory::where('icd_code', $value)
        ->where('is_active', true)
        ->exists();
    }

    public function isIcdYhctCategoryValue($value)
    {
        return IcdYhctCategory::where('icd_code', $value)
        ->where('is_active', true)
        ->first();
    }

    public function isAdministrativeUnitProvinceValid($value)
    {
        return AdministrativeUnit::where('province_code', $value)
        ->where('is_active', true)
        ->exists();
    }

    public function isAdministrativeUnitDistrictValid($value)
    {
        return AdministrativeUnit::where('district_code', $value)
        ->where('is_active', true)
        ->exists();
    }

    public function isAdministrativeUnitCommuneValid($value)
    {
        return AdministrativeUnit::where('commune_code', $value)
        ->where('is_active', true)
        ->exists();
    }

    public function isAdministrativeUnitDistrictInProvinceValid($province_code, $district_code)
    {
        return AdministrativeUnit::where('province_code', $province_code)
        ->where('district_code', $district_code)
        ->where('is_active', true)
        ->exists();
    }

    public function isAdministrativeUnitWardInDistrictValid($district_code, $commune_code)
    {
        return AdministrativeUnit::where('district_code', $district_code)
        ->where('commune_code', $commune_code)
        ->where('is_active', true)
        ->exists();
    }

    public function isJobCategoryValid($value)
    {
        return JobCategory::where('job_code', $value)
        ->exists();
    }

    // Thêm các phương thức kiểm tra khác ở đây
}
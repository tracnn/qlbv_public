<?php

namespace App\Services;

use App\Models\BHYT\MedicalStaff;
use App\Models\BHYT\MedicalOrganization;
use App\Models\BHYT\Icd10Category;
use App\Models\BHYT\IcdYhctCategory;
use App\Models\BHYT\AdministrativeUnit;
use App\Models\BHYT\JobCategory;
use App\Models\BHYT\DvktCanMaMay;
use App\Models\BHYT\BenhPl1CapChuyenSau;
use App\Services\Xml3176\Support\MaDvktMatcher;

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

    /**
     * Xa co thuoc tinh khong. Sau khi bo cap huyen day la quan he long nhau duy nhat
     * con lai giua hai cap.
     */
    public function isAdministrativeUnitWardInProvinceValid($province_code, $commune_code)
    {
        return AdministrativeUnit::where('province_code', $province_code)
        ->where('commune_code', $commune_code)
        ->where('is_active', true)
        ->exists();
    }

    /**
     * Danh muc DVKT can ma may da duoc nap chua (co dong nao dang dung khong).
     *
     * Quy tac ma may dung ket qua nay de quyet dinh co lui ve cach loc theo nhom cu
     * hay khong - danh muc rong ma van doi theo danh muc thi khong ho so nao bi bao
     * thieu ma may nua, tuc mat sach canh bao ma khong ai biet.
     */
    public function coDanhMucDvktCanMaMay()
    {
        return DvktCanMaMay::where('is_active', true)->exists();
    }

    /**
     * Cac dong dang dung cua danh muc benh Phu luc I Thong tu 01/2025/TT-BYT.
     *
     * KHONG luu dem: queue worker song lau, dem se giu danh muc cu sau khi nguoi dung nap lai.
     * Chi ho so ma doi tuong 1.17 moi goi ham nay nen mot truy van moi ho so la khong dang ke.
     *
     * @return array cac dong ['stt' => int, 'ma_icd' => string, 'loai' => string, 'tuoi_duoi' => int|null]
     */
    public function danhMucBenhPl1()
    {
        return BenhPl1CapChuyenSau::where('is_active', true)
            ->get(['stt', 'ma_icd', 'loai', 'tuoi_duoi'])
            ->map(function ($d) {
                return [
                    'stt'       => (int) $d->stt,
                    'ma_icd'    => (string) $d->ma_icd,
                    'loai'      => (string) $d->loai,
                    'tuoi_duoi' => $d->tuoi_duoi === null ? null : (int) $d->tuoi_duoi,
                ];
            })
            ->all();
    }

    /**
     * DVKT nay co bat buoc phai gui kem ma may khong.
     *
     * Thu ca ma khai lan ma goc: co so dat hau to sau dau gach duoi de phan biet bien
     * the (02.0261.0319_TB) trong khi danh muc chi liet ke ma goc.
     */
    public function isDvktCanMaMay($maDvkt)
    {
        $ma = trim((string) $maDvkt);

        if ($ma === '') {
            return false;
        }

        $goc = MaDvktMatcher::maGoc($ma);
        $ung = $goc !== '' && $goc !== $ma ? [$ma, $goc] : [$ma];

        return DvktCanMaMay::whereIn('ma_dvkt', $ung)
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
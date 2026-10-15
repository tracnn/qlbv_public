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
use App\Models\BHYT\EquipmentCatalog;
use App\Services\Xml3176\Support\MaDvktMatcher;
use App\Services\Xml3176\Xml3176BoDem;

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
        return Xml3176BoDem::nho('cvs.cskcb', self::khoa($value), function () use ($value) {
            return MedicalOrganization::where('ma_cskcb', $value)
            ->exists();
        });
    }

    public function isIcd10CategoryValid($value)
    {
        return Xml3176BoDem::nho('cvs.icd10', self::khoa($value), function () use ($value) {
            return Icd10Category::where('icd_code', $value)
            ->where('is_active', true)
            ->exists();
        });
    }

    public function isIcdYhctCategoryValid($value)
    {
        return Xml3176BoDem::nho('cvs.icdyhct', self::khoa($value), function () use ($value) {
            return IcdYhctCategory::where('icd_code', $value)
            ->where('is_active', true)
            ->exists();
        });
    }

    public function isIcdYhctCategoryValue($value)
    {
        return Xml3176BoDem::nho('cvs.icdyhct_dong', self::khoa($value), function () use ($value) {
            return IcdYhctCategory::where('icd_code', $value)
            ->where('is_active', true)
            ->first();
        });
    }

    public function isAdministrativeUnitProvinceValid($value)
    {
        return Xml3176BoDem::nho('cvs.hc_tinh', self::khoa($value), function () use ($value) {
            return AdministrativeUnit::where('province_code', $value)
            ->where('is_active', true)
            ->exists();
        });
    }

    public function isAdministrativeUnitDistrictValid($value)
    {
        return Xml3176BoDem::nho('cvs.hc_huyen', self::khoa($value), function () use ($value) {
            return AdministrativeUnit::where('district_code', $value)
            ->where('is_active', true)
            ->exists();
        });
    }

    public function isAdministrativeUnitCommuneValid($value)
    {
        return Xml3176BoDem::nho('cvs.hc_xa', self::khoa($value), function () use ($value) {
            return AdministrativeUnit::where('commune_code', $value)
            ->where('is_active', true)
            ->exists();
        });
    }

    public function isAdministrativeUnitDistrictInProvinceValid($province_code, $district_code)
    {
        return Xml3176BoDem::nho('cvs.hc_huyen_trong_tinh', self::khoa($province_code, $district_code), function () use ($province_code, $district_code) {
            return AdministrativeUnit::where('province_code', $province_code)
            ->where('district_code', $district_code)
            ->where('is_active', true)
            ->exists();
        });
    }

    public function isAdministrativeUnitWardInDistrictValid($district_code, $commune_code)
    {
        return Xml3176BoDem::nho('cvs.hc_xa_trong_huyen', self::khoa($district_code, $commune_code), function () use ($district_code, $commune_code) {
            return AdministrativeUnit::where('district_code', $district_code)
            ->where('commune_code', $commune_code)
            ->where('is_active', true)
            ->exists();
        });
    }

    /**
     * Xa co thuoc tinh khong. Sau khi bo cap huyen day la quan he long nhau duy nhat
     * con lai giua hai cap.
     */
    public function isAdministrativeUnitWardInProvinceValid($province_code, $commune_code)
    {
        return Xml3176BoDem::nho('cvs.hc_xa_trong_tinh', self::khoa($province_code, $commune_code), function () use ($province_code, $commune_code) {
            return AdministrativeUnit::where('province_code', $province_code)
            ->where('commune_code', $commune_code)
            ->where('is_active', true)
            ->exists();
        });
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
        return Xml3176BoDem::nho('cvs.dvkt_can_ma_may_co', self::khoa(0), function () {
            return DvktCanMaMay::where('is_active', true)->exists();
        });
    }

    /**
     * Cac dong dang dung cua danh muc benh Phu luc I Thong tu 01/2025/TT-BYT.
     *
     * Luu dem tinh 600 giay (Xml3176BoDem): worker song lau nen sau khi nguoi dung nap lai
     * danh muc, toi da 10 phut sau worker moi doc ban moi (hoac xoa dem bang Xml3176BoDem::xoaTatCa).
     *
     * @return array cac dong ['stt' => int, 'ma_icd' => string, 'loai' => string, 'tuoi_duoi' => int|null]
     */
    public function danhMucBenhPl1()
    {
        return Xml3176BoDem::nho('cvs.benh_pl1', 'all', function () {
            return $this->docDanhMucBenhPl1();
        });
    }

    private function docDanhMucBenhPl1()
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

        return Xml3176BoDem::nho('cvs.dvkt_can_ma_may', self::khoa($maDvkt), function () use ($ung) {
            return DvktCanMaMay::whereIn('ma_dvkt', $ung)
            ->where('is_active', true)
            ->exists();
        });
    }

    public function isJobCategoryValid($value)
    {
        return Xml3176BoDem::nho('cvs.nghe', self::khoa($value), function () use ($value) {
            return JobCategory::where('job_code', $value)
            ->exists();
        });
    }

    /** Ma may co trong danh muc trang thiet bi hay khong (ma da duoc xu ly truoc khi goi). */
    public function isEquipmentMaMayValid($maMay)
    {
        return Xml3176BoDem::nho('cvs.ma_may', self::khoa($maMay), function () use ($maMay) {
            return EquipmentCatalog::where('ma_may', $maMay)->exists();
        });
    }

    /** Khoa bo dem gom moi doi so nguyen van (gom ca kieu null/chuoi) de khong tra nham. */
    public static function khoa(...$doiSo)
    {
        return serialize($doiSo);
    }

    // Thêm các phương thức kiểm tra khác ở đây
}
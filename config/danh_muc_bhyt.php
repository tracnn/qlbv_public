<?php

/**
 * So dang ky 11 bo danh muc BHYT — nguon DUY NHAT cho cau hoi "11 bo la nhung bo nao".
 *
 * Truoc day thong tin nay nam rai o ba noi: config/catalog_import_mapping.php (11 khoa),
 * menu trong config/adminlte.php (8 muc), va CategoryBHYTController (8 cap method).
 *
 * Khoa phai TRUNG khoa cua catalog_import_mapping.
 *
 * theo_co_so: danh muc co tach theo co so KCB khong. CHI dung voi medicine,
 * medical_supply, service. Luu y bang medical_organizations CUNG co cot ma_cskcb nhung
 * do la KHOA CUA CHINH DANH MUC (ma cua tung co so trong danh sach), khong phai cot
 * phan tach — nen KHONG duoc suy ra co nay tu su ton tai cua cot ma_cskcb.
 */

return [
    'medicine' => [
        'ten' => 'DM thuốc BHYT',
        'model' => App\Models\BHYT\MedicineCatalog::class,
        'bang' => 'medicine_catalogs',
        'theo_co_so' => true,
    ],
    'medical_supply' => [
        'ten' => 'DM Vật tư y tế',
        'model' => App\Models\BHYT\MedicalSupplyCatalog::class,
        'bang' => 'medical_supply_catalogs',
        'theo_co_so' => true,
    ],
    'service' => [
        'ten' => 'DM Dịch vụ kỹ thuật',
        'model' => App\Models\BHYT\ServiceCatalog::class,
        'bang' => 'service_catalogs',
        'theo_co_so' => true,
    ],
    'icd10' => [
        'ten' => 'DM ICD-10',
        'model' => App\Models\BHYT\Icd10Category::class,
        'bang' => 'icd10_categories',
        'theo_co_so' => false,
    ],
    'icd_yhct' => [
        'ten' => 'DM ICD-YHCT',
        'model' => App\Models\BHYT\IcdYhctCategory::class,
        'bang' => 'icd_yhct_categories',
        'theo_co_so' => false,
    ],
    'medical_staff' => [
        'ten' => 'DM Nhân viên y tế',
        'model' => App\Models\BHYT\MedicalStaff::class,
        'bang' => 'medical_staffs',
        'theo_co_so' => false,
    ],
    'department_bed' => [
        'ten' => 'DM Khoa Phòng Giường',
        'model' => App\Models\BHYT\DepartmentBedCatalog::class,
        'bang' => 'department_bed_catalogs',
        'theo_co_so' => true,
    ],
    'equipment' => [
        'ten' => 'DM Trang thiết bị',
        'model' => App\Models\BHYT\EquipmentCatalog::class,
        'bang' => 'equipment_catalogs',
        'theo_co_so' => false,
    ],
    'administrative_unit' => [
        'ten' => 'DM Đơn vị hành chính',
        'model' => App\Models\BHYT\AdministrativeUnit::class,
        'bang' => 'administrative_units',
        'theo_co_so' => false,
    ],
    'medical_organization' => [
        'ten' => 'DM Cơ sở KCB',
        'model' => App\Models\BHYT\MedicalOrganization::class,
        'bang' => 'medical_organizations',
        'theo_co_so' => false,
    ],
    'job_categories' => [
        'ten' => 'DM Nghề nghiệp',
        'model' => App\Models\BHYT\JobCategory::class,
        'bang' => 'job_categories',
        'theo_co_so' => false,
    ],
];

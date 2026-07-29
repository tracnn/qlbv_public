<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use App\Imports\CatalogChunkImport;
use App\Services\Import\GhiTheoLo;
use App\Services\Import\KetQuaNhapDanhMuc;
use App\Models\BHYT\MedicineCatalog;
use App\Models\BHYT\MedicalSupplyCatalog;
use App\Models\BHYT\ServiceCatalog;
use App\Models\BHYT\MedicalStaff;
use App\Models\BHYT\DepartmentBedCatalog;
use App\Models\BHYT\EquipmentCatalog;
use App\Models\BHYT\AdministrativeUnit;
use App\Models\BHYT\MedicalOrganization;
use App\Models\BHYT\JobCategory;
use App\Models\BHYT\Icd10Category;
use App\Models\BHYT\IcdYhctCategory;

class CatalogImportService
{
    protected $columnMapper;
    protected $catalogConfigs;

    /** @var KetQuaNhapDanhMuc dat lai moi lan import() */
    protected $ketQua;

    public function __construct(ExcelColumnMapper $columnMapper)
    {
        $this->columnMapper = $columnMapper;
        $this->catalogConfigs = config('catalog_import_mapping');
    }

    /** Ba danh muc do BHXH cap RIENG cho tung co so kham chua benh */
    const DANH_MUC_THEO_CO_SO = ['medicine', 'medical_supply', 'service'];

    /**
     * Gan ma co so cho khoa duy nhat khi dong khong tu khai.
     *
     * Ham thuan de kiem duoc. Gia tri trong TEP luon thang: mot tep co the chua nhieu co
     * so, con o chon tren man nhap chi la mac dinh cho dong bo trong.
     */
    public static function ganCoSo(array $uniqueKeys, $maCskcb)
    {
        $maCskcb = trim((string) $maCskcb);

        if ($maCskcb === '') {
            return $uniqueKeys;
        }

        if (!isset($uniqueKeys['ma_cskcb']) || trim((string) $uniqueKeys['ma_cskcb']) === '') {
            $uniqueKeys['ma_cskcb'] = $maCskcb;
        }

        return $uniqueKeys;
    }

    /**
     * @param string $filePath
     * @param string|null $maCskcb Ma CSKCB chon tren man nhap; chi ap cho ba danh muc
     *                             theo co so, va chi cho dong khong tu khai MA_CSKCB.
     */
    public function import($filePath, $maCskcb = null)
    {
        $this->ketQua = new KetQuaNhapDanhMuc();

        $tt = ['type' => null, 'mapping' => null, 'header' => null, 'ghi' => null,
               'lo' => [], 'gom' => null];

        // Doc THEO LO: Excel::toCollection nap toan bo tep, do duoc tep 1,3 MB (10.000 dong
        // x 23 cot) lam DINH bo nho 208 MB.
        $imp = new CatalogChunkImport(function ($rows, $dongDau, $laLoDau) use (&$tt, $maCskcb) {
            if ($laLoDau) {
                $this->nhanDienTuLoDau($rows, $tt);
                $rows = $rows->slice(1);
                $dongDau = 2;
            }

            if ($tt['type'] === null) {
                return;
            }

            if (in_array($tt['type'], self::DANH_MUC_THEO_CO_SO, true)) {
                $this->xuLyLoTheoCoSo($rows, $dongDau, $tt, $maCskcb);

                return;
            }

            // Tam loai con lai giu duong cu: gom lai roi goi ham nhap tuong ung o cuoi.
            if ($tt['gom'] === null) {
                $tt['gom'] = collect();
            }

            foreach ($rows as $r) {
                $tt['gom']->push($r);
            }
        });

        Excel::import($imp, $filePath);

        if ($tt['type'] === null) {
            throw new \Exception('File không chứa dữ liệu');
        }

        if (in_array($tt['type'], self::DANH_MUC_THEO_CO_SO, true)) {
            $tt['ghi']->ghi($tt['lo']);   // lo cuoi con du

            return $this->ketQua;
        }

        // Dung lai collection co dong tieu de o dau vi cac ham cu deu slice(1).
        $data = collect([$tt['header']])->merge($tt['gom'] ?: collect());
        $catalogType = $tt['type'];
        $fieldMapping = $tt['mapping'];


        // Gọi method import tương ứng
        $methodMap = [
            'medicine' => 'importMedicine',
            'medical_supply' => 'importMedicalSupply',
            'service' => 'importService',
            'medical_staff' => 'importMedicalStaff',
            'department_bed' => 'importDepartmentBed',
            'equipment' => 'importEquipment',
            'administrative_unit' => 'importAdministrativeUnit',
            'medical_organization' => 'importMedicalOrganization',
            'job_categories' => 'importJobCategories',
            'icd10' => 'importIcd10',
            'icd_yhct' => 'importIcdYhct',
        ];

        $methodName = $methodMap[$catalogType] ?? null;

        if (!$methodName || !method_exists($this, $methodName)) {
            throw new \Exception('Không tìm thấy method import cho loại catalog: ' . $catalogType);
        }

        $this->$methodName($data, $fieldMapping, $this->catalogConfigs[$catalogType]);

        return $this->ketQua;
    }

    /** Nhan dien loai danh muc va dung anh xa cot tu LO DAU (dong tieu de chi co o day). */
    protected function nhanDienTuLoDau($rows, array &$tt)
    {
        if ($rows->isEmpty()) {
            return;
        }

        $tt['header'] = $rows->first();
        $headerRow = $rows->first()->values()->toArray();

        $tt['type'] = $this->columnMapper->detectCatalogType($headerRow, $this->catalogConfigs);

        if (!$tt['type']) {
            throw new \Exception('Không thể xác định loại danh mục. Vui lòng kiểm tra lại cấu trúc file.');
        }

        $cfg = $this->catalogConfigs[$tt['type']];
        $tt['mapping'] = $this->columnMapper->createFieldMapping($headerRow, $cfg['mapping']);

        $batBuoc = isset($cfg['required_fields']) ? $cfg['required_fields'] : [];
        $thieu = array_diff($batBuoc, array_keys($tt['mapping']));

        if (!empty($thieu)) {
            throw new \Exception(
                'Thiếu các cột bắt buộc: ' . implode(', ', $thieu) .
                '. Vui lòng kiểm tra lại file Excel.'
            );
        }

        if (in_array($tt['type'], self::DANH_MUC_THEO_CO_SO, true)) {
            $tt['ghi'] = new GhiTheoLo($this->bangCua($tt['type']), $cfg['unique_keys'], $this->ketQua);
        }
    }

    /** Ten bang cua ba danh muc theo co so */
    protected function bangCua($type)
    {
        $map = [
            'medicine' => 'medicine_catalogs',
            'medical_supply' => 'medical_supply_catalogs',
            'service' => 'service_catalogs',
        ];

        return $map[$type];
    }

    /**
     * Xu ly mot lo cua ba danh muc theo co so: dung du lieu roi gom vao lo ghi.
     *
     * Doi Collection sang mang MOT LAN moi dong: getRowValue cu goi toArray() moi truong
     * moi dong - cham 20 lan o doan do.
     */
    protected function xuLyLoTheoCoSo($rows, $dongDau, array &$tt, $maCskcb)
    {
        $cfg = $this->catalogConfigs[$tt['type']];
        $i = 0;

        foreach ($rows as $row) {
            $dongExcel = $dongDau + $i;
            $i++;

            $mang = $row instanceof \Illuminate\Support\Collection ? $row->toArray() : (array) $row;

            if (!$this->hasRequiredFields($mang, $cfg['required_fields'], $tt['mapping'])) {
                $this->ketQua->themBoQua($dongExcel, 'Thiếu trường bắt buộc');
                continue;
            }

            $duLieu = [];

            foreach ($cfg['mapping'] as $field => $x) {
                $v = $this->getRowValue($mang, $field, $tt['mapping']);

                if ($v !== null) {
                    $duLieu[$field] = $v;
                }
            }

            if ($tt['type'] === 'service') {
                // Giu nguyen hanh vi cu cua importService.
                $duLieu['cskcb_cgkt'] = null;
                $duLieu['cskcb_cls'] = null;
            }

            $duLieu = self::ganCoSo($duLieu, $maCskcb);
            $tt['lo'][] = ['dong_excel' => $dongExcel, 'du_lieu' => $duLieu];

            if (count($tt['lo']) >= 500) {
                $tt['ghi']->ghi($tt['lo']);
                $tt['lo'] = [];
            }
        }
    }

    /** Dem mot ban ghi vua updateOrCreate vao ket qua */
    protected function demGhi($banGhi)
    {
        if ($banGhi && $banGhi->wasRecentlyCreated) {
            $this->ketQua->themNhap();

            return;
        }

        $this->ketQua->themCapNhat();
    }

    /**
     * Lấy giá trị từ row dựa trên field mapping
     *
     * @param array|\Illuminate\Support\Collection $row
     * @param string $field
     * @param array $fieldMapping
     * @return mixed|null
     */
    private function getRowValue($row, string $field, array $fieldMapping)
    {
        if (!isset($fieldMapping[$field])) {
            return null;
        }

        $index = $fieldMapping[$field];
        
        // Convert collection to array if needed
        if ($row instanceof \Illuminate\Support\Collection) {
            $row = $row->toArray();
        }
        
        return $row[$index] ?? null;
    }

    /**
     * Kiểm tra các trường bắt buộc có giá trị không
     *
     * @param array|\Illuminate\Support\Collection $row
     * @param array $requiredFields
     * @param array $fieldMapping
     * @return bool
     */
    private function hasRequiredFields($row, array $requiredFields, array $fieldMapping): bool
    {
        foreach ($requiredFields as $field) {
            $value = $this->getRowValue($row, $field, $fieldMapping);
            if (empty($value)) {
                return false;
            }
        }
        return true;
    }

    private function importMedicine($data, array $fieldMapping, array $config, $maCskcb = null)
    {
        $data = $data->slice(1); // Bỏ qua dòng đầu tiên
        
        foreach ($data as $row) {
            // Kiểm tra các trường bắt buộc
            if (!$this->hasRequiredFields($row, $config['required_fields'], $fieldMapping)) {
                $this->ketQua->themBoQua(0, 'Thiếu trường bắt buộc');
                continue;
            }

            try {
                $uniqueKeys = [];
                $updateData = [];
                
                foreach ($config['unique_keys'] as $key) {
                    $value = $this->getRowValue($row, $key, $fieldMapping);
                    if ($value !== null) {
                        $uniqueKeys[$key] = $value;
                    }
                }

                foreach ($config['mapping'] as $field => $possibleNames) {
                    if (!in_array($field, $config['unique_keys'])) {
                        $value = $this->getRowValue($row, $field, $fieldMapping);
                        if ($value !== null) {
                            $updateData[$field] = $value;
                        }
                    }
                }

                $uniqueKeys = self::ganCoSo($uniqueKeys, $maCskcb);

                $banGhi = MedicineCatalog::updateOrCreate($uniqueKeys, $updateData);
                $this->demGhi($banGhi);
            } catch (\Exception $e) {
                $this->ketQua->themLoi(0, $e->getMessage());
                Log::error('Error updating or creating MedicineCatalog record', [
                    'error' => $e->getMessage(),
                    'row' => $row
                ]);
                continue;
            }
        }
    }

    private function importMedicalSupply($data, array $fieldMapping, array $config, $maCskcb = null)
    {
        $data = $data->slice(1);
        
        foreach ($data as $row) {
            if (!$this->hasRequiredFields($row, $config['required_fields'], $fieldMapping)) {
                $this->ketQua->themBoQua(0, 'Thiếu trường bắt buộc');
                continue;
            }

            try {
                $uniqueKeys = [];
                $updateData = [];
                
                foreach ($config['unique_keys'] as $key) {
                    $value = $this->getRowValue($row, $key, $fieldMapping);
                    if ($value !== null) {
                        $uniqueKeys[$key] = $value;
                    }
                }

                foreach ($config['mapping'] as $field => $possibleNames) {
                    if (!in_array($field, $config['unique_keys'])) {
                        $value = $this->getRowValue($row, $field, $fieldMapping);
                        if ($value !== null && $value !== '') {
                            $updateData[$field] = $value;
                        }
                    }
                }

                $uniqueKeys = self::ganCoSo($uniqueKeys, $maCskcb);

                $banGhi = MedicalSupplyCatalog::updateOrCreate($uniqueKeys, $updateData);
                $this->demGhi($banGhi);
            } catch (\Exception $e) {
                $this->ketQua->themLoi(0, $e->getMessage());
                Log::error('Error updating or creating MedicalSupplyCatalog record', [
                    'error' => $e->getMessage(),
                    'row' => $row
                ]);
                continue;
            }
        }
    }

    private function importService($data, array $fieldMapping, array $config, $maCskcb = null)
    {
        $data = $data->slice(1);
        
        foreach ($data as $row) {
            // Loại bỏ ký tự đặc biệt trong cột 'Tên dịch vụ'
            $tenDichVuIndex = $fieldMapping['ten_dich_vu'] ?? null;
            if ($tenDichVuIndex !== null && isset($row[$tenDichVuIndex])) {
                $row[$tenDichVuIndex] = preg_replace('/[^\p{L}\p{N}\s]/u', '', $row[$tenDichVuIndex]);
            }

            if (!$this->hasRequiredFields($row, $config['required_fields'], $fieldMapping)) {
                $this->ketQua->themBoQua(0, 'Thiếu trường bắt buộc');
                continue;
            }

            try {
                $uniqueKeys = [];
                $updateData = [];
                
                foreach ($config['unique_keys'] as $key) {
                    $value = $this->getRowValue($row, $key, $fieldMapping);
                    if ($value !== null) {
                        $uniqueKeys[$key] = $value;
                    }
                }

                foreach ($config['mapping'] as $field => $possibleNames) {
                    if (!in_array($field, $config['unique_keys'])) {
                        $value = $this->getRowValue($row, $field, $fieldMapping);
                        if ($value !== null) {
                            $updateData[$field] = $value;
                        }
                    }
                }

                // Set default values
                $updateData['cskcb_cgkt'] = null;
                $updateData['cskcb_cls'] = null;

                $uniqueKeys = self::ganCoSo($uniqueKeys, $maCskcb);

                $banGhi = ServiceCatalog::updateOrCreate($uniqueKeys, $updateData);
                $this->demGhi($banGhi);
            } catch (\Exception $e) {
                $this->ketQua->themLoi(0, $e->getMessage());
                Log::error('Error updating or creating ServiceCatalog record', [
                    'error' => $e->getMessage(),
                    'row' => $row
                ]);
                continue;
            }
        }
    }

    private function importIcd10($data, array $fieldMapping, array $config)
    {
        $data = $data->slice(1);

        foreach ($data as $row) {
            if (!$this->hasRequiredFields($row, $config['required_fields'], $fieldMapping)) {
                $this->ketQua->themBoQua(0, 'Thiếu trường bắt buộc');
                continue;
            }

            try {
                $uniqueKeys = [];
                $updateData = [];

                foreach ($config['unique_keys'] as $key) {
                    $value = $this->getRowValue($row, $key, $fieldMapping);
                    if ($value !== null) {
                        $uniqueKeys[$key] = $value;
                    }
                }

                foreach ($config['mapping'] as $field => $possibleNames) {
                    if (!in_array($field, $config['unique_keys'])) {
                        $value = $this->getRowValue($row, $field, $fieldMapping);
                        if ($value !== null) {
                            $updateData[$field] = $value;
                        }
                    }
                }

                // Chuẩn hóa cờ mãn tính về boolean nếu file có cột đó.
                if (array_key_exists('is_chronic', $updateData)) {
                    $v = mb_strtolower(trim((string) $updateData['is_chronic']));
                    $updateData['is_chronic'] = in_array($v, ['1', 'true', 'x', 'co', 'có', 'yes'], true);
                }

                $banGhi = Icd10Category::updateOrCreate($uniqueKeys, $updateData);
                $this->demGhi($banGhi);
            } catch (\Exception $e) {
                $this->ketQua->themLoi(0, $e->getMessage());
                Log::error('Error updating or creating Icd10Category record', [
                    'error' => $e->getMessage(),
                    'row' => $row,
                ]);
                continue;
            }
        }
    }

    private function importIcdYhct($data, array $fieldMapping, array $config)
    {
        $data = $data->slice(1);

        foreach ($data as $row) {
            if (!$this->hasRequiredFields($row, $config['required_fields'], $fieldMapping)) {
                $this->ketQua->themBoQua(0, 'Thiếu trường bắt buộc');
                continue;
            }

            try {
                $uniqueKeys = [];
                $updateData = [];

                foreach ($config['unique_keys'] as $key) {
                    $value = $this->getRowValue($row, $key, $fieldMapping);
                    if ($value !== null) {
                        $uniqueKeys[$key] = $value;
                    }
                }

                foreach ($config['mapping'] as $field => $possibleNames) {
                    if (!in_array($field, $config['unique_keys'])) {
                        $value = $this->getRowValue($row, $field, $fieldMapping);
                        if ($value !== null) {
                            $updateData[$field] = $value;
                        }
                    }
                }

                $banGhi = IcdYhctCategory::updateOrCreate($uniqueKeys, $updateData);
                $this->demGhi($banGhi);
            } catch (\Exception $e) {
                $this->ketQua->themLoi(0, $e->getMessage());
                Log::error('Error updating or creating IcdYhctCategory record', [
                    'error' => $e->getMessage(),
                    'row' => $row,
                ]);
                continue;
            }
        }
    }

    private function importMedicalStaff($data, array $fieldMapping, array $config)
    {
        $data = $data->slice(1);

        // Xác định unique keys: dùng ma_bhxh nếu có trong file, ngược lại dùng so_dinh_danh
        $activeUniqueKeys = $config['unique_keys']; // ['ma_bhxh']
        if (!isset($fieldMapping['ma_bhxh']) && isset($fieldMapping['so_dinh_danh'])) {
            $activeUniqueKeys = $config['unique_keys_alt'] ?? ['so_dinh_danh'];
        }

        foreach ($data as $row) {
            if (!$this->hasRequiredFields($row, $config['required_fields'], $fieldMapping)) {
                Log::error('Error importing medical staff', [
                    'error' => 'Thiếu dữ liệu bắt buộc',
                    'row' => $row
                ]);
                continue;
            }

            // Chuyển đổi định dạng ngày NGAYCAP_CCHN về dạng text YYYYMMDD
            $ngaycap_cchn = $this->getRowValue($row, 'ngaycap_cchn', $fieldMapping);
            if ($ngaycap_cchn !== null) {
                if (is_numeric($ngaycap_cchn)) {
                    $ngaycap_cchn = Carbon::instance(Date::excelToDateTimeObject($ngaycap_cchn))->format('Ymd');
                } else {
                    try {
                        $ngaycap_cchn = Carbon::createFromFormat('m/d/Y H:i', $ngaycap_cchn)->format('Ymd');
                    } catch (\Exception $e) {
                        // Thử format khác nếu format trên không match
                        $ngaycap_cchn = Carbon::parse($ngaycap_cchn)->format('Ymd');
                    }
                }
            }

            try {
                $uniqueKeys = [];
                $updateData = [];

                foreach ($activeUniqueKeys as $key) {
                    $value = $this->getRowValue($row, $key, $fieldMapping);
                    if ($value !== null) {
                        $uniqueKeys[$key] = $value;
                    }
                }

                foreach ($config['mapping'] as $field => $possibleNames) {
                    if (!in_array($field, $activeUniqueKeys)) {
                        $value = $this->getRowValue($row, $field, $fieldMapping);
                        if ($value !== null) {
                            $updateData[$field] = $value;
                        }
                    }
                }

                // Override với giá trị đã format
                if ($ngaycap_cchn !== null) {
                    $updateData['ngaycap_cchn'] = $ngaycap_cchn;
                }

                $banGhi = MedicalStaff::updateOrCreate($uniqueKeys, $updateData);
                $this->demGhi($banGhi);
            } catch (\Exception $e) {
                $this->ketQua->themLoi(0, $e->getMessage());
                Log::error('Error importing medical staff', [
                    'error' => $e->getMessage(),
                    'row' => $row
                ]);
                continue;
            }
        }
    }

    private function importDepartmentBed($data, array $fieldMapping, array $config)
    {
        $data = $data->slice(1);
        
        foreach ($data as $row) {
            if (!$this->hasRequiredFields($row, $config['required_fields'], $fieldMapping)) {
                $this->ketQua->themBoQua(0, 'Thiếu trường bắt buộc');
                continue;
            }

            try {
                $uniqueKeys = [];
                $updateData = [];
                
                foreach ($config['unique_keys'] as $key) {
                    $value = $this->getRowValue($row, $key, $fieldMapping);
                    if ($value !== null) {
                        $uniqueKeys[$key] = $value;
                    }
                }

                foreach ($config['mapping'] as $field => $possibleNames) {
                    if (!in_array($field, $config['unique_keys'])) {
                        $value = $this->getRowValue($row, $field, $fieldMapping);
                        if ($value !== null) {
                            $updateData[$field] = $value;
                        }
                    }
                }

                $banGhi = DepartmentBedCatalog::updateOrCreate($uniqueKeys, $updateData);
                $this->demGhi($banGhi);
            } catch (\Exception $e) {
                $this->ketQua->themLoi(0, $e->getMessage());
                Log::error('Error updating or creating DepartmentBedCatalog record', [
                    'error' => $e->getMessage(),
                    'row' => $row
                ]);
                continue;
            }
        }
    }

    private function importEquipment($data, array $fieldMapping, array $config)
    {
        $data = $data->slice(1);
        
        foreach ($data as $row) {
            if (!$this->hasRequiredFields($row, $config['required_fields'], $fieldMapping)) {
                $this->ketQua->themBoQua(0, 'Thiếu trường bắt buộc');
                continue;
            }

            try {
                $uniqueKeys = [];
                $updateData = [];
                
                foreach ($config['unique_keys'] as $key) {
                    $value = $this->getRowValue($row, $key, $fieldMapping);
                    if ($value !== null) {
                        $uniqueKeys[$key] = $value;
                    }
                }

                foreach ($config['mapping'] as $field => $possibleNames) {
                    if (!in_array($field, $config['unique_keys'])) {
                        $value = $this->getRowValue($row, $field, $fieldMapping);
                        if ($value !== null) {
                            $updateData[$field] = $value;
                        }
                    }
                }

                $banGhi = EquipmentCatalog::updateOrCreate($uniqueKeys, $updateData);
                $this->demGhi($banGhi);
            } catch (\Exception $e) {
                $this->ketQua->themLoi(0, $e->getMessage());
                Log::error('Error updating or creating EquipmentCatalog record', [
                    'error' => $e->getMessage(),
                    'row' => $row
                ]);
                continue;
            }
        }
    }

    private function importAdministrativeUnit($data, array $fieldMapping, array $config)
    {
        // Deactivate all existing active records
        AdministrativeUnit::where('is_active', true)->update(['is_active' => false]);

        $data = $data->slice(1);
        
        foreach ($data as $row) {
            if (!$this->hasRequiredFields($row, $config['required_fields'], $fieldMapping)) {
                $this->ketQua->themBoQua(0, 'Thiếu trường bắt buộc');
                continue;
            }

            try {
                $uniqueKeys = [];
                $updateData = [];
                
                foreach ($config['unique_keys'] as $key) {
                    $value = $this->getRowValue($row, $key, $fieldMapping);
                    if ($value !== null) {
                        $uniqueKeys[$key] = $value;
                    }
                }

                foreach ($config['mapping'] as $field => $possibleNames) {
                    if (!in_array($field, $config['unique_keys'])) {
                        $value = $this->getRowValue($row, $field, $fieldMapping);
                        if ($value !== null) {
                            $updateData[$field] = $value;
                        }
                    }
                }

                $updateData['is_active'] = true;

                $banGhi = AdministrativeUnit::updateOrCreate($uniqueKeys, $updateData);
                $this->demGhi($banGhi);
            } catch (\Exception $e) {
                $this->ketQua->themLoi(0, $e->getMessage());
                Log::error('Error updating or creating AdministrativeUnit record', [
                    'error' => $e->getMessage(),
                    'row' => $row
                ]);
                continue;
            }
        }
    }

    private function importMedicalOrganization($data, array $fieldMapping, array $config)
    {
        // Deactivate all existing active records
        MedicalOrganization::where('is_active', true)->update(['is_active' => false]);

        $data = $data->slice(1);
        
        foreach ($data as $row) {
            if (!$this->hasRequiredFields($row, $config['required_fields'], $fieldMapping)) {
                $this->ketQua->themBoQua(0, 'Thiếu trường bắt buộc');
                continue;
            }

            try {
                $uniqueKeys = [];
                $updateData = [];
                
                foreach ($config['unique_keys'] as $key) {
                    $value = $this->getRowValue($row, $key, $fieldMapping);
                    if ($value !== null) {
                        $uniqueKeys[$key] = $value;
                    }
                }

                foreach ($config['mapping'] as $field => $possibleNames) {
                    if (!in_array($field, $config['unique_keys'])) {
                        $value = $this->getRowValue($row, $field, $fieldMapping);
                        if ($value !== null) {
                            $updateData[$field] = $value;
                        }
                    }
                }

                $updateData['is_active'] = true;

                $banGhi = MedicalOrganization::updateOrCreate($uniqueKeys, $updateData);
                $this->demGhi($banGhi);
            } catch (\Exception $e) {
                $this->ketQua->themLoi(0, $e->getMessage());
                Log::error('Error updating or creating MedicalOrganization record', [
                    'error' => $e->getMessage(),
                    'row' => $row
                ]);
                continue;
            }
        }
    }

    private function importJobCategories($data, array $fieldMapping, array $config)
    {
        $data = $data->slice(1);
        
        foreach ($data as $row) {
            if (!$this->hasRequiredFields($row, $config['required_fields'], $fieldMapping)) {
                $this->ketQua->themBoQua(0, 'Thiếu trường bắt buộc');
                continue;
            }

            try {
                $uniqueKeys = [];
                $updateData = [];
                
                foreach ($config['unique_keys'] as $key) {
                    $value = $this->getRowValue($row, $key, $fieldMapping);
                    if ($value !== null) {
                        $uniqueKeys[$key] = $value;
                    }
                }

                foreach ($config['mapping'] as $field => $possibleNames) {
                    if (!in_array($field, $config['unique_keys'])) {
                        $value = $this->getRowValue($row, $field, $fieldMapping);
                        if ($value !== null) {
                            $updateData[$field] = $value;
                        }
                    }
                }

                $banGhi = JobCategory::updateOrCreate($uniqueKeys, $updateData);
                $this->demGhi($banGhi);
            } catch (\Exception $e) {
                $this->ketQua->themLoi(0, $e->getMessage());
                Log::error('Error updating or creating JobCategory record', [
                    'error' => $e->getMessage(),
                    'row' => $row
                ]);
            }
        }
    }
}
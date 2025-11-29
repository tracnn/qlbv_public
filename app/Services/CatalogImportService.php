<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use App\Models\BHYT\MedicineCatalog;
use App\Models\BHYT\MedicalSupplyCatalog;
use App\Models\BHYT\ServiceCatalog;
use App\Models\BHYT\MedicalStaff;
use App\Models\BHYT\DepartmentBedCatalog;
use App\Models\BHYT\EquipmentCatalog;
use App\Models\BHYT\AdministrativeUnit;
use App\Models\BHYT\MedicalOrganization;

class CatalogImportService
{
    protected $columnMapper;
    protected $catalogConfigs;

    public function __construct(ExcelColumnMapper $columnMapper)
    {
        $this->columnMapper = $columnMapper;
        $this->catalogConfigs = config('catalog_import_mapping');
    }

    public function import($filePath)
    {
        $data = Excel::toCollection(null, $filePath)->first();

        if ($data->isEmpty()) {
            throw new \Exception('File không chứa dữ liệu');
        }

        $headerRow = $data->first()->values()->toArray();

        // Detect catalog type dựa trên key columns
        $catalogType = $this->columnMapper->detectCatalogType($headerRow, $this->catalogConfigs);

        if (!$catalogType) {
            throw new \Exception('Không thể xác định loại danh mục. Vui lòng kiểm tra lại cấu trúc file.');
        }

        // Tạo field mapping từ header
        $fieldMapping = $this->columnMapper->createFieldMapping(
            $headerRow,
            $this->catalogConfigs[$catalogType]['mapping']
        );

        // Kiểm tra các trường bắt buộc có tồn tại không
        $requiredFields = $this->catalogConfigs[$catalogType]['required_fields'] ?? [];
        $missingFields = array_diff($requiredFields, array_keys($fieldMapping));
        
        if (!empty($missingFields)) {
            throw new \Exception(
                'Thiếu các cột bắt buộc: ' . implode(', ', $missingFields) . 
                '. Vui lòng kiểm tra lại file Excel.'
            );
        }

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
        ];

        $methodName = $methodMap[$catalogType] ?? null;
        if ($methodName && method_exists($this, $methodName)) {
            return $this->$methodName($data, $fieldMapping, $this->catalogConfigs[$catalogType]);
        }

        throw new \Exception('Không tìm thấy method import cho loại catalog: ' . $catalogType);
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

    private function importMedicine($data, array $fieldMapping, array $config)
    {
        $data = $data->slice(1); // Bỏ qua dòng đầu tiên
        
        foreach ($data as $row) {
            // Kiểm tra các trường bắt buộc
            if (!$this->hasRequiredFields($row, $config['required_fields'], $fieldMapping)) {
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

                MedicineCatalog::updateOrCreate($uniqueKeys, $updateData);
            } catch (\Exception $e) {
                Log::error('Error updating or creating MedicineCatalog record', [
                    'error' => $e->getMessage(),
                    'row' => $row
                ]);
                continue;
            }
        }
    }

    private function importMedicalSupply($data, array $fieldMapping, array $config)
    {
        $data = $data->slice(1);
        
        foreach ($data as $row) {
            if (!$this->hasRequiredFields($row, $config['required_fields'], $fieldMapping)) {
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

                MedicalSupplyCatalog::updateOrCreate($uniqueKeys, $updateData);
            } catch (\Exception $e) {
                Log::error('Error updating or creating MedicalSupplyCatalog record', [
                    'error' => $e->getMessage(),
                    'row' => $row
                ]);
                continue;
            }
        }
    }

    private function importService($data, array $fieldMapping, array $config)
    {
        $data = $data->slice(1);
        
        foreach ($data as $row) {
            // Loại bỏ ký tự đặc biệt trong cột 'Tên dịch vụ'
            $tenDichVuIndex = $fieldMapping['ten_dich_vu'] ?? null;
            if ($tenDichVuIndex !== null && isset($row[$tenDichVuIndex])) {
                $row[$tenDichVuIndex] = preg_replace('/[^\p{L}\p{N}\s]/u', '', $row[$tenDichVuIndex]);
            }

            if (!$this->hasRequiredFields($row, $config['required_fields'], $fieldMapping)) {
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

                ServiceCatalog::updateOrCreate($uniqueKeys, $updateData);
            } catch (\Exception $e) {
                Log::error('Error updating or creating ServiceCatalog record', [
                    'error' => $e->getMessage(),
                    'row' => $row
                ]);
                continue;
            }
        }
    }

    private function importMedicalStaff($data, array $fieldMapping, array $config)
    {
        $data = $data->slice(1);

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

                // Override với giá trị đã format
                if ($ngaycap_cchn !== null) {
                    $updateData['ngaycap_cchn'] = $ngaycap_cchn;
                }

                MedicalStaff::updateOrCreate($uniqueKeys, $updateData);
            } catch (\Exception $e) {
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

                DepartmentBedCatalog::updateOrCreate($uniqueKeys, $updateData);
            } catch (\Exception $e) {
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

                EquipmentCatalog::updateOrCreate($uniqueKeys, $updateData);
            } catch (\Exception $e) {
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

                AdministrativeUnit::updateOrCreate($uniqueKeys, $updateData);
            } catch (\Exception $e) {
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

                MedicalOrganization::updateOrCreate($uniqueKeys, $updateData);
            } catch (\Exception $e) {
                Log::error('Error updating or creating MedicalOrganization record', [
                    'error' => $e->getMessage(),
                    'row' => $row
                ]);
                continue;
            }
        }
    }
}
<?php

namespace App\Services;

use App\Models\BHYT\Qd130Xml2;
use App\Models\BHYT\Qd130Xml1;
use App\Models\BHYT\DepartmentBedCatalog;
use App\Models\BHYT\EquipmentCatalog;
use App\Models\BHYT\MedicalStaff;
use App\Models\BHYT\MedicalSupplyCatalog;
use App\Models\BHYT\MedicineCatalog;
use App\Models\BHYT\ServiceCatalog;

use Illuminate\Support\Collection;

class Qd130Xml2Checker
{
    protected $xmlErrorService;
    protected $prefix;

    protected $xmlType;
    protected $drugGroups;
    protected $bloodGroup;
    protected $excludedDepartments;
    protected $drugGroupNotCheck;
    protected $drugCodeNotCheck;

    public function __construct(Qd130XmlErrorService $xmlErrorService)
    {
        $this->xmlErrorService = $xmlErrorService;
        $this->setConditions();
    }

    protected function setConditions()
    {
        $this->xmlType = 'XML2';
        $this->prefix = $this->xmlType . '_';

        $this->drugGroups = config('qd130xml.drug_group_code'); // Mã nhóm thuốc cần kiểm tra
        $this->bloodGroup = config('qd130xml.blood_group_code'); // Mã nhóm máu
        $this->excludedDepartments = config('organization.exclude_department'); //Mã khoa không cần kiểm tra
        $this->drugGroupNotCheck = config('qd130xml.drug_group_not_check');
        $this->drugCodeNotCheck = config('qd130xml.drug_code_not_check'); // Các mã thuốc cụ thể không cần kiểm tra
    }

    protected function generateErrorCode(string $errorKey): string
    {
        return $this->prefix . $errorKey;
    }

    /**
     * Check XML2 Errors
     *
     * @param XML2 $data
     * @return void
     */
    public function checkErrors(Qd130Xml2 $data): void
    {
        // Thực hiện kiểm tra lỗi
        $errors = collect();

        // Load related XML1
        $data->load('Qd130Xml1');
        if ($data->Qd130Xml1) {
            $errors = $errors->merge($this->checkOrderTime($data));
        }

        $errors = $errors->merge($this->infoChecker($data));
        $errors = $errors->merge($this->checkMedicalStaff($data));
        $errors = $errors->merge($this->checkDrugCatalog($data));
        
        if (config('qd130xml.general.check_valid_department_req')) {
            $errors = $errors->merge($this->checkValidMakhoaReq($data)); // Kiểm tra tính hợp lệ của khoa chỉ định
        }

        $additionalData = [
            'ngay_yl' => $data->ngay_yl
        ];

        // Save errors to xml_error_checks table
        $this->xmlErrorService->saveErrors($this->xmlType, $data->ma_lk, $data->stt, $errors, $additionalData);
    }

    private function infoChecker(Qd130Xml2 $data): Collection
    {
        $errors = collect();

        if (!empty($data->tt_thau)) {
            $ttThauParts = explode(';', $data->tt_thau);

            // Ensure we have exactly 4 parts (QĐ thầu, Gói thầu, Nhóm thầu, Năm thầu)
            if (count($ttThauParts) == 4) {
                [$qdThau, $goiThau, $nhomThau, $namThau] = $ttThauParts;

                // Load the patterns from the configuration
                $config = config('qd130xml.xml2.tt_thau');

                $goiThauPattern = $config['goi_thau_pattern'] ?? null;
                $nhomThauPattern = $config['nhom_thau_pattern'] ?? null;
                $namThauPattern = $config['nam_thau_pattern'] ?? null;

                // Validate Gói thầu
                if ($goiThauPattern && !preg_match($goiThauPattern, $goiThau)) {
                    $errorCode = $this->generateErrorCode('INFO_ERROR_GOI_THAU');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Gói thầu không đúng định dạng',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'Gói thầu của dịch vụ: ' . $data->ten_thuoc . '; không đúng định dạng: ' . $data->tt_thau
                    ]);
                }

                // Validate Nhóm thầu
                if ($nhomThauPattern && !preg_match($nhomThauPattern, $nhomThau)) {
                    $errorCode = $this->generateErrorCode('INFO_ERROR_NHOM_THAU');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Nhóm thầu không đúng định dạng',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'Nhóm thầu của dịch vụ: ' . $data->ten_thuoc . '; không đúng định dạng: ' . $data->tt_thau
                    ]);
                }

                // Validate Năm thầu
                if ($namThauPattern && !preg_match($namThauPattern, $namThau)) {
                    $errorCode = $this->generateErrorCode('INFO_ERROR_NAM_THAU');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Năm thầu không đúng định dạng',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'Năm thầu của dịch vụ: ' . $data->ten_thuoc . '; không đúng định dạng: ' . $data->tt_thau
                    ]);
                }
            }
        }

        // Check tyle_tt_bh value
        if (isset($data->tyle_tt_bh) && ($data->tyle_tt_bh < 0 || $data->tyle_tt_bh > 100)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_TYLE_TT_BH');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Tỷ lệ TT BH không nằm trong khoảng cho phép',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Tỷ lệ TT BH của dịch vụ: ' . $data->ten_thuoc . '; không nằm trong khoảng 0-100: ' . $data->tyle_tt_bh
            ]);
        }

        return $errors;
    }


    /**
     * Check for invalid order time
     *
     * @param XML2 $data
     * @return Collection
     */
    private function checkOrderTime(Qd130Xml2 $data): Collection
    {
        $errors = collect();

        if (in_array($data->ma_nhom, $this->drugGroups)) {
            if ($data->Qd130Xml1) {
                $ngayVao = $data->Qd130Xml1->ngay_vao;
                $ngayRa = $data->Qd130Xml1->ngay_ra;
                $ngayYLenh = $data->ngay_yl;

                if ($ngayYLenh && $ngayVao && $ngayRa && ($ngayYLenh < $ngayVao || $ngayYLenh > $ngayRa)) {
                    $errorCode = $this->generateErrorCode('INVALID_ORDER_TIME');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Thời gian y lệnh không hợp lệ',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'Thời gian y lệnh nhỏ hơn ngày vào hoặc lớn hơn ngày ra (Mã thuốc: ' . $data->ma_thuoc . ')'
                    ]);
                }
            }
        }

        return $errors;
    }

    /**
     * Check for medical staff errors
     *
     * @param XML2 $data
     * @return Collection
     */
    private function checkMedicalStaff(Qd130Xml2 $data): Collection
    {
        $errors = collect();

        if (empty($data->ma_bac_si)) {
            $errorCode = $this->generateErrorCode('MISSING_MEDICAL_STAFF_CODE');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã bác sĩ',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Không có mã bác sĩ chỉ định'
            ]);
        } else {
            $staff = MedicalStaff::where('macchn', $data->ma_bac_si)->exists();
            if (!$staff) {
                $errorCode = $this->generateErrorCode('INVALID_MEDICAL_STAFF_CODE');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã CCHN chưa được duyệt',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã bác sĩ chưa được duyệt danh mục trên cổng BHXH (Mã CCHN: ' . $data->ma_bac_si . ')'
                ]);
            }
        }

        return $errors;
    }

    /**
     * Check for drug catalog errors
     *
     * @param XML2 $data
     * @return Collection
     */
    private function checkDrugCatalog(Qd130Xml2 $data): Collection
    {
        $errors = collect();

        if (in_array($data->ma_nhom, $this->drugGroups) && !in_array($data->ma_khoa, $this->excludedDepartments)) {
            $drugGroup = explode('.', $data->ma_thuoc)[0];
            // Chỉ kiểm tra những mã thuốc có nhóm không thuộc drugGroupNotCheck và mã thuốc không thuộc drugCodeNotCheck
            if (!in_array($drugGroup, $this->drugGroupNotCheck) && !in_array($data->ma_thuoc, $this->drugCodeNotCheck)) {
                if (empty($data->ma_thuoc)) {
                    $errorCode = $this->generateErrorCode('MISSING_DRUG_CODE');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Không có mã thuốc',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'Không có mã thuốc'
                    ]);
                }

                if (empty($data->ten_thuoc)) {
                    $errorCode = $this->generateErrorCode('MISSING_DRUG_NAME');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Không có tên thuốc',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => $data->ma_thuoc . '; Không có tên thuốc'
                    ]);
                }

                if (empty($data->ham_luong)) {
                    $errorCode = $this->generateErrorCode('MISSING_DRUG_CONCENTRATION');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Không có hàm lượng thuốc',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => $data->ma_thuoc . '; Không có hàm lượng thuốc'
                    ]);
                }

                if (empty($data->so_dang_ky)) {
                    $errorCode = $this->generateErrorCode('MISSING_REGISTRATION_NUMBER');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Không có số đăng ký thuốc',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => $data->ma_thuoc . '; Không có số đăng ký thuốc'
                    ]);
                }

                if (empty($data->tt_thau)) {
                    $errorCode = $this->generateErrorCode('MISSING_TT_THAU');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Không có thông tin thầu thuốc',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => $data->ma_thuoc . '; Không có TT_THAU'
                    ]);
                }
                
                if ($errors->isNotEmpty()) {
                    return $errors;
                }

                $ttThauParts = explode(";", $data->tt_thau);
                if (count($ttThauParts) < 4 || in_array('', $ttThauParts, true)) {
                    $errorCode = $this->generateErrorCode('INVALID_TT_THAU_FORMAT');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Thông tin thầu không đúng định dạng',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'TT_THAU không đúng định dạng (Mã thuốc: ' . $data->ma_thuoc . '). TT_THAU: ' . $data->tt_thau
                    ]);
                } else {
                    $ttThau = $ttThauParts[0] . ";" . $ttThauParts[1] . ";" . $ttThauParts[2] . ";" . $ttThauParts[3];

                    $medicine = MedicineCatalog::where('ma_thuoc', $data->ma_thuoc)
                        ->where('ham_luong', $data->ham_luong)
                        ->where('so_dang_ky', $data->so_dang_ky)
                        ->where('tt_thau', 'LIKE', $ttThau . '%')
                        ->first(); //* Không được thay bằng exists() *\\

                    if (!$medicine) {
                        if (!MedicineCatalog::where('ma_thuoc', $data->ma_thuoc)->exists()) {
                            $errorCode = $this->generateErrorCode('INVALID_DRUG_CODE');
                            $errors->push((object)[
                                'error_code' => $errorCode,
                                'error_name' => 'Mã thuốc chưa có trong danh mục BHYT',
                                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                                'description' => 'Mã thuốc không có trong danh mục BHYT: ' . formatDescription($data->ma_thuoc)
                            ]);
                        } elseif (!MedicineCatalog::where('ma_thuoc', $data->ma_thuoc)->where('ham_luong', $data->ham_luong)->exists()) {
                            $errorCode = $this->generateErrorCode('INVALID_DRUG_CONCENTRATION');
                            $errors->push((object)[
                                'error_code' => $errorCode,
                                'error_name' => 'Mã thuốc sai hàm lượng trong danh mục BHYT',
                                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                                'description' => 'Mã thuốc: ' . $data->ma_thuoc . '; Sai hàm lượng: ' . formatDescription($data->ham_luong)
                            ]);
                        } elseif (!MedicineCatalog::where('ma_thuoc', $data->ma_thuoc)->where('ham_luong', $data->ham_luong)->where('so_dang_ky', $data->so_dang_ky)->exists()) {
                            $errorCode = $this->generateErrorCode('INVALID_REGISTRATION_NUMBER');
                            $errors->push((object)[
                                'error_code' => $errorCode,
                                'error_name' => 'Mã thuốc sai số đăng ký trong danh mục BHYT',
                                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                                'description' => 'Mã thuốc: ' . $data->ma_thuoc . '; Số ĐK: ' . formatDescription($data->so_dang_ky)
                            ]);
                        } else {
                            $errorCode = $this->generateErrorCode('INVALID_TT_THAU');
                            $errors->push((object)[
                                'error_code' => $errorCode,
                                'error_name' => 'Mã thuốc sai thông tin thầu trong danh mục BHYT',
                                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                                'description' => 'Mã thuốc: ' . $data->ma_thuoc . '; Sai TT_THAU: ' . formatDescription($data->tt_thau)
                            ]);
                        }
                    } else {
                        // Kiểm tra tên thuốc
                        if ($data->ten_thuoc != $medicine->ten_thuoc) {
                            $errorCode = $this->generateErrorCode('INVALID_DRUG_NAME');
                            $errors->push((object)[
                                'error_code' => $errorCode,
                                'error_name' => 'Mã thuốc sai tên trong danh mục BHYT',
                                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                                'description' => 'Mã thuốc: ' . $data->ma_thuoc . '; Thuốc sai tên so với danh mục được duyệt: ' . formatDescription($data->ten_thuoc) . ' # ' . $medicine->ten_thuoc
                            ]);
                        }

                        // Kiểm tra giá thuốc
                        if ($data->don_gia > $medicine->don_gia) {
                            $errorCode = $this->generateErrorCode('PRICE_EXCEEDS_APPROVED');
                            $errors->push((object)[
                                'error_code' => $errorCode,
                                'error_name' => 'Giá thuốc cao hơn giá được duyệt',
                                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                                'description' => 'Mã thuốc: ' . $data->ma_thuoc . '; Giá cao hơn giá được duyệt: ' . $data->don_gia . ' > ' . $medicine->don_gia
                            ]);
                        }  
                    }
                }
            }
        }

        return $errors;
    }

    private function checkValidMakhoaReq(Qd130Xml2 $data): Collection
    {
        $errors = collect();

        if (in_array($data->Qd130Xml1->ma_loai_kcb, config('qd130xml.treatment_type_inpatient'))) {

            $is_ma_doituong_kcb_trai_tuyen = false;
            foreach (config('qd130xml.xml1.ma_doituong_kcb_trai_tuyen') as $ma_doituong) {
                if (strpos($data->Qd130Xml1->ma_doituong_kcb, (string)$ma_doituong) === 0) {
                    $is_ma_doituong_kcb_trai_tuyen = true;
                    break;
                }
            }

            if ($is_ma_doituong_kcb_trai_tuyen && 
                in_array($data->ma_khoa, config('qd130xml.general.ma_khoa_kkb'))) {
                $errorCode = $this->generateErrorCode('MA_KHOA_REQ_INVALID');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Khoa chỉ định thuốc không hợp lệ',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Khoa khám bệnh: ' . implode(',', config('qd130xml.general.ma_khoa_kkb')) . '; không được chỉ định: ' . $data->ten_thuoc . '; Đối với BN Nội trú - Trái tuyến'
                ]);
            }
        }

        return $errors;
    }

    // Thêm các phương thức kiểm tra khác ở đây
}
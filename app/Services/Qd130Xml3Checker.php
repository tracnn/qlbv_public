<?php

namespace App\Services;

use App\Models\BHYT\Qd130Xml3;
use App\Models\BHYT\MedicalSupplyCatalog;
use App\Models\BHYT\Icd10Category;
use App\Models\BHYT\IcdYhctCategory;
use App\Models\BHYT\ServiceCatalog;
use App\Models\BHYT\EquipmentCatalog;
use Illuminate\Support\Collection;

class Qd130Xml3Checker
{
    protected $xmlErrorService;
    protected $commonValidationService;
    protected $prefix;

    protected $xmlType;
    protected $materialGroupCodes;
    protected $bedGroupCodes;
    protected $excludedBedDepartments;
    protected $outpatientTypes;
    protected $examinationGroupCodes;
    protected $transportGroupCodes;
    protected $bedCodePattern;
    protected $excludedMaterialGroupCodes;
    protected $groupCodeWithExecutor;
    protected $serviceGroupsRequiringAnesthesia;
    protected $serviceDisplay;

    public function __construct(Qd130XmlErrorService $xmlErrorService, CommonValidationService $commonValidationService)
    {
        $this->xmlErrorService = $xmlErrorService;
        $this->commonValidationService = $commonValidationService;
        $this->setConditions();
    }

    protected function setConditions()
    {
        $this->xmlType = 'XML3';
        $this->prefix = $this->xmlType . '_';
        
        $this->materialGroupCodes = config('qd130xml.material_group_code');
        $this->bedGroupCodes = config('qd130xml.bed_group_code');
        $this->excludedBedDepartments = config('organization.exclude_department');
        $this->outpatientTypes = config('qd130xml.treatment_type_outpatient');
        $this->examinationGroupCodes = config('qd130xml.examination_group_code');
        $this->transportGroupCodes = config('qd130xml.transport_group_code');
        $this->bedCodePattern = config('qd130xml.bed_code_pattern');
        $this->excludedMaterialGroupCodes = config('qd130xml.excluded_material_group_code');
        $this->groupCodeWithExecutor = config('qd130xml.group_code_with_executor');
        $this->serviceGroupsRequiringAnesthesia = config('qd130xml.service_groups_requiring_anesthesia');
    }

    protected function generateErrorCode(string $errorKey): string
    {
        return $this->prefix . $errorKey;
    }

    /**
     * Check Qd130Xml3 Errors
     *
     * @param Qd130Xml3 $data
     * @return void
     */
    public function checkErrors(Qd130Xml3 $data): void
    {
        // Thực hiện kiểm tra lỗi
        $errors = collect();

        // Load related XML1
        $data->load('Qd130Xml1');
        $this->serviceDisplay = !empty($data->ten_vat_tu) ? $data->ten_vat_tu : $data->ten_dich_vu;

        if ($data->Qd130Xml1) {
            $errors = $errors->merge($this->checkOutpatientBedDayErrors($data));
            $errors = $errors->merge($this->checkOrderTime($data));
            $errors = $errors->merge($this->checkNgayKq($data)); // Thêm kiểm tra NGAY_KQ
        }
        $errors = $errors->merge($this->infoChecker($data));
        $errors = $errors->merge($this->checkMissingServiceOrMaterial($data));
        $errors = $errors->merge($this->checkBedGroupCodeConditions($data));
        $errors = $errors->merge($this->checkExcludedMaterialGroupCode($data));
        //$errors = $errors->merge($this->checkGroupCodeWithExecutor($data));
        $errors = $errors->merge($this->checkBedDayQuantity($data)); // Thêm kiểm tra số lượng ngày giường
        $errors = $errors->merge($this->checkMedicalSupplyCatalog($data)); // Thêm kiểm tra VTYT
        $errors = $errors->merge($this->checkMedicalService($data)); // Kiểm tra dịch vụ kỹ thuật

        if (config('qd130xml.general.check_valid_department_req')) {
            $errors = $errors->merge($this->checkValidMakhoaReq($data)); // Kiểm tra tính hợp lệ của khoa chỉ định
        }

        $additionalData = [
            'ngay_yl' => $data->ngay_yl
        ];

        if (!empty($data->ngay_kq)) {
            $additionalData['ngay_kq'] = $data->ngay_kq;
        }

        // Save errors to xml_error_checks table
        $this->xmlErrorService->saveErrors($this->xmlType, $data->ma_lk, $data->stt, $errors, $additionalData);
    }

    /**
     * Check for outpatient bed day errors
     *
     * @param Qd130Xml3 $data
     * @return Collection
     */
    private function infoChecker(Qd130Xml3 $data): Collection
    {
        $errors = collect();

        if (!empty($data->tt_thau)) {
            $ttThauParts = explode(';', $data->tt_thau);

            // Ensure we have exactly 4 parts (QĐ thầu, Gói thầu, Nhóm thầu, Năm thầu)
            if (count($ttThauParts) == 4) {
                [$qdThau, $goiThau, $nhomThau, $namThau] = $ttThauParts;

                // Load the patterns from the configuration
                $config = config('qd130xml.xml3.tt_thau');

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
                        'description' => 'Gói thầu của dịch vụ: ' . $this->serviceDisplay . '; không đúng định dạng: ' . $data->tt_thau
                    ]);
                }

                // Validate Nhóm thầu
                if ($nhomThauPattern && !preg_match($nhomThauPattern, $nhomThau)) {
                    $errorCode = $this->generateErrorCode('INFO_ERROR_NHOM_THAU');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Nhóm thầu không đúng định dạng',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'Nhóm thầu của dịch vụ: ' . $this->serviceDisplay . '; không đúng định dạng: ' . $data->tt_thau
                    ]);
                }

                // Validate Năm thầu
                if ($namThauPattern && !preg_match($namThauPattern, $namThau)) {
                    $errorCode = $this->generateErrorCode('INFO_ERROR_NAM_THAU');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Năm thầu không đúng định dạng',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'Năm thầu của dịch vụ: ' . $this->serviceDisplay . '; không đúng định dạng: ' . $data->tt_thau
                    ]);
                }
            }
        }

        if (in_array($data->ma_nhom, $this->groupCodeWithExecutor) && empty($data->nguoi_thuc_hien)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_GROUP_CODE_NGUOI_THUC_HIEN');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu người thực hiện',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Người thực hiện không được để trống đối với DVKT: ' . $this->serviceDisplay
            ]);
        }

        if (!empty($data->nguoi_thuc_hien)) {
            $nguoi_thuc_hien_array = explode(';', $data->nguoi_thuc_hien);
            foreach ($nguoi_thuc_hien_array as $nguoi_thuc_hien) {
                if (!$this->commonValidationService->isMedicalStaffValid($nguoi_thuc_hien)) {
                    $errorCode = $this->generateErrorCode('INFO_ERROR_NGUOI_THUC_HIEN_NOT_FOUND');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Người thực hiện không tồn tại',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'Người thực hiện không tồn tại trong danh mục NVYT: ' . $nguoi_thuc_hien
                    ]);
                }
            }
        }

        // Bổ sung kiểm tra bắt buộc phải có mã máy đối với những nhóm
        if (in_array($data->ma_nhom, config('qd130xml.xml3.service_groups_requiring_machine')) && empty($data->ma_may)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_GROUP_CODE_MA_MAY');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã máy',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã máy không được để trống đối với DVKT: ' . $this->serviceDisplay
            ]);
        }

        if (empty($data->ma_bac_si)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_MA_BAC_SI');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã bác sĩ',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã bác sĩ không được để trống'
            ]);
        } else {
            $ma_bac_si_array = explode(';', $data->ma_bac_si);
            foreach ($ma_bac_si_array as $ma_bac_si) {
                if (!$this->commonValidationService->isMedicalStaffValid($data->ma_bac_si)) {
                    $errorCode = $this->generateErrorCode('INFO_ERROR_MA_BAC_SI_NOT_FOUND');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Mã bác sĩ không tồn tại',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'Mã bác sĩ không tồn tại trong danh mục NVYT: ' . $ma_bac_si
                    ]);
                }
            }
        }
        
        // Check for serviceGroupsRequiringAnesthesia
        if (in_array($data->ma_nhom, $this->serviceGroupsRequiringAnesthesia)) {
            // Kiểm tra số lượng không được lớn hơn 1
            if ($data->so_luong > 1) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_SO_LUONG_INVALID');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Số lượng không hợp lệ',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Số lượng không được > 1 cho dịch vụ: ' . $this->serviceDisplay
                ]);
            }
            // Kiểm tra phương pháp vô cảm
            if (empty($data->pp_vo_cam)) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_PP_VO_CAM_EMPTY');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Thiếu phương pháp vô cảm',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Phương pháp vô cảm không được để trống đối với dịch vụ: ' . $this->serviceDisplay
                ]);
            } elseif (!in_array($data->pp_vo_cam, config('qd130xml.anesthesia_code'))) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_PP_VO_CAM_INVALID');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Phương pháp vô cảm không hợp lệ',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Phương pháp vô cảm không hợp lệ. Giá trị phải thuộc ' . implode(',', config('qd130xml.anesthesia_code')) . ' : ' . $data->pp_vo_cam
                ]);
            }
        }

        // Check for serviceGroupsRequiringAnesthesia
        if (in_array($data->ma_nhom, $this->serviceGroupsRequiringAnesthesia)) {
            if (empty($data->pp_vo_cam)) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_PP_VO_CAM_EMPTY');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Thiếu phương pháp vô cảm',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Phương pháp vô cảm không được để trống đối với dịch vụ: ' . $this->serviceDisplay
                ]);
            } elseif (!in_array($data->pp_vo_cam, config('qd130xml.anesthesia_code'))) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_PP_VO_CAM_INVALID');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Phương pháp vô cảm không hợp lệ',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Phương pháp vô cảm không hợp lệ. Giá trị phải thuộc [1, 2, 3, 4]: ' . $data->pp_vo_cam
                ]);
            }
        }

        // Check ma_benh
        if (!empty($data->ma_benh)) {
            if (strlen($data->ma_benh) > 100) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_MA_BENH_TOO_LONG');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã bệnh quá dài',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã bệnh: ' . $data->ma_benh . ' vượt quá 100 kí tự'
                ]);
            }
            $ma_benh_array = explode(';', $data->ma_benh);
            foreach ($ma_benh_array as $ma_benh) {
                // Check ma_benh in array
                if (!Icd10Category::where('icd_code', $ma_benh)->where('is_active', true)->exists()) {
                    $existIcdYhct = IcdYhctCategory::where('icd_code', $ma_benh)->where('is_active', true)->first();
                    if ($existIcdYhct) {
                        $errorCode = $this->generateErrorCode('INFO_ERROR_MA_BENH_IN_YHCT');
                        $errors->push((object)[
                            'error_code' => $errorCode,
                            'error_name' => 'Mã bệnh thuộc bệnh YHCT',
                            'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                            'description' => 'Mã bệnh: ' . $ma_benh . ' thuộc DM YHCT tương đương với: ' . $existIcdYhct->icd10_code . ' trong DM ICD10'
                        ]);
                    } else {
                        $errorCode = $this->generateErrorCode('INFO_ERROR_MA_BENH_INVALID');
                        $errors->push((object)[
                            'error_code' => $errorCode,
                            'error_name' => 'Mã bệnh không tồn tại trong DM ICD10',
                            'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                            'description' => 'Mã bệnh không tồn tại trong danh mục ICD10: ' . $ma_benh
                        ]);
                    }
                }
            }
        }

        // Check ma_benh_yhct
        if (!empty($data->ma_benh_yhct)) {
            if (strlen($data->ma_benh_yhct) > 255) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_MA_BENH_YHCT_TOO_LONG');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã bệnh YHCT quá dài',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã bệnh YHCT vượt quá 255 kí tự: ' . $data->ma_benh_yhct
                ]);
            }
            $ma_benh_yhct_array = explode(';', $data->ma_benh_yhct);
            foreach ($ma_benh_yhct_array as $ma_benh_yhct) {
                if (!IcdYhctCategory::where('icd_code', $ma_benh_yhct)->where('is_active', true)->exists()) {
                    $errorCode = $this->generateErrorCode('INFO_ERROR_MA_BENH_YHCT_INVALID');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Mã bệnh không thuộc DM ICD YHCT',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'Mã bệnh không thuộc DM ICD YHCT: ' . $ma_benh_yhct
                    ]);
                }
            }
        }

        // Check ma_may
        if (!empty($data->ma_may)) {
            if (strlen($data->ma_may) > 1024) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_MA_MAY_TOO_LONG');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã máy quá dài',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã máy vượt quá 1024 kí tự: ' . $data->ma_may
                ]);
            } else {
                $maMay = trim($data->ma_may);

                // Regex 2 nhánh:
                // - Nhánh 1: PREFIX.[12].YYYYY.Z
                // - Nhánh 2: PREFIX.3[...].Z    (bỏ qua kiểm tra YYYYY)
                $prefixCharset = 'A-ZĐÁÀẠÂẦẬẨẤẪÃẢĂẰẶẲẮẴÈÉẸÊỀỆỂẾỄẺÍÌỊĨỈÒÓỌÔỒỘỔỐỖÕỎƠỜỢỞỚỠÙÚỤƯỪỰỬỨỮŨỦÝỲỴỶỸ';
                $pattern = '/^(?:'
                         . '(?P<prefix12>['.$prefixCharset.']{2,3})\.(?P<n12>[12])\.(?P<facility>\d{5})\.(?P<z>.+)'
                         . '|'
                         . '(?P<prefix3>['.$prefixCharset.']{2,3})\.(?P<n3>3[^\.\r\n]*)\.(?P<z3>.+)'
                         . ')$/u';

                if (!preg_match($pattern, $maMay, $m)) {
                    $errorCode = $this->generateErrorCode('INFO_ERROR_INVALID_MA_MAY_FORMAT');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Mã máy không đúng định dạng',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'Mã máy không đúng định dạng: ' . $maMay
                    ]);
                } else {
                    // Xác định n + facility (nếu có)
                    $n = isset($m['n12']) ? $m['n12'] : (isset($m['n3']) ? $m['n3'] : null);
                    $facility = isset($m['facility']) ? $m['facility'] : null;

                    // Chỉ kiểm tra facility khi n = 1|2
                    if ($n === '1' || $n === '2') {
                        // Đảm bảo đúng 5 số (regex đã bắt buộc), kiểm tra thêm theo config
                        if (!ctype_digit($facility) || strlen($facility) !== 5) {
                            $errorCode = $this->generateErrorCode('INFO_ERROR_INVALID_YYYYY');
                            $errors->push((object)[
                                'error_code' => $errorCode,
                                'error_name' => 'Mã cơ sở KBCB trong MA_MAY không hợp lệ',
                                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                                'description' => 'Mã cơ sở KBCB trong MA_MAY không hợp lệ: ' . $facility
                            ]);
                        } else {
                            $validFacilities = (array) config('organization.correct_facility_code', []);
                            if (!in_array($facility, $validFacilities, true)) {
                                $errorCode = $this->generateErrorCode('INFO_ERROR_INVALID_YYYYY_NOT_FOUND');
                                $errors->push((object)[
                                    'error_code' => $errorCode,
                                    'error_name' => 'Mã cơ sở KBCB trong MA_MAY không đúng',
                                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                                    'description' => 'Mã cơ sở KBCB trong MA_MAY: ' . $facility . ' không thuộc: ' . implode(',', $validFacilities)
                                ]);
                            }
                        }
                    }

                    // Kiểm tra tồn tại trong danh mục trang thiết bị theo MA_MAY đầy đủ
                    $existEquipment = EquipmentCatalog::where('ma_may', $maMay)->exists();
                    if (!$existEquipment) {
                        $errorCode = $this->generateErrorCode('INFO_ERROR_MA_MAY_NOT_FOUND');
                        $errors->push((object)[
                            'error_code' => $errorCode,
                            'error_name' => 'Mã máy không tồn tại trong danh mục trang thiết bị',
                            'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                            'description' => 'Mã máy không tồn tại: ' . $maMay
                        ]);
                    }
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
                'description' => 'Tỷ lệ TT BH của dịch vụ: ' . $this->serviceDisplay . '; không nằm trong khoảng 0-100: ' . $data->tyle_tt_bh
            ]);
        }

        // Check tyle_tt_dv value
        if (isset($data->tyle_tt_dv) && ($data->tyle_tt_dv < 0 || $data->tyle_tt_dv > 100)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_TYLE_TT_DV');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Tỷ lệ TT DV không nằm trong khoảng cho phép',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Tỷ lệ TT DV của dịch vụ: ' . $this->serviceDisplay . '; không nằm trong khoảng 0-100: ' . $data->tyle_tt_dv
            ]);
        }

        return $errors;
    }

    /**
     * Check for outpatient bed day errors
     *
     * @param Qd130Xml3 $data
     * @param int $maLoaiKcb
     * @return Collection
     */
    private function checkOutpatientBedDayErrors(Qd130Xml3 $data): Collection
    {
        $errors = collect();

        if (in_array($data->Qd130Xml1->ma_loai_kcb, $this->outpatientTypes) && in_array($data->ma_nhom, $this->bedGroupCodes)) {
            $errorCode = $this->generateErrorCode('OUTPATIENT_BED_DAY_ERROR');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Mã loại KCB không được chỉ định ngày giường',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'MA_LOAI_KCB là (Khám hoặc Điều trị ngoại trú) nhưng có chỉ định dịch vụ ngày giường'
            ]);
        }

        return $errors;
    }

    /**
     * Check for missing service or material code
     *
     * @param Qd130Xml3 $data
     * @return Collection
     */
    private function checkMissingServiceOrMaterial(Qd130Xml3 $data): Collection
    {
        $errors = collect();

        if (empty($data->ma_dich_vu) && empty($data->ma_vat_tu)) {
            $errorCode = $this->generateErrorCode('MISSING_SERVICE_OR_MATERIAL');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Mã dịch vụ và Mã vật tư rỗng',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'MA_DICH_VU và MA_VAT_TU rỗng'
            ]);
        }

        return $errors;
    }

    /**
     * Check for bed group code conditions
     *
     * @param Qd130Xml3 $data
     * @return Collection
     */
    private function checkBedGroupCodeConditions(Qd130Xml3 $data): Collection
    {
        $errors = collect();

        if (in_array($data->ma_nhom, $this->bedGroupCodes)) {
            // Kiểm tra định dạng ma_giuong (null-safe)
            $maGiuong = (string) ($data->ma_giuong ?? '');
            if (!preg_match($this->bedCodePattern, $maGiuong)) {
                $errorCode = $this->generateErrorCode('INVALID_BED_CODE_FORMAT');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã giường không đúng định dạng',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã giường: ' . $maGiuong . ' không đúng định dạng (ký tự đầu phải là H, T, C, K và 3 ký tự sau là số từ 0 đến 9)'
                ]);
            }

            // Chỉ kiểm tra trùng giường khi có đủ khoảng thời gian
            if (!empty($data->ngay_th_yl) && !empty($data->ngay_kq)) {
                $overlappingRecords = Qd130Xml3::where('ma_khoa', $data->ma_khoa)
                    ->where('ma_giuong', $maGiuong)
                    ->where('id', '!=', $data->id)
                    ->where(function ($query) use ($data) {
                        $query->where(function ($q) use ($data) {
                            $q->where('ngay_th_yl', '<', $data->ngay_kq)
                              ->where('ngay_kq', '>', $data->ngay_th_yl);
                        });
                    })
                    ->get();

                if ($overlappingRecords->isNotEmpty()) {
                    foreach ($overlappingRecords as $overlappingRecord) {
                        $errorCode = $this->generateErrorCode('OVERLAPPING_BED_USAGE');
                        $errors->push((object)[
                            'error_code' => $errorCode,
                            'error_name' => 'Giường sử dụng trùng lặp',
                            'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                            'description' => 'Giường ' . $maGiuong . ' tại khoa ' . $data->ma_khoa .
                                ' sử dụng trùng lặp trong khoảng thời gian từ ' .
                                strtodatetime($data->ngay_th_yl) . ' đến ' . strtodatetime($data->ngay_kq) .
                                '. Trùng lặp với hồ sơ có mã: ' . $overlappingRecord->ma_lk .
                                ' (NGAY_TH_YL: ' . strtodatetime($overlappingRecord->ngay_th_yl) .
                                ', NGAY_KQ: ' . strtodatetime($overlappingRecord->ngay_kq) . ')'
                        ]);
                    }
                }
            }

            // Bỏ kiểm tra nếu khoa thuộc danh sách loại trừ
            if (in_array($data->ma_khoa, $this->excludedBedDepartments, true)) {
                return $errors;
            }

            // Chỉ kiểm tra tiền tố khoa khi MA_DICH_VU hợp lệ và có first component khác rỗng
            $maDichVu = (string) ($data->ma_dich_vu ?? '');
            if ($maDichVu !== '') {
                $parts = explode('.', $maDichVu, 2);
                $firstComponent = trim($parts[0] ?? '');

                if ($firstComponent !== '' && strpos($data->ma_khoa, $firstComponent) !== 0) {
                    $errorCode = $this->generateErrorCode('INVALID_DEPARTMENT_CODE');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Khoa chỉ định giường không đúng quy định',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'Khoa chỉ định: ' . $data->ma_khoa . '; Mã giường: ' . $maDichVu
                    ]);
                }
            }
        }

        return $errors;
    }

    /**
     * Check order time
     *
     * @param Qd130Xml3 $data
     * @return Collection
     */
    private function checkOrderTime(Qd130Xml3 $data): Collection
    {
        $errors = collect();

        if ($data->Qd130Xml1) {
            $ngayVao = $data->Qd130Xml1->ngay_vao;
            $ngayRa = $data->Qd130Xml1->ngay_ra;
            $ngayYLenh = $data->ngay_yl;

            if ($ngayYLenh < $ngayVao || $ngayYLenh > $ngayRa) {
                $errorCode = $this->generateErrorCode('INVALID_ORDER_TIME');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Thời gian chỉ định không hợp lệ',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Thời gian y lệnh không nằm trong khoảng thời gian vào (' . strtodatetime($ngayVao) . ') và ra (' . strtodatetime($ngayRa) . ')'
                ]);
            }
        }

        return $errors;
    }

    /**
     * Check for T_TRANTT and T_BHTT conditions
     *
     * @param Qd130Xml3 $data
     * @return Collection
     */
    private function checkTTranttAndTBhtt(Qd130Xml3 $data): Collection
    {
        $errors = collect();

        if (!empty($data->t_trantt) && $data->t_trantt < $data->t_bhtt) {
            $errorCode = $this->generateErrorCode('INVALID_T_TRANTT_T_BHTT');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Tiền bảo hiểm thanh toán lớn hơn trần thanh toán',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Tiền trần thanh toán: ' . $data->t_trantt .' nhỏ hơn tiền BH thanh toán: ' . $data->t_bhtt
            ]);
        }

        return $errors;
    }

    /**
     * Check for bed day quantity
     *
     * @param Qd130Xml3 $data
     * @return Collection
     */
    private function checkBedDayQuantity(Qd130Xml3 $data): Collection
    {
        $errors = collect();

        if (in_array($data->ma_nhom, $this->bedGroupCodes) && $data->so_luong > 1) {
            $errorCode = $this->generateErrorCode('BED_DAY_QUANTITY_ERROR');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Chỉ định ngày giường > 1',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Số lượng ngày giường > 1. Ngày y lệnh: ' . strtodatetime($data->ngay_yl)
            ]);
        }

        return $errors;
    }

    /**
     * Check NGAY_KQ against NGAY_VAO and NGAY_RA
     *
     * @param Qd130Xml3 $data
     * @return Collection
     */
    private function checkNgayKq(Qd130Xml3 $data): Collection
    {
        $errors = collect();

        // Nếu MA_NHOM thuộc materialGroupCodes, bỏ qua kiểm tra
        if (in_array($data->ma_nhom, $this->materialGroupCodes)) {
            return $errors;
        }

        if (!empty($data->ngay_kq) && $data->Qd130Xml1) {
            $ngayKq = $data->ngay_kq;
            $ngayVao = $data->Qd130Xml1->ngay_vao;
            $ngayRa = $data->Qd130Xml1->ngay_ra;

            if ($ngayKq < $ngayVao || $ngayKq > $ngayRa) {
                $errorCode = $this->generateErrorCode('INVALID_NGAY_KQ');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Ngày trả kết quả không hợp lệ',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Ngày trả kết quả (NGAY_KQ) không nằm trong khoảng thời gian vào (' . strtodatetime($ngayVao) . ') và ra (' . strtodatetime($ngayRa) . ')'
                ]);
            }

            if ($data->ngay_kq < $data->ngay_yl) {
                $errorCode = $this->generateErrorCode('INVALID_NGAY_KQ_LESSTHEN_NGAY_YL');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Ngày kết quả nhỏ hơn ngày y lệnh',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Ngày kết quả của: ' . $this->serviceDisplay . '. Không được nhỏ hơn ngày y lệnh: ' . strtodatetime($data->ngay_kq) . ' < ' . strtodatetime($data->ngay_yl)
                ]);
            }
        }

        return $errors;
    }

    /**
     * Check if medical supply (ma_vat_tu) has tt_thau that is not in the MedicalSupplyCatalog
     *
     * @param Qd130Xml3 $data
     * @return Collection
     */
    private function checkMedicalSupplyCatalog(Qd130Xml3 $data): Collection
    {
        $errors = collect();

        if (in_array($data->ma_nhom, $this->materialGroupCodes)) {

            // Kiểm tra nếu ma_vat_tu hoặc ten_vat_tu rỗng
            if (empty($data->ma_vat_tu)) {
                $errorCode = $this->generateErrorCode('MISSING_MATERIAL_CODE');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã vật tư rỗng',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Không được để trống trường mã vật tư. Ngày y lệnh: ' . strtodatetime($data->ngay_yl)
                ]);
            }

            if (empty($data->ten_vat_tu)) {
                $errorCode = $this->generateErrorCode('MISSING_MATERIAL_NAME');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Tên vật tư rỗng',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Không được để trống trường tên vật tư. Ngày y lệnh: ' . strtodatetime($data->ngay_yl)
                ]);
            }

            // Kiểm tra định dạng tt_thau trong XML3 (mã thầu;gói thầu;nhóm thầu;năm thầu)
            $parts = explode(';', $data->tt_thau);
            if (count($parts) < 4 || in_array('', $parts, true)) {
                $errorCode = $this->generateErrorCode('INVALID_TT_THAU_FORMAT');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Thông tin thầu không đúng định dạng',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã vật tư: ' . $data->ma_vat_tu . '; có TT_THAU không đúng định dạng: ' . $data->tt_thau
                ]);
            } else {
                list($dataDecision, $dataPackage, $dataGroup, $dataYear) = $parts;

                // Lấy các bản ghi từ MedicalSupplyCatalog có ma_vat_tu khớp với ma_vat_tu trong $data
                $medicalSupplies = MedicalSupplyCatalog::where('ma_vat_tu', $data->ma_vat_tu)->get();
                $found = false;

                foreach ($medicalSupplies as $supply) {
                    $supplyParts = explode(';', $supply->tt_thau);

                    if (count($supplyParts) >= 4) {
                        $supplyDecision = $supplyParts[0];
                        $supplyPackage = $supplyParts[1];
                        $supplyGroup = $supplyParts[2];
                        $supplyYear = $supplyParts[3];

                        if ($supplyDecision == $dataDecision && $supplyPackage == $dataPackage && $supplyGroup == $dataGroup && $supplyYear == $dataYear) {
                            $found = true;

                            if ($data->don_gia_bh > $supply->don_gia_bh) {
                                $errorCode = $this->generateErrorCode('EXCEEDS_APPROVED_PRICE');
                                $errors->push((object)[
                                    'error_code' => $errorCode,
                                    'error_name' => 'Giá vật tư cao hơn giá được phê duyệt',
                                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                                    'description' => 'Mã VTYT: ' . $data->ma_vat_tu . '; Có giá: ' . $data->don_gia_bh . '; Giá phê duyệt: ' . $supply->don_gia_bh
                                ]);
                            }

                            if ($data->ten_vat_tu != $supply->ten_vat_tu) {
                                $errorCode = $this->generateErrorCode('INVALID_MATERIAL_NAME');
                                $errors->push((object)[
                                    'error_code' => $errorCode,
                                    'error_name' => 'Tên vật tư không khớp với danh mục phê duyệt',
                                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                                    'description' => 'Mã VTYT: ' . $data->ma_vat_tu . ' có tên: ' . formatDescription($data->ten_vat_tu) . '; Tên phê duyệt: ' . $supply->ten_vat_tu
                                ]);
                            }

                            break;
                        }
                    }
                }

                if (!$found) {
                    $errorCode = $this->generateErrorCode('MEDICAL_SUPPLY_NOT_IN_CATALOG');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'VTYT nằm ngoài danh mục BHYT',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'VTYT có mã: ' . $data->ma_vat_tu . ' với TT_THAU: ' . formatDescription($data->tt_thau) . ' không có trong danh mục BHYT'
                    ]);
                }
            }

            // Bổ sung kiểm tra với ma_dich_vu
            if (!empty($data->ma_dich_vu)) {
                $matchedServices = Qd130Xml3::where('ma_dich_vu', $data->ma_dich_vu)
                ->where('ma_lk', $data->ma_lk)
                ->where('ma_vat_tu', '')
                ->get();

                $dataDate = substr($data->ngay_yl, 0, 8);
                $hasMatchingDate = false;

                foreach ($matchedServices as $matchedService) {
                    $matchedDate = substr($matchedService->ngay_yl, 0, 8);

                    if ($dataDate == $matchedDate) {
                        $hasMatchingDate = true;
                        break; // Dừng vòng lặp khi tìm thấy ngày khớp
                    }
                }

                if (!$hasMatchingDate) {
                    $errorCode = $this->generateErrorCode('DATE_MISMATCH');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Ngày y lệnh không khớp',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'Mã dịch vụ: ' . $data->ma_dich_vu . '; Không tìm thấy ngày y lệnh khớp với vật tư: ' . $data->ma_vat_tu
                    ]);
                }
            }
        }

        return $errors;
    }

    /**
     * Check if material group code is excluded
     *
     * @param Qd130Xml3 $data
     * @return Collection
     */
    private function checkExcludedMaterialGroupCode(Qd130Xml3 $data): Collection
    {
        $errors = collect();

        if (in_array($data->ma_nhom, $this->excludedMaterialGroupCodes)) {
            $errorCode = $this->generateErrorCode('EXCLUDED_MATERIAL_GROUP_CODE');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Vật tư nằm ngoài danh mục hoặc vật tư thay thế',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã vật tư: ' . $data->ma_vat_tu . ' nằm ngoài danh mục hoặc là vật tư thay thế'
            ]);
        }

        return $errors;
    }

    private function checkMedicalService(Qd130Xml3 $data): Collection
    {
        $errors = collect();

        if ( !in_array($data->ma_nhom, $this->materialGroupCodes) &&
            !in_array($data->ma_nhom, $this->bedGroupCodes) &&
            !in_array($data->ma_nhom, $this->examinationGroupCodes) &&
            !in_array($data->ma_nhom, $this->transportGroupCodes) ) {

            $serviceExists = ServiceCatalog::where('ma_dich_vu', $data->ma_dich_vu)->exists();

            if (!$serviceExists) {
                $errorCode = $this->generateErrorCode('INVALID_MA_DICH_VU');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã dịch vụ không tồn tại',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Dịch vụ không tồn tại trong DM DVKT: ' . $data->ma_dich_vu
                ]);
            } else {
                $ngayVao = \DateTime::createFromFormat('YmdHi', $data->Qd130Xml1->ngay_vao)->format('Ymd');

                $validServiceExists = ServiceCatalog::where('ma_dich_vu', $data->ma_dich_vu)
                ->where('tu_ngay', '<=', $ngayVao)
                ->where(function ($query) use ($ngayVao) {
                    $query->where('den_ngay', '>=', $ngayVao)
                          ->orWhereNull('den_ngay');
                })->get();

                if ($validServiceExists->isEmpty()) {
                    $errorCode = $this->generateErrorCode('INVALID_MA_DICH_VU_NGAY_VAO');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Không tìm thấy dịch vụ hợp lệ',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'Không tìm thấy dịch vụ hợp lệ cho mã dịch vụ: ' . $data->ma_dich_vu . ' với ngày vào: ' . strtodatetime($data->Qd130Xml1->ngay_vao)
                    ]);
                } else {
                    // Kiểm tra giá: Nếu tất cả các don_gia đều nhỏ hơn $data->don_gia_bh thì báo lỗi
                    $allPricesLower = $validServiceExists->every(function ($service) use ($data) {
                        return $service->don_gia < $data->don_gia_bh;
                    });

                    if ($allPricesLower) {
                        $errorCode = $this->generateErrorCode('INVALID_APPROVED_DON_GIA_BH');
                        $errors->push((object)[
                            'error_code' => $errorCode,
                            'error_name' => 'Giá DVKT cao hơn giá được phê duyệt',
                            'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                            'description' => 'Mã DVKT: ' . $data->ma_dich_vu . '; Có giá: ' . $data->don_gia_bh . '; Giá phê duyệt nhỏ hơn: ' . $validServiceExists->pluck('don_gia')->implode(', ')
                        ]);
                    }

                    // Kiểm tra tên: Tạm thời chưa xử lý vì liên quan đến tên ánh xạ trên cổng BHXH
                    // if ($data->ten_dich_vu != $validServiceExists->ten_dich_vu) {
                    //     $errorCode = $this->generateErrorCode('INVALID_TEN_DICH_VU');
                    //     $errors->push((object)[
                    //         'error_code' => $errorCode,
                    //         'error_name' => 'Tên dịch vụ kỹ thuật khác tên được phê duyệt',
                    //         'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    //         'description' => 'Mã DVKT: ' . $data->ma_dich_vu . ' có tên: ' . 
                    //                 formatDescription($data->ten_dich_vu) . '; Tên phê duyệt: ' . $validServiceExists->ten_dich_vu
                    //     ]);
                    // }
                }
            }
        }

        return $errors;
    }

    private function checkValidMakhoaReq(Qd130Xml3 $data): Collection
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

            if ($is_ma_doituong_kcb_trai_tuyen && in_array($data->ma_khoa, config('qd130xml.general.ma_khoa_kkb'))) {
                $errorCode = $this->generateErrorCode('MA_KHOA_REQ_INVALID');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Khoa chỉ định dịch vụ không hợp lệ',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Khoa khám bệnh: ' . implode(',', config('qd130xml.general.ma_khoa_kkb')) . '; không được chỉ định: ' . $this->serviceDisplay . '; Đối với BN Nội trú - Trái tuyến'
                ]);
            }
        }

        return $errors;
    }

    //Bổ sung kiểm tra mã nhóm là pttt mà trùng ma_bac_si + ngay_yl đã có trong cơ sở dữ liệu thì cảnh báo
    private function checkServiceGroupPttt(Qd130Xml3 $data): Collection
    {
        $errors = collect();

        if (in_array($data->ma_nhom, config('qd130xml.xml3.service_groups_pttt'))) {
            $serviceExists = Qd130Xml3::where('ma_lk', '!=', $data->ma_lk)
            ->where('ma_bac_si', $data->ma_bac_si)
            ->where('ngay_yl', $data->ngay_yl)
            ->where('ma_nhom', $data->ma_nhom)
            ->first();

            if ($serviceExists) {
                $errorCode = $this->generateErrorCode('SERVICE_GROUP_PTTT_DUPLICATE');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã nhóm là pttt trùng y lệnh',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã nhóm là pttt trùng: ' . $serviceExists->ma_nhom . '; Mã bác sĩ: ' . $serviceExists->ma_bac_si . '; Ngày y lệnh: ' . $serviceExists->ngay_yl . '; Ma_lk: ' . $serviceExists->ma_lk
                ]);
            }
        }

        return $errors;
    }
}
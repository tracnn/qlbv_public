<?php

namespace App\Services;

use App\Models\BHYT\Xml3176Xml3;
use App\Models\BHYT\MedicalSupplyCatalog;
use App\Models\BHYT\Icd10Category;
use App\Models\BHYT\IcdYhctCategory;
use App\Models\BHYT\ServiceCatalog;
use App\Models\BHYT\EquipmentCatalog;
use App\Services\Xml3176\Support\Xml3176DateHelper;
use App\Services\Xml3176\Support\TyLeComparator;
use App\Services\Xml3176\Support\ServiceOverlapChecker;
use App\Services\Xml3176\Support\MaDvktStructure;
use App\Services\Xml3176\Support\TienTeCalculator;
use App\Services\Xml3176\Support\TextNormalizer;
use Illuminate\Support\Collection;

class Xml3176Xml3Checker
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

    public function __construct(Xml3176ErrorService $xmlErrorService, CommonValidationService $commonValidationService)
    {
        $this->xmlErrorService = $xmlErrorService;
        $this->commonValidationService = $commonValidationService;
        $this->setConditions();
    }

    protected function setConditions()
    {
        $this->xmlType = 'XML3';
        $this->prefix = $this->xmlType . '_';
        
        $this->materialGroupCodes = config('xml3176.material_group_code');
        $this->bedGroupCodes = config('xml3176.bed_group_code');
        $this->excludedBedDepartments = config('organization.exclude_department');
        $this->outpatientTypes = config('xml3176.treatment_type_outpatient');
        $this->examinationGroupCodes = config('xml3176.examination_group_code');
        $this->transportGroupCodes = config('xml3176.transport_group_code');
        $this->bedCodePattern = config('xml3176.bed_code_pattern');
        $this->excludedMaterialGroupCodes = config('xml3176.excluded_material_group_code');
        $this->groupCodeWithExecutor = config('xml3176.group_code_with_executor');
        $this->serviceGroupsRequiringAnesthesia = config('xml3176.service_groups_requiring_anesthesia');
    }

    protected function generateErrorCode(string $errorKey): string
    {
        return $this->prefix . $errorKey;
    }

    /** So ten phe duyet toi da liet ke trong mo ta loi */
    const TOI_DA_NEU_TEN = 3;

    /**
     * Gom ten phe duyet tu cac dong danh muc con hieu luc.
     *
     * Bo trung theo dang CHUAN HOA (hoa thuong, khoang trang) nhung giu chu GOC cua lan
     * xuat hien dau tien - de mo ta loi hien dung chu trong danh muc.
     *
     * @param \Illuminate\Support\Collection|array $dsDanhMuc cac dong ServiceCatalog
     * @return string[] da trim, bo rong, bo trung, giu thu tu
     */
    public static function tenPheDuyet($dsDanhMuc): array
    {
        $ten = [];
        $daCo = [];

        foreach ($dsDanhMuc as $d) {
            $t = trim((string) (is_object($d) ? $d->ten_dich_vu : $d));
            $khoa = TextNormalizer::chuan($t);

            if ($khoa !== '' && !isset($daCo[$khoa])) {
                $daCo[$khoa] = true;
                $ten[] = $t;
            }
        }

        return $ten;
    }

    /**
     * Ten khai co lech danh muc khong.
     *
     * MOT ma DVKT co the co NHIEU dong danh muc (nhieu dot phe duyet, nhieu quy trinh),
     * nen ten khai chi can trung MOT dong la dat. Ban cu so thang
     * $data->ten_dich_vu != $validServiceExists->ten_dich_vu trong khi $validServiceExists
     * la Collection - truy thuoc tinh tren Collection ra null nen MOI dong DVKT deu thanh
     * vi pham. Do la ly do quy tac nay tung bi chu thich tat.
     *
     * Ngu nghia nay thong nhat voi quy tac A_BHYT_SERVICE_NAME_MISMATCH ben order-check,
     * de hai noi khong cho hai ket luan khac nhau tren cung mot ho so.
     *
     * So dang CHUAN HOA qua TextNormalizer::chuan() (hoa thuong, khoang trang) - giong
     * INVALID_DRUG_NAME, INVALID_MATERIAL_NAME va A_BHYT_*_NAME_MISMATCH ben order-check.
     */
    public static function tenLechDanhMuc($tenKhai, array $tenPheDuyet): bool
    {
        $tenKhai = TextNormalizer::chuan($tenKhai);

        if ($tenKhai === '' || empty($tenPheDuyet)) {
            return false;   // thieu ten la viec cua quy tac khac; danh muc khong co ten thi khong co gi de so
        }

        foreach ($tenPheDuyet as $t) {
            if (TextNormalizer::chuan($t) === $tenKhai) {
                return false;
            }
        }

        return true;
    }

    /** Liet ke ten phe duyet trong mo ta, cat bot khi qua dai */
    public static function neuTenPheDuyet(array $ten): string
    {
        $chuoi = implode(', ', array_slice($ten, 0, self::TOI_DA_NEU_TEN));

        if (count($ten) > self::TOI_DA_NEU_TEN) {
            $chuoi .= ' …';
        }

        return $chuoi;
    }

    /**
     * Bon phan TT_THAU cua VTYT (quyet dinh; goi thau; nhom; nam) co khop danh muc khong.
     *
     * Bo phan biet hoa thuong qua TextNormalizer, thong nhat voi TT_THAU cua THUOC o XML2
     * (so bang SQL LIKE, von khong phan biet).
     *
     * So LONG (==) sau chuan hoa, CO CHU DICH: ban cu so == nen '01' va '1' la khop. Yeu
     * cau chi la bo phan biet hoa thuong - khong duoc am tham doi luon ngu nghia do.
     *
     * @param array $danhMuc 4 phan tach tu tt_thau cua dong danh muc
     * @param array $hoSo 4 phan tach tu tt_thau cua ho so
     */
    public static function ttThauKhop(array $danhMuc, array $hoSo): bool
    {
        for ($i = 0; $i < 4; $i++) {
            if (!array_key_exists($i, $danhMuc) || !array_key_exists($i, $hoSo)) {
                return false;
            }

            if (TextNormalizer::chuan($danhMuc[$i]) != TextNormalizer::chuan($hoSo[$i])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check Xml3176Xml3 Errors
     *
     * @param Xml3176Xml3 $data
     * @return void
     */
    public function checkErrors(Xml3176Xml3 $data): void
    {
        // Thực hiện kiểm tra lỗi
        $errors = collect();

        // Load related XML1
        $data->load('Xml3176Xml1');
        $this->serviceDisplay = !empty($data->ten_vat_tu) ? $data->ten_vat_tu : $data->ten_dich_vu;

        if ($data->Xml3176Xml1) {
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
        $errors = $errors->merge($this->checkServiceGroupPtttDuplicate($data)); // Kiểm tra dịch vụ kỹ thuật
        $errors = $errors->merge($this->checkOverlappingServiceExecution($data));
        $errors = $errors->merge($this->checkCauTrucMaDichVu($data));
        $errors = $errors->merge($this->checkCongThucTien($data));
        $errors = $errors->merge($this->checkTapGiaTri($data));

        if (config('xml3176.general.check_valid_department_req')) {
            $errors = $errors->merge($this->checkValidMakhoaReq($data)); // Kiểm tra tính hợp lệ của khoa chỉ định
        }

        $errors = $errors->merge($this->checkTimingAndExecutor($data));

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
     * @param Xml3176Xml3 $data
     * @return Collection
     */
    private function infoChecker(Xml3176Xml3 $data): Collection
    {
        $errors = collect();

        if (!empty($data->tt_thau)) {
            $ttThauParts = explode(';', $data->tt_thau);

            // Ensure we have exactly 4 parts (QĐ thầu, Gói thầu, Nhóm thầu, Năm thầu)
            if (count($ttThauParts) == 4) {
                [$qdThau, $goiThau, $nhomThau, $namThau] = $ttThauParts;

                // Load the patterns from the configuration
                $config = config('xml3176.xml3.tt_thau');

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

        // Bat buoc co ma may: can cu DANH MUC DVKT do BHXH ban hanh, khong con loc theo nhom.
        //
        // Danh muc chua nap -> LUI ve cach loc theo nhom cu. Neu doi thang theo danh muc ma
        // bang con rong thi khong ho so nao bi bao thieu ma may nua - mat sach canh bao ma
        // khong co dau hieu gi. Nap danh muc xong thi tu chuyen sang quy tac moi.
        if ($this->commonValidationService->coDanhMucDvktCanMaMay()) {
            $canMaMay = $this->commonValidationService->isDvktCanMaMay($data->ma_dich_vu);
        } else {
            $canMaMay = in_array($data->ma_nhom, config('xml3176.xml3.service_groups_requiring_machine'));
        }

        if ($canMaMay && empty($data->ma_may)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_GROUP_CODE_MA_MAY');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã máy',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã máy không được để trống đối với DVKT: ' . $this->serviceDisplay
                    . ' (mã ' . $data->ma_dich_vu . ')'
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
            } elseif (!in_array($data->pp_vo_cam, config('xml3176.anesthesia_code'))) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_PP_VO_CAM_INVALID');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Phương pháp vô cảm không hợp lệ',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Phương pháp vô cảm không hợp lệ. Giá trị phải thuộc ' . implode(',', config('xml3176.anesthesia_code')) . ' : ' . $data->pp_vo_cam
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
            } elseif (!in_array($data->pp_vo_cam, config('xml3176.anesthesia_code'))) {
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
                // Loại bỏ chuỗi ký tự dạng [...] nếu có
                $maMay = trim(preg_replace('/\[.*?\]/', '', $maMay));
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
     * @param Xml3176Xml3 $data
     * @param int $maLoaiKcb
     * @return Collection
     */
    private function checkOutpatientBedDayErrors(Xml3176Xml3 $data): Collection
    {
        $errors = collect();

        if (in_array($data->Xml3176Xml1->ma_loai_kcb, $this->outpatientTypes) && in_array($data->ma_nhom, $this->bedGroupCodes)) {
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
     * @param Xml3176Xml3 $data
     * @return Collection
     */
    private function checkMissingServiceOrMaterial(Xml3176Xml3 $data): Collection
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
     * @param Xml3176Xml3 $data
     * @return Collection
     */
    private function checkBedGroupCodeConditions(Xml3176Xml3 $data): Collection
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
                $overlappingRecords = Xml3176Xml3::where('ma_khoa', $data->ma_khoa)
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
     * @param Xml3176Xml3 $data
     * @return Collection
     */
    private function checkOrderTime(Xml3176Xml3 $data): Collection
    {
        $errors = collect();

        if ($data->Xml3176Xml1) {
            $ngayVao = $data->Xml3176Xml1->ngay_vao;
            $ngayRa = $data->Xml3176Xml1->ngay_ra;
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
     * @param Xml3176Xml3 $data
     * @return Collection
     */
    private function checkTTranttAndTBhtt(Xml3176Xml3 $data): Collection
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
     * @param Xml3176Xml3 $data
     * @return Collection
     */
    private function checkBedDayQuantity(Xml3176Xml3 $data): Collection
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
     * @param Xml3176Xml3 $data
     * @return Collection
     */
    private function checkNgayKq(Xml3176Xml3 $data): Collection
    {
        $errors = collect();

        // Nếu MA_NHOM thuộc materialGroupCodes, bỏ qua kiểm tra
        if (in_array($data->ma_nhom, $this->materialGroupCodes)) {
            return $errors;
        }

        if (!empty($data->ngay_kq) && $data->Xml3176Xml1) {
            $ngayKq = $data->ngay_kq;
            $ngayVao = $data->Xml3176Xml1->ngay_vao;
            $ngayRa = $data->Xml3176Xml1->ngay_ra;

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
     * @param Xml3176Xml3 $data
     * @return Collection
     */
    private function checkMedicalSupplyCatalog(Xml3176Xml3 $data): Collection
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
                // Danh muc VTYT cap theo CO SO; dong khong gan ma co so dung chung.
                $maCskcb = $data->Xml3176Xml1 ? $data->Xml3176Xml1->ma_cskcb : null;
                $medicalSupplies = MedicalSupplyCatalog::cuaCoSo($maCskcb)->where('ma_vat_tu', $data->ma_vat_tu)->get();
                $found = false;

                foreach ($medicalSupplies as $supply) {
                    $supplyParts = explode(';', $supply->tt_thau);

                    if (count($supplyParts) >= 4) {
                        $supplyDecision = $supplyParts[0];
                        $supplyPackage = $supplyParts[1];
                        $supplyGroup = $supplyParts[2];
                        $supplyYear = $supplyParts[3];

                        if (self::ttThauKhop(
                            [$supplyDecision, $supplyPackage, $supplyGroup, $supplyYear],
                            [$dataDecision, $dataPackage, $dataGroup, $dataYear]
                        )) {
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

                            // So dang chuan hoa (hoa thuong, khoang trang); mo ta loi van giu chu goc.
                            if (!TextNormalizer::bang($data->ten_vat_tu, $supply->ten_vat_tu)) {
                                $errorCode = $this->generateErrorCode('INVALID_MATERIAL_NAME');
                                $errors->push((object)[
                                    'error_code' => $errorCode,
                                    'error_name' => 'Tên vật tư không khớp với danh mục phê duyệt',
                                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                                    'description' => 'Mã VTYT: ' . $data->ma_vat_tu . ' có tên: ' . formatDescription($data->ten_vat_tu) . '; Tên phê duyệt: ' . $supply->ten_vat_tu
                                ]);
                            }

                            // Đối chiếu tỷ lệ thanh toán BHYT với tỷ lệ duyệt trong danh mục (Thay đổi tỷ lệ TT)
                            $tyleEps = (float) config('xml3176.xml3.tyle_epsilon', 0.01);
                            if (TyLeComparator::lech($data->tyle_tt_bh, $supply->tyle_tt_bh, $tyleEps)) {
                                $errorCode = $this->generateErrorCode('INVALID_APPROVED_TYLE_TT_BH');
                                $errors->push((object)[
                                    'error_code' => $errorCode,
                                    'error_name' => 'Tỷ lệ thanh toán BHYT của VTYT không khớp tỷ lệ duyệt',
                                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                                    'description' => 'Mã VTYT: ' . $data->ma_vat_tu . '; Tỷ lệ TT BH trong hồ sơ: ' . $data->tyle_tt_bh . '; Tỷ lệ duyệt: ' . $supply->tyle_tt_bh
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
                $matchedServices = Xml3176Xml3::where('ma_dich_vu', $data->ma_dich_vu)
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
     * @param Xml3176Xml3 $data
     * @return Collection
     */
    private function checkExcludedMaterialGroupCode(Xml3176Xml3 $data): Collection
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

    private function checkMedicalService(Xml3176Xml3 $data): Collection
    {
        $errors = collect();

        if ( !in_array($data->ma_nhom, $this->materialGroupCodes) &&
            !in_array($data->ma_nhom, $this->bedGroupCodes) &&
            !in_array($data->ma_nhom, $this->examinationGroupCodes) &&
            !in_array($data->ma_nhom, $this->transportGroupCodes) ) {

            // Danh muc DVKT cap theo CO SO; dong khong gan ma co so dung chung.
            $maCskcb = $data->Xml3176Xml1 ? $data->Xml3176Xml1->ma_cskcb : null;
            $serviceExists = ServiceCatalog::cuaCoSo($maCskcb)->where('ma_dich_vu', $data->ma_dich_vu)->exists();

            if (!$serviceExists) {
                $errorCode = $this->generateErrorCode('INVALID_MA_DICH_VU');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã dịch vụ không tồn tại',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Dịch vụ không tồn tại trong DM DVKT: ' . $data->ma_dich_vu
                ]);
            } else {
                $ngayVao = \DateTime::createFromFormat('YmdHi', $data->Xml3176Xml1->ngay_vao)->format('Ymd');

                $validServiceExists = ServiceCatalog::cuaCoSo($maCskcb)->where('ma_dich_vu', $data->ma_dich_vu)
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
                        'description' => 'Không tìm thấy dịch vụ hợp lệ cho mã dịch vụ: ' . $data->ma_dich_vu . ' với ngày vào: ' . strtodatetime($data->Xml3176Xml1->ngay_vao)
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

                    // Kiểm tra tên DVKT so với danh mục phê duyệt còn hiệu lực.
                    $tenPheDuyet = self::tenPheDuyet($validServiceExists);

                    if (self::tenLechDanhMuc($data->ten_dich_vu, $tenPheDuyet)) {
                        $errorCode = $this->generateErrorCode('INVALID_TEN_DICH_VU');
                        $errors->push((object)[
                            'error_code' => $errorCode,
                            'error_name' => 'Tên dịch vụ kỹ thuật khác tên được phê duyệt',
                            'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                            'description' => 'Mã DVKT: ' . $data->ma_dich_vu . ' có tên: '
                                    . formatDescription($data->ten_dich_vu)
                                    . '; Tên phê duyệt: ' . self::neuTenPheDuyet($tenPheDuyet)
                        ]);
                    }
                }
            }
        }

        return $errors;
    }

    private function checkValidMakhoaReq(Xml3176Xml3 $data): Collection
    {
        $errors = collect();

        if (in_array($data->Xml3176Xml1->ma_loai_kcb, config('xml3176.treatment_type_inpatient'))) {

            $is_ma_doituong_kcb_trai_tuyen = false;
            foreach (config('xml3176.xml1.ma_doituong_kcb_trai_tuyen') as $ma_doituong) {
                if (strpos($data->Xml3176Xml1->ma_doituong_kcb, (string)$ma_doituong) === 0) {
                    $is_ma_doituong_kcb_trai_tuyen = true;
                    break;
                }
            }

            if ($is_ma_doituong_kcb_trai_tuyen && in_array($data->ma_khoa, config('xml3176.general.ma_khoa_kkb'))) {
                $errorCode = $this->generateErrorCode('MA_KHOA_REQ_INVALID');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Khoa chỉ định dịch vụ không hợp lệ',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Khoa khám bệnh: ' . implode(',', config('xml3176.general.ma_khoa_kkb')) . '; không được chỉ định: ' . $this->serviceDisplay . '; Đối với BN Nội trú - Trái tuyến'
                ]);
            }
        }

        return $errors;
    }

    /**
     * Một BỆNH NHÂN không thể trải qua hai dịch vụ PTTT chồng thời gian thực hiện.
     * Chỉ soi trong CÙNG hồ sơ (ma_lk) và chỉ các nhóm cấu hình (mặc định PTTT 8,18) —
     * xét nghiệm/CĐHA ghi cùng khung giờ là bình thường nên không xét.
     *
     * Mỗi cặp chồng nhau chỉ báo MỘT lần (chỉ so với dòng có id lớn hơn).
     */
    private function checkOverlappingServiceExecution(Xml3176Xml3 $data): Collection
    {
        $errors = collect();

        $groups = (array) config('xml3176.xml3.overlap_execution_groups', []);
        if (empty($groups) || !in_array($data->ma_nhom, $groups)) {
            return $errors;
        }

        if (empty($data->ngay_th_yl) || empty($data->ngay_kq)) {
            return $errors; // guard: thiếu mốc thời gian
        }

        $others = Xml3176Xml3::where('ma_lk', $data->ma_lk)
            ->whereIn('ma_nhom', $groups)
            ->where('id', '>', $data->id)
            ->whereNotNull('ngay_th_yl')
            ->whereNotNull('ngay_kq')
            ->get();

        foreach ($others as $other) {
            if (!ServiceOverlapChecker::chongNhau($data->ngay_th_yl, $data->ngay_kq, $other->ngay_th_yl, $other->ngay_kq)) {
                continue;
            }

            $errorCode = $this->generateErrorCode('OVERLAPPING_SERVICE_EXECUTION');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thời gian thực hiện trùng với dịch vụ khác',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Dịch vụ ' . $data->ma_dich_vu . ' (' . strtodatetime($data->ngay_th_yl)
                    . ' - ' . strtodatetime($data->ngay_kq) . ') chồng thời gian thực hiện với dịch vụ '
                    . $other->ma_dich_vu . ' (' . strtodatetime($other->ngay_th_yl) . ' - '
                    . strtodatetime($other->ngay_kq) . ') trong cùng hồ sơ.'
            ]);
        }

        return $errors;
    }

    /**
     * Cau truc ma dich vu theo truong MA_PTTT_QT cua chuan du lieu dau ra (QD 130,
     * sua doi theo QD 4750).
     *
     * Ma dich vu rong thi im lang: da co MISSING_SERVICE_OR_MATERIAL lo viec do.
     */
    private function checkCauTrucMaDichVu(Xml3176Xml3 $data): Collection
    {
        $errors = collect();
        $ma = trim((string) $data->ma_dich_vu);

        if ($ma === '') {
            return $errors;
        }

        // Hau to '_TB': DVKT da chi dinh nhung khong the tiep tuc thuc hien
        // (khoan 3 Dieu 7 TT 39/2018/TT-BYT) => DON_GIA_BH = 0 va DON_GIA_BV = 0.
        if (MaDvktStructure::laKhongThucHien($ma)
            && ((float) $data->don_gia_bh != 0 || (float) $data->don_gia_bv != 0)) {
            $errorCode = $this->generateErrorCode('MA_DICH_VU_TB_CO_DON_GIA');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'DVKT không thực hiện được nhưng vẫn có đơn giá',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã ' . $ma . ' có hậu tố _TB (chỉ định nhưng không thực hiện được) '
                    . 'nên đơn giá BH và đơn giá BV phải bằng 0. Hiện đơn giá BH: '
                    . number_format((float) $data->don_gia_bh)
                    . ', đơn giá BV: ' . number_format((float) $data->don_gia_bv),
            ]);
        }

        // 04 ky tu cuoi la '0000': DVKT chua duoc quy dinh muc gia => DON_GIA_BH = 0.
        if (MaDvktStructure::laChuaCoGia($ma) && (float) $data->don_gia_bh != 0) {
            $errorCode = $this->generateErrorCode('MA_DICH_VU_CHUA_CO_GIA_CO_DON_GIA_BH');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'DVKT chưa có mức giá nhưng vẫn có đơn giá BH',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã ' . $ma . ' kết thúc bằng 0000 (chưa được quy định mức giá) '
                    . 'nên đơn giá BH phải bằng 0. Hiện là: ' . number_format((float) $data->don_gia_bh),
            ]);
        }

        // Chuan chi dinh nghia hai hau to: '_TB' va '_GT' (gay te).
        $hauTo = MaDvktStructure::hauTo($ma);
        if ($hauTo !== '' && !in_array($hauTo, ['TB', 'GT'], true)) {
            $errorCode = $this->generateErrorCode('MA_DICH_VU_HAU_TO_LA');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Hậu tố mã dịch vụ không hợp lệ',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã ' . $ma . ' có hậu tố _' . $hauTo
                    . '. Chuẩn chỉ quy định hai hậu tố: _TB và _GT',
            ]);
        }

        // Van chuyen nguoi benh: VC.XXXXX.
        if (MaDvktStructure::laVanChuyen($ma)) {
            if (empty($data->ma_xang_dau)) {
                $errorCode = $this->generateErrorCode('MA_DICH_VU_VAN_CHUYEN_THIEU_XANG_DAU');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Vận chuyển người bệnh nhưng thiếu mã xăng dầu',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã ' . $ma . ' là dịch vụ vận chuyển nhưng MA_XANG_DAU để trống',
                ]);
            }

            $maCoSo = MaDvktStructure::maCoSoVanChuyen($ma);
            if ($maCoSo !== '' && !$this->commonValidationService->isMedicalOrganizationValid($maCoSo)) {
                $errorCode = $this->generateErrorCode('MA_DICH_VU_VAN_CHUYEN_CSKCB_NOT_FOUND');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã cơ sở nơi chuyển đến không có trong danh mục',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã ' . $ma . ' chỉ tới cơ sở KBCB ' . $maCoSo
                        . ' nhưng mã này không có trong danh mục cơ sở KBCB',
                ]);
            }
        }

        // Chuyen mau benh pham: XX.YYYY.ZZZZ.K.WWWWW (TT 09/2019/TT-BYT).
        $maChuyenMau = MaDvktStructure::maCoSoChuyenMau($ma);
        if ($maChuyenMau !== ''
            && !$this->commonValidationService->isMedicalOrganizationValid($maChuyenMau)) {
            $errorCode = $this->generateErrorCode('MA_DICH_VU_CHUYEN_MAU_CSKCB_NOT_FOUND');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Mã cơ sở nơi thực hiện cận lâm sàng không có trong danh mục',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã ' . $ma . ' chỉ tới cơ sở KBCB ' . $maChuyenMau
                    . ' nhưng mã này không có trong danh mục cơ sở KBCB',
            ]);
        }

        return $errors;
    }

    /**
     * Cong thuc tien TUNG DONG theo chuan (QD 4750). Tong cap ho so da co
     * Xml3176CompleteChecker::checkExpenseErrors() lo, nhung tong khop khong suy ra
     * tung dong dung: hai dong sai nguoc chieu nhau van cho tong dung.
     *
     * Thieu can cu thi im lang - toan hang null/khong phai so, hoac ty le ngoai khoang
     * (0,100], deu khong ket luan.
     */
    private function checkCongThucTien(Xml3176Xml3 $data): Collection
    {
        $errors = collect();
        $saiSo = (float) config('xml3176.tien.sai_so', 1.0);

        // Guard ty le tach theo tung quy tac: THANH_TIEN_BV chi phu thuoc TYLE_TT_DV, khong
        // lien quan TYLE_TT_BH - gac ca hai se khoa mieng no bang mot ty le no khong dung.
        // THANH_TIEN_BH dung ca hai ty le nen can ca hai hop le. Khop voi XML2 (nhanh BV
        // khong gac ty le nao vi XML2 khong co TYLE_TT_DV).
        $tyLeDvHopLe = TienTeCalculator::tyLeHopLe($data->tyle_tt_dv);
        $tyLeHopLe = $tyLeDvHopLe && TienTeCalculator::tyLeHopLe($data->tyle_tt_bh);

        if ($tyLeDvHopLe
            && TienTeCalculator::laSo($data->so_luong)
            && TienTeCalculator::laSo($data->don_gia_bv)
            && TienTeCalculator::laSo($data->thanh_tien_bv)) {
            $kyVong = TienTeCalculator::thanhTienBvXml3(
                $data->so_luong, $data->don_gia_bv, $data->tyle_tt_dv
            );

            if (TienTeCalculator::lech($data->thanh_tien_bv, $kyVong, $saiSo)) {
                $errorCode = $this->generateErrorCode('THANH_TIEN_BV_SAI_CONG_THUC');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Thành tiền BV không đúng công thức',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'THANH_TIEN_BV = SO_LUONG x DON_GIA_BV x TYLE_TT_DV/100 = '
                        . number_format($kyVong, 2) . ', hiện khai: '
                        . number_format((float) $data->thanh_tien_bv, 2),
                ]);
            }
        }

        if ($tyLeHopLe
            && TienTeCalculator::laSo($data->so_luong)
            && TienTeCalculator::laSo($data->don_gia_bh)
            && TienTeCalculator::laSo($data->thanh_tien_bh)) {
            $kyVong = TienTeCalculator::thanhTienBhXml3(
                $data->so_luong, $data->don_gia_bh, $data->tyle_tt_dv, $data->tyle_tt_bh
            );

            if (TienTeCalculator::lech($data->thanh_tien_bh, $kyVong, $saiSo)) {
                $errorCode = $this->generateErrorCode('THANH_TIEN_BH_SAI_CONG_THUC');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Thành tiền BH không đúng công thức',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'THANH_TIEN_BH = SO_LUONG x DON_GIA_BH x TYLE_TT_DV/100 '
                        . 'x TYLE_TT_BH/100 = ' . number_format($kyVong, 2) . ', hiện khai: '
                        . number_format((float) $data->thanh_tien_bh, 2),
                ]);
            }
        }

        // CO Y: khong guard laSo() rieng cho bon thanh phan con (t_nguonkhac_nsnn,
        // t_nguonkhac_vtnn, t_nguonkhac_vttn, t_nguonkhac_cl). Day la truong TIEN, thanh
        // phan vang nghia la "nguon do khong chi tra" nen quy ve 0 la cach doc dung -
        // khac han so_luong/don_gia, thanh phan vang o do nghia la "khong biet" nen moi
        // phai im lang. Neu HIS khai T_NGUONKHAC > 0 ma bo trong ca bon thanh phan thi
        // do la bat nhat that, quy tac phai bat chu khong duoc im lang truoc chinh
        // khiem khuyet ma no sinh ra de bat.
        //
        // MO RONG (khong dao ruling tren): importer (Xml3176Service) quy 0 ve NULL, nen ca
        // T_NGUONKHAC_NSNN = 100000 ma T_NGUONKHAC = 0 (luu NULL) truoc day im lang du tong
        // lech dung 100.000d - dung loai bat nhat quy tac nay sinh ra de bat. Vao than khi
        // BAT KY thanh phan nao (tong hoac mot trong bon nguon con) la so, khong chi rieng
        // t_nguonkhac.
        if (TienTeCalculator::laSo($data->t_nguonkhac)
            || TienTeCalculator::laSo($data->t_nguonkhac_nsnn)
            || TienTeCalculator::laSo($data->t_nguonkhac_vtnn)
            || TienTeCalculator::laSo($data->t_nguonkhac_vttn)
            || TienTeCalculator::laSo($data->t_nguonkhac_cl)) {
            $kyVong = TienTeCalculator::tongNguonKhac(
                $data->t_nguonkhac_nsnn, $data->t_nguonkhac_vtnn,
                $data->t_nguonkhac_vttn, $data->t_nguonkhac_cl
            );

            if (TienTeCalculator::lech($data->t_nguonkhac, $kyVong, $saiSo)) {
                $errorCode = $this->generateErrorCode('T_NGUONKHAC_SAI_TONG');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Tiền nguồn khác không bằng tổng bốn nguồn thành phần',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'T_NGUONKHAC = NSNN + VTNN + VTTN + CL = '
                        . number_format($kyVong, 2) . ', hiện khai: '
                        . number_format((float) $data->t_nguonkhac, 2),
                ]);
            }
        }

        // T_BHTT chi ket luan duoc khi khong co nguon khac (cong thuc co nhanh giam tru
        // phu thuoc loai nguon ma du lieu khong phan biet) va khong co tran thanh toan
        // (da co INVALID_T_TRANTT_T_BHTT lo).
        $coNguonKhac = TienTeCalculator::laSo($data->t_nguonkhac) && (float) $data->t_nguonkhac != 0;
        $coTran = TienTeCalculator::laSo($data->t_trantt) && (float) $data->t_trantt != 0;

        if (!$coNguonKhac && !$coTran
            && TienTeCalculator::tyLeHopLe($data->muc_huong)
            && TienTeCalculator::laSo($data->thanh_tien_bh)
            && TienTeCalculator::laSo($data->t_bhtt)) {
            $kyVong = TienTeCalculator::tBhtt($data->thanh_tien_bh, $data->muc_huong);

            if (TienTeCalculator::lech($data->t_bhtt, $kyVong, $saiSo)) {
                $errorCode = $this->generateErrorCode('T_BHTT_SAI_CONG_THUC');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Tiền BHYT thanh toán không đúng công thức',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'T_BHTT = THANH_TIEN_BH x MUC_HUONG/100 = '
                        . number_format($kyVong, 2) . ', hiện khai: '
                        . number_format((float) $data->t_bhtt, 2),
                ]);
            }
        }

        return $errors;
    }

    /**
     * Tap gia tri hop le cua PHAM_VI va TAI_SU_DUNG theo chuan du lieu dau ra.
     *
     * Ten ma la PHAM_VI_NGOAI_TAP_GIA_TRI chu khong phai PHAM_VI_INVALID: XML2 da co
     * XML2_PHAM_VI_INVALID mang nghia hoan toan khac (pham vi phai la 3 voi the CBCS).
     */
    private function checkTapGiaTri(Xml3176Xml3 $data): Collection
    {
        $errors = collect();

        $phamVi = trim((string) $data->pham_vi);

        // Truong nay khong bat buoc theo chuan nen rong thi im lang.
        if ($phamVi !== '') {
            if (!in_array($phamVi, ['1', '2', '3'], true)) {
                $errorCode = $this->generateErrorCode('PHAM_VI_NGOAI_TAP_GIA_TRI');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Phạm vi ngoài tập giá trị hợp lệ',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'PHAM_VI = ' . $phamVi . '. Chuẩn chỉ quy định 1, 2 hoặc 3',
                ]);
            }

            // QD 4750 sua toan bo dien giai: ma 2 = VTYT/DVKT do NGUOI BENH TU TRA.
            // CHI xet T_BHTT, KHONG xet THANH_TIEN_BH: THANH_TIEN_BH chi la so tien theo
            // gia BH, bo xuat khai cho moi dong co ma BH la hop le du pham_vi la gi; T_BHTT
            // moi la "de nghi quy thanh toan" - dung dieu kien rong hon se no tren moi dong
            // co tien (761/762 dong xml3 that dang mang pham_vi = 2). Khop voi quy tac anh
            // em NGUON_CTRA_NGOAI_QUY_MA_BH_TRA ben XML2, cung chi xet t_bhtt.
            if ($phamVi === '2' && (float) $data->t_bhtt > 0) {
                $errorCode = $this->generateErrorCode('PHAM_VI_TU_TRA_MA_BH_TRA');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Người bệnh tự trả nhưng quỹ BHYT vẫn thanh toán',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'PHAM_VI = 2 (người bệnh tự trả) nhưng T_BHTT = '
                        . number_format((float) $data->t_bhtt, 2),
                ]);
            }
        }

        $taiSuDung = trim((string) $data->tai_su_dung);

        if ($taiSuDung !== '') {
            if ($taiSuDung !== '1') {
                $errorCode = $this->generateErrorCode('TAI_SU_DUNG_INVALID');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã tái sử dụng không hợp lệ',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'TAI_SU_DUNG = ' . $taiSuDung
                        . '. Chuẩn chỉ cho ghi 1, không tái sử dụng thì để trống',
                ]);
            } elseif (TienTeCalculator::laSo($data->don_gia_bv)
                && TienTeCalculator::laSo($data->don_gia_bh)
                && TienTeCalculator::lech(
                    $data->don_gia_bv, $data->don_gia_bh,
                    (float) config('xml3176.tien.sai_so', 1.0)
                )) {
                $errorCode = $this->generateErrorCode('TAI_SU_DUNG_DON_GIA_LECH');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'VTYT tái sử dụng nhưng hai đơn giá lệch nhau',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'VTYT tái sử dụng phải có DON_GIA_BV = DON_GIA_BH. Hiện BV: '
                        . number_format((float) $data->don_gia_bv, 2) . ', BH: '
                        . number_format((float) $data->don_gia_bh, 2),
                ]);
            }
        }

        return $errors;
    }

    //Bổ sung kiểm tra mã nhóm là pttt mà trùng ma_bac_si + ngay_yl đã có trong cơ sở dữ liệu thì cảnh báo
    private function checkServiceGroupPtttDuplicate(Xml3176Xml3 $data): Collection
    {
        $errors = collect();

        if (in_array($data->ma_nhom, config('xml3176.xml3.service_groups_pttt'))) {
            $serviceExists = Xml3176Xml3::where('ma_bac_si', $data->ma_bac_si)
            ->where(function ($q) use ($data) {
                $q->where('ma_lk', '!=', $data->ma_lk)
                  ->orWhere(function ($q2) use ($data) {
                      $q2->where('ma_lk', $data->ma_lk)
                         ->where('stt', '!=', $data->stt);
                  });
            })
            ->where('ngay_yl', $data->ngay_yl)
            ->where('ngay_th_yl', $data->ngay_th_yl)
            ->where('ngay_kq', $data->ngay_kq)
            ->whereIn('ma_nhom', config('xml3176.xml3.service_groups_pttt'))
            ->get();

            if ($serviceExists->isNotEmpty()) {
                $details = $serviceExists->map(function ($item) {
                    $ngayYl = strtodatetime($item->ngay_yl);
                    $ngayThYl = strtodatetime($item->ngay_th_yl);
                    $ngayKq = strtodatetime($item->ngay_kq);

                    return "- Mã điều trị={$item->ma_lk}; stt={$item->stt}; Bác sĩ={$item->ma_bac_si}; Ngày yl={$ngayYl}; Ngày th={$ngayThYl}; Ngày kq={$ngayKq}";
                })->implode("\n");

                $errorCode = $this->generateErrorCode('SERVICE_GROUP_PTTT_DUPLICATE');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'PTTT trùng y lệnh',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => $details
                ]);
            }
        }
        return $errors;
    }

    /**
     * #2391 TG th YL trùng TG KQ; #199 ngày KQ > ngày ra;
     * #2452/2453 thực hiện < 3 phút (nhóm XN/TDCN); #2486 BS vừa YL vừa thực hiện.
     */
    private function checkTimingAndExecutor(Xml3176Xml3 $data): Collection
    {
        $errors = collect();
        $data->loadMissing('Xml3176Xml1');

        // #2391
        if (!empty($data->ngay_th_yl) && !empty($data->ngay_kq) && $data->ngay_th_yl === $data->ngay_kq) {
            $code = $this->generateErrorCode('NGAY_TH_YL_EQUALS_NGAY_KQ');
            $errors->push((object) [
                'error_code' => $code, 'error_name' => 'Thời gian thực hiện y lệnh trùng thời gian kết quả',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                'description' => 'NGAY_TH_YL = NGAY_KQ = ' . strtodatetime($data->ngay_kq) . '. Dịch vụ: ' . $data->ten_dich_vu,
            ]);
        }

        // #199
        if (!empty($data->ngay_kq) && $data->Xml3176Xml1 && !empty($data->Xml3176Xml1->ngay_ra)) {
            $kq = Xml3176DateHelper::datePart($data->ngay_kq);
            $ra = Xml3176DateHelper::datePart($data->Xml3176Xml1->ngay_ra);
            if ($kq !== null && $ra !== null && $kq > $ra) {
                $code = $this->generateErrorCode('NGAY_KQ_GREATER_NGAY_RA');
                $errors->push((object) [
                    'error_code' => $code, 'error_name' => 'Ngày kết quả dịch vụ lớn hơn ngày ra viện',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                    'description' => 'Ngày KQ (' . strtodatetime($data->ngay_kq) . ') > ngày ra (' . strtodatetime($data->Xml3176Xml1->ngay_ra) . '). Dịch vụ: ' . $data->ten_dich_vu,
                ]);
            }
        }

        // #2452/2453
        $groups = array_map('intval', (array) config('xml3176.xml3.execution_time_check_groups', [1, 3]));
        $minMin = (int) config('xml3176.xml3.execution_min_minutes', 3);
        if (in_array((int) $data->ma_nhom, $groups, true)) {
            $d = Xml3176DateHelper::diffMinutes($data->ngay_th_yl, $data->ngay_kq);
            if ($d !== null && $d < $minMin) {
                $code = $this->generateErrorCode('EXECUTION_TIME_UNDER_3MIN');
                $errors->push((object) [
                    'error_code' => $code, 'error_name' => 'Thời gian thực hiện nhỏ hơn quy định',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                    'description' => 'Nhóm ' . $data->ma_nhom . ': thực hiện ' . $d . ' phút (< ' . $minMin . '). Dịch vụ: ' . $data->ten_dich_vu,
                ]);
            }
        }

        // #2486
        $sameGroups = array_map('intval', (array) config('xml3176.xml3.same_doctor_check_groups', [1, 2, 3]));
        if (in_array((int) $data->ma_nhom, $sameGroups, true)
            && !empty($data->ma_bac_si) && !empty($data->nguoi_thuc_hien)
            && $data->ma_bac_si === $data->nguoi_thuc_hien) {
            $code = $this->generateErrorCode('SAME_DOCTOR_ORDER_AND_EXECUTE');
            $errors->push((object) [
                'error_code' => $code, 'error_name' => 'Bác sĩ vừa ra y lệnh vừa thực hiện',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                'description' => 'Mã bác sĩ = người thực hiện = ' . $data->ma_bac_si . '. Dịch vụ: ' . $data->ten_dich_vu,
            ]);
        }

        return $errors;
    }
}
<?php

namespace App\Services;

use App\Models\BHYT\Qd130Xml3;
use App\Models\BHYT\MedicalSupplyCatalog;
use App\Models\BHYT\MedicalStaff;
use Illuminate\Support\Collection;

class Qd130Xml3Checker
{
    protected $xmlErrorService;
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


    public function __construct(Qd130XmlErrorService $xmlErrorService)
    {
        $this->xmlErrorService = $xmlErrorService;
        $this->setConditions();
    }

    protected function setConditions()
    {
        $this->xmlType = 'XML3';
        $this->prefix = $this->xmlType . '_';
        
        $this->materialGroupCodes = [10, 11];
        $this->bedGroupCodes = [14, 15, 16];
        $this->excludedBedDepartments = ['K0249', 'K26'];
        $this->outpatientTypes = ['01', '02'];
        $this->examinationGroupCodes = [13];
        $this->transportGroupCodes = [12];
        $this->bedCodePattern = '/^[HTCK]\d{3}$/';
        $this->excludedMaterialGroupCodes = [11];
        $this->groupCodeWithExecutor = [1,2,3,8,18];
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

        if (in_array($data->ma_nhom, $this->groupCodeWithExecutor) && empty($data->nguoi_thuc_hien)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_GROUP_CODE_NGUOI_THUC_HIEN',
                'error_name' => 'Thiếu người thực hiện',
                'critical_error' => true,
                'description' => 'Người thực hiện không được để trống đối với DVKT: ' . $data->ten_dich_vu
            ]);
        }

        if (!empty($data->nguoi_thuc_hien)) {
            $nguoi_thuc_hien_array = explode(';', $data->nguoi_thuc_hien);
            foreach ($nguoi_thuc_hien_array as $nguoi_thuc_hien) {
                if (!MedicalStaff::where('macchn', $nguoi_thuc_hien)->exists()) {
                    $errors->push((object)[
                        'error_code' => $this->prefix . 'INFO_ERROR_NGUOI_THUC_HIEN_NOT_FOUND',
                        'error_name' => 'Người thực hiện không tồn tại',
                        'critical_error' => true,
                        'description' => 'Người thực hiện không tồn tại trong danh mục NVYT: ' . $nguoi_thuc_hien
                    ]);
                }
            }
        }

        if (empty($data->ma_bac_si)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_BAC_SI',
                'error_name' => 'Thiếu mã bác sĩ',
                'critical_error' => true,
                'description' => 'Mã bác sĩ không được để trống'
            ]);
        } else {
            $ma_bac_si_array = explode(';', $data->ma_bac_si);
            foreach ($ma_bac_si_array as $ma_bac_si) {
                if (!MedicalStaff::where('macchn', $ma_bac_si)->exists()) {
                    $errors->push((object)[
                        'error_code' => $this->prefix . 'INFO_ERROR_MA_BAC_SI_NOT_FOUND',
                        'error_name' => 'Mã bác sĩ không tồn tại',
                        'critical_error' => true,
                        'description' => 'Mã bác sĩ tồn tại trong danh mục NVYT: ' . $ma_bac_si
                    ]);
                }
            }
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
            $errors->push((object)[
                'error_code' => $this->prefix . 'OUTPATIENT_BED_DAY_ERROR',
                'error_name' => 'Mã loại KCB không được chỉ định ngày giường',
                'critical_error' => true,
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
            $errors->push((object)[
                'error_code' => $this->prefix . 'MISSING_SERVICE_OR_MATERIAL',
                'error_name' => 'Mã dịch vụ và Mã vật tư rỗng',
                'critical_error' => true,
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
            // Kiểm tra định dạng của ma_giuong
            if (!preg_match($this->bedCodePattern, $data->ma_giuong)) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INVALID_BED_CODE_FORMAT',
                    'error_name' => 'Mã giường không đúng định dạng',
                    'critical_error' => true,
                    'description' => 'Mã giường: ' . $data->ma_giuong . ' không đúng định dạng (ký tự đầu phải là H, T, C, K và 3 ký tự sau là số từ 0 đến 9)'
                ]);
            }
            // Kiểm tra nếu MA_KHOA thuộc danh sách những khoa không kiểm tra giường
            if (in_array($data->ma_khoa, $this->excludedBedDepartments)) {
                return $errors;
            }
            $firstComponent = explode('.', $data->ma_dich_vu)[0];
            if (strpos($data->ma_khoa, $firstComponent) !== 0) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INVALID_DEPARTMENT_CODE',
                    'error_name' => 'Khoa chỉ định giường không đúng quy định',
                    'description' => 'Khoa chỉ định: ' . $data->ma_khoa . '; Mã giường: ' . $data->ma_dich_vu
                ]);
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
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INVALID_ORDER_TIME',
                    'error_name' => 'Thời gian chỉ định không hợp lệ',
                    'critical_error' => true,
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
            $errors->push((object)[
                'error_code' => $this->prefix . 'INVALID_T_TRANTT_T_BHTT',
                'error_name' => 'Tiền bảo hiểm thanh toán lớn hơn trần thanh toán',
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
            $errors->push((object)[
                'error_code' => $this->prefix . 'BED_DAY_QUANTITY_ERROR',
                'error_name' => 'Chỉ định ngày giường > 1',
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
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INVALID_NGAY_KQ',
                    'error_name' => 'Ngày trả kết quả không hợp lệ',
                    'description' => 'Ngày trả kết quả (NGAY_KQ) không nằm trong khoảng thời gian vào (' . strtodatetime($ngayVao) . ') và ra (' . strtodatetime($ngayRa) . ')'
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
                $errors->push((object)[
                    'error_code' => $this->prefix . 'MISSING_MATERIAL_CODE',
                    'error_name' => 'Mã vật tư rỗng',
                    'critical_error' => true,
                    'description' => 'Không được để trống trường mã vật tư. Ngày y lệnh: ' . strtodatetime($data->ngay_yl)
                ]);
            }

            if (empty($data->ten_vat_tu)) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'MISSING_MATERIAL_NAME',
                    'error_name' => 'Tên vật tư rỗng',
                    'critical_error' => true,
                    'description' => 'Không được để trống trường tên vật tư. Ngày y lệnh: ' . strtodatetime($data->ngay_yl)
                ]);
            }

            // Kiểm tra định dạng tt_thau trong XML3 (mã thầu;gói thầu;nhóm thầu;năm thầu)
            $parts = explode(';', $data->tt_thau);
            if (count($parts) < 4 || in_array('', $parts, true)) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INVALID_TT_THAU_FORMAT',
                    'error_name' => 'Thông tin thầu không đúng định dạng',
                    'critical_error' => true,
                    'description' => 'Mã vật tư: ' . $data->ma_vat_tu . '; có TT_THAU không đúng định dạng: ' . $data->tt_thau
                ]);
            } else {
                list($dataDecision, $dataPackage, $dataGroup, $dataYear) = $parts;

                // Lấy các bản ghi từ MedicalSupplyCatalog có ma_vat_tu khớp với ma_vat_tu trong $data
                $medicalSupplies = MedicalSupplyCatalog::where('ma_vat_tu', $data->ma_vat_tu)->get();

                $found = false;

                foreach ($medicalSupplies as $supply) {
                    // Tách thông tin từ tt_thau trong MedicalSupplyCatalog
                    $supplyParts = explode(';', $supply->tt_thau);
                    
                    // Kiểm tra nếu số lượng phần tử đủ để truy cập
                    if (count($supplyParts) >= 4) {
                        $supplyDecision = $supplyParts[0];
                        $supplyPackage = $supplyParts[1];
                        $supplyGroup = $supplyParts[2];
                        $supplyYear = $supplyParts[3];

                        // Kiểm tra từng phần
                        if ($supplyDecision == $dataDecision && $supplyPackage == $dataPackage && 
                            $supplyGroup == $dataGroup && $supplyYear == $dataYear) {
                            $found = true;

                            // Kiểm tra giá
                            if ($data->don_gia_bh > $supply->don_gia_bh) {
                                $errors->push((object)[
                                    'error_code' => $this->prefix . 'EXCEEDS_APPROVED_PRICE',
                                    'error_name' => 'Giá vật tư cao hơn giá được phê duyệt',
                                    'description' => 'Mã VTYT: ' . $data->ma_vat_tu . '; Có giá: ' . $data->don_gia_bh . '; Giá phê duyệt: ' . $supply->don_gia_bh
                                ]);
                            }

                            // Kiểm tra tên vật tư
                            if ($data->ten_vat_tu != $supply->ten_vat_tu) {
                                $errors->push((object)[
                                    'error_code' => $this->prefix . 'INVALID_MATERIAL_NAME',
                                    'error_name' => 'Tên vật tư không khớp với danh mục phê duyệt',
                                    'description' => 'Mã VTYT: ' . $data->ma_vat_tu . ' có tên: ' . 
                                    formatDescription($data->ten_vat_tu) . '; Tên phê duyệt: ' . $supply->ten_vat_tu
                                ]);
                            }

                            break;
                        }
                    }
                }

                if (!$found) {
                    $errors->push((object)[
                        'error_code' => $this->prefix . 'MEDICAL_SUPPLY_NOT_IN_CATALOG',
                        'error_name' => 'VTYT nằm ngoài danh mục BHYT',
                        'description' => 'VTYT có mã: ' . $data->ma_vat_tu . ' với TT_THAU: ' . 
                        formatDescription($data->tt_thau) . ' không có trong danh mục BHYT'
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
            $errors->push((object)[
                'error_code' => $this->prefix . 'EXCLUDED_MATERIAL_GROUP_CODE',
                'error_name' => 'Vật tư nằm ngoài danh mục hoặc vật tư thay thế',
                'critical_error' => true,
                'description' => 'Mã vật tư: ' . $data->ma_vat_tu . ' nằm ngoài danh mục hoặc là vật tư thay thế'
            ]);
        }

        return $errors;
    }

    // Thêm các phương thức kiểm tra khác ở đây
}
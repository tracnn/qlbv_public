<?php

namespace App\Services;

use App\Models\BHYT\XML3;
use App\Models\BHYT\MedicalSupplyCatalog;
use Illuminate\Support\Collection;

class Xml3Checker
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


    public function __construct(XmlErrorService $xmlErrorService)
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
        $this->outpatientTypes = [1, 2];
        $this->examinationGroupCodes = [13];
        $this->transportGroupCodes = [12];
    }

    /**
     * Check XML3 Errors
     *
     * @param XML3 $data
     * @return void
     */
    public function checkErrors(XML3 $data): void
    {
        // Thực hiện kiểm tra lỗi
        $errors = collect();

        // Load related XML1
        $data->load('xml1');

        if ($data->xml1) {
            $errors = $errors->merge($this->checkOutpatientBedDayErrors($data));
            $errors = $errors->merge($this->checkOrderTime($data));
            $errors = $errors->merge($this->checkNgayKq($data)); // Thêm kiểm tra NGAY_KQ
        }

        $errors = $errors->merge($this->checkMissingServiceOrMaterial($data));
        $errors = $errors->merge($this->checkBedGroupCodeConditions($data));
        $errors = $errors->merge($this->checkTTranttAndTBhtt($data)); // Thêm kiểm tra T_TRANTT và T_BHTT
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
     * @param XML3 $data
     * @param int $maLoaiKcb
     * @return Collection
     */
    private function checkOutpatientBedDayErrors(XML3 $data): Collection
    {
        $errors = collect();

        if (in_array($data->xml1->ma_loai_kcb, $this->outpatientTypes) && in_array($data->ma_nhom, $this->bedGroupCodes)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'OUTPATIENT_BED_DAY_ERROR',
                'error_name' => 'Mã loại KCB không được chỉ định ngày giường',
                'description' => 'MA_LOAI_KCB là (Khám hoặc Điều trị ngoại trú) nhưng có chỉ định dịch vụ ngày giường'
            ]);
        }

        return $errors;
    }

    /**
     * Check for missing service or material code
     *
     * @param XML3 $data
     * @return Collection
     */
    private function checkMissingServiceOrMaterial(XML3 $data): Collection
    {
        $errors = collect();

        if (empty($data->ma_dich_vu) && empty($data->ma_vat_tu)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'MISSING_SERVICE_OR_MATERIAL',
                'error_name' => 'Mã dịch vụ và Mã vật tư rỗng',
                'description' => 'MA_DICH_VU và MA_VAT_TU rỗng'
            ]);
        }

        return $errors;
    }

    /**
     * Check for bed group code conditions
     *
     * @param XML3 $data
     * @return Collection
     */
    private function checkBedGroupCodeConditions(XML3 $data): Collection
    {
        $errors = collect();

        if (in_array($data->ma_nhom, $this->bedGroupCodes)) {
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
     * @param XML3 $data
     * @return Collection
     */
    private function checkOrderTime(XML3 $data): Collection
    {
        $errors = collect();

        if ($data->xml1) {
            $ngayVao = $data->xml1->ngay_vao;
            $ngayRa = $data->xml1->ngay_ra;
            $ngayYLenh = $data->ngay_yl;

            if ($ngayYLenh < $ngayVao || $ngayYLenh > $ngayRa) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INVALID_ORDER_TIME',
                    'error_name' => 'Thời gian chỉ định không hợp lệ',
                    'description' => 'Thời gian y lệnh không nằm trong khoảng thời gian vào (' . $ngayVao . ') và ra (' . $ngayRa . ')'
                ]);
            }
        }

        return $errors;
    }

    /**
     * Check for T_TRANTT and T_BHTT conditions
     *
     * @param XML3 $data
     * @return Collection
     */
    private function checkTTranttAndTBhtt(XML3 $data): Collection
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
     * @param XML3 $data
     * @return Collection
     * @param XML3 $data
     * @return Collection
     */
    private function checkBedDayQuantity(XML3 $data): Collection
    {
        $errors = collect();

        if (in_array($data->ma_nhom, $this->bedGroupCodes) && $data->so_luong > 1) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'BED_DAY_QUANTITY_ERROR',
                'error_name' => 'Chỉ định ngày giường > 1',
                'description' => 'Số lượng ngày giường > 1. Ngày y lệnh: ' . $data->ngay_yl
            ]);
        }

        return $errors;
    }

    /**
     * Check NGAY_KQ against NGAY_VAO and NGAY_RA
     *
     * @param XML3 $data
     * @return Collection
     */
    private function checkNgayKq(XML3 $data): Collection
    {
        $errors = collect();

        // Nếu MA_NHOM thuộc materialGroupCodes, bỏ qua kiểm tra
        if (in_array($data->ma_nhom, $this->materialGroupCodes)) {
            return $errors;
        }

        if (!empty($data->ngay_kq) && $data->xml1) {
            $ngayKq = $data->ngay_kq;
            $ngayVao = $data->xml1->ngay_vao;
            $ngayRa = $data->xml1->ngay_ra;

            if ($ngayKq < $ngayVao || $ngayKq > $ngayRa) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INVALID_NGAY_KQ',
                    'error_name' => 'Ngày trả kết quả không hợp lệ',
                    'description' => 'Ngày trả kết quả (NGAY_KQ) không nằm trong khoảng thời gian vào (' . $ngayVao . ') và ra (' . $ngayRa . ')'
                ]);
            }
        }

        return $errors;
    }

    /**
     * Check if medical supply (ma_vat_tu) has tt_thau that is not in the MedicalSupplyCatalog
     *
     * @param XML3 $data
     * @return Collection
     */
    private function checkMedicalSupplyCatalog(XML3 $data): Collection
    {
        $errors = collect();

        if (in_array($data->ma_nhom, $this->materialGroupCodes)) {

            // Kiểm tra nếu ma_vat_tu hoặc ten_vat_tu rỗng
            if (empty($data->ma_vat_tu)) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'MISSING_MATERIAL_CODE',
                    'error_name' => 'Mã vật tư rỗng',
                    'description' => 'Không được để trống trường mã vật tư. Ngày y lệnh: ' . $data->ngay_yl
                ]);
            }

            if (empty($data->ten_vat_tu)) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'MISSING_MATERIAL_NAME',
                    'error_name' => 'Tên vật tư rỗng',
                    'description' => 'Không được để trống trường tên vật tư. Ngày y lệnh: ' . $data->ngay_yl
                ]);
            }

            // Kiểm tra định dạng tt_thau trong XML3 (năm.thầu.gói thầu)
            $parts = explode('.', $data->tt_thau);
            if (count($parts) < 3 || !preg_match('/^\d{4}$/', $parts[0])) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INVALID_TT_THAU_FORMAT',
                    'error_name' => 'Thông tin thầu không đúng định dạng',
                    'description' => 'TT_THAU không đúng định dạng năm.thầu.gói thầu'
                ]);
            } else {
                list($dataYear, $dataPackage, $dataDecision) = $parts;

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
                        $supplyYear = $supplyParts[3];

                        // Kiểm tra từng phần
                        if ($supplyDecision == $dataDecision && $supplyPackage == $dataPackage && $supplyYear == $dataYear) {
                            $found = true;

                            // Kiểm tra giá
                            $approvedPrice = min($supply->don_gia, $supply->don_gia_bh);
                            if ($data->don_gia > $approvedPrice) {
                                $errors->push((object)[
                                    'error_code' => $this->prefix . 'EXCEEDS_APPROVED_PRICE',
                                    'error_name' => 'Giá vật tư cao hơn giá được phê duyệt',
                                    'description' => 'Mã VTYT: ' . $data->ma_vat_tu . '; Có giá: ' . $data->don_gia . 
                                    '; Giá phê duyệt: ' . $approvedPrice
                                ]);
                            }

                            // Kiểm tra tên vật tư
                            if ($data->ten_vat_tu != $supply->ten_vat_tu) {
                                $errors->push((object)[
                                    'error_code' => $this->prefix . 'INVALID_MATERIAL_NAME',
                                    'error_name' => 'Tên vật tư không khớp với danh mục phê duyệt',
                                    'description' => 'Mã VTYT: ' . $data->ma_vat_tu . ' có tên: ' . 
                                    formatDescription($data->ten_vat_tu) . 
                                    '; Tên phê duyệt: ' . $supply->ten_vat_tu
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
    
    // Thêm các phương thức kiểm tra khác ở đây
}
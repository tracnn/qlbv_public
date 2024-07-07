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

        $this->drugGroups = [4, 5]; // Mã nhóm thuốc cần kiểm tra
        $this->bloodGroup = [7]; // Mã nhóm máu
        $this->excludedDepartments = []; //Mã khoa không cần kiểm tra
        $this->drugGroupNotCheck = ['05V', '05C', 'HD'];
        $this->drugCodeNotCheck = ['40.17']; // Các mã thuốc cụ thể không cần kiểm tra
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

        $errors = $errors->merge($this->checkMedicalStaff($data));
        $errors = $errors->merge($this->checkDrugCatalog($data));

        $additionalData = [
            'ngay_yl' => $data->ngay_yl
        ];

        // Save errors to xml_error_checks table
        $this->xmlErrorService->saveErrors($this->xmlType, $data->ma_lk, $data->stt, $errors, $additionalData);
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
                    $errors->push((object)[
                        'error_code' => $this->prefix . 'INVALID_ORDER_TIME',
                        'error_name' => 'Thời gian y lệnh không hợp lệ',
                        'description' => 'Thời gian y lệnh nhỏ hơn ngày vào hoặc lớn hơn ngày ra (Mã thuốc: ' . $data->ma_thuoc .')'
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
            $errors->push((object)[
                'error_code' => $this->prefix . 'MISSING_MEDICAL_STAFF_CODE',
                'error_name' => 'Không có mã bác sĩ (Mã CCHN)',
                'description' => 'Không có mã bác sĩ'
            ]);
        } else {
            $staff = MedicalStaff::where('macchn', $data->ma_bac_si)->first();
            if (!$staff) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INVALID_MEDICAL_STAFF_CODE',
                    'error_name' => 'Mã CCHN chưa được duyệt',
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
                    $errors->push((object)[
                        'error_code' => $this->prefix . 'MISSING_DRUG_CODE',
                        'error_name' => 'Không có mã thuốc',
                        'description' => 'Không có mã thuốc'
                    ]);
                }
                if (empty($data->ten_thuoc)) {
                    $errors->push((object)[
                        'error_code' => $this->prefix . 'MISSING_DRUG_NAME',
                        'error_name' => 'Không có tên thuốc',
                        'description' => $data->ma_thuoc . '; Không có tên thuốc'
                    ]);
                }
                if (empty($data->ham_luong)) {
                    $errors->push((object)[
                        'error_code' => $this->prefix . 'MISSING_DRUG_CONCENTRATION',
                        'error_name' => 'Không có hàm lượng thuốc',
                        'description' => $data->ma_thuoc . '; Không có hàm lượng thuốc'
                    ]);
                }
                if (empty($data->so_dang_ky)) {
                    $errors->push((object)[
                        'error_code' => $this->prefix . 'MISSING_REGISTRATION_NUMBER',
                        'error_name' => 'Không có số đăng ký thuốc',
                        'description' => $data->ma_thuoc . '; Không có số đăng ký thuốc'
                    ]);
                }
                if (empty($data->tt_thau)) {
                    $errors->push((object)[
                        'error_code' => $this->prefix . 'MISSING_TT_THAU',
                        'error_name' => 'Không có thông tin thầu thuốc',
                        'description' => $data->ma_thuoc . '; Không có TT_THAU'
                    ]);
                }
                
                if ($errors->isNotEmpty()) {
                    return $errors;
                }

                $ttThauParts = explode(";", $data->tt_thau);
                if (count($ttThauParts) !== 3 && count($ttThauParts) !== 4) {
                    $errors->push((object)[
                        'error_code' => $this->prefix . 'INVALID_TT_THAU_FORMAT',
                        'error_name' => 'Thông tin thầu không đúng định dạng',
                        'description' => 'TT_THAU không đúng định dạng (Mã thuốc: ' . $data->ma_thuoc . ')'
                    ]);
                } else {
                    $ttThau = $ttThauParts[0] . ";" . $ttThauParts[1] . ";" . $ttThauParts[2];

                    $medicine = MedicineCatalog::where('ma_thuoc', $data->ma_thuoc)
                        ->where('ham_luong', $data->ham_luong)
                        ->where('so_dang_ky', $data->so_dang_ky)
                        ->where('tt_thau', 'LIKE', $ttThau . '%')
                        ->first();

                    if (!$medicine) {
                        if (!MedicineCatalog::where('ma_thuoc', $data->ma_thuoc)->exists()) {
                            $errors->push((object)[
                                'error_code' => $this->prefix . 'INVALID_DRUG_CODE',
                                'error_name' => 'Mã thuốc chưa có trong danh mục BHYT',
                                'description' => 'Mã thuốc không có trong danh mục BHYT: ' . formatDescription($data->ma_thuoc)
                            ]);
                        } elseif (!MedicineCatalog::where('ma_thuoc', $data->ma_thuoc)->where('ham_luong', $data->ham_luong)->exists()) {
                            $errors->push((object)[
                                'error_code' => $this->prefix . 'INVALID_DRUG_CONCENTRATION',
                                'error_name' => 'Mã thuốc sai hàm lượng trong danh mục BHYT',
                                'description' => 'Mã thuốc: ' . $data->ma_thuoc . '; Sai hàm lượng: ' . formatDescription($data->ham_luong)
                            ]);
                        } elseif (!MedicineCatalog::where('ma_thuoc', $data->ma_thuoc)->where('ham_luong', $data->ham_luong)->where('so_dang_ky', $data->so_dang_ky)->exists()) {
                            $errors->push((object)[
                                'error_code' => $this->prefix . 'INVALID_REGISTRATION_NUMBER',
                                'error_name' => 'Mã thuốc sai số đăng ký trong danh mục BHYT',
                                'description' => 'Mã thuốc: ' . $data->ma_thuoc . '; Số ĐK: ' . formatDescription($data->so_dang_ky)
                            ]);
                        } else {
                            $errors->push((object)[
                                'error_code' => $this->prefix . 'INVALID_TT_THAU',
                                'error_name' => 'Mã thuốc sai thông tin thầu trong danh mục BHYT',
                                'description' => 'Mã thuốc: ' . $data->ma_thuoc . '; Sai TT_THAU: ' . formatDescription($data->tt_thau)
                            ]);
                        }
                    } else {
                        //Kiểm tra tên thuốc
                        if ($data->ten_thuoc != $medicine->ten_thuoc) {
                            $errors->push((object)[
                                'error_code' => $this->prefix . 'INVALID_DRUG_NAME',
                                'error_name' => 'Mã thuốc sai tên trong danh mục BHYT',
                                'description' => 'Mã thuốc: ' . $data->ma_thuoc . '; Thuốc sai tên so với danh mục được duyệt: ' . formatDescription($data->ten_thuoc) . ' # ' . $medicine->ten_thuoc
                            ]);
                        }

                        // Kiểm tra giá thuốc
                        if ($data->don_gia > $medicine->don_gia) {
                            $errors->push((object)[
                                'error_code' => $this->prefix . 'PRICE_EXCEEDS_APPROVED',
                                'error_name' => 'Giá thuốc cao hơn giá được duyệt',
                                'description' => 'Mã thuốc: ' . $data->ma_thuoc . '; Giá cao hơn giá được duyệt: ' . $data->don_gia . ' > ' . $medicine->don_gia
                            ]);
                        }    
                    }
                }
            }
        }

        return $errors;
    }

    // Thêm các phương thức kiểm tra khác ở đây
}
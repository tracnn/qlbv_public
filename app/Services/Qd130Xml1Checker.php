<?php

namespace App\Services;

use App\Services\Qd130XmlChecker\Qd130Xml1AdministrativeInfoChecker;

use App\Models\BHYT\Qd130Xml1;
use App\Models\BHYT\Icd10Category;
use App\Models\BHYT\IcdYhctCategory;
use Illuminate\Support\Collection;

class Qd130Xml1Checker
{
    protected $xmlErrorService;
    protected $prefix;

    protected $xmlType;
    protected $maxWeight;
    protected $specialDKBD;
    protected $admissionReasons;
    protected $invalidKetQuaDtri;
    protected $invalidMaLoaiRV;
    protected $bedGroupCodes;
    protected $treatmentTypeInpatient;

    protected $adminInfoChecker;

    public function __construct(Qd130XmlErrorService $xmlErrorService)
    {
        $this->xmlErrorService = $xmlErrorService;
        $this->setConditions();
        $this->adminInfoChecker = new Qd130Xml1AdministrativeInfoChecker($this->prefix);
    }

    protected function setConditions()
    {
        $this->xmlType = 'XML1';
        $this->prefix = $this->xmlType . '_';

        $this->maxWeight = 200;
        $this->specialDKBD = ['01013', '01061'];
        $this->admissionReasons = [3, 4];
        $this->invalidKetQuaDtri = [3, 4, 5, 6];
        $this->invalidMaLoaiRV = [2, 3, 4];
        $this->bedGroupCodes = [14, 15, 16];
        $this->treatmentTypeInpatient = ['03'];
    }

    /**
     * Check XML1 Errors
     *
     * @param string $fromDateTime
     * @param string $toDateTime
     * @return Collection
     */
    /**
     * Check XML1 Errors
     *
     * @param XML1 $data
     * @return void
     */
    public function checkErrors(Qd130Xml1 $data): void
    {
        // Delete errors to xml_error_checks table
        $this->xmlErrorService->deleteErrors($data->ma_lk);

        // Thực hiện kiểm tra lỗi
        $errors = collect();

        $errors = $errors->merge($this->adminInfoChecker->check($data));
        //$errors = $errors->merge($this->checkReasonForAdmission($data));
        $errors = $errors->merge($this->checkLongTermTreatment($data));
        //$errors = $errors->merge($this->checkInvalidBedDays($data));
        $errors = $errors->merge($this->checkSpecialInpatientConditions($data));
        $errors = $errors->merge($this->checkDiseaseIcdCodes($data));

        // Save errors to xml_error_checks table
        $this->xmlErrorService->saveErrors($this->xmlType, $data->ma_lk, $data->stt, $errors);
    }


    /**
     * Check for reason for admission errors
     *
     * @param XML1 $data
     * @return Collection
     */
    private function checkReasonForAdmission(Qd130Xml1 $data): Collection
    {
        $errors = collect();

        if (in_array($data->ma_dkbd, $this->specialDKBD) && in_array($data->ma_loai_kcb, $this->admissionReasons)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'REASON_ERROR_SPECIAL',
                'error_name' => 'Lý do vào viện không hợp lệ với MA_DKBD đúng tuyến',
                'critical_error' => true,
                'description' => 'Lý do vào viện không hợp lệ với MA_DKBD thuộc danh sách đặc biệt'
            ]);
        } elseif (!in_array($data->ma_dkbd, $this->specialDKBD) && $data->ma_loai_kcb == 4) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'REASON_ERROR_NON_SPECIAL',
                'error_name' => 'Lý do vào viện không hợp lệ với MA_DKBD trái tuyến',
                'critical_error' => true,
                'description' => 'Lý do vào viện không hợp lệ với MA_DKBD không thuộc danh sách đặc biệt'
            ]);
        } elseif ($data->ma_loai_kcb == 1 && !in_array($data->ma_dkbd, $this->specialDKBD) && empty($data->ma_noi_di)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'REASON_ERROR_LYDO_1',
                'error_name' => 'Đúng tuyến nhưng Nơi DKBD <> CSKCB và Không có nơi chuyển đến',
                'critical_error' => true,
                'description' => 'Lý do vào viện không hợp lệ: Đúng tuyến nhưng Nơi DKBD <> CSKCB và Không có nơi chuyển đến'
            ]);
        }

        return $errors;
    }

    /**
     * Check for long-term treatment errors
     *
     * @param XML1 $data
     * @return Collection
     */
    private function checkLongTermTreatment(Qd130Xml1 $data): Collection
    {
        $errors = collect();

        if ($data->ma_loai_kcb == 1) {
            $ngayVao = \DateTime::createFromFormat('YmdHi', $data->ngay_vao);
            $ngayRa = \DateTime::createFromFormat('YmdHi', $data->ngay_ra);

            if ($ngayVao && $ngayRa) {
                $diff = $ngayRa->diff($ngayVao);
                if ($diff->days > 1) {
                    $errors->push((object)[
                        'error_code' => $this->prefix . 'LONG_TERM_TREATMENT',
                        'error_name' => 'Khám bệnh dài ngày',
                        'description' => 'Điều trị kéo dài quá 1 ngày với MA_LOAI_KCB là 1'
                    ]);
                }
            }
        }

        return $errors;
    }

    /**
     * Check for invalid bed days errors
     *
     * @param XML1 $data
     * @return Collection
     */
    private function checkInvalidBedDays(Qd130Xml1 $data): Collection
    {
        $errors = collect();

        if (in_array($data->ma_loai_kcb, $this->treatmentTypeInpatient) && $data->so_ngay_dtri >= 2 &&
            (!in_array($data->ket_qua_dtri, $this->invalidKetQuaDtri) ||
            !in_array($data->ma_loai_rv, $this->invalidMaLoaiRV))) {

            $totalBedDays = $data->Qd130Xml3()->whereIn('ma_nhom', $this->bedGroupCodes)->sum('so_luong');

            if ($totalBedDays >= $data->so_ngay_dtri) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INVALID_BED_DAYS',
                    'error_name' => 'Thanh toán ngày giường sai quy định (trừ trường hợp đặc biệt)',
                    'description' => 'Thanh toán ngày giường sai quy định (trừ trường hợp đặc biệt)'
                ]);
            }
        }

        return $errors;
    }
    
    /**
     * Check for special inpatient conditions errors
     *
     * @param Qd130Xml1 $data
     * @return Collection
     */
    private function checkSpecialInpatientConditions(Qd130Xml1 $data): Collection
    {
        $errors = collect();

        if (in_array($data->ma_loai_kcb, $this->treatmentTypeInpatient)) {
            if (empty($data->ly_do_vnt)) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'SPECIAL_INPATIENT_ERROR_LY_DO_VNT',
                    'error_name' => 'Thiếu lý do vào nội trú',
                    'critical_error' => true,
                    'description' => 'Lý do vào nội trú không được để trống'
                ]);
            }

            if (empty($data->ma_ly_do_vnt)) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'SPECIAL_INPATIENT_ERROR_MA_LY_DO_VNT',
                    'error_name' => 'Thiếu mã lý do vào nội trú',
                    'description' => 'Mã lý do vào nội trú không được để trống'
                ]);
            }

            if (empty($data->ngay_vao_noi_tru)) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'SPECIAL_INPATIENT_ERROR_NGAY_VAO_NOI_TRU',
                    'error_name' => 'Thiếu ngày vào nội trú',
                    'critical_error' => true,
                    'description' => 'Ngày vào nội trú không được để trống'
                ]);
            }

            if (empty($data->pp_dieu_tri)) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'SPECIAL_INPATIENT_ERROR_PP_DIEU_TRI',
                    'error_name' => 'Thiếu phương pháp điều trị',
                    'critical_error' => true,
                    'description' => 'Phương pháp điều trị không được để trống'
                ]);
            }
        }

        return $errors;
    }

    /**
     * Check for disease codes errors
     *
     * @param Qd130Xml1 $data
     * @return Collection
     */
    private function checkDiseaseIcdCodes(Qd130Xml1 $data): Collection
    {
        $errors = collect();

        // Check ma_benh_chinh
        if (!Icd10Category::where('icd_code', $data->ma_benh_chinh)->exists()) {
            $existIcdYhct = IcdYhctCategory::where('icd_code', $data->ma_benh_chinh)->first();
            if($existIcdYhct) {
               $errors->push((object)[
                    'error_code' => $this->prefix . 'DISEASE_ICD_CODE_ERROR_MA_BENH_CHINH_IN_YHCT',
                    'error_name' => 'Mã bệnh chính thuộc bệnh YHCT',
                    'critical_error' => true,
                    'description' => 'Mã bệnh chính: ' . $data->ma_benh_chinh . ' thuộc DM YHCT tương đương với: ' . $existIcdYhct->icd10_code . ' trong DM ICD10'
                ]); 
            } else {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'DISEASE_ICD_CODE_ERROR_MA_BENH_CHINH',
                    'error_name' => 'Mã bệnh chính không tồn tại',
                    'critical_error' => true,
                    'description' => 'Mã bệnh chính không tồn tại trong danh mục ICD10: ' . $data->ma_benh_chinh
                ]);                
            }
        }

        // Check ma_benh_kt
        if (!empty($data->ma_benh_kt)) {
            $ma_benh_kt_array = explode(';', $data->ma_benh_kt);
            foreach ($ma_benh_kt_array as $ma_benh_kt) {
                if (!Icd10Category::where('icd_code', $ma_benh_kt)->exists()) {
                    $existIcdYhct = IcdYhctCategory::where('icd_code', $ma_benh_kt)->first();
                    if($existIcdYhct) {
                        $errors->push((object)[
                            'error_code' => $this->prefix . 'DISEASE_ICD_CODE_ERROR_MA_BENH_KT_IN_YHCT',
                            'error_name' => 'Mã bệnh kèm theo thuộc bệnh YHCT',
                            'critical_error' => true,
                            'description' => 'Mã bệnh kèm theo: ' . $ma_benh_kt .' thuộc DM YHCT tương đương với: ' . $existIcdYhct->icd10_code . ' trong DM ICD10'
                        ]);
                    } else {
                        $errors->push((object)[
                            'error_code' => $this->prefix . 'DISEASE_ICD_CODE_ERROR_MA_BENH_KT',
                            'error_name' => 'Mã bệnh kèm theo không tồn tại',
                            'critical_error' => true,
                            'description' => 'Mã bệnh kèm theo không tồn tại trong danh mục ICD10: ' . $ma_benh_kt
                        ]);                        
                    }
                }
            }
        }

        // Check ma_benh_yhct
        if (!empty($data->ma_benh_yhct)) {
            $ma_benh_yhct_array = explode(';', $data->ma_benh_yhct);
            foreach ($ma_benh_yhct_array as $ma_benh_yhct) {
                if (!IcdYhctCategory::where('icd_code', $ma_benh_yhct)->exists()) {
                    $errors->push((object)[
                        'error_code' => $this->prefix . 'DISEASE_ICD_CODE_ERROR_MA_BENH_YHCT',
                        'error_name' => 'Mã bệnh YHCT không tồn tại',
                        'critical_error' => true,
                        'description' => 'Mã bệnh YHCT không tồn tại trong danh mục ICD YHCT: ' . $ma_benh_yhct
                    ]);
                }
            }
        }

        return $errors;
    }

    // Thêm các phương thức kiểm tra khác ở đây
}
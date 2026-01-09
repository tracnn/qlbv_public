<?php

namespace App\Services;

use App\Models\BHYT\Xml3176Xml1;
use App\Models\BHYT\Xml3176Xml2;
use App\Models\BHYT\Xml3176Xml3;
use App\Models\BHYT\Xml3176Xml4;
use App\Models\BHYT\Xml3176Xml5;
use App\Models\BHYT\Xml3176Xml7;
use App\Models\BHYT\Xml3176Xml8;
use App\Models\BHYT\Xml3176Xml9;
use App\Models\BHYT\Xml3176Xml11;
use App\Models\BHYT\Xml3176Xml12;
use App\Models\BHYT\Xml3176Xml13;
use App\Models\BHYT\Xml3176Xml14;
use Illuminate\Support\Collection;

use DateTime;

class Xml3176XmlCompleteChecker
{
    protected $xmlErrorService;
    protected $prefix;

    protected $xmlType;

    protected $xmlTypeMustHaveXml7;
    protected $invalidKetQuaDtri;
    protected $invalidMaLoaiRV;
    protected $bedGroupCodes;
    protected $treatmentTypeInpatient;
    protected $materialGroupCodes;
    protected $examinationGroupCodes;

    public function __construct(Xml3176XmlErrorService $xmlErrorService)
    {
        $this->xmlErrorService = $xmlErrorService;
        $this->setConditions();
    }

    protected function setConditions()
    {
        $this->xmlType = 'XMLComplete';
        $this->prefix = $this->xmlType . '_';
        $this->xmlTypeMustHaveXml7 = config('xml3176.treatment_type_inpatient');
        $this->materialGroupCodes = config('xml3176.material_group_code');
        $this->invalidKetQuaDtri = config('xml3176.invalid_treatment_result');
        $this->invalidMaLoaiRV = config('xml3176.invalid_end_type_treatment');
        $this->bedGroupCodes = config('xml3176.bed_group_code');
        $this->treatmentTypeInpatient = config('xml3176.treatment_type_inpatient');
        $this->examinationGroupCodes = config('xml3176.examination_group_code');
    }

    protected function generateErrorCode(string $errorKey): string
    {
        return $this->prefix . $errorKey;
    }

    /**
     * Check Xml3176Xml Errors
     *
     * @param $ma_lk
     * @return void
     */
    public function checkErrors($ma_lk): void
    {
        $data = Xml3176Xml1::where('ma_lk', $ma_lk)->first();
        // Thực hiện kiểm tra lỗi
        if ($data) {
            $errors = collect();
        
            $errors = $errors->merge($this->infoChecker($ma_lk));
            $errors = $errors->merge($this->checkInvalidBedDays($data));
            $errors = $errors->merge($this->checkExpenseErrors($data));
            $errors = $errors->merge($this->checkExaminationErrors($data));

            // Save errors to xml_error_checks table
            $this->xmlErrorService->saveErrors($this->xmlType, $data->ma_lk, $data->stt, $errors);
        }
    }

    /**
     * Check for reason for admission errors
     *
     * @param Xml3176Xml11 $data
     * @return Collection
     */
    private function checkExaminationErrors(Xml3176Xml1 $data): Collection
    {
        $errors = collect();

        // Check for multiple records in Xml3176Xml3 with ma_lk and ma_nhom in examinationGroupCodes
        $records = Xml3176Xml3::where('ma_lk', $data->ma_lk)
            ->whereIn('ma_nhom', $this->examinationGroupCodes)
            ->get();

        // Check if the treatment type is inpatient and there are multiple examination fees
        if (in_array($data->ma_loai_kcb, $this->treatmentTypeInpatient) && $records->count() >= 2) {
            $errorCode = $this->generateErrorCode('ERROR_MULTIPLE_EXAMINATION_FEES_INPATIENT');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thừa công khám cho Điều trị nội trú',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Chỉ cho phép có một lần công khám, số lượng hiện tại: ' . $records->count()
            ]);
        }

        return $errors;
    }

    /**
     * Check for reason for admission errors
     *
     * @param Xml3176Xml11 $data
     * @return Collection
     */
    private function infoChecker($ma_lk): Collection
    {
        $errors = collect();

        if (empty($ma_lk)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_XML_COMPLETE_MA_LK');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã liên kết hồ sơ',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã liên kết hồ sơ không được để trống'
            ]);
        } else {
            $existXml1 = Xml3176Xml1::where('ma_lk', $ma_lk)->first();
            if (!$existXml1) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_XML_COMPLETE_MA_LK_NOT_FOUND');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Hồ sơ không tồn tại',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Hồ sơ không tồn tại. Mã hồ sơ: ' . $ma_lk
                ]);
            } else {
                // Kiểm tra ma_loai_kcb thuộc xmlTypeMustHaveXml7
                if (in_array($existXml1->ma_loai_kcb, $this->xmlTypeMustHaveXml7) 
                    && !in_array($existXml1->ma_loai_rv, config('xml3176.treatment_end_type_absconding'))) {
                    $existXml7 = Xml3176Xml7::where('ma_lk', $ma_lk)->exists();
                    if (!$existXml7) {
                        $errorCode = $this->generateErrorCode('INFO_ERROR_XML_COMPLETE_MISSING_XML7');
                        $errors->push((object)[
                            'error_code' => $errorCode,
                            'error_name' => 'Thiếu hồ sơ XML7 (Giấy ra viện)',
                            'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                            'description' => 'Không tồn tại hồ sơ XML7 (Giấy ra viện) với loại KCB thuộc: ' . implode(', ', $this->xmlTypeMustHaveXml7)
                        ]);
                    }
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
    private function checkInvalidBedDays(Xml3176Xml1 $data): Collection
    {
        $errors = collect();

        // Convert ngay_vao and ngay_ra to DateTime objects
        $ngayVao = DateTime::createFromFormat('YmdHi', $data->ngay_vao);
        $ngayRa = DateTime::createFromFormat('YmdHi', $data->ngay_ra);

        // Calculate the difference in hours
        $interval = $ngayRa->diff($ngayVao);
        $hoursDifference = ($interval->days * 24) + $interval->h + ($interval->i / 60);
        $hoursExcludeDaysDifference = $interval->h + ($interval->i / 60);

        if (in_array($data->ma_loai_kcb, $this->treatmentTypeInpatient) && $data->so_ngay_dtri <= 2) {
            $totalBedDays = $data->Xml3176Xml3()->whereIn('ma_nhom', $this->bedGroupCodes)->sum('so_luong');
            
            if ($hoursDifference < 4) {
                // Check if there are bed charges in Xml3176Xml3
                if ($totalBedDays > 0) {
                    $errorCode = $this->generateErrorCode('SHORT_INPATIENT_STAY');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Điều trị nội trú < 4h không được tính tiền giường',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'Thời gian điều trị nội trú nhỏ hơn 4 giờ, không được tính tiền giường.'
                    ]);
                }
            } elseif ($hoursDifference >= 4 && $hoursDifference <= 24) {
                // Check if the total bed days exceed the treatment days
                if ($totalBedDays >= 2) {
                    $errorCode = $this->generateErrorCode('EXCESS_BED_DAYS');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Điều trị nội trú >= 4h và <= 24h tính thừa ngày giường',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'Thời gian điều trị nội trú từ 4 đến 24 giờ, tính thừa ngày giường: ' . $totalBedDays
                    ]);
                }
            }
        }

        if (in_array($data->ma_loai_kcb, $this->treatmentTypeInpatient) && $data->so_ngay_dtri >= 2 &&
            (!in_array($data->ket_qua_dtri, $this->invalidKetQuaDtri) ||
            !in_array($data->ma_loai_rv, $this->invalidMaLoaiRV))) {

            $totalBedDays = $data->Xml3176Xml3()->whereIn('ma_nhom', $this->bedGroupCodes)->sum('so_luong');

            if ($totalBedDays >= $data->so_ngay_dtri && $hoursExcludeDaysDifference < 4) {
                $errorCode = $this->generateErrorCode('INVALID_BED_DAYS');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Thanh toán ngày giường sai quy định (trừ trường hợp đặc biệt)',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Tổng ngày giường: ' . $totalBedDays . ' lớn hơn hoặc bằng số ngày điều trị + 1: ' . $data->so_ngay_dtri
                ]);
            }
        }

        return $errors;
    }

    /**
     * Hàm kiểm tra các loại tiền chi phí trong hồ sơ tổng hợp và hồ sơ chi tiết
     */
    private function checkExpenseErrors(Xml3176Xml1 $data): Collection
    {
        $errors = collect();

        // Kiểm tra t_thuoc
        $sum_t_thuoc = Xml3176Xml2::where('ma_lk', $data->ma_lk)->sum('thanh_tien_bv');
        if ($data->t_thuoc != round($sum_t_thuoc, 2)) {
            $errorCode = $this->generateErrorCode('INVALID_EXPENSE_DRUG');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Tiền thuốc không khớp',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Tiền thuốc trong XML1: ' . number_format($data->t_thuoc) . ' <> tổng tiền trong XML2: ' . number_format($sum_t_thuoc)
            ]);
        }

        // Kiểm tra tiền VTYT
        $sum_t_vtyt = Xml3176Xml3::where('ma_lk', $data->ma_lk)->whereIn('ma_nhom', $this->materialGroupCodes)->sum('thanh_tien_bv');
        if ($data->t_vtyt != round($sum_t_vtyt, 2)) {
            $errorCode = $this->generateErrorCode('INVALID_EXPENSE_T_VTYT');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Tiền vật tư y tế không khớp',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Tiền VTYT trong XML1: ' . number_format($data->t_vtyt) . ' <> tổng tiền VTYT trong XML3: ' . number_format($sum_t_vtyt)
            ]);
        }

        // Kiểm tra t_tongchi_bv
        $sum_t_tongchi_bv_xml2 = Xml3176Xml2::where('ma_lk', $data->ma_lk)->sum('thanh_tien_bv');
        $sum_t_tongchi_bv_xml3 = Xml3176Xml3::where('ma_lk', $data->ma_lk)->sum('thanh_tien_bv');
        $sum_t_tongchi_bv = $sum_t_tongchi_bv_xml2 + $sum_t_tongchi_bv_xml3;

        if ($data->t_tongchi_bv != round($sum_t_tongchi_bv, 2)) {
            $errorCode = $this->generateErrorCode('INVALID_EXPENSE_T_TONGCHI_BV');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Tổng chi phí không khớp',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Tiền tổng chi phí trong XML1: ' . number_format($data->t_tongchi_bv) . ' <> chi phí trong XML2 và XML3: ' . number_format($sum_t_tongchi_bv)
            ]);
        }

        // Kiểm tra t_tongchi_bh
        $sum_t_tongchi_bh_xml2 = Xml3176Xml2::where('ma_lk', $data->ma_lk)->sum('thanh_tien_bh');
        $sum_t_tongchi_bh_xml3 = Xml3176Xml3::where('ma_lk', $data->ma_lk)->sum('thanh_tien_bh');
        $sum_t_tongchi_bh = $sum_t_tongchi_bh_xml2 + $sum_t_tongchi_bh_xml3;

        if ($data->t_tongchi_bh != round($sum_t_tongchi_bh, 2)) {
            $errorCode = $this->generateErrorCode('INVALID_EXPENSE_T_TONGCHI_BH');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Tổng chi phí BH thanh toán không khớp',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Tiền tổng chi phí BH trong XML1: ' . number_format($data->t_tongchi_bh) . ' <> chi phí BH trong XML2 và XML3: ' . number_format($sum_t_tongchi_bh)
            ]);
        }

        // Kiểm tra t_bntt
        $sum_t_bntt_xml2 = Xml3176Xml2::where('ma_lk', $data->ma_lk)->sum('t_bntt');
        $sum_t_bntt_xml3 = Xml3176Xml3::where('ma_lk', $data->ma_lk)->sum('t_bntt');
        $sum_t_bntt = $sum_t_bntt_xml2 + $sum_t_bntt_xml3;

        if ($data->t_bntt != round($sum_t_bntt, 2)) {
            $errorCode = $this->generateErrorCode('INVALID_EXPENSE_T_BNTT');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Tổng chi phí BN thanh toán không khớp',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Tiền tổng chi phí BN trong XML1: ' . number_format($data->t_bntt) . ' <> chi phí BN trong XML2 và XML3: ' . number_format($sum_t_bntt)
            ]);
        }

        // Kiểm tra t_bncct
        $sum_t_bncct_xml2 = Xml3176Xml2::where('ma_lk', $data->ma_lk)->sum('t_bncct');
        $sum_t_bncct_xml3 = Xml3176Xml3::where('ma_lk', $data->ma_lk)->sum('t_bncct');
        $sum_t_bncct = $sum_t_bncct_xml2 + $sum_t_bncct_xml3;

        if ($data->t_bncct != round($sum_t_bncct, 2)) {
            $errorCode = $this->generateErrorCode('INVALID_EXPENSE_T_BNCCT');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Tổng chi phí BN CCT không khớp',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Tiền tổng chi phí BN CCT trong XML1: ' . number_format($data->t_bncct) . ' <> chi phí BN CCT trong XML2 và XML3: ' . number_format($sum_t_bncct)
            ]);
        }

        // Kiểm tra t_bhtt
        $t_tongchi_bh = Xml3176Xml1::where('ma_lk', $data->ma_lk)->sum('t_tongchi_bh');
        $t_bncct = Xml3176Xml1::where('ma_lk', $data->ma_lk)->sum('t_bncct');
        $t_bhtt = $t_tongchi_bh - $t_bncct;

        if ($data->t_bhtt != round($t_bhtt, 2)) {
            $errorCode = $this->generateErrorCode('INVALID_EXPENSE_T_BHTT');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Tiền BHTT không khớp',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Tiền BHTT trong XML1: ' . number_format($data->t_bhtt) . ' <> chi phí T_TONGCHI_BH trong XML1 - T_BNCCT trong XML1: ' . number_format($t_bhtt)
            ]);
        }

        // Kiểm tra t_nguonkhac
        $t_nguonkhac_xml2 = Xml3176Xml2::where('ma_lk', $data->ma_lk)->sum('t_nguonkhac');
        $t_nguonkhac_xml3 = Xml3176Xml3::where('ma_lk', $data->ma_lk)->sum('t_nguonkhac');
        $t_nguonkhac = $t_nguonkhac_xml2 + $t_nguonkhac_xml3;

        if ($data->t_nguonkhac != round($t_nguonkhac, 2)) {
            $errorCode = $this->generateErrorCode('INVALID_EXPENSE_T_NGUONKHAC');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Tiền nguồn khác chi trả ngoài BH không khớp',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Tiền nguồn khác trong XML1: ' . number_format($data->t_nguonkhac) . ' <> chi phí tổng tiền nguồn khác trong XML2 và XML3: ' . number_format($t_nguonkhac)
            ]);
        }

        // Kiểm tra tiền t_bhtt_gdv
        $prefix_hein_card_exclude_t_bhtt_gdv = config('xml3176.prefix_hein_card_exclude_t_bhtt_gdv');
        // Check if the ma_the_bhyt starts with any of the excluded prefixes
        $excluded = false;
        foreach ($prefix_hein_card_exclude_t_bhtt_gdv as $prefix) {
            if (strpos($data->ma_the_bhyt, $prefix) === 0) {
                $excluded = true;
                break;
            }
        }

        if (!$excluded) {
            $sum_t_bhtt_xml2 = Xml3176Xml2::where('ma_lk', $data->ma_lk)->where('ma_pttt', 1)->sum('t_bhtt');
            $sum_t_bhtt_xml3 = Xml3176Xml3::where('ma_lk', $data->ma_lk)->where('ma_pttt', 1)->sum('t_bhtt');
            $total_t_bhtt = $sum_t_bhtt_xml2 + $sum_t_bhtt_xml3;
            $t_bhtt_gdv = doubleval($data->t_bhtt_gdv) ?? 0;

            if ($data->t_bhtt_gdv != round($total_t_bhtt, 2)) {
                $errorCode = $this->generateErrorCode('INVALID_EXPENSE_T_BHTT_GDV');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Tiền bảo hiểm thanh toán GDV không khớp',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Tiền bảo hiểm thanh toán GDV trong XML1: ' . $t_bhtt_gdv . ' <> tổng tiền trong XML2 và XML3: ' . $total_t_bhtt
                ]);
            }
        }

        return $errors;
    }

    // Thêm các phương thức kiểm tra khác ở đây
}
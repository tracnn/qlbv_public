<?php

namespace App\Services;

use App\Models\BHYT\Qd130Xml1;
use App\Models\BHYT\Qd130Xml2;
use App\Models\BHYT\Qd130Xml3;
use App\Models\BHYT\Qd130Xml4;
use App\Models\BHYT\Qd130Xml5;
use App\Models\BHYT\Qd130Xml7;
use App\Models\BHYT\Qd130Xml8;
use App\Models\BHYT\Qd130Xml9;
use App\Models\BHYT\Qd130Xml11;
use App\Models\BHYT\Qd130Xml12;
use App\Models\BHYT\Qd130Xml13;
use App\Models\BHYT\Qd130Xml14;
use Illuminate\Support\Collection;

use DateTime;

class Qd130XmlCompleteChecker
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


    public function __construct(Qd130XmlErrorService $xmlErrorService)
    {
        $this->xmlErrorService = $xmlErrorService;
        $this->setConditions();
    }

    protected function setConditions()
    {
        $this->xmlType = 'XMLComplete';
        $this->prefix = $this->xmlType . '_';
        $this->xmlTypeMustHaveXml7 = config('qd130xml.treatment_type_inpatient');
        $this->materialGroupCodes = config('qd130xml.material_group_code');
        $this->invalidKetQuaDtri = config('qd130xml.invalid_treatment_result');
        $this->invalidMaLoaiRV = config('qd130xml.invalid_end_type_treatment');
        $this->bedGroupCodes = config('qd130xml.bed_group_code');
        $this->treatmentTypeInpatient = config('qd130xml.treatment_type_inpatient');
    }

    /**
     * Check Qd130Xml Errors
     *
     * @param $ma_lk
     * @return void
     */
    public function checkErrors($ma_lk): void
    {
        $data = Qd130Xml1::where('ma_lk', $ma_lk)->first();
        // Thực hiện kiểm tra lỗi
        if ($data) {
            $errors = collect();
        
            $errors = $errors->merge($this->infoChecker($ma_lk));
            $errors = $errors->merge($this->checkInvalidBedDays($data));
            $errors = $errors->merge($this->checkExpenseErrors($data));

            // Save errors to xml_error_checks table
            $this->xmlErrorService->saveErrors($this->xmlType, $data->ma_lk, $data->stt, $errors);
        }
    }

    /**
     * Check for reason for admission errors
     *
     * @param Qd130Xml11 $data
     * @return Collection
     */
    private function infoChecker($ma_lk): Collection
    {
        $errors = collect();

        if (empty($ma_lk)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_XML_COMPLETE_MA_LK',
                'error_name' => 'Thiếu mã liên kết hồ sơ',
                'critical_error' => true,
                'description' => 'Mã liên kết hồ sơ không được để trống'
            ]);
        } else {
            $existXml1 = Qd130Xml1::where('ma_lk', $ma_lk)->first();
            if (!$existXml1) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INFO_ERROR_XML_COMPLETE_MA_LK_NOT_FOUND',
                    'error_name' => 'Hồ sơ không tồn tại',
                    'critical_error' => true,
                    'description' => 'Hồ sơ không tồn tại. Mã hồ sơ: ' . $ma_lk
                ]);
            } else {
                // Kiểm tra ma_loai_kcb thuộc xmlTypeMustHaveXml7
                if (in_array($existXml1->ma_loai_kcb, $this->xmlTypeMustHaveXml7) 
                    && !in_array($existXml1->ma_loai_rv, config('qd130xml.treatment_end_type_absconding'))) {
                    $existXml7 = Qd130Xml7::where('ma_lk', $ma_lk)->exists();
                    if (!$existXml7) {
                        $errors->push((object)[
                            'error_code' => $this->prefix . 'INFO_ERROR_XML_COMPLETE_MISSING_XML7',
                            'error_name' => 'Thiếu hồ sơ XML7 (Giấy ra viện)',
                            'critical_error' => true,
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
    private function checkInvalidBedDays(Qd130Xml1 $data): Collection
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
            $totalBedDays = $data->Qd130Xml3()->whereIn('ma_nhom', $this->bedGroupCodes)->sum('so_luong');
            if ($hoursDifference < 4) {
                // Check if there are bed charges in Qd130Xml3
                if ($totalBedDays > 0) {
                    $errors->push((object)[
                        'error_code' => $this->prefix . 'SHORT_INPATIENT_STAY',
                        'error_name' => 'Điều trị nội trú < 4h không được tính tiền giường',
                        'description' => 'Thời gian điều trị nội trú nhỏ hơn 4 giờ, không được tính tiền giường.'
                    ]);
                }
            } elseif ($hoursDifference >= 4 && $hoursDifference <= 24) {
                // Check if the total bed days exceed the treatment days
                if ($totalBedDays >= 2) {
                    $errors->push((object)[
                        'error_code' => $this->prefix . 'EXCESS_BED_DAYS',
                        'error_name' => 'Điều trị nội trú >= 4h và <= 24h tính thừa ngày giường',
                        'description' => 'Thời gian điều trị nội trú từ 4 đến 24 giờ, tính thừa ngày giường: ' .$totalBedDays
                    ]);
                }
            }
        }

        if (in_array($data->ma_loai_kcb, $this->treatmentTypeInpatient) && $data->so_ngay_dtri >= 2 &&
            (!in_array($data->ket_qua_dtri, $this->invalidKetQuaDtri) ||
            !in_array($data->ma_loai_rv, $this->invalidMaLoaiRV))) {

            $totalBedDays = $data->Qd130Xml3()->whereIn('ma_nhom', $this->bedGroupCodes)->sum('so_luong');

            if ($totalBedDays >= $data->so_ngay_dtri && $hoursExcludeDaysDifference < 4) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INVALID_BED_DAYS',
                    'error_name' => 'Thanh toán ngày giường sai quy định (trừ trường hợp đặc biệt)',
                    'description' => 'Tổng ngày giường: ' . $totalBedDays . ' lớn hơn hoặc bằng số ngày điều trị + 1: ' . $data->so_ngay_dtri
                ]);
            }
        }

        return $errors;
    }

    /**
     * Hàm kiểm tra các loại tiền chi phí trong hồ sơ tổng hợp và hồ sơ chi tiết
     */
    private function checkExpenseErrors(Qd130Xml1 $data): Collection
    {
        $errors = collect();

        // Kiểm tra t_thuoc
        $sum_t_thuoc = Qd130Xml2::where('ma_lk', $data->ma_lk)->sum('thanh_tien_bv');
        if ($data->t_thuoc != round($sum_t_thuoc,2)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INVALID_EXPENSE_DRUG',
                'error_name' => 'Tiền thuốc không khớp',
                'critical_error' => true,
                'description' => 'Tiền thuốc trong XML1: ' . number_format($data->t_thuoc) . ' <> tổng tiền trong XML2: ' . number_format($sum_t_thuoc)
            ]);
        }

        //Kiểm tra tiền VTYT
        $sum_t_vtyt = Qd130Xml3::where('ma_lk', $data->ma_lk)->whereIn('ma_nhom', $this->materialGroupCodes)->sum('thanh_tien_bv');
        if ($data->t_vtyt != round($sum_t_vtyt,2)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INVALID_EXPENSE_T_VTYT',
                'error_name' => 'Tiền vật tư y tế không khớp',
                'critical_error' => true,
                'description' => 'Tiền VTYT trong XML1: ' . number_format($data->t_vtyt) . ' <> tổng tiền VTYT trong XML3: ' . number_format($sum_t_vtyt)
            ]);
        }

        //Kiểm tra t_tongchi_bv
        $sum_t_tongchi_bv_xml2 = Qd130Xml2::where('ma_lk', $data->ma_lk)->sum('thanh_tien_bv');
        $sum_t_tongchi_bv_xml3 = Qd130Xml3::where('ma_lk', $data->ma_lk)->sum('thanh_tien_bv');
        $sum_t_tongchi_bv = $sum_t_tongchi_bv_xml2 + $sum_t_tongchi_bv_xml3;
        if ($data->t_tongchi_bv != round($sum_t_tongchi_bv,2)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INVALID_EXPENSE_T_TONGCHI_BV',
                'error_name' => 'Tổng chi phí không khớp',
                'critical_error' => true,
                'description' => 'Tiền tổng chi phí trong XML1: ' . number_format($data->t_tongchi_bv) . ' <> chi phí trong XML2 và XML3: ' . number_format($sum_t_tongchi_bv)
            ]);
        }

        //Kiểm tra t_tongchi_bh
        $sum_t_tongchi_bh_xml2 = Qd130Xml2::where('ma_lk', $data->ma_lk)->sum('thanh_tien_bh');
        $sum_t_tongchi_bh_xml3 = Qd130Xml3::where('ma_lk', $data->ma_lk)->sum('thanh_tien_bh');
        $sum_t_tongchi_bh = $sum_t_tongchi_bh_xml2 + $sum_t_tongchi_bh_xml3;
        if ($data->t_tongchi_bh != round($sum_t_tongchi_bh,2)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INVALID_EXPENSE_T_TONGCHI_BH',
                'error_name' => 'Tổng chi phí BH thanh toán không khớp',
                'critical_error' => true,
                'description' => 'Tiền tổng chi phí BH trong XML1: ' . number_format($data->sum_t_tongchi_bh) . ' <> chi phí BH trong XML2 và XML3: ' . number_format($sum_t_tongchi_bh)
            ]);
        }

        //Kiểm tra t_bntt
        $sum_t_bntt_xml2 = Qd130Xml2::where('ma_lk', $data->ma_lk)->sum('t_bntt');
        $sum_t_bntt_xml3 = Qd130Xml3::where('ma_lk', $data->ma_lk)->sum('t_bntt');
        $sum_t_bntt = $sum_t_bntt_xml2 + $sum_t_bntt_xml3;
        if ($data->t_bntt != round($sum_t_bntt,2)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INVALID_EXPENSE_T_BNTT',
                'error_name' => 'Tổng chi phí BN thanh toán không khớp',
                'critical_error' => true,
                'description' => 'Tiền tổng chi phí BN trong XML1: ' . number_format($data->t_bntt) . ' <> chi phí BN trong XML2 và XML3: ' . number_format($sum_t_bntt)
            ]);
        }

        //Kiểm tra t_bncct
        $sum_t_bncct_xml2 = Qd130Xml2::where('ma_lk', $data->ma_lk)->sum('t_bncct');
        $sum_t_bncct_xml3 = Qd130Xml3::where('ma_lk', $data->ma_lk)->sum('t_bncct');
        $sum_t_bncct = $sum_t_bncct_xml2 + $sum_t_bncct_xml3;
        if ($data->t_bncct != round($sum_t_bncct,2)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INVALID_EXPENSE_T_BNCCT',
                'error_name' => 'Tổng chi phí BN CCT không khớp',
                'critical_error' => true,
                'description' => 'Tiền tổng chi phí BN CCT trong XML1: ' . number_format($data->sum_t_bncct) . ' <> chi phí BN CCT trong XML2 và XML3: ' . number_format($sum_t_bncct)
            ]);
        }

        //Kiểm tra t_bhtt
        $t_tongchi_bh = Qd130Xml1::where('ma_lk', $data->ma_lk)->sum('t_tongchi_bh');
        $t_bncct = Qd130Xml1::where('ma_lk', $data->ma_lk)->sum('t_bncct');
        $t_bhtt = $t_tongchi_bh - $t_bncct;
        if ($data->t_bhtt != round($t_bhtt,2)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INVALID_EXPENSE_T_BHTT',
                'error_name' => 'Tiền BHTT không khớp',
                'critical_error' => true,
                'description' => 'Tiền BHTT trong XML1: ' . number_format($data->t_bhtt) . ' <> chi phí T_TONGCHI_BH trong XML1 - T_BNCCT trong XML1: ' . number_format($t_bhtt)
            ]);
        }

        //Kiểm tra t_nguonkhac
        $t_nguonkhac_xml2 = Qd130Xml2::where('ma_lk', $data->ma_lk)->sum('t_nguonkhac');
        $t_nguonkhac_xml3 = Qd130Xml3::where('ma_lk', $data->ma_lk)->sum('t_nguonkhac');
        $t_nguonkhac = $t_nguonkhac_xml2 + $t_nguonkhac_xml3;
        if ($data->t_nguonkhac != round($t_nguonkhac,2)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INVALID_EXPENSE_T_NGUONKHAC',
                'error_name' => 'Tiền nguồn khác chi trả ngoài BH không khớp',
                'critical_error' => true,
                'description' => 'Tiền nguồn khác trong XML1: ' . number_format($data->t_nguonkhac) . ' <> chi phí tổng tiền nguồn khác trong XML2 và XML3: ' . number_format($t_nguonkhac)
            ]);
        }

        // Kiểm tra tiền t_bhtt_gdv
        $sum_t_bhtt_xml2 = Qd130Xml2::where('ma_lk', $data->ma_lk)->where('ma_pttt', 1)->sum('t_bhtt');
        $sum_t_bhtt_xml3 = Qd130Xml3::where('ma_lk', $data->ma_lk)->where('ma_pttt', 1)->sum('t_bhtt');
        $total_t_bhtt = $sum_t_bhtt_xml2 + $sum_t_bhtt_xml3;
        $t_bhtt_gdv = doubleval($data->t_bhtt_gdv) ?? 0;

        if ($data->t_bhtt_gdv != round($total_t_bhtt, 2)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INVALID_EXPENSE_T_BHTT_GDV',
                'error_name' => 'Tiền bảo hiểm thanh toán GDV không khớp',
                'critical_error' => true,
                'description' => 'Tiền bảo hiểm thanh toán GDV trong XML1: ' . $t_bhtt_gdv . ' <> tổng tiền trong XML2 và XML3: ' . $total_t_bhtt
            ]);
        }

        return $errors;
    }

    // Thêm các phương thức kiểm tra khác ở đây
}
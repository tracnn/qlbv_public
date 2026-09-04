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
use App\Services\Xml3176\Support\Xml3176DateHelper;
use App\Models\BHYT\MedicalOrganization;
use App\Services\Xml3176\Support\MucHuongCalculator;
use App\Services\Xml3176\Support\BedDaysTT39Calculator;
use App\Services\Xml3176\Support\ExaminationFeeCalculator;
use App\Services\Mcct\NguongMienCungChiTra;
use Illuminate\Support\Collection;

use DateTime;

class Xml3176CompleteChecker
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

    public function __construct(Xml3176ErrorService $xmlErrorService)
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
            $errors = $errors->merge($this->checkMissingTransferOrAppointment($data));
            $errors = $errors->merge($this->checkXml4NgayKqMismatchXml3($ma_lk));
            $errors = $errors->merge($this->checkSecondSurgeryFullPayment($ma_lk));
            $errors = $errors->merge($this->checkMucHuong($data));
            $errors = $errors->merge($this->checkBedDaysBelowTT39($data));

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

        // Hồ sơ KHÔNG phải nội trú: theo TT39/2024/TT-BYT, khám nhiều chuyên khoa KHÁC nhau
        // trong cùng một lần đến khám là hợp lệ (từ lần 2 tính 30% mức giá), nên chỉ bắt:
        //  (1) cùng MỘT mã dịch vụ khám bị lặp > 1 lần;
        //  (2) tổng tiền khám vượt trần 2 lần mức giá của 1 lần khám;
        //  (3) có nhiều hơn 1 dòng khám ở mức giá đầy đủ (lần 2+ chưa tính 30%).
        if (!in_array($data->ma_loai_kcb, $this->treatmentTypeInpatient) && $records->count() > 0) {
            $maTrung = ExaminationFeeCalculator::maTrung($records->pluck('ma_dich_vu')->all());
            if (!empty($maTrung)) {
                $errorCode = $this->generateErrorCode('DUPLICATE_EXAMINATION_SERVICE');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Trùng dịch vụ khám bệnh',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mỗi dịch vụ khám chỉ được tính 1 lần. Dịch vụ khám bị lặp: '
                        . implode(', ', $maTrung)
                ]);
            }

            $tongThanhTien = (float) $records->sum('thanh_tien_bh');
            $donGiaMax     = (float) $records->max('don_gia_bh');
            $heSo          = (float) config('xml3176.examination.cap_multiplier', 2.0);
            $eps           = (float) config('xml3176.examination.cap_epsilon', 0.01);

            if (ExaminationFeeCalculator::vuotTran($tongThanhTien, $donGiaMax, $heSo, $eps)) {
                $errorCode = $this->generateErrorCode('EXAMINATION_FEE_EXCEEDS_CAP');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Tiền khám vượt trần 2 lần mức giá một lần khám',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Tổng tiền khám ' . $tongThanhTien . ' vượt trần ' . ($heSo * $donGiaMax)
                        . ' (= ' . $heSo . ' x mức giá một lần khám ' . $donGiaMax . ').'
                ]);
            }

            // Từ lần khám thứ 2 trở đi chỉ được tính 30% mức giá: chỉ 1 dòng khám được ở
            // mức giá đầy đủ. Soi thành tiền để đúng dù cơ sở ghi phần giảm ở đơn giá hay tỷ lệ.
            $tyLeLan2 = (float) config('xml3176.examination.second_visit_rate', 0.30);
            $thanhTiens = $records->pluck('thanh_tien_bh')->all();
            $soChuaGiam = ExaminationFeeCalculator::soDongChuaGiam($thanhTiens, $tyLeLan2, $eps);
            if ($soChuaGiam > 1) {
                $errorCode = $this->generateErrorCode('EXAMINATION_SECOND_VISIT_RATE');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Lần khám thứ 2 trở đi chưa tính 30% mức giá',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Từ lần khám thứ 2 chỉ được tính ' . ($tyLeLan2 * 100)
                        . '% mức giá một lần khám; có ' . $soChuaGiam . ' dòng khám ở mức giá đầy đủ.'
                ]);
            }
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
     * Cảnh báo hồ sơ nội trú khai tổng ngày giường NHỎ HƠN số ngày điều trị
     * tính theo TT39 (dương lịch + quy tắc 4h + cờ đặc biệt). Lỗi giám định 440.
     * Guard "thiếu căn cứ thì im lặng". critical_error do catalog quyết định (=false sau seed).
     *
     * @param Xml3176Xml1 $data
     * @return Collection
     */
    private function checkBedDaysBelowTT39(Xml3176Xml1 $data): Collection
    {
        $errors = collect();

        if (!in_array($data->ma_loai_kcb, (array) config('xml3176.treatment_type_inpatient', []))) {
            return $errors; // chỉ hồ sơ nội trú
        }

        if (in_array($data->ma_loai_kcb, (array) config('xml3176.xml1.ma_loai_kcb_khong_tinh_ngay_dieu_tri', []))) {
            return $errors; // loại KCB không tính ngày điều trị (vd '09') -> không đòi ngày giường tối thiểu
        }

        $dtVao = Xml3176DateHelper::toDateTime($data->ngay_vao);
        $dtRa  = Xml3176DateHelper::toDateTime($data->ngay_ra);
        if ($dtVao === null || $dtRa === null) {
            return $errors; // guard: ngày không hợp lệ
        }

        $elapsedHours = ($dtRa->getTimestamp() - $dtVao->getTimestamp()) / 3600;
        if ($elapsedHours < 0) {
            return $errors; // guard: ra trước vào
        }

        $calendarDays = (int) (new DateTime($dtVao->format('Y-m-d')))
            ->diff(new DateTime($dtRa->format('Y-m-d')))->days;

        $special = in_array($data->ket_qua_dtri, (array) config('xml3176.invalid_treatment_result', []))
                || in_array($data->ma_loai_rv, (array) config('xml3176.invalid_end_type_treatment', []));

        $expected = BedDaysTT39Calculator::expected($calendarDays, $elapsedHours, $special);
        if ($expected < 1) {
            return $errors; // guard: lưu trú <4h hợp lệ
        }

        $totalBedDays = (float) $data->Xml3176Xml3()
            ->whereIn('ma_nhom', (array) config('xml3176.bed_group_code', []))
            ->sum('so_luong');

        $tol = (float) config('xml3176.bed_days_tt39.tolerance', 0.5);
        if (BedDaysTT39Calculator::isBelow($totalBedDays, $expected, $tol)) {
            $errorCode = $this->generateErrorCode('BED_DAYS_BELOW_TT39');
            $errors->push((object)[
                'error_code'     => $errorCode,
                'error_name'     => 'Tổng ngày giường nhỏ hơn hướng dẫn TT39',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description'    => 'Tổng ngày giường khai ' . $totalBedDays . ' nhỏ hơn số ngày điều trị theo TT39 '
                                  . $expected . ' (chênh ' . round($expected - $totalBedDays, 2) . ').',
            ]);
        }

        return $errors;
    }

    /**
     * Kiểm tra mức hưởng khai (muc_huong) có vượt trần cho phép không.
     *  - Đúng tuyến, chi phí >= 15% lương cơ sở -> trần = quyền lợi thẻ.
     *  - Trái tuyến nội trú tuyến TW -> trần = 40%.
     * Guard "thiếu căn cứ thì im lặng" (xem spec §7). Phát tối đa 1 lỗi/hồ sơ.
     *
     * @param Xml3176Xml1 $data
     * @return Collection
     */
    private function checkMucHuong(Xml3176Xml1 $data): Collection
    {
        $errors = collect();
        $cfg = config('xml3176.muc_huong');

        $qlChar = MucHuongCalculator::quyenLoiChar($data->ma_the_bhyt);
        if ($qlChar === null) {
            return $errors; // guard: quyền lợi mơ hồ
        }
        $entitlement = MucHuongCalculator::entitlement($qlChar, (array) $cfg['quyen_loi_map']);
        if ($entitlement === null) {
            return $errors; // guard: không map được quyền lợi
        }

        $traiTuyenPrefixes = (array) config('xml3176.xml1.ma_doituong_kcb_trai_tuyen', []);
        $maDoiTuong = (string) $data->ma_doituong_kcb;
        $traiTuyen = false;
        foreach ($traiTuyenPrefixes as $prefix) {
            if ($prefix !== '' && strpos($maDoiTuong, (string) $prefix) === 0) {
                $traiTuyen = true;
                break;
            }
        }
        $noiTru = in_array($data->ma_loai_kcb, (array) config('xml3176.treatment_type_inpatient', []));

        if ($traiTuyen) {
            if (!$noiTru) {
                return $errors; // ngoài phạm vi: trái tuyến ngoại trú
            }
            $tuyen = MedicalOrganization::where('ma_cskcb', $data->ma_cskcb)->value('tuyen_cmkt');
            if (!in_array($tuyen, (array) $cfg['tuyen_tw_values'], true)) {
                return $errors; // GUARD: không xác định được tuyến TW
            }
            $tran = (int) $cfg['trai_tuyen_noi_tru_tw_rate'];
            $errorKey = 'MUC_HUONG_TRAI_TUYEN_TW';
            $loaiMo = 'trái tuyến nội trú tuyến TW';
        } else {
            $dt = Xml3176DateHelper::toDateTime($data->ngay_vao);
            if ($dt === null) {
                return $errors; // guard: ngày vào không hợp lệ
            }
            $lcs = NguongMienCungChiTra::luongCoSoTaiNgay($dt->format('Y-m-d'), (array) config('mcct.luong_co_so', []));
            $tran = MucHuongCalculator::tranDungTuyen(
                $entitlement,
                (float) $data->t_tongchi_bh,
                $lcs ?: null,
                (float) $cfg['nguong_luong_co_so_rate']
            );
            if ($tran === null) {
                return $errors; // guard: chưa có mốc lương cơ sở
            }
            $errorKey = 'MUC_HUONG_EXCEEDS_ENTITLEMENT';
            $loaiMo = 'đúng tuyến';
        }

        $maxXml2 = $data->Xml3176Xml2()->whereNotNull('muc_huong')->max('muc_huong');
        $maxXml3 = $data->Xml3176Xml3()->whereNotNull('muc_huong')->max('muc_huong');
        if ($maxXml2 === null && $maxXml3 === null) {
            return $errors; // guard: không dòng nào khai mức hưởng
        }
        $maxDeclared = max((float) $maxXml2, (float) $maxXml3);

        if ($maxDeclared > $tran + 0.01) {
            $errorCode = $this->generateErrorCode($errorKey);
            $errors->push((object)[
                'error_code'     => $errorCode,
                'error_name'     => 'Mức hưởng khai vượt trần cho phép',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description'    => 'Hồ sơ ' . $loaiMo . ': mức hưởng khai tối đa ' . $maxDeclared
                                  . '%, trần cho phép ' . $tran . '% (quyền lợi thẻ ' . $entitlement . '%).',
            ]);
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

    /**
     * #2498 — Có mã nơi đi nhưng thiếu CẢ giấy chuyển tuyến (XML13) LẪN giấy hẹn khám lại (XML14).
     */
    private function checkMissingTransferOrAppointment(Xml3176Xml1 $data): Collection
    {
        $errors = collect();
        if (empty($data->ma_noi_di)) {
            return $errors;
        }
        $hasXml13 = Xml3176Xml13::where('ma_lk', $data->ma_lk)->exists();
        $hasXml14 = Xml3176Xml14::where('ma_lk', $data->ma_lk)->exists();
        if (!$hasXml13 && !$hasXml14) {
            $code = $this->generateErrorCode('MISSING_TRANSFER_OR_APPOINTMENT');
            $errors->push((object) [
                'error_code' => $code, 'error_name' => 'Có nơi đi nhưng thiếu giấy chuyển tuyến hoặc hẹn khám lại',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                'description' => 'Mã nơi đi ' . $data->ma_noi_di . ' nhưng không có XML13 (chuyển tuyến) lẫn XML14 (hẹn khám lại)',
            ]);
        }
        return $errors;
    }

    /**
     * #2098 — Ngày KQ tại XML4 không khớp ngày KQ tại XML3 (cùng ma_dich_vu).
     */
    private function checkXml4NgayKqMismatchXml3($ma_lk): Collection
    {
        $errors = collect();

        $xml3 = Xml3176Xml3::where('ma_lk', $ma_lk)
            ->whereNotNull('ngay_kq')->where('ngay_kq', '<>', '')->get()->groupBy('ma_dich_vu');
        $xml4 = Xml3176Xml4::where('ma_lk', $ma_lk)
            ->whereNotNull('ngay_kq')->where('ngay_kq', '<>', '')->get();

        foreach ($xml4 as $r4) {
            if (!isset($xml3[$r4->ma_dich_vu])) {
                continue;
            }
            $days3 = $xml3[$r4->ma_dich_vu]
                ->map(function ($r) { return Xml3176DateHelper::datePart($r->ngay_kq); })
                ->filter()->unique();
            $day4 = Xml3176DateHelper::datePart($r4->ngay_kq);
            if ($day4 !== null && $days3->isNotEmpty() && !$days3->contains($day4)) {
                $code = $this->generateErrorCode('XML4_NGAY_KQ_MISMATCH_XML3');
                $errors->push((object) [
                    'error_code' => $code, 'error_name' => 'Ngày KQ XML4 khác ngày KQ XML3',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                    'description' => 'Dịch vụ ' . $r4->ma_dich_vu . ': ngày KQ XML4 (' . strtodatetime($r4->ngay_kq) . ') khác ngày KQ XML3',
                ]);
            }
        }
        return $errors;
    }

    /**
     * #891 — PTTT lần 2 trở đi trong cùng ngày có tỷ lệ thanh toán = 100% (CV824/QĐ3176).
     */
    private function checkSecondSurgeryFullPayment($ma_lk): Collection
    {
        $errors = collect();
        $rate = (float) config('xml3176.xml3.surgery_full_payment_rate', '100');

        $rows = Xml3176Xml3::where('ma_lk', $ma_lk)
            ->whereNotNull('ma_pttt')->where('ma_pttt', '<>', '')
            ->orderBy('ngay_yl')->get();

        $byDay = [];
        foreach ($rows as $r) {
            $day = Xml3176DateHelper::datePart($r->ngay_yl);
            if ($day === null) {
                continue;
            }
            $byDay[$day][] = $r;
        }

        foreach ($byDay as $day => $list) {
            if (count($list) < 2) {
                continue;
            }
            for ($i = 1; $i < count($list); $i++) {
                if ((float) $list[$i]->tyle_tt_dv === $rate) {
                    $code = $this->generateErrorCode('SECOND_SURGERY_FULL_PAYMENT');
                    $errors->push((object) [
                        'error_code' => $code, 'error_name' => 'PTTT lần 2 trong ngày thanh toán 100%',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                        'description' => 'PTTT lần ' . ($i + 1) . ' ngày ' . $day . ' (dịch vụ ' . $list[$i]->ma_dich_vu . ') thanh toán ' . $rate . '%',
                    ]);
                }
            }
        }
        return $errors;
    }

    // Thêm các phương thức kiểm tra khác ở đây
}
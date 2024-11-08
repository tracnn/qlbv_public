<?php

namespace App\Services;

use App\Models\BHYT\Qd130Xml11;
use Illuminate\Support\Collection;

use Carbon\Carbon;

class Qd130Xml11Checker
{
    protected $xmlErrorService;
    protected $commonValidationService;
    protected $prefix;

    protected $xmlType;

    protected $docXml11Type;

    public function __construct(Qd130XmlErrorService $xmlErrorService, CommonValidationService $commonValidationService)
    {
        $this->xmlErrorService = $xmlErrorService;
        $this->commonValidationService = $commonValidationService;
        $this->setConditions();
    }

    protected function setConditions()
    {
        $this->xmlType = 'XML11';
        $this->prefix = $this->xmlType . '_';
        $this->docXml11Type = config('qd130xml.type_xml11_doc');

    }

    protected function generateErrorCode(string $errorKey): string
    {
        return $this->prefix . $errorKey;
    }
    
    /**
     * Check Qd130Xml11 Errors
     *
     * @param Qd130Xml11 $data
     * @return void
     */
    public function checkErrors(Qd130Xml11 $data): void
    {
        // Thực hiện kiểm tra lỗi
        $errors = collect();

        $errors = $errors->merge($this->infoChecker($data));

        // Save errors to xml_error_checks table
        $this->xmlErrorService->saveErrors($this->xmlType, $data->ma_lk, 1, $errors);
    }


    /**
     * Check for reason for admission errors
     *
     * @param Qd130Xml11 $data
     * @return Collection
     */
    private function infoChecker(Qd130Xml11 $data): Collection
    {
        $errors = collect();

        if (empty($data->so_ct)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_SO_CT');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu số chứng từ',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Số chứng từ không được để trống'
            ]);
        }

        if (empty($data->so_seri)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_SO_SERI');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu số serial',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Số serial không được để trống'
            ]);
        }

        if (empty($data->so_kcb)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_SO_KCB');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu số KCB',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Số KCB không được để trống'
            ]);
        }

        if (empty($data->ma_bhxh)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_MA_BHXH');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã BHXH',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã BHXH không được để trống'
            ]);
        } elseif (strlen($data->ma_bhxh) !== 10) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_MA_BHXH_LENGTH');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Mã BHXH không hợp lệ',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã BHXH phải có độ dài là 10 ký tự: ' . $data->ma_bhxh
            ]);
        }

        if (empty($data->ma_the_bhyt)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_MA_THE_BHYT');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã thẻ BHYT',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã thẻ BHYT không được để trống'
            ]);
        } elseif (strlen($data->ma_the_bhyt) !== 15) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_MA_THE_BHYT_LENGTH');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Mã thẻ BHYT không hợp lệ',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã thẻ BHYT phải có độ dài là 15 ký tự: ' . $data->ma_the_bhyt
            ]);
        }

        if (empty($data->chan_doan_rv)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_CHAN_DOAN_RV');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu chẩn đoán ra viện',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Chẩn đoán ra viện không được để trống'
            ]);
        }

        if (empty($data->pp_dieutri)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_PP_DIEUTRI');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu phương pháp điều trị',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Phương pháp điều trị không được để trống'
            ]);
        }

        if (empty($data->so_ngay_nghi)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_SO_NGAY_NGHI');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu số ngày nghỉ',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Số ngày nghỉ không được để trống'
            ]);
        } elseif (!ctype_digit((string)$data->so_ngay_nghi) || (int)$data->so_ngay_nghi <= 0) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_SO_NGAY_NGHI_NOT_INT');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Số ngày nghỉ không hợp lệ',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Số ngày nghỉ phải là một số nguyên lớn hơn 0: ' . $data->so_ngay_nghi
            ]);
        }

        if (empty($data->tu_ngay)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_TU_NGAY');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu từ ngày',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Từ ngày không được để trống'
            ]);
        } else {
            try {
                // Parse tu_ngay (Ymd format) using Carbon and remove time (set to 00:00:00)
                $tuNgayDate = Carbon::createFromFormat('Ymd', $data->tu_ngay)->startOfDay();

                // Parse ngay_ra (YmdHi format) using Carbon and remove time (set to 00:00:00)
                $ngayRaDate = Carbon::createFromFormat('YmdHi', $data->Qd130Xml1->ngay_ra)->startOfDay();

                // Check if tu_ngay is greater than ngay_ra
                if ($tuNgayDate->gt($ngayRaDate)) {
                    $errorCode = $this->generateErrorCode('INFO_ERROR_TU_NGAY_GREATER_NGAY_RA');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Từ ngày lớn hơn ngày ra',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'Từ ngày: ' . $tuNgayDate->format('d/m/Y') . ' không được lớn hơn ngày ra: ' . $ngayRaDate->format('d/m/Y')
                    ]);
                }
            } catch (\Exception $e) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_INVALID_DATE_TU_NGAY');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Định dạng từ ngày không hợp lệ',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Định dạng ngày không đúng, vui lòng kiểm tra lại: ' .$data->tu_ngay
                ]);
            }
        }

        if (empty($data->den_ngay)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_DEN_NGAY');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu đến ngày',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Đến ngày không được để trống'
            ]);
        } else {
            try {
                // Parse den_ngay (Ymd format) using Carbon and remove time (set to 00:00:00)
                $denNgayDate = Carbon::createFromFormat('Ymd', $data->den_ngay)->startOfDay();

                // Parse ngay_vao (YmdHi format) using Carbon and remove time (set to 00:00:00)
                $ngayVaoDate = Carbon::createFromFormat('YmdHi', $data->Qd130Xml1->ngay_vao)->startOfDay();

                // Check if den_ngay is smaller than ngay_vao
                if ($denNgayDate->lt($ngayVaoDate)) {
                    $errorCode = $this->generateErrorCode('INFO_ERROR_DEN_NGAY_LESS_THAN_NGAY_VAO');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Đến ngày nhỏ hơn ngày vào',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'Đến ngày: ' . $denNgayDate->format('d/m/Y') . ' không được nhỏ hơn ngày vào: ' . $ngayVaoDate->format('d/m/Y')
                    ]);
                }
            } catch (\Exception $e) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_INVALID_DATE_DEN_NGAY');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Định dạng đến ngày không hợp lệ',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Định dạng ngày không đúng, vui lòng kiểm tra lại: ' .$data->den_ngay
                ]);
            }
        }

        if (empty($data->ngay_ct)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_NGAY_CT');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu ngày chứng từ',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Ngày chứng từ không được để trống'
            ]);
        }

        if (!empty($data->tu_ngay) && !empty($data->den_ngay)) {
            $tu_ngay_date = \DateTime::createFromFormat('Ymd', $data->tu_ngay);
            $den_ngay_date = \DateTime::createFromFormat('Ymd', $data->den_ngay);

            if ($tu_ngay_date && $den_ngay_date) {
                if ($den_ngay_date < $tu_ngay_date) {
                    $errorCode = $this->generateErrorCode('INFO_ERROR_DATE_RANGE');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Ngày không hợp lệ',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'Đến ngày phải lớn hơn hoặc bằng từ ngày'
                    ]);
                }
            } else {
                $errorCode = $this->generateErrorCode('INFO_ERROR_DATE_FORMAT');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Định dạng ngày không hợp lệ',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Định dạng ngày phải là Ymd'
                ]);
            }
        }

        if (empty($data->ma_bs)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_MA_BS');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã bác sĩ (Mã BHXH)',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã bác sĩ (Mã BHXH) không được để trống'
            ]);
        } else {
            if (!$this->commonValidationService->isMedicalStaffValid($data->ma_bs, 'ma_bhxh')) {
                $errorCode = $this->generateErrorCode('INVALID_MEDICAL_STAFF_MA_BS');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã bác sĩ (Mã BHXH) chưa được duyệt',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã bác sĩ chưa được duyệt danh mục (Mã BHXH: ' . $data->ma_bs . ')'
                ]);
            }
        }

        if (empty($data->ma_ttdv)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_MA_TTDV');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã thủ trưởng đơn vị',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Thủ trưởng đơn vị không được để trống'
            ]);
        } else {
            if (!$this->commonValidationService->isMedicalStaffValid($data->ma_ttdv, 'ma_bhxh')) {
                $errorCode = $this->generateErrorCode('INVALID_MEDICAL_STAFF_MA_TTDV');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã thủ trưởng đơn vị chưa được duyệt',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã thủ trưởng đơn vị chưa được duyệt danh mục (Mã CCHN: ' . $data->ma_ttdv . ')'
                ]);
            }
        }

        if (empty($data->mau_so)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_MAU_SO');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mẫu số',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mẫu số không được để trống'
            ]);
        } elseif (!in_array($data->mau_so, $this->docXml11Type)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_INVALID_MAU_SO');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Mẫu số không hợp lệ',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mẫu số không thuộc loại hợp lệ. Mẫu số: ' . $data->mau_so . '. Loại hợp lệ: ' . implode(', ', $this->docXml11Type)
            ]);
        }

        return $errors;
    }

    // Thêm các phương thức kiểm tra khác ở đây
}
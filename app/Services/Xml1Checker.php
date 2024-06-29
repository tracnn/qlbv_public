<?php

namespace App\Services;

use App\Models\BHYT\XML1;
use Illuminate\Support\Collection;

class Xml1Checker
{
    protected $xmlErrorService;
    protected $prefix;

    protected $xmlType;
    protected $maxWeight;
    protected $specialDKBD;
    protected $admissionReasons;
    protected $invalidKetQuaDtri;
    protected $invalidTinhTrangRV;
    protected $bedGroupCodes;

    public function __construct(XmlErrorService $xmlErrorService)
    {
        $this->xmlErrorService = $xmlErrorService;
        $this->setConditions();
    }

    protected function setConditions()
    {
        $this->xmlType = 'XML1';
        $this->prefix = $this->xmlType . '_';

        $this->maxWeight = 200;
        $this->specialDKBD = ['01013', '01061'];
        $this->admissionReasons = [3, 4];
        $this->invalidKetQuaDtri = [3, 4, 5];
        $this->invalidTinhTrangRV = [2, 3, 4];
        $this->bedGroupCodes = [14, 15, 16];
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
    public function checkErrors(XML1 $data): void
    {
        // Delete errors to xml_error_checks table
        $this->xmlErrorService->deleteErrors($data->ma_lk);

        // Thực hiện kiểm tra lỗi
        $errors = collect();

        $errors = $errors->merge($this->checkWeightError($data));
        $errors = $errors->merge($this->checkReasonForAdmission($data));
        $errors = $errors->merge($this->checkLongTermTreatment($data));
        $errors = $errors->merge($this->checkBirthdateError($data));
        $errors = $errors->merge($this->checkInvalidBedDays($data));

        // Save errors to xml_error_checks table
        $this->xmlErrorService->saveErrors($this->xmlType, $data->ma_lk, $data->stt, $errors);
    }

    /**
     * Check for weight errors
     *
     * @param XML1 $data
     * @return Collection
     */
    private function checkWeightError(XML1 $data): Collection
    {
        $errors = collect();

        if ($data->can_nang > $this->maxWeight) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'WEIGHT_ERROR',
                'error_name' => 'Sai cân nặng',
                'description' => 'Cân nặng vượt quá ' . $this->maxWeight . 'kg'
            ]);
        }

        return $errors;
    }

    /**
     * Check for reason for admission errors
     *
     * @param XML1 $data
     * @return Collection
     */
    private function checkReasonForAdmission(XML1 $data): Collection
    {
        $errors = collect();

        if (in_array($data->ma_dkbd, $this->specialDKBD) && in_array($data->ma_lydo_vvien, $this->admissionReasons)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'REASON_ERROR_SPECIAL',
                'error_name' => 'Lý do vào viện không hợp lệ với MA_DKBD đúng tuyến',
                'description' => 'Lý do vào viện không hợp lệ với MA_DKBD thuộc danh sách đặc biệt'
            ]);
        } elseif (!in_array($data->ma_dkbd, $this->specialDKBD) && $data->ma_lydo_vvien == 4) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'REASON_ERROR_NON_SPECIAL',
                'error_name' => 'Lý do vào viện không hợp lệ với MA_DKBD trái tuyến',
                'description' => 'Lý do vào viện không hợp lệ với MA_DKBD không thuộc danh sách đặc biệt'
            ]);
        } elseif ($data->ma_lydo_vvien == 1 && !in_array($data->ma_dkbd, $this->specialDKBD) && empty($data->ma_noi_chuyen)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'REASON_ERROR_LYDO_1',
                'error_name' => 'Đúng tuyến nhưng Nơi DKBD <> CSKCB và Không có nơi chuyển đến',
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
    private function checkLongTermTreatment(XML1 $data): Collection
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
     * Check for birthdate errors
     *
     * @param XML1 $data
     * @return Collection
     */
    private function checkBirthdateError(XML1 $data): Collection
    {
        $errors = collect();
        $birthdate = $data->ngay_sinh;

        if (strlen($birthdate) == 4) {
            if (!preg_match('/^\d{4}$/', $birthdate)) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'BIRTHDATE_FORMAT_ERROR',
                    'error_name' => 'Sai định dạng năm sinh',
                    'description' => 'Ngày sinh phải ở dạng YYYY'
                ]);
            }
        } elseif (strlen($birthdate) == 8) {
            if (!preg_match('/^\d{4}\d{2}\d{2}$/', $birthdate) || preg_match('/^\d{4}0000$/', $birthdate)) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'BIRTHDATE_FORMAT_ERROR',
                    'error_name' => 'Sai định dạng ngày tháng năm sinh',
                    'description' => 'Ngày sinh phải ở dạng YYYYMMDD và không được là YYYY0000'
                ]);
            }
        } else {
            $errors->push((object)[
                'error_code' => $this->prefix . 'BIRTHDATE_LENGTH_ERROR',
                'error_name' => 'Sai định dạng ngày tháng năm sinh',
                'description' => 'Độ dài của ngày sinh phải là 4 hoặc 8 ký tự'
            ]);
        }

        return $errors;
    }

    /**
     * Check for invalid bed days errors
     *
     * @param XML1 $data
     * @return Collection
     */
    private function checkInvalidBedDays(XML1 $data): Collection
    {
        $errors = collect();

        if ($data->ma_loai_kcb == 3 && $data->so_ngay_dtri >= 2 &&
            (!in_array($data->ket_qua_dtri, $this->invalidKetQuaDtri) ||
            !in_array($data->tinh_trang_rv, $this->invalidTinhTrangRV))) {

            $totalBedDays = $data->xml3()->whereIn('ma_nhom', $this->bedGroupCodes)->sum('so_luong');

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

    // Thêm các phương thức kiểm tra khác ở đây
}
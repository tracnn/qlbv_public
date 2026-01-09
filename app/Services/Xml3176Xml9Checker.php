<?php

namespace App\Services;

use App\Models\BHYT\Xml3176Xml9;
use Illuminate\Support\Collection;

use DateTime;

class Xml3176Xml9Checker
{
    protected $xmlErrorService;
    protected $commonValidationService;
    protected $prefix;

    protected $xmlType;

    public function __construct(Xml3176ErrorService $xmlErrorService, CommonValidationService $commonValidationService)
    {
        $this->xmlErrorService = $xmlErrorService;
        $this->commonValidationService = $commonValidationService;
        $this->setConditions();
    }

    protected function setConditions()
    {
        $this->xmlType = 'XML9';
        $this->prefix = $this->xmlType . '_';
    }

    protected function generateErrorCode(string $errorKey): string
    {
        return $this->prefix . $errorKey;
    }

    /**
     * Check Xml3176Xml9 Errors
     *
     * @param Xml3176Xml9 $data
     * @return void
     */
    public function checkErrors(Xml3176Xml9 $data): void
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
     * @param Xml3176Xml11 $data
     * @return Collection
     */
    private function infoChecker(Xml3176Xml9 $data): Collection
    {
        $errors = collect();

        if (empty($data->ma_bhxh_nnd)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_MA_BHXH_NND');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã BHXH Người nuôi dưỡng',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã BHXH người nuôi dưỡng không được để trống'
            ]);
        } elseif (strlen($data->ma_bhxh_nnd) !== 10) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_MA_BHXH_NND_LENGTH');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Mã BHXH Người nuôi dưỡng không hợp lệ',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã BHXH Người nuôi dưỡng phải có độ dài là 10 ký tự: ' . $data->ma_bhxh_nnd
            ]);
        }

        if (empty($data->ma_the_nnd)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_MA_THE_NND');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã thẻ BHYT Người nuôi dưỡng',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã thẻ BHYT Người nuôi dưỡng không được để trống'
            ]);
        } elseif (strlen($data->ma_the_nnd) !== 15) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_MA_THE_BHYT_NND_LENGTH');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Mã thẻ BHYT Người nuôi dưỡng không hợp lệ',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã thẻ BHYT Người nuôi dưỡng phải có độ dài là 15 ký tự: ' . $data->ma_the_nnd
            ]);
        }

        if (empty($data->ho_ten_nnd)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_HO_TEN_NND');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu họ tên người nuôi dưỡng',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Họ tên người nuôi dưỡng không được để trống'
            ]);
        }

        if (empty($data->ngaysinh_nnd)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_NGAYSINH_NND');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu ngày sinh người nuôi dưỡng',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Ngày sinh người nuôi dưỡng không được để trống'
            ]);
        } else {
            $ngaysinh_nnd_date = DateTime::createFromFormat('Ymd', $data->ngaysinh_nnd);
            
            // Kiểm tra xem ngày sinh có hợp lệ và đúng định dạng Ymd không
            if (!$ngaysinh_nnd_date || $ngaysinh_nnd_date->format('Ymd') !== $data->ngaysinh_nnd) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_INVALID_NGAYSINH_NND');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Định dạng ngày sinh không hợp lệ',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Ngày sinh người nuôi dưỡng phải đúng định dạng Ymd: ' . $data->ngaysinh_nnd
                ]);
            }
        }

        if (empty($data->ma_dantoc_nnd)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_MA_DANTOC_NND');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã dân tộc người nuôi dưỡng',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã dân tộc người nuôi dưỡng không được để trống'
            ]);
        } else {
            // Kiểm tra định dạng mã dân tộc phải là 2 chữ số
            if (!preg_match('/^\d{2}$/', $data->ma_dantoc_nnd)) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_INVALID_MA_DANTOC_NND');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Định dạng mã dân tộc không hợp lệ',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã dân tộc người nuôi dưỡng phải là 2 ký tự số: ' . $data->ma_dantoc_nnd
                ]);
            }
        }
        
        if (empty($data->so_cccd_nnd)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_SO_CCCD_NND');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu CCCD người nuôi dưỡng',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'CCCD người nuôi dưỡng không được để trống'
            ]);
        }

        if (empty($data->ngaycap_cccd_nnd)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_NGAYCAP_CCCD_NND');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu ngày cấp CCCD người nuôi dưỡng',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Ngày cấp CCCD người nuôi dưỡng không được để trống'
            ]);
        } else {
            $ngaycap_cccd_nnd_date = DateTime::createFromFormat('Ymd', $data->ngaycap_cccd_nnd);
            
            // Check if the date is valid and matches the Ymd format
            if (!$ngaycap_cccd_nnd_date || $ngaycap_cccd_nnd_date->format('Ymd') !== $data->ngaycap_cccd_nnd) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_INVALID_NGAYCAP_CCCD_NND');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Định dạng ngày cấp CCCD không hợp lệ',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Ngày cấp CCCD người nuôi dưỡng phải đúng định dạng Ymd: ' . $data->ngaycap_cccd_nnd
                ]);
            } else {
                $currentDate = new DateTime(); // Lấy ngày hiện tại
                if ($ngaycap_cccd_nnd_date > $currentDate) {
                    $errorCode = $this->generateErrorCode('INFO_ERROR_NGAYCAP_CCCD_NND_FUTURE_DATE');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Ngày cấp CCCD không được lớn hơn ngày hiện tại',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'Ngày cấp CCCD người nuôi dưỡng: ' . $data->ngaycap_cccd_nnd . ' không được lớn hơn ngày hiện tại'
                    ]);
                }
            }
        }

        if (empty($data->noicap_cccd_nnd)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_NOICAP_CCCD_NND');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu nơi cấp CCCD người nuôi dưỡng',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Nơi cấp CCCD người nuôi dưỡng không được để trống'
            ]);
        }

        if (empty($data->noi_cu_tru_nnd)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_NOI_CU_TRU_NND');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu nơi cư trú người nuôi dưỡng',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Nơi cư trú người nuôi dưỡng không được để trống'
            ]);
        }

        if (empty($data->ma_quoctich)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_MA_QUOCTICH');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã quốc tịch',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã quốc tịch không được để trống'
            ]);
        } else {
            if (!preg_match('/^\d{3}$/', $data->ma_quoctich)) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_INVALID_MA_QUOCTICH');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Định dạng mã quốc tịch không hợp lệ',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã quốc tịch phải là 3 ký tự số: ' . $data->ma_quoctich
                ]);
            }
        }

        if (empty($data->matinh_cu_tru)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_MATINH_CU_TRU');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã tỉnh cư trú',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã tỉnh cư trú không được để trống'
            ]);
        } else {
            if (!preg_match('/^\d{2}$/', $data->matinh_cu_tru)) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_INVALID_MATINH_CU_TRU');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Định dạng mã tỉnh cư trú không hợp lệ',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã tỉnh cư trú phải là 2 ký tự số: ' . $data->matinh_cu_tru
                ]);
            }
        }

        if (empty($data->mahuyen_cu_tru)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_MAHUYEN_CU_TRU');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã huyện cư trú',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã huyện cư trú không được để trống'
            ]);
        } else {
            if (!preg_match('/^\d{3}$/', $data->mahuyen_cu_tru)) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_INVALID_MAHUYEN_CU_TRU');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Định dạng mã huyện cư trú không hợp lệ',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã huyện cư trú phải là 3 ký tự số: ' . $data->mahuyen_cu_tru
                ]);
            }
        }

        if (empty($data->maxa_cu_tru)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_MAXA_CU_TRU');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã xã cư trú',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã xã cư trú không được để trống'
            ]);
        } else {
            if (!preg_match('/^\d{5}$/', $data->maxa_cu_tru)) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_INVALID_MAXA_CU_TRU');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Định dạng mã xã cư trú không hợp lệ',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã xã cư trú phải là 5 ký tự số: ' . $data->maxa_cu_tru
                ]);
            }
        }

        if (empty($data->ma_the_tam)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_MA_THE_TAM');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã thẻ tạm',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã thẻ tạm không được để trống'
            ]);
        } else {
            $prefixPattern = config('xml3176.hein_card_temp_prefix_pattern'); // '^TE1'
            $numPattern = config('xml3176.hein_card_temp_num_pattern'); // '\d{10}$'

            // Escape mã tỉnh để tránh các ký tự đặc biệt
            $maTinhPattern = preg_quote($data->matinh_cu_tru, '/');
            $fullPattern = '/' . $prefixPattern . $maTinhPattern . $numPattern . '/';

            if (!preg_match($fullPattern, $data->ma_the_tam)) {
                $errorDescription = 'Mã thẻ tạm không đúng theo quy định: ' . $data->ma_the_tam;

                // Kiểm tra phần đầu TE1
                if (!preg_match('/^TE1/', $data->ma_the_tam)) {
                    $errorDescription .= ' - Phần đầu tiên không phải là: TE1';
                }

                // Kiểm tra phần mã tỉnh cụ thể tại vị trí 4 và 5
                if (substr($data->ma_the_tam, 3, 2) !== $data->matinh_cu_tru) { // Vị trí 3 và 4 vì 'TE1' chiếm 3 ký tự đầu
                    $errorDescription .= ' - Phần mã tỉnh của thẻ tạm không đúng, mã đúng: ' . $data->matinh_cu_tru;
                }

                // Kiểm tra phần 10 chữ số cuối
                if (!preg_match('/\d{10}$/', $data->ma_the_tam)) {
                    $errorDescription .= ' - Phần cuối không chứa 10 chữ số.';
                }

                $errorCode = $this->generateErrorCode('INFO_ERROR_INVALID_MA_THE_TAM');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Định dạng mã thẻ tạm không hợp lệ',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => $errorDescription
                ]);
            }
        }

        if (empty($data->ho_ten_con)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_HO_TEN_CON');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu họ tên con',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Họ tên con không được để trống'
            ]);
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
            if (!$this->commonValidationService->isMedicalStaffValid($data->ma_ttdv)) {
                $errorCode = $this->generateErrorCode('INVALID_MEDICAL_STAFF_MA_TTDV');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã thủ trưởng đơn vị chưa được duyệt',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã thủ trưởng đơn vị chưa được duyệt danh mục (Mã CCHN: ' . $data->ma_ttdv . ')'
                ]);
            }
        }

        return $errors;
    }

    // Thêm các phương thức kiểm tra khác ở đây
}
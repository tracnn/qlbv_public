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

    public function __construct(Qd130XmlErrorService $xmlErrorService)
    {
        $this->xmlErrorService = $xmlErrorService;
        $this->setConditions();
    }

    protected function setConditions()
    {
        $this->xmlType = 'XMLComplete';
        $this->prefix = $this->xmlType . '_';
        $this->xmlTypeMustHaveXml7 = ['03', '04','09'];
    }

    /**
     * Check Qd130Xml Errors
     *
     * @param $ma_lk
     * @return void
     */
    public function checkErrors($ma_lk): void
    {
        // Thực hiện kiểm tra lỗi
        $errors = collect();

        $errors = $errors->merge($this->infoChecker($ma_lk));

        // Save errors to xml_error_checks table
        $this->xmlErrorService->saveErrors($this->xmlType, $ma_lk, 1, $errors);
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
                'description' => 'Mã liên kết hồ sơ không được để trống'
            ]);
        } else {
            $existXml1 = Qd130Xml1::where('ma_lk', $ma_lk)->first();
            if (!$existXml1) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INFO_ERROR_XML_COMPLETE_MA_LK_NOT_FOUND',
                    'error_name' => 'Hồ sơ không tồn tại',
                    'description' => 'Hồ sơ không tồn tại. Mã hồ sơ: ' . $ma_lk
                ]);
            } else {
                // Kiểm tra ma_loai_kcb thuộc xmlTypeMustHaveXml7
                if (in_array($existXml1->ma_loai_kcb, $this->xmlTypeMustHaveXml7)) {
                    $existXml7 = Qd130Xml7::where('ma_lk', $ma_lk)->exists();
                    if (!$existXml7) {
                        $errors->push((object)[
                            'error_code' => $this->prefix . 'INFO_ERROR_XML_COMPLETE_MISSING_XML7',
                            'error_name' => 'Thiếu hồ sơ XML7 (Giấy ra viện)',
                            'description' => 'Không tồn tại hồ sơ XML7 (Giấy ra viện) với loại KCB thuộc: ' . implode(', ', $this->xmlTypeMustHaveXml7)
                        ]);
                    }
                }
            }
        }
        return $errors;
    }

    // Thêm các phương thức kiểm tra khác ở đây
}
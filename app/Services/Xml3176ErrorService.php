<?php

namespace App\Services;

use App\Models\BHYT\Xml3176XmlErrorResult;
use App\Models\BHYT\Xml3176XmlErrorCatalog;
use Illuminate\Support\Collection;

class Xml3176XmlErrorService
{
    /**
    * Xóa các lỗi cũ và lưu các lỗi mới
    *
    * @param string $xmlType
    * @param string $ma_lk
    * @param int $stt
    * @param Collection $errors
    * @return void
    **/
    public function deleteErrors(string $ma_lk): void
    {
        // Delete old errors
        Xml3176XmlErrorResult::where('ma_lk', $ma_lk)
        ->delete();
    }

    public function getCriticalErrorStatus($errorCode)
    {
        // Tìm bản ghi trong Xml3176XmlErrorCatalog theo error_code
        $errorCatalog = Xml3176XmlErrorCatalog::where('error_code', $errorCode)->first();

        // Trả về critical_error nếu có, nếu không thì trả về true
        return $errorCatalog ? $errorCatalog->critical_error : true;
    }

    public function saveErrors(string $xmlType, string $ma_lk, int $stt, Collection $errors,  array $additionalData = []): void
    {
        // Save errors to xml_error_checks table
        foreach ($errors as $error) {
            // Xem lỗi này có được đánh dấu kiểm tra không
            $skipCheck = Xml3176XmlErrorCatalog::where('error_code', $error->error_code)
                ->where('is_check', false)
                ->exists();

            // Nếu lỗi được đánh dấu là không kiểm tra thì bỏ qua
            if ($skipCheck) {
                continue;
            }

            $data = [
                'xml' => $xmlType,
                'ma_lk' => $ma_lk,
                'stt' => $stt,
                'error_code' => $error->error_code,
                'description' => $error->description,
                'critical_error' => $error->critical_error ?? false
            ];

            // Merge additional data if provided
            if (!empty($additionalData)) {
                $data = array_merge($data, $additionalData);
            }
            
            Xml3176XmlErrorResult::create($data);

            // Create or update in Xml3176XmlErrorCatalog
            Xml3176XmlErrorCatalog::createOrUpdate($xmlType, $error->error_code, $error->error_name ?? null, $error->critical_error ?? false);
        }
    }
}
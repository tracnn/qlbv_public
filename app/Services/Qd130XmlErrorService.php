<?php

namespace App\Services;

use App\Models\BHYT\Qd130XmlErrorResult;
use App\Models\BHYT\Qd130XmlErrorCatalog;
use Illuminate\Support\Collection;

class Qd130XmlErrorService
{
    /**
     * Xóa các lỗi cũ và lưu các lỗi mới
     *
     * @param string $xmlType
     * @param string $ma_lk
     * @param int $stt
     * @param Collection $errors
     * @return void
     */
    public function deleteErrors(string $ma_lk): void
    {
        // Delete old errors
        Qd130XmlErrorResult::where('ma_lk', $ma_lk)
        ->delete();
    }

    public function saveErrors(string $xmlType, string $ma_lk, int $stt, Collection $errors,  array $additionalData = []): void
    {
        // Save errors to xml_error_checks table
        foreach ($errors as $error) {
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
            
            Qd130XmlErrorResult::create($data);

            // Create or update in Qd130XmlErrorCatalog
            Qd130XmlErrorCatalog::createOrUpdate($xmlType, $error->error_code, $error->error_name ?? null);
        }
    }
}
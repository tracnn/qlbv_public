<?php

namespace App\Services;

use App\Models\XmlErrorCheck;
use App\Models\BHYT\XmlErrorCatalog;
use Illuminate\Support\Collection;

class XmlErrorService
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
        XmlErrorCheck::where('ma_lk', $ma_lk)
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
                'description' => $error->description
            ];

            // Merge additional data if provided
            if (!empty($additionalData)) {
                $data = array_merge($data, $additionalData);
            }
            
            XmlErrorCheck::create($data);

            // Create or update in XmlErrorCatalog
            XmlErrorCatalog::createOrUpdate($xmlType, $error->error_code, $error->error_name ?? null);
        }
    }
}
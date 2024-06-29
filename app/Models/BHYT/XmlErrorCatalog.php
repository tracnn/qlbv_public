<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class XmlErrorCatalog extends Model
{
    protected $fillable = [
        'xml',
        'error_code',
        'error_name',
        'description',
    ];

    /**
     * Create or update an error catalog entry
     *
     * @param string $xmlType
     * @param string $errorCode
     * @param string $description
     * @return void
     */
    public static function createOrUpdate(string $xmlType, string $errorCode, string $errorName = null): void
    {
        $existingRecord = static::where('xml', $xmlType)
                                ->where('error_code', $errorCode)
                                ->first();

        if (!$existingRecord) {
            // Nếu bản ghi không tồn tại, tạo mới với description = $errorCode
            static::create([
                'xml' => $xmlType,
                'error_code' => $errorCode,
                'error_name' => $errorName,
                'description' => $errorName,
            ]);
        }
    }
}

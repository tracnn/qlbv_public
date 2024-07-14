<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class Qd130XmlErrorCatalog extends Model
{
    protected $fillable = [
        'xml',
        'error_code',
        'error_name',
        'description',
        'critical_error',
    ];

    /**
     * Create or update an error catalog entry
     *
     * @param string $xmlType
     * @param string $errorCode
     * @param string $description
     * @return void
     */
    public static function createOrUpdate(string $xmlType, string $errorCode, string $errorName = null, bool $criticalError = false): void
    {
        static::updateOrCreate(
            [
                'xml' => $xmlType,
                'error_code' => $errorCode
            ],
            [
                'error_name' => $errorName,
                'description' => $errorName,
                'critical_error' => $criticalError,
            ]
        );
    }
}

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
        static::updateOrCreate(
            [
                'xml' => $xmlType,
                'error_code' => $errorCode
            ],
            [
                'error_name' => $errorName,
                'description' => $errorName
            ]
        );
    }
}

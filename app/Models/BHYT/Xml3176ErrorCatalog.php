<?php

namespace App\Models\BHYT;

use App\Services\Xml3176\Xml3176BoDem;
use Illuminate\Database\Eloquent\Model;

class Xml3176ErrorCatalog extends Model
{
    protected $table = 'xml3176_error_catalogs';

    protected $fillable = [
        'xml',
        'error_code',
        'error_name',
        'description',
        'critical_error',
        'is_check',
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
        $dong = static::firstOrNew(['xml' => $xmlType, 'error_code' => $errorCode]);
        $dong->error_name = $errorName;
        $dong->description = $errorName;

        // Chi dat muc chan khi tao moi: admin da doi tren man quan tri thi khong bi ghi de.
        if (!$dong->exists) {
            $dong->critical_error = $criticalError;
        }

        $dong->save();

        // Ma chua co trong bang bo dem thi them gia tri thuc trong CSDL; ma da co giu nguyen
        // (bo dem theo dong dau tien cua ma, dong nay khong lam doi no). Bang chua nap hoac da
        // het han thi dat() khong lam gi: lan nap sau doc CSDL moi da co ma nay.
        if (!Xml3176BoDem::coTrongBang('ma_loi', $errorCode)) {
            Xml3176BoDem::dat('ma_loi', $errorCode, [
                'critical_error' => (bool) $dong->critical_error,
                'is_check'       => $dong->is_check === null ? true : (bool) $dong->is_check,
            ]);
        }
    }
}

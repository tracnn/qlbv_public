<?php

namespace App\Services\OrderCheck\Scanners;

use App\Services\OrderCheck\HisOrderSource;

class ScannerRegistry
{
    /**
     * @param HisOrderSource $source
     * @return \App\Services\OrderCheck\Contracts\Scanner[]
     */
    public static function all(HisOrderSource $source)
    {
        return [
            new ServiceReqScanner(),
            new InteractionLogScanner(),
            new MedicineScanner(),
            new ServiceRestrictionScanner(),
        ];
    }
}

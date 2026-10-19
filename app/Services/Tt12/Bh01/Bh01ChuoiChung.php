<?php

namespace App\Services\Tt12\Bh01;

use Box\Spout\Reader\XLSX\Manager\SharedStringsCaching\CachingStrategyFactory;

/**
 * Chon cach Spout giu "chuoi dung chung" (sharedStrings.xml) khi doc tep 01/BH.
 *
 * VI SAO GHI DE: Spout 3.3 uoc 12 KB cho MOI chuoi va chi giu trong RAM khi memory_limit lon hon
 * con so do; con lai no ghi chuoi ra tep tam va doc lai - rat cham. Tep do chinh chuc nang Xuat
 * Excel (va moi tep Excel luu lai) dung chuoi dung chung cho tung o. Do that 19/10/2026 tep
 * 21.125 dong co 411.502 chuoi: Spout uoc 4,9 GB nen chon tep tam, 40 giay moi doc 14.635 dong,
 * nhap hai luot vuot 60 giay. Giu trong RAM thuc te chi 8,5 giay va dinh 28 MB (~60 byte/chuoi).
 *
 * Uoc 100 byte/chuoi (gap ~1,7 lan so do; 411.502 chuoi -> ~40 MB) va chi giu trong RAM khi
 * con duoi MOT NUA memory_limit - phan con lai de cho doc dong va ghi CSDL. Vuot nguong thi van
 * lui ve cach cua Spout (tep tam): cham nhung khong het bo nho.
 */
class Bh01ChuoiChung extends CachingStrategyFactory
{
    const BYTE_MOI_CHUOI = 100;

    protected function isInMemoryStrategyUsageSafe($sharedStringsUniqueCount)
    {
        if ($sharedStringsUniqueCount === null) {
            return false;
        }

        $gioiHanKb = $this->getMemoryLimitInKB();

        if ($gioiHanKb === -1) {
            return parent::isInMemoryStrategyUsageSafe($sharedStringsUniqueCount);
        }

        $canKb = $sharedStringsUniqueCount * self::BYTE_MOI_CHUOI / 1024;

        return $canKb < $gioiHanKb / 2;
    }
}

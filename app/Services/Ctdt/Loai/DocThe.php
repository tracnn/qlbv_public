<?php

namespace App\Services\Ctdt\Loai;

/**
 * Doc mot the XML ve chuoi hoac null.
 *
 * VI SAO CAN: (string) tren the vang tra ve chuoi rong, va chuoi rong khac "khong khai"
 * khi doi soat voi BHXH. Chin lop loai deu can dung mot cach doc nay - viet mot lan.
 */
class DocThe
{
    /**
     * @return string|null null khi the khong ton tai hoac rong sau khi trim
     */
    public static function chuoi(\SimpleXMLElement $xml, $ten)
    {
        if (!isset($xml->{$ten})) {
            return null;
        }

        $giaTri = trim((string) $xml->{$ten});

        return $giaTri === '' ? null : $giaTri;
    }
}

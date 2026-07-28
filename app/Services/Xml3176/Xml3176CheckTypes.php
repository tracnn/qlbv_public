<?php

namespace App\Services\Xml3176;

/**
 * Dang ky cac loai XML co checker, kem model tuong ung.
 *
 * Dung 12 loai ma CheckXml3176ErrorsJob (job cu, mot job moi DONG) tung xu ly.
 * XML6, XML12, XML15 khong co checker - dieu do la co san, khong phai thieu sot.
 */
class Xml3176CheckTypes
{
    const LOAI = [
        'XML1'  => ['model' => \App\Models\BHYT\Xml3176Xml1::class,  'checker' => \App\Services\Xml3176Xml1Checker::class],
        'XML2'  => ['model' => \App\Models\BHYT\Xml3176Xml2::class,  'checker' => \App\Services\Xml3176Xml2Checker::class],
        'XML3'  => ['model' => \App\Models\BHYT\Xml3176Xml3::class,  'checker' => \App\Services\Xml3176Xml3Checker::class],
        'XML4'  => ['model' => \App\Models\BHYT\Xml3176Xml4::class,  'checker' => \App\Services\Xml3176Xml4Checker::class],
        'XML5'  => ['model' => \App\Models\BHYT\Xml3176Xml5::class,  'checker' => \App\Services\Xml3176Xml5Checker::class],
        'XML7'  => ['model' => \App\Models\BHYT\Xml3176Xml7::class,  'checker' => \App\Services\Xml3176Xml7Checker::class],
        'XML8'  => ['model' => \App\Models\BHYT\Xml3176Xml8::class,  'checker' => \App\Services\Xml3176Xml8Checker::class],
        'XML9'  => ['model' => \App\Models\BHYT\Xml3176Xml9::class,  'checker' => \App\Services\Xml3176Xml9Checker::class],
        'XML10' => ['model' => \App\Models\BHYT\Xml3176Xml10::class, 'checker' => \App\Services\Xml3176Xml10Checker::class],
        'XML11' => ['model' => \App\Models\BHYT\Xml3176Xml11::class, 'checker' => \App\Services\Xml3176Xml11Checker::class],
        'XML13' => ['model' => \App\Models\BHYT\Xml3176Xml13::class, 'checker' => \App\Services\Xml3176Xml13Checker::class],
        'XML14' => ['model' => \App\Models\BHYT\Xml3176Xml14::class, 'checker' => \App\Services\Xml3176Xml14Checker::class],
    ];

    public static function coChecker($loai)
    {
        return is_string($loai) && array_key_exists($loai, self::LOAI);
    }

    public static function cauHinh($loai)
    {
        if (!self::coChecker($loai)) {
            throw new \InvalidArgumentException('Loai XML khong co checker: ' . $loai);
        }

        return self::LOAI[$loai];
    }
}

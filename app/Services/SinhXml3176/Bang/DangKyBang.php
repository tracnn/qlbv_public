<?php

namespace App\Services\SinhXml3176\Bang;

/** Danh sach bang da co; chan chay that khi chua du 14 bang. */
class DangKyBang
{
    const CAN_CO = ['XML1', 'XML2', 'XML3', 'XML4', 'XML5', 'XML6', 'XML7', 'XML8', 'XML9', 'XML10', 'XML11', 'XML13', 'XML14', 'XML15'];

    private $cacDung;

    public function __construct(array $cacDung)
    {
        $this->cacDung = array_values($cacDung);
    }

    public static function macDinh()
    {
        return new self([new Xml2Dung(), new Xml3Dung(), new Xml1Dung()]);
    }

    /** Theo thu tu dung: cac bang con truoc, XML1 sau cung (XML1 dung ket qua XML2, XML3). */
    public function cacDung()
    {
        $ds = $this->cacDung;
        usort($ds, function ($a, $b) {
            return self::khoaSapXep($a->loai()) - self::khoaSapXep($b->loai());
        });

        return $ds;
    }

    public function daDu()
    {
        return count($this->thieu()) === 0;
    }

    /** Ma cac loai con thieu, sap theo so. */
    public function thieu()
    {
        $co = [];
        foreach ($this->cacDung as $d) {
            $co[$d->loai()] = true;
        }
        $thieu = [];
        foreach (self::CAN_CO as $l) {
            if (!isset($co[$l])) {
                $thieu[] = $l;
            }
        }
        usort($thieu, function ($a, $b) {
            return (int) substr($a, 3) - (int) substr($b, 3);
        });

        return $thieu;
    }

    private static function khoaSapXep($loai)
    {
        return $loai === 'XML1' ? 1000 : (int) substr($loai, 3);
    }
}

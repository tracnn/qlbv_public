<?php

namespace App\Services\SinhXml3176\Bang;

/**
 * Khong dung duoc mot bang cua ho so (thieu danh muc, du lieu le). LuotQuet ghi trang thai 'loi'
 * kem loai bang va mo ta, khong dung ca luot.
 */
class LoiDungBang extends \RuntimeException
{
    private $loaiBang;

    /**
     * @param string $loai vi du 'XML2'
     * @param string $moTa mo ta tieng Viet
     */
    public function __construct($loai, $moTa)
    {
        parent::__construct((string) $moTa);
        $this->loaiBang = (string) $loai;
    }

    public function loaiBang(): string
    {
        return $this->loaiBang;
    }
}

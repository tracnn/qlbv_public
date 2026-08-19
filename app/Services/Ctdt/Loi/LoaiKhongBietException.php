<?php

namespace App\Services\Ctdt\Loi;

/** Gia tri LOAIHOSO khong nam trong dang ky cua CtdtLoaiRegistry. */
class LoaiKhongBietException extends \InvalidArgumentException implements CtdtLoiNap
{
}

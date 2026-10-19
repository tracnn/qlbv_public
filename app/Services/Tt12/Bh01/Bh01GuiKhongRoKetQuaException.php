<?php

namespace App\Services\Tt12\Bh01;

/**
 * SubmitBh01Job goi cong ma khong nhan duoc phan hoi (loi mang, timeout). Job da ghi dong lich
 * su cho lan thu nay roi moi nem lai bang lop nay - failed() nhin lop de biet khong ghi them dong.
 * Giu nguyen thong diep goc (va loi goc o getPrevious()) de log/hang doi van thay ly do that.
 */
class Bh01GuiKhongRoKetQuaException extends \RuntimeException
{
}

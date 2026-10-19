<?php

namespace App\Services\Tt12\Bh01;

/**
 * Thu vien doc Excel khong mo/doc duoc tep (hong, khong phai xlsx that).
 * Lop rieng de controller chi bat dung loi nay: QueryException cung la RuntimeException, bat chung
 * se bao nham loi may chu thanh "tep hong".
 */
class Bh01TepExcelHongException extends \RuntimeException
{
}

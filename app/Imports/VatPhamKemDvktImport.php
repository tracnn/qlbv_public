<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/** Doc tep Excel bang tra vat pham -> DVKT me (dong 1 la tieu de = VatPhamKemDvktExport::COT). */
class VatPhamKemDvktImport implements ToArray, WithHeadingRow
{
    /** @var array */
    public $hang = [];

    public function array(array $array)
    {
        $this->hang = array_merge($this->hang, $array);
    }
}

<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;

/**
 * Doc tep danh muc THEO LO.
 *
 * Excel::toCollection nap TOAN BO tep vao bo nho: do duoc tep 10.000 dong x 23 cot (1,3 MB)
 * lam DINH bo nho 208 MB - gap ~160 lan co tep. Dropzone dang cho tai tep toi 10 MB.
 *
 * Dong tieu de CHI co o lo dau. Lop nay khong tu xu ly ma goi lai ham duoc truyen vao, kem
 * so dong Excel that va co phai lo dau khong - de CatalogImportService quyet dinh viec nhan
 * dien loai danh muc va dung anh xa cot.
 */
class CatalogChunkImport implements ToCollection, WithChunkReading
{
    const CO_LO = 1000;

    /** @var callable|null nhan (Collection $rows, int $dongDau, bool $laLoDau) */
    protected $xuLy;

    /** @var int so dong da doc, de tinh so dong Excel that */
    protected $daDoc = 0;

    public function __construct(callable $xuLy = null)
    {
        $this->xuLy = $xuLy;
    }

    public function chunkSize(): int
    {
        return self::CO_LO;
    }

    public function collection(Collection $rows)
    {
        $laLoDau = $this->daDoc === 0;
        $dongDau = $this->daDoc + 1;   // dong Excel dau tien cua lo nay (dong tieu de la 1)
        $this->daDoc += $rows->count();

        if ($this->xuLy !== null) {
            call_user_func($this->xuLy, $rows, $dongDau, $laLoDau);
        }
    }

    public function soDongDaDoc()
    {
        return $this->daDoc;
    }
}

<?php

namespace App\Services\ExcelLuong;

/**
 * Boc mot lop export san co (Laravel Excel concern) de bo ghi luong doc: ten, tieu de, dinh
 * dang va CAC DONG DA map(). Noi DUY NHAT biet khac biet FromGenerator / FromQuery.
 *
 * RAM chi giu mot lo LO dong - khong gom ca sheet nhu Laravel Excel 3.1.25 (appendRows ->
 * new Collection(generator)).
 */
class NguonSheet
{
    const LO = 1000;

    const MAC_DINH = [
        'do_rong' => [],
        'cot_so' => [],
        'kieu_o' => KieuO::TU_DONG,
        'xuong_dong' => false,
        'tieu_de_can_giua' => false,
    ];

    protected $export;

    public function __construct($export)
    {
        $this->export = $export;
    }

    public function ten(): string
    {
        return $this->export->title();
    }

    public function tieuDe(): array
    {
        return $this->export->headings();
    }

    public function dinhDang(): array
    {
        $rieng = method_exists($this->export, 'dinhDangLuong') ? $this->export->dinhDangLuong() : [];

        return array_merge(self::MAC_DINH, $rieng);
    }

    /**
     * Cac dong da map(). 'yield' tung phan tu - KHONG 'yield from $mang': khoa lap lai giua cac
     * lo, ai doc bang iterator_to_array giu khoa se mat dong (bai hoc 05/10/2026).
     */
    public function dong(): \Generator
    {
        if (method_exists($this->export, 'generator')) {
            foreach ($this->export->generator() as $r) {
                yield $this->export->map($r);
            }

            return;
        }

        $q = $this->export->query();

        // Duong cu FromQuery dung chunk() - Eloquent tu orderBy khoa chinh khi chua co thu tu
        // (enforceOrderBy). cursor() khong lam: tu them de thu tu dong giong tep cu.
        if (empty($q->getQuery()->orders)) {
            $q->orderBy($q->getModel()->getQualifiedKeyName());
        }

        $lo = [];

        foreach ($q->cursor() as $r) {
            $lo[] = $r;

            if (count($lo) >= self::LO) {
                foreach ($this->mapLo($lo) as $d) {
                    yield $d;
                }
                $lo = [];
            }
        }

        foreach ($this->mapLo($lo) as $d) {
            yield $d;
        }
    }

    protected function mapLo(array $lo): array
    {
        if (!$lo) {
            return [];
        }

        $rows = collect($lo);

        if (method_exists($this->export, 'prepareRows')) {
            $rows = $this->export->prepareRows($rows);
        }

        $ra = [];

        foreach ($rows as $r) {
            $ra[] = $this->export->map($r);
        }

        return $ra;
    }
}

<?php

namespace Tests\Support;

/**
 * Sheet gia cho test bo ghi luong: cung "hinh" voi lop export FromGenerator (title, headings,
 * generator, map, dinhDangLuong). $nemSau: nem loi sau N dong de thu duong hong giua chung.
 */
class SheetGia
{
    public $ten;
    public $tieuDe;
    public $dong;
    public $dinhDang;
    public $nemSau;

    public function __construct(string $ten, array $tieuDe, $dong, array $dinhDang = [], int $nemSau = null)
    {
        $this->ten = $ten;
        $this->tieuDe = $tieuDe;
        $this->dong = $dong;
        $this->dinhDang = $dinhDang;
        $this->nemSau = $nemSau;
    }

    public function title(): string
    {
        return $this->ten;
    }

    public function headings(): array
    {
        return $this->tieuDe;
    }

    public function generator(): \Generator
    {
        $i = 0;

        foreach ($this->dong as $d) {
            if ($this->nemSau !== null && $i >= $this->nemSau) {
                throw new \RuntimeException('hong giua chung');
            }
            $i++;
            yield $d;
        }
    }

    public function map($d): array
    {
        return $d;
    }

    public function dinhDangLuong(): array
    {
        return $this->dinhDang;
    }
}

<?php

namespace App\Services\Tt12\Loi;

/**
 * Tep chua dong mang ma co so khac voi co so nguoi dung da chon.
 *
 * Mang theo DANH SACH dong lech chu khong chi mot cau van: nguoi dung can biet dong nao
 * de mo Excel sua, va mot thong bao "co dong lech" khong giup ho tim ra dong do trong
 * tep vai nghin dong.
 */
class MaCskcbLechException extends \RuntimeException
{
    /** @var array [['stt' => int, 'gia_tri' => string], ...] */
    private $dongLech;

    public function __construct($moTa, array $dongLech)
    {
        parent::__construct($moTa);

        $this->dongLech = $dongLech;
    }

    /** @return array */
    public function dongLech()
    {
        return $this->dongLech;
    }
}

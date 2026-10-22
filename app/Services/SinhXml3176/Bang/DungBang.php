<?php

namespace App\Services\SinhXml3176\Bang;

use App\Services\SinhXml3176\DuLieuHoSo;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;

/**
 * Lop dung mot bang XML con (XML1, XML2, ...) tu du lieu HIS cua mot ho so.
 * Lop dung la ham thuan tuy: khong truy cap CSDL, moi du lieu lay tu DuLieuHoSo va HisDanhMuc.
 */
interface DungBang
{
    /** Loai bang, vi du 'XML2'. */
    public function loai(): string;

    /** Ho so co dong nao cho bang nay khong. */
    public function coTrongHoSo(DuLieuHoSo $d): bool;

    /**
     * Dung cac dong cua bang. Moi dong la mang ket hop, thu tu khoa dung thu tu chi tieu.
     *
     * @param array $daDung cac bang da dung truoc do, khoa la loai ('XML2' => [dong...])
     * @return array[]
     * @throws LoiDungBang du lieu khong dung duoc
     */
    public function dung(DuLieuHoSo $d, HisDanhMuc $dm, array $daDung): array;
}

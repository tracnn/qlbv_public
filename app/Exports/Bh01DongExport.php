<?php

namespace App\Exports;

use App\Services\Tt12\Bh01\Bh01DacTa;
use Illuminate\Support\Facades\DB;

/**
 * Sheet C79 cua tep xuat/tep mau ho so tong hop 01/BH, cho duong ghi luong (GhiExcelLuong).
 *
 * Cot va thu tu lay tu Bh01DacTa::the() - cung nguon voi bo nhap, nen tep xuat ra nhap lai duoc.
 * banId null = chi tieu de (tep mau). Moi o la chu ('kieu_o' => 'chu'): ma '01929', thang '09'
 * khong duoc mat so 0 dau khi mo bang Excel.
 */
class Bh01DongExport
{
    protected $banId;

    public function __construct($banId = null)
    {
        $this->banId = $banId;
    }

    public function title()
    {
        return 'C79';
    }

    public function headings()
    {
        $tieuDe = array();
        foreach (Bh01DacTa::the() as $dt) {
            $tieuDe[] = $dt['the'];
        }

        return $tieuDe;
    }

    public function generator()
    {
        if ($this->banId === null) {
            return;
        }

        // cursor(): ban co the toi 100000 dong, khong nap het vao RAM.
        foreach (DB::table('bh01_dong')->where('ban_id', $this->banId)->orderBy('stt')->cursor() as $dong) {
            yield $dong;
        }
    }

    public function map($dong)
    {
        $dong = (array) $dong;
        $ra = array();

        foreach (Bh01DacTa::the() as $dt) {
            $khoa = strtolower($dt['the']);
            $ra[] = isset($dong[$khoa]) ? (string) $dong[$khoa] : '';
        }

        return $ra;
    }

    public function dinhDangLuong()
    {
        return array(
            'do_rong' => array(),
            'cot_so' => array(),
            'kieu_o' => 'chu',
            'xuong_dong' => false,
            'tieu_de_can_giua' => false,
        );
    }
}

<?php

namespace App\Services\Tt12;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CatalogChunkImport;

/**
 * Doc tep .xlsx TT12 theo lo va chuan hoa tung o.
 *
 * DUNG LAI CatalogChunkImport chu khong viet lop doc thu hai: lop do da do tren tep that
 * cua cong BHXH (52.551 dong x 15 cot) va chot co lo 5.000 la diem can bang cua may chu
 * 128 MB / 120 giay. Viet lop doc rieng la lam lai phep do do tu dau, sai.
 *
 * Excel::toCollection nap TOAN BO tep vao bo nho nen KHONG dung o day.
 */
class Tt12DocExcel
{
    /**
     * Doc tep, goi $xuLy theo tung lo.
     *
     * @param string   $duongDan
     * @param callable $xuLy nhan (array $hangDuLieu, int $sttBatDau) - $hangDuLieu la
     *                       mang cac mang chi so, KHONG gom hang tieu de
     * @param callable $nhanHeader nhan (array $header) - goi DUNG MOT LAN o lo dau
     * @return int tong so dong du lieu da doc
     */
    public function doc($duongDan, callable $xuLy, callable $nhanHeader)
    {
        $tongDong = 0;
        $daBaoHeader = false;

        $import = new CatalogChunkImport(
            function (Collection $hang, $dongDau, $laLoDau) use (&$tongDong, &$daBaoHeader, $xuLy, $nhanHeader) {
                $mang = $hang->toArray();

                if ($laLoDau) {
                    // Hang tieu de CHI co o lo dau.
                    $header = array_shift($mang);

                    if ($header === null) {
                        return;
                    }

                    call_user_func($nhanHeader, array_values($header));
                    $daBaoHeader = true;
                }

                $mang = $this->bocDongRong($mang);

                if ($mang === array()) {
                    return;
                }

                call_user_func($xuLy, $mang, $tongDong + 1);

                $tongDong += count($mang);
            }
        );

        Excel::import($import, $duongDan);

        if (!$daBaoHeader) {
            call_user_func($nhanHeader, array());
        }

        return $tongDong;
    }

    /**
     * Bo cac dong ma MOI o deu rong.
     *
     * Tep Excel do nguoi dung sua tay thuong keo theo hang tram dong trong o cuoi sheet.
     * Giu chung lai se sinh hang tram dong tt12_dong rong va hang tram loi "thieu truong
     * bat buoc" - nguoi dung khong hieu vi sao tep 50 dong lai bao 300 loi.
     */
    private function bocDongRong(array $hang)
    {
        $giu = array();

        foreach ($hang as $dong) {
            foreach ($dong as $o) {
                if (trim((string) $o) !== '') {
                    $giu[] = $dong;
                    break;
                }
            }
        }

        return $giu;
    }

    /**
     * Chuan hoa mot o ve CHUOI.
     *
     * Ham THUAN, static, de kiem duoc va de moi noi doc deu giong nhau.
     *
     * Ba viec:
     *  - null thanh chuoi rong: the XML rong phai la <X/> chu khong phai the mang chu 'null'.
     *  - float nguyen thanh so nguyen: Excel tra o so duoi dang float, noi thang vao XML
     *    cho ra '3.0' thay vi '3' va cong tra 205 vi truong khai la So.
     *  - DateTime thanh yyyymmdd: neu nguoi dung dinh dang o TU_NGAY la kieu Ngay thi
     *    PhpSpreadsheet tra ve DateTime chu khong phai chuoi 8 ky tu.
     *
     * @return string
     */
    public static function chuanHoaO($o)
    {
        if ($o === null) {
            return '';
        }

        if ($o instanceof \DateTimeInterface) {
            return $o->format('Ymd');
        }

        if (is_float($o) && floor($o) == $o && abs($o) < 1.0e+15) {
            return number_format($o, 0, '.', '');
        }

        if (is_bool($o)) {
            return $o ? '1' : '0';
        }

        return trim((string) $o);
    }
}

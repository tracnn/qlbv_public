<?php

namespace App\Services\Tt12\Bh01;

/**
 * Doi mot dong xml3176_xml1s thanh gia tri se in vao 01/BH.
 * Nguyen tac (spec 4.2): chep nguyen XML1; chi doi dinh dang khi dac ta the doi. Sua gia tri
 * la lech voi Bang ke chi tiet. Cac the kieu 'so' (MA_LOAI_KCB, NAM_QT, THANG_QT) van la chuoi
 * nguyen van de giu so 0 dung dau ('01', '09').
 */
class Bh01ChuanHoa
{
    /** Tra mang khoa chu thuong cua 19 the (khong co STT - STT danh khi ghi bang). */
    public static function dong($xml1)
    {
        $nguon = (array) $xml1;

        $ra = array();
        foreach (Bh01DacTa::the() as $dt) {
            if ($dt['the'] === 'STT') {
                continue;
            }
            $cot = strtolower($dt['the']);
            $giaTri = isset($nguon[$cot]) ? $nguon[$cot] : null;

            if ($dt['kieu'] === 'tien') {
                $ra[$cot] = self::tien($giaTri);
            } elseif ($cot === 'ma_the_bhyt') {
                // XML1 co the noi nhieu the bang ';' -> lay the dau (spec 4.2)
                $parts = explode(';', (string) $giaTri);
                $ra[$cot] = trim($parts[0]);
            } else {
                $ra[$cot] = $giaTri === null ? '' : (string) $giaTri;
            }
        }
        return $ra;
    }

    /** Toi da 2 chu so thap phan, dau cham, bo so 0 thua; null -> '0'. */
    public static function tien($giaTri)
    {
        if ($giaTri === null || $giaTri === '') {
            return '0';
        }
        $s = number_format(round((float) $giaTri, 2), 2, '.', '');
        $s = rtrim(rtrim($s, '0'), '.');
        return ($s === '' || $s === '-0') ? '0' : $s;
    }
}

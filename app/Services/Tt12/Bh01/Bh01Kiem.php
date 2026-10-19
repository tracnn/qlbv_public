<?php

namespace App\Services\Tt12\Bh01;

/**
 * Kiem mot dong da chuan hoa (spec 4.3). Hai muc:
 *  - chan: thieu truong ma XML vo nghia, ngay (khi co) sai dinh dang, tien khong phai so, MA_LOAI_KCB ngoai 01-09
 *  - canh_bao: vuot do dai toi da cua dac ta 01/BH nhung gia tri dung bang XML1 (khong cat, vi cat se lech Bang ke)
 * Do dai do bang KY TU (mb_strlen): ma ICD co dau dao (E11.4 + dau dao) dai 6 ky tu nhung 8 byte.
 */
class Bh01Kiem
{
    public static function dong(array $daChuanHoa)
    {
        $chan = array();
        $canhBao = array();

        $batBuoc = array('ho_ten', 'ma_the_bhyt', 'ngay_vao', 'ngay_ra', 'ma_loai_kcb', 'ma_cskcb');
        foreach ($batBuoc as $cot) {
            if (self::lay($daChuanHoa, $cot) === '') {
                $chan[] = strtoupper($cot) . ' bat buoc nhung dang rong';
            }
        }

        foreach (Bh01DacTa::the() as $dt) {
            if ($dt['the'] === 'STT') {
                continue;
            }
            $cot = strtolower($dt['the']);
            $v = self::lay($daChuanHoa, $cot);

            if ($dt['kieu'] === 'ngay12') {
                // Chi kiem ngay CO gia tri: NGAY_VAO_NOI_TRU duoc phep rong, NGAY_VAO/NGAY_RA rong
                // da bi chan o tren, NGAY_SINH rong chi canh bao (khong lam XML vo nghia).
                if ($v === '') {
                    if ($cot === 'ngay_sinh') {
                        $canhBao[] = 'NGAY_SINH dang rong';
                    }
                } elseif (!preg_match('/^\d{12}$/', $v)) {
                    $chan[] = $dt['the'] . ' khong du 12 chu so (yyyyMMddHHmm): "' . $v . '"';
                }
                continue;
            }

            if ($dt['kieu'] === 'tien' && !is_numeric($v)) {
                $chan[] = $dt['the'] . ' khong phai so: "' . $v . '"';
                continue;
            }

            if ($v !== '' && mb_strlen($v, 'UTF-8') > $dt['max']) {
                $canhBao[] = $dt['the'] . ' dai ' . mb_strlen($v, 'UTF-8') . ' ky tu, vuot toi da ' . $dt['max']
                    . ' cua dac ta 01/BH (giu nguyen theo XML1)';
            }
        }

        $loai = self::lay($daChuanHoa, 'ma_loai_kcb');
        if ($loai !== '' && !preg_match('/^0[1-9]$/', $loai)) {
            $chan[] = 'MA_LOAI_KCB "' . $loai . '" ngoai 01-09, khong xep duoc vao muc I-IV';
        }

        return array('chan' => $chan, 'canh_bao' => $canhBao);
    }

    private static function lay(array $d, $cot)
    {
        return isset($d[$cot]) ? trim((string) $d[$cot]) : '';
    }
}

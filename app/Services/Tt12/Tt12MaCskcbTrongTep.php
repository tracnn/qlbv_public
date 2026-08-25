<?php

namespace App\Services\Tt12;

/**
 * Doi chieu cot MA_CSKCB cua mot lo dong voi co so nguoi dung da chon.
 *
 * VI SAO LA LOP RIENG chu khong phai ham private trong Tt12Importer: day la luat nghiep
 * vu duy nhat co the huy ca mot lan nap, nen phai kiem duoc rieng - voi o rong, o co
 * khoang trang thua, o duoc Excel tra ve dang so, va tep khong co cot MA_CSKCB.
 *
 * Ham THUAN: khong doc CSDL, khong ghi tep.
 */
class Tt12MaCskcbTrongTep
{
    /** Nhieu nhat bao nhieu dong lech duoc liet ke trong thong bao */
    const TOI_DA_LIET_KE = 20;

    /**
     * @param array    $loDong    mang cac mang chi so, dung thu tu cot cua tep
     * @param int|null $chiSoCot  vi tri cot MA_CSKCB; null neu tep khong co cot nay
     * @param string   $maChon    ma co so nguoi dung chon tren man hinh
     * @param int      $sttBatDau so thu tu cua dong dau tien trong lo
     * @return array ['lech' => [['stt' => int, 'gia_tri' => string], ...], 'so_o_trong' => int]
     */
    public static function kiem(array $loDong, $chiSoCot, $maChon, $sttBatDau)
    {
        $lech = array();
        $soORong = 0;

        if ($chiSoCot === null) {
            // Tep khong co cot MA_CSKCB. Khong phai loi lech - moi dong se duoc dien theo
            // o chon o Tt12LuuHoSo. Nhan dien mau da khang dinh tap cot khop dac ta nen
            // truong hop nay chi xay ra voi mau khong co cot do; ca sau mau TT12 deu co,
            // nen day la nhanh phong ho.
            return array('lech' => array(), 'so_o_trong' => count($loDong));
        }

        $mong = trim((string) $maChon);
        $stt = $sttBatDau;

        foreach ($loDong as $dong) {
            $giaTri = array_key_exists($chiSoCot, $dong)
                ? Tt12DocExcel::chuanHoaO($dong[$chiSoCot])
                : '';

            if ($giaTri === '') {
                // O trong KHONG phai lech: se duoc dien theo o chon. Coi no la lech se
                // chan nhung tep hop le ma nguoi dung co y de trong cot nay.
                $soORong++;
            } elseif ($giaTri !== $mong) {
                $lech[] = array('stt' => $stt, 'gia_tri' => $giaTri);
            }

            $stt++;
        }

        return array('lech' => $lech, 'so_o_trong' => $soORong);
    }

    /**
     * Cau thong bao cho nguoi dung, liet ke toi da TOI_DA_LIET_KE dong.
     *
     * @param array  $lech
     * @param string $maChon
     * @return string
     */
    public static function moTaLech(array $lech, $maChon)
    {
        $mau = array();

        foreach (array_slice($lech, 0, self::TOI_DA_LIET_KE) as $mot) {
            $mau[] = 'dòng ' . $mot['stt'] . ' = "' . $mot['gia_tri'] . '"';
        }

        $them = count($lech) > self::TOI_DA_LIET_KE
            ? ' và ' . (count($lech) - self::TOI_DA_LIET_KE) . ' dòng khác'
            : '';

        return 'Tệp có ' . count($lech) . ' dòng mang MA_CSKCB khác cơ sở đã chọn ('
            . $maChon . '): ' . implode('; ', $mau) . $them
            . '. Một hồ sơ TT12 chỉ gửi được bằng tài khoản của một cơ sở, nên tệp phải '
            . 'thuộc trọn một cơ sở. Sửa tệp hoặc chọn lại cơ sở rồi nạp lại.';
    }
}

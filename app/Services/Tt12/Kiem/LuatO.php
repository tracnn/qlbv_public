<?php

namespace App\Services\Tt12\Kiem;

/**
 * Luat theo tung O, sinh TU DAC TA COT chu khong viet tay tung truong.
 *
 * Viet tay se la khoang 130 khoi if cho sau mau, va moi lan BHXH doi kich thuoc mot
 * truong lai phai tim dung khoi do. Dac ta la MOT nguon su that; luat doc no.
 *
 * Ham THUAN: khong doc CSDL, khong ghi tep.
 */
class LuatO
{
    /**
     * @param string $lop     ten lop dac ta mau
     * @param array  $duLieu  [TEN_THE => gia tri chuoi]
     * @param int    $sttDong
     * @return array cac Tt12Loi
     */
    public static function kiem($lop, array $duLieu, $sttDong)
    {
        $loi = array();

        foreach ($lop::cot() as $cot) {
            $the = $cot['the'];
            $giaTri = isset($duLieu[$the]) ? trim((string) $duLieu[$the]) : '';

            if ($giaTri === '') {
                if ($cot['bat_buoc']) {
                    $loi[] = Tt12Loi::loi(
                        'THIEU_BAT_BUOC',
                        'Thiếu giá trị bắt buộc ở cột ' . $the,
                        $sttDong, $the
                    );
                }

                // Rong va khong bat buoc: DUNG o day. Kiem kieu tren chuoi rong se bao
                // "khong phai so" o moi dong khong dien - hang nghin loi gia.
                continue;
            }

            $loiO = self::kiemGiaTri($cot, $giaTri, $sttDong);

            if ($loiO !== null) {
                $loi[] = $loiO;
                continue;
            }

            if ($cot['max'] !== null && mb_strlen($giaTri) > $cot['max']) {
                $loi[] = Tt12Loi::loi(
                    'QUA_DAI',
                    'Cột ' . $the . ' dài ' . mb_strlen($giaTri)
                    . ' ký tự, vượt giới hạn ' . $cot['max'],
                    $sttDong, $the
                );
            }
        }

        return $loi;
    }

    /** @return Tt12Loi|null */
    private static function kiemGiaTri(array $cot, $giaTri, $sttDong)
    {
        $the = $cot['the'];

        if ($cot['kieu'] === 'so') {
            // Chap nhan ca so thap phan: DON_GIA co the la 15000.5. Khong chap nhan dau
            // phan cach hang nghin - '15.000' se di thang vao XML va cong tu choi.
            if (!preg_match('/^-?\d+(\.\d+)?$/', $giaTri)) {
                return Tt12Loi::loi(
                    'SAI_KIEU_SO',
                    'Cột ' . $the . ' phải là số, giá trị hiện tại: "' . $giaTri . '"',
                    $sttDong, $the
                );
            }

            if ($giaTri[0] === '-') {
                return Tt12Loi::loi(
                    'SO_AM',
                    'Cột ' . $the . ' không được là số âm: "' . $giaTri . '"',
                    $sttDong, $the
                );
            }

            return null;
        }

        if ($cot['kieu'] === 'ngay8') {
            if (!preg_match('/^\d{8}$/', $giaTri)) {
                return Tt12Loi::loi(
                    'SAI_DINH_DANG_NGAY',
                    'Cột ' . $the . ' phải gồm đúng 8 chữ số dạng yyyymmdd, '
                    . 'giá trị hiện tại: "' . $giaTri . '"',
                    $sttDong, $the
                );
            }

            // checkdate BAT BUOC: 20260231 dung 8 chu so nhung 31 thang 2 khong ton tai.
            $nam   = (int) substr($giaTri, 0, 4);
            $thang = (int) substr($giaTri, 4, 2);
            $ngay  = (int) substr($giaTri, 6, 2);

            if (!checkdate($thang, $ngay, $nam)) {
                return Tt12Loi::loi(
                    'NGAY_KHONG_CO_THAT',
                    'Cột ' . $the . ' không phải ngày có thật: "' . $giaTri . '"',
                    $sttDong, $the
                );
            }

            return null;
        }

        return null;
    }
}

<?php

namespace App\Services\Xml3176\Support;

/**
 * Đối chiếu 3 thuộc tính thuốc của dòng XML2 với danh mục thuốc BHYT đã khớp:
 * đường dùng (#170), dạng bào chế (#2053), đơn vị tính (#2351).
 *
 * So sánh chuẩn hoá (bỏ hoa/thường/khoảng trắng) qua TextNormalizer.
 * Bỏ qua từng thuộc tính khi trường XML rỗng HOẶC danh mục thiếu căn cứ đối chiếu.
 */
class DrugCatalogAttrChecker
{
    /**
     * @param array $xml ['duong_dung'=>?, 'dang_bao_che'=>?, 'don_vi_tinh'=>?]
     * @param array $dm  ['ma_duong_dung'=>?, 'duong_dung'=>?, 'dang_bao_che'=>?, 'don_vi_tinh'=>?]
     * @return string[] Tập con của ['DUONG_DUNG','DANG_BAO_CHE','DON_VI_TINH'] — các thuộc tính LỆCH.
     */
    public static function lech(array $xml, array $dm): array
    {
        $lech = [];

        // #170 — đường dùng: khớp nếu bằng mã HOẶC tên đường dùng của danh mục.
        $duongDung = self::val($xml, 'duong_dung');
        if ($duongDung !== '') {
            $ungVien = array_values(array_filter([
                self::chuan(self::val($dm, 'ma_duong_dung')),
                self::chuan(self::val($dm, 'duong_dung')),
            ], function ($v) {
                return $v !== '';
            }));
            if (!empty($ungVien) && !in_array(self::chuan($duongDung), $ungVien, true)) {
                $lech[] = 'DUONG_DUNG';
            }
        }

        // #2053 — dạng bào chế.
        if (self::lechDon(self::val($xml, 'dang_bao_che'), self::val($dm, 'dang_bao_che'))) {
            $lech[] = 'DANG_BAO_CHE';
        }

        // #2351 — đơn vị tính (không phân biệt hoa/thường).
        if (self::lechDon(self::val($xml, 'don_vi_tinh'), self::val($dm, 'don_vi_tinh'))) {
            $lech[] = 'DON_VI_TINH';
        }

        return $lech;
    }

    /** Một thuộc tính đơn: lệch khi cả hai bên có giá trị và chuẩn hoá khác nhau. */
    private static function lechDon($xmlVal, $dmVal): bool
    {
        $x = self::chuan($xmlVal);
        $d = self::chuan($dmVal);
        return $x !== '' && $d !== '' && $x !== $d;
    }

    private static function val(array $a, string $k): string
    {
        return isset($a[$k]) ? (string) $a[$k] : '';
    }

    private static function chuan($v): string
    {
        return TextNormalizer::chuan($v);
    }
}

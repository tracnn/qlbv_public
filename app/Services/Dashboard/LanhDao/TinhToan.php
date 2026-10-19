<?php

namespace App\Services\Dashboard\LanhDao;

/**
 * Hàm tính thuần (không chạm CSDL) dùng chung cho Dashboard lãnh đạo.
 */
class TinhToan
{
    /** Nhóm phút: [từ, đến) — đến = null là không chặn trên. */
    const NHOM_PHUT = [
        '<10' => [0, 10],
        '10–20' => [10, 20],
        '20–30' => [20, 30],
        '30–60' => [30, 60],
        '60–120' => [60, 120],
        '>120' => [120, null],
    ];

    public static function tyLe($tu, $mau)
    {
        if ($mau <= 0) {
            return null;
        }

        return round($tu / $mau * 100, 1);
    }

    public static function trungVi(array $so)
    {
        if (!$so) {
            return null;
        }
        $so = array_values($so);
        sort($so);
        $n = count($so);
        $giua = intdiv($n, 2);

        return (float) ($n % 2 ? $so[$giua] : ($so[$giua - 1] + $so[$giua]) / 2);
    }

    public static function congSuat($ngayGiuong, $giuongPd, $soNgay)
    {
        if ($giuongPd <= 0 || $soNgay <= 0) {
            return null;
        }

        return round($ngayGiuong / ($giuongPd * $soNgay) * 100, 1);
    }

    /** Gộp các khoảng chồng/chạm nhau; trả về đã sắp theo thời điểm bắt đầu. */
    public static function gopKhoang(array $khoang)
    {
        usort($khoang, function ($a, $b) {
            return $a[0] <=> $b[0];
        });
        $kq = [];
        foreach ($khoang as $k) {
            $cuoi = count($kq) - 1;
            if ($cuoi >= 0 && $k[0] <= $kq[$cuoi][1]) {
                $kq[$cuoi][1] = max($kq[$cuoi][1], $k[1]);
            } else {
                $kq[] = [$k[0], $k[1]];
            }
        }

        return $kq;
    }

    /** Tổng giây của các khoảng (đã gộp) nằm trong [$tu, $den]. */
    public static function giayTrongKy(array $khoang, $tu, $den)
    {
        $tong = 0;
        foreach (self::gopKhoang($khoang) as $k) {
            $tong += max(0, min($k[1], $den) - max($k[0], $tu));
        }

        return (int) $tong;
    }

    /** Sắp giảm dần theo $truong; null xuống cuối; ổn định với giá trị bằng nhau. */
    public static function sapGiam(array $cot, $truong)
    {
        $cot = array_values($cot);
        $chiSo = array_keys($cot);
        usort($chiSo, function ($a, $b) use ($cot, $truong) {
            $x = $cot[$a][$truong];
            $y = $cot[$b][$truong];
            if ($x === null || $y === null) {
                return $x === $y ? $a <=> $b : ($x === null ? 1 : -1);
            }

            return ($y <=> $x) ?: ($a <=> $b);
        });

        return array_map(function ($i) use ($cot) {
            return $cot[$i];
        }, $chiSo);
    }
}

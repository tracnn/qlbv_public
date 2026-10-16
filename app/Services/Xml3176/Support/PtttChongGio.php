<?php

namespace App\Services\Xml3176\Support;

/**
 * Hai phep tinh cho quy tac thoi gian thuc hien PTTT trong cung ho so.
 *
 * Do 15/10/2026: dong Hut dom ghi NGAY_KQ don ve cuoi dot (toi 1.228 gio) chong voi moi dong
 * khac - so tung cap thi ho so 000007038497 ra 372.807 loi va tran 512 MB. Dong vuot nguong
 * bao loi rieng; phan con lai quet mot luot, moi dong chi giu so doi tac va vai doi tac dau.
 *
 * Helper thuan - khong cham DB/model/config.
 */
class PtttChongGio
{
    /**
     * Nguong gio thuc hien toi da cua ma dich vu: tien to dai nhat trong $theoMa khop dau ma
     * (ma co hau to _XX van khop), khong khop thi $macDinh.
     */
    public static function nguongGio(string $maDichVu, int $macDinh, array $theoMa): int
    {
        $nguong = $macDinh;
        $dai = -1;
        foreach ($theoMa as $tienTo => $gio) {
            $tienTo = (string) $tienTo;
            if ($tienTo !== '' && strpos($maDichVu, $tienTo) === 0 && strlen($tienTo) > $dai) {
                $nguong = (int) $gio;
                $dai = strlen($tienTo);
            }
        }

        return $nguong;
    }

    /**
     * Quet mot luot tim cac dong chong thoi gian [tu, den) (bien nua mo nhu ServiceOverlapChecker).
     *
     * @param array $dong     list ['id' => int, 'tu' => int, 'den' => int] (timestamp)
     * @param int   $toiDaNeu so doi tac toi da giu lai moi dong
     * @return array [id => ['so' => int, 'neu' => int[]]] chi gom dong co chong, khoa theo id
     *               tang dan; 'neu' theo (gio bat dau, id) tang dan
     */
    public static function quet(array $dong, int $toiDaNeu): array
    {
        $dong = array_values(array_filter($dong, function ($d) {
            return $d['den'] > $d['tu'];
        }));
        usort($dong, function ($a, $b) {
            return [$a['tu'], $a['id']] <=> [$b['tu'], $b['id']];
        });

        $kq = [];
        $dangMo = [];
        foreach ($dong as $d) {
            $dangMo = array_filter($dangMo, function ($m) use ($d) {
                return $m['den'] > $d['tu'];
            });

            foreach ($dangMo as $m) {
                self::them($kq, $d['id'], $m['id'], $toiDaNeu);
                self::them($kq, $m['id'], $d['id'], $toiDaNeu);
            }

            $dangMo[] = $d;
        }
        ksort($kq);

        return $kq;
    }

    private static function them(array &$kq, int $id, int $doiTac, int $toiDaNeu): void
    {
        if (!isset($kq[$id])) {
            $kq[$id] = ['so' => 0, 'neu' => []];
        }
        $kq[$id]['so']++;
        if (count($kq[$id]['neu']) < $toiDaNeu) {
            $kq[$id]['neu'][] = $doiTac;
        }
    }
}

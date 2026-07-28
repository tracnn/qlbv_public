<?php

namespace App\Services\OrderCheck\Support;

/**
 * Tach chuoi ma benh cua phieu chi dinh.
 *
 * his_service_req.icd_sub_code la chuoi nhieu ma ngan boi ';' va CO dau ';' DAN DAU:
 * ';A04.9', ';A04.9;E87.8'. Nen explode luon sinh mot phan tu rong dau tien. Khong bo no
 * thi MOI phieu co chan doan phu deu thanh vi pham - 39.242 phieu moi 7 ngay.
 *
 * icd_code thi luon la ma don (0/61.003 phieu co dau ';'), nhung van tach cho dong nhat.
 */
class MaBenh
{
    /**
     * @param mixed $chuoi
     * @return string[] da trim, bo rong, bo trung, giu thu tu xuat hien
     */
    public static function tach($chuoi)
    {
        $ra = [];

        foreach (explode(';', (string) $chuoi) as $m) {
            $m = trim($m);

            if ($m !== '' && !in_array($m, $ra, true)) {
                $ra[] = $m;
            }
        }

        return $ra;
    }

    /**
     * Gom ma cua chan doan chinh va chan doan phu.
     *
     * Mot ma khai sai o ca hai cho van chi la MOT ma khai sai.
     *
     * @return array ma => 'chinh' | 'phu' | 'ca_hai'
     */
    public static function gom($chinh, $phu)
    {
        $ra = [];

        foreach (self::tach($chinh) as $m) {
            $ra[$m] = 'chinh';
        }

        foreach (self::tach($phu) as $m) {
            $ra[$m] = isset($ra[$m]) ? 'ca_hai' : 'phu';
        }

        return $ra;
    }

    /** Nhan hien thi cua vi tri, dung trong thong diep vi pham */
    public static function nhanViTri($viTri)
    {
        $nhan = [
            'chinh' => 'chẩn đoán chính',
            'phu' => 'chẩn đoán phụ',
            'ca_hai' => 'chẩn đoán chính và phụ',
        ];

        return isset($nhan[$viTri]) ? $nhan[$viTri] : $viTri;
    }
}

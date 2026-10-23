<?php

namespace App\Services\SinhXml3176\SoSanh;

/**
 * Ghep dong va so truong cua mot bang giua hai ben (A, B).
 *
 * - Khoa dong theo tung bang (CAC_BANG); neu mot khoa xuat hien nhieu lan o mot ben thi them hau to #1, #2...
 *   cho moi lan xuat hien (o ca hai ben) de cac dong van ghep duoc theo thu tu.
 * - Chuan hoa truoc khi so: cat khoang trang; so thi so theo gia tri ("15000" == "15000.00", chi cat so 0 o phan
 *   thap phan, "17000" != "17"); rong == "0" chi o cot so (tien/so luong/ty le).
 */
class SoSanhBang
{
    /** loai => [[the goc, the dong], danh sach khoa] */
    const CAC_BANG = [
        'XML1' => [null, ['MA_LK']],
        'XML2' => [['CHITIEU_CHITIET_THUOC', 'CHI_TIET_THUOC'], ['MA_LK', 'MA_THUOC', 'NGAY_YL']],
        'XML3' => [['CHITIEU_CHITIET_DVKT_VTYT', 'CHI_TIET_DVKT'], ['MA_LK', 'MA_DICH_VU', 'MA_VAT_TU', 'NGAY_YL']],
        'XML4' => [['CHITIEU_CHITIET_DICHVUCANLAMSANG', 'CHI_TIET_CLS'], ['MA_LK', 'MA_DICH_VU', 'MA_CHI_SO', 'NGAY_KQ']],
        'XML5' => [['CHITIEU_CHITIET_DIENBIENLAMSANG', 'CHI_TIET_DIEN_BIEN_BENH'], ['MA_LK', 'THOI_DIEM_DBLS']],
        'XML6' => [['CHI_TIEU_HO_SO_BENH_AN_CHAM_SOC_VA_DIEU_TRI_HIV_AIDS', 'HO_SO_BENH_AN_CHAM_SOC_VA_DIEU_TRI_HIV_AIDS'], ['MA_LK', 'STT']],
        'XML7' => [null, ['MA_LK']],
        'XML8' => [null, ['MA_LK']],
        // XML9: MA_LK + SO (so giay chung sinh); SO rong thi MA_LK + NGAY_SINH_CON
        'XML9' => [['CHI_TIEU_DU_LIEU_GIAY_CHUNG_SINH', 'DU_LIEU_GIAY_CHUNG_SINH'], ['MA_LK', 'SO']],
        'XML10' => [null, ['MA_LK']],
        'XML11' => [null, ['MA_LK']],
        'XML13' => [null, ['MA_LK']],
        'XML14' => [null, ['MA_LK']],
        'XML15' => [['CHI_TIEU_DIEUTRI_BENHLAO', 'CHITIET_DIEUTRI_BENHLAO'], ['MA_LK', 'STT']],
    ];

    /** Truong khoa du phong: khi truong khoa rong thi lay truong nay thay vao (loai => [truong => truong thay]). */
    const KHOA_DU_PHONG = [
        'XML9' => ['SO' => 'NGAY_SINH_CON'],
    ];

    /**
     * @param string  $loai  mot khoa cua CAC_BANG
     * @param array[] $dongA danh sach dong (ten truong => gia tri)
     * @param array[] $dongB nt
     *
     * @return array[] cac muc ['khoa', 'kieu' => chi_co_a|chi_co_b|khac, 'truong', 'a', 'b']
     */
    public static function so($loai, array $dongA, array $dongB)
    {
        $khoaA = self::danhKhoa($loai, $dongA);
        $khoaB = self::danhKhoa($loai, $dongB);
        // khoa trung o mot ben thi danh so ca hai ben
        $trung = [];
        foreach ([$khoaA, $khoaB] as $ds) {
            foreach (array_count_values($ds) as $k => $n) {
                if ($n > 1) {
                    $trung[(string) $k] = true;
                }
            }
        }
        $mA = self::gan($khoaA, $dongA, $trung);
        $mB = self::gan($khoaB, $dongB, $trung);

        $kq = [];
        foreach (array_unique(array_merge(array_keys($mA), array_keys($mB))) as $khoa) {
            $khoa = (string) $khoa;
            if (!isset($mB[$khoa])) {
                $kq[] = ['khoa' => $khoa, 'kieu' => 'chi_co_a', 'truong' => null, 'a' => null, 'b' => null];
                continue;
            }
            if (!isset($mA[$khoa])) {
                $kq[] = ['khoa' => $khoa, 'kieu' => 'chi_co_b', 'truong' => null, 'a' => null, 'b' => null];
                continue;
            }
            foreach (array_unique(array_merge(array_keys($mA[$khoa]), array_keys($mB[$khoa]))) as $t) {
                $x = isset($mA[$khoa][$t]) ? trim($mA[$khoa][$t]) : '';
                $y = isset($mB[$khoa][$t]) ? trim($mB[$khoa][$t]) : '';
                if (!self::bang($x, $y, self::laCotSo($t))) {
                    $kq[] = ['khoa' => $khoa, 'kieu' => 'khac', 'truong' => (string) $t, 'a' => $x, 'b' => $y];
                }
            }
        }

        return $kq;
    }

    private static function danhKhoa($loai, array $dong)
    {
        $kq = [];
        foreach ($dong as $d) {
            $p = [];
            foreach (self::CAC_BANG[$loai][1] as $t) {
                $v = isset($d[$t]) ? trim($d[$t]) : '';
                if ($v === '' && isset(self::KHOA_DU_PHONG[$loai][$t])) {
                    $tt = self::KHOA_DU_PHONG[$loai][$t];
                    $v = isset($d[$tt]) ? trim($d[$tt]) : '';
                }
                $p[] = $v;
            }
            $kq[] = implode('|', $p);
        }

        return $kq;
    }

    /** khoa (co hau to #n neu trung) => dong */
    private static function gan(array $khoa, array $dong, array $trung)
    {
        $kq = [];
        $dem = [];
        foreach ($khoa as $i => $k) {
            if (isset($trung[$k])) {
                $dem[$k] = isset($dem[$k]) ? $dem[$k] + 1 : 1;
                $k .= '#' . $dem[$k];
            }
            $kq[$k] = $dong[$i];
        }

        return $kq;
    }

    /** Cot so: tien, so luong, ty le... Chi o cot nay rong == 0. */
    private static function laCotSo($truong)
    {
        return (bool) preg_match('/^(T_|THANH_TIEN|DON_GIA|SO_LUONG|TYLE|MUC_HUONG|SO_NGAY|CAN_NANG)/', $truong);
    }

    private static function bang($x, $y, $cotSo)
    {
        $x = self::chuanHoa($x);
        $y = self::chuanHoa($y);
        if ($x === $y) {
            return true;
        }

        return $cotSo && in_array($x, ['', '0'], true) && in_array($y, ['', '0'], true);
    }

    /** So thi dua ve dang thap phan chuan ("15000.00" -> "15000"); chi cat so 0 sau dau thap phan; khong dung float. */
    private static function chuanHoa($v)
    {
        if (preg_match('/^(-?)(0|[1-9][0-9]*)(?:\.([0-9]+))?$/', $v, $m)) {
            $le = isset($m[3]) ? rtrim($m[3], '0') : '';
            $so = $m[2] . ($le !== '' ? '.' . $le : '');

            return $so === '0' ? '0' : $m[1] . $so;
        }

        return $v;
    }
}

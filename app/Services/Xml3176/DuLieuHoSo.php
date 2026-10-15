<?php

namespace App\Services\Xml3176;

use App\Services\Xml3176\Support\SoSanhSql;
use Illuminate\Support\Facades\DB;

/**
 * Du lieu cua MOT ho so (ma_lk) nap mot lan: moi bang xml3176_xmlNs mot truy van, sap theo id.
 * Checker doc tu day thay vi tung truy van rieng; cac ham tinh tinh (tong/max/bang/trongTap)
 * tai hien ngu nghia SQL cu tren MySQL de ket qua kiem khong doi.
 *
 * Kieu cot thuc (migration 2026_01_09_1528*):
 * - Cot tien/so luong/muc_huong (thanh_tien_bv, thanh_tien_bh, t_bntt, t_bncct, t_nguonkhac,
 *   t_bhtt, so_luong, muc_huong, don_gia_bh, t_tongchi_bh...) la DOUBLE.
 * - ma_nhom, ma_pttt, ngay_kq... la VARCHAR(255) utf8_general_ci.
 *
 * Khong phu thuoc Eloquent: dong la stdClass tu Query Builder.
 */
class DuLieuHoSo
{
    /** Cac loai XML nap mac dinh (loai co checker / kiem tong the doc toi). */
    const LOAI_MAC_DINH = ['xml1', 'xml2', 'xml3', 'xml4', 'xml5', 'xml7', 'xml8', 'xml9', 'xml10', 'xml11', 'xml13', 'xml14'];

    /** @var string */
    private $maLk;

    /** @var array loai => stdClass[] (sap theo id) */
    private $bang = [];

    private function __construct($maLk)
    {
        $this->maLk = $maLk;
    }

    /**
     * Nap ho so: moi loai mot truy van `where ma_lk = ? order by id`, lay tat ca cot.
     *
     * @param string     $maLk
     * @param array|null $loai vd ['xml1', 'xml3']; null = LOAI_MAC_DINH
     */
    public static function nap(string $maLk, array $loai = null): self
    {
        $hs = new self($maLk);
        foreach ($loai === null ? self::LOAI_MAC_DINH : $loai as $l) {
            $l = strtolower($l);
            if (!preg_match('/^xml\d{1,2}$/', $l)) {
                throw new \InvalidArgumentException('Loai XML khong hop le: ' . $l);
            }
            $hs->bang[$l] = DB::table('xml3176_' . $l . 's')
                ->where('ma_lk', $maLk)
                ->orderBy('id')
                ->get()
                ->all();
        }

        return $hs;
    }

    public function maLk(): string
    {
        return $this->maLk;
    }

    /** Dong XML1 dau tien theo id (nhu ->first() sau orderBy id), null neu khong co. */
    public function xml1()
    {
        return $this->dau('xml1');
    }

    /**
     * Moi dong cua mot loai, sap theo id. Loai khong nap -> nem LogicException (tranh lang le
     * coi la "khong co dong" khi quen nap).
     *
     * @return \stdClass[]
     */
    public function dong(string $loai): array
    {
        $loai = strtolower($loai);
        if (!array_key_exists($loai, $this->bang)) {
            throw new \LogicException('Loai ' . $loai . ' chua duoc nap cho ho so ' . $this->maLk);
        }

        return $this->bang[$loai];
    }

    /** Co dong nao (thoa $loc neu co) - nhu ->exists(). */
    public function co(string $loai, callable $loc = null): bool
    {
        return $this->dau($loai, $loc) !== null;
    }

    /** Dong dau tien theo id (thoa $loc neu co) - nhu ->orderBy('id')->first(). */
    public function dau(string $loai, callable $loc = null)
    {
        foreach ($this->dong($loai) as $r) {
            if ($loc === null || $loc($r)) {
                return $r;
            }
        }

        return null;
    }

    /** So dong (thoa $loc neu co) - nhu ->count(). */
    public function dem(string $loai, callable $loc = null): int
    {
        return $loc === null ? count($this->dong($loai)) : count($this->loc($loai, $loc));
    }

    /** Cac dong thoa $loc, giu thu tu id, danh so lai tu 0. */
    public function loc(string $loai, callable $loc): array
    {
        return array_values(array_filter($this->dong($loai), $loc));
    }

    /**
     * Cac dong (thoa $loc neu co) theo thu tu MariaDB tra cho `where ma_lk = ? [and ...]` KHONG
     * orderBy: khoa `*_ma_lk_stt_unique` (xml1-5) -> stt tang dan, roi id (du phong). Bang chi co
     * khoa ma_lk unique (xml7..14) chi co mot dong nen thu tu khong anh huong. Dung thay cho
     * ->get()/->first()/->value() cu ma ket qua phu thuoc thu tu (mo ta, dong dau tien...).
     */
    public function dongTheoStt(string $loai, callable $loc = null): array
    {
        $ds = $loc === null ? $this->dong($loai) : $this->loc($loai, $loc);
        $khoa = [];
        foreach ($ds as $i => $r) {
            $khoa[$i] = [
                isset($r->stt) ? (int) $r->stt : PHP_INT_MIN,
                isset($r->id) ? (int) $r->id : PHP_INT_MIN,
                $i,
            ];
        }
        // Khoa (stt, id, vi tri) duy nhat nen usort khong on dinh cung khong anh huong
        uksort($ds, function ($a, $b) use ($khoa) {
            return $khoa[$a] <=> $khoa[$b];
        });

        return array_values($ds);
    }

    // ------------------------------------------------------------------ phep tinh kieu SQL

    /**
     * Tuong duong `SUM(cot)` cua Builder::sum() tren cot DOUBLE (cot tien, so_luong) - GIONG HET
     * MariaDB, ke ca sai so nhi phan (vd 45 x 0.135 = 6.074999999999994, round 2 -> 6.07):
     * - Cong so double PHP (IEEE 754, nhu MariaDB) tuan tu; KHONG dung bcadd (thap phan chinh
     *   xac se lech o ranh gioi .xx5 sau round(..., 2)).
     * - Thu tu cong = thu tu MariaDB quet: EXPLAIN tren prod cho `where ma_lk = ?` dung khoa
     *   `*_ma_lk_stt_unique` -> dong duoc duyet theo stt tang dan (stt integer, unique theo ma_lk
     *   nen khong hoa). Vi vay sap theo (int) stt, roi id, roi vi tri dau vao (du phong) - KHONG
     *   theo thu tu id. Tap con da loc (vd whereIn ma_nhom) van giu thu tu stt tuong doi.
     * - Bo NULL; tap rong/toan NULL -> 0.0 (Builder::sum() tra `?: 0`).
     * - Gia tri chuoi ep sang so theo kieu MySQL (soSql) roi sang float: '' -> 0, '12abc' -> 12.
     *
     * @param array $dong stdClass[] hoac mang (can cot stt de cong dung thu tu MariaDB)
     */
    public static function tong(array $dong, string $cot): float
    {
        $ds = [];
        foreach (array_values($dong) as $i => $r) {
            $v = self::giaTri($r, $cot);
            if ($v === null) {
                continue;
            }
            $stt = self::giaTri($r, 'stt');
            $id = self::giaTri($r, 'id');
            $ds[] = [
                $stt === null ? PHP_INT_MIN : (int) $stt,
                $id === null ? PHP_INT_MIN : (int) $id,
                $i,
                is_float($v) ? $v : (float) self::soSql($v),
            ];
        }
        // Khoa (stt, id, vi tri) la duy nhat nen usort khong on dinh cung khong anh huong
        usort($ds, function ($a, $b) {
            return [$a[0], $a[1], $a[2]] <=> [$b[0], $b[1], $b[2]];
        });

        $tong = 0.0;
        foreach ($ds as $x) {
            $tong += $x[3];
        }

        return $tong;
    }

    /**
     * Tuong duong `MAX(cot)` tren cot DOUBLE (vd muc_huong): bo NULL, so sanh SO (khong theo
     * chuoi: '100' > '95'); khong co gia tri -> null. Tra NGUYEN gia tri tho cua dong lon nhat
     * (dong dau tien neu bang nhau) - cung dang chuoi MySQL tra cho cot double, caller tu ep (float).
     * Khong dung cho cot VARCHAR (MAX tren varchar so sanh chuoi theo collation).
     */
    public static function max(array $dong, string $cot)
    {
        $max = null;
        $maxSo = null;
        foreach ($dong as $r) {
            $v = self::giaTri($r, $cot);
            if ($v === null) {
                continue;
            }
            $so = (float) self::soSql($v);
            if ($maxSo === null || $so > $maxSo) {
                $max = $v;
                $maxSo = $so;
            }
        }

        return $max;
    }

    /**
     * Tuong duong `cot = 'b'` tren cot VARCHAR utf8_general_ci: khong phan biet hoa thuong,
     * bo dau cach CUOI (PAD SPACE). NULL o bat ky ve nao -> NULL -> khong khop (false).
     * Dung cho ca `cot <> ''`: viet `$v !== null && !DuLieuHoSo::bang($v, '')`.
     */
    public static function bang($a, $b): bool
    {
        if ($a === null || $b === null) {
            return false;
        }

        return SoSanhSql::khoa($a) === SoSanhSql::khoa($b);
    }

    /**
     * Tuong duong `cot IN (...)` / `where(cot, x)` nhu Laravel bind roi MySQL so:
     * - Phan tu int (config nhu ma_nhom [10, 11]) -> Laravel bind PDO::PARAM_INT -> hang so so ->
     *   MySQL so chuoi voi so dang DOUBLE: '010', ' 10', '10.0', '10abc' deu = 10; '' = 0.
     * - Phan tu khac (chuoi, float, bool) -> bind PARAM_STR -> so chuoi theo collation (bang()).
     *   Float doi sang chuoi bang (string) nhu PDO.
     * - $cotSo = true: cot la DOUBLE -> MySQL ep moi hang so sang so, luon so dang so.
     * - NULL (gia tri hoac phan tu) khong bao gio khop.
     */
    public static function trongTap($v, array $tap, bool $cotSo = false): bool
    {
        if ($v === null) {
            return false;
        }
        foreach ($tap as $p) {
            if ($p === null) {
                continue;
            }
            if ($cotSo || is_int($p)) {
                if ((float) self::soSql($v) == (float) self::soSql($p)) {
                    return true;
                }
            } elseif (self::bang($v, (string) $p)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ep gia tri sang chuoi so thap phan (khong so mu) theo cach MySQL ep chuoi -> so:
     * bo khoang trang dau, lay tien to so dai nhat [+-]digits[.digits][e[+-]digits], phan con
     * lai bo qua; khong co chu so -> 0. Float PHP dung var_export (do chinh xac khu hoan toan,
     * vd 0.30000000000000004) chu khong dung (string) (precision 14).
     * So mu duoc mo rong chinh xac bang thao tac chuoi; mu bi chan trong [-400, 400] (ngoai
     * khoang do MySQL tran double hoac ve 0 o thang 6 - chi la du lieu rac, chap nhan).
     */
    public static function soSql($v): string
    {
        if (is_int($v)) {
            return (string) $v;
        }
        if (is_float($v)) {
            $v = var_export($v, true);
        }
        $v = (string) $v;

        if (!preg_match('/^\s*([+-]?)(\d*)(?:\.(\d*))?(?:[eE]([+-]?\d+))?/', $v, $m)
            || ($m[2] ?? '') === '' && ($m[3] ?? '') === ''
        ) {
            return '0';
        }
        $dau = $m[1] === '-' ? '-' : '';
        $nguyen = $m[2];
        $le = $m[3] ?? '';
        $mu = isset($m[4]) && $m[4] !== '' ? max(-400, min(400, (int) $m[4])) : 0;

        // Dich dau thap phan $mu vi tri tren chuoi chu so
        $chuSo = $nguyen . $le;
        $viTri = strlen($nguyen) + $mu;
        if ($viTri <= 0) {
            $nguyen = '0';
            $le = str_repeat('0', -$viTri) . $chuSo;
        } elseif ($viTri >= strlen($chuSo)) {
            $nguyen = $chuSo . str_repeat('0', $viTri - strlen($chuSo));
            $le = '';
        } else {
            $nguyen = substr($chuSo, 0, $viTri);
            $le = substr($chuSo, $viTri);
        }
        $nguyen = ltrim($nguyen, '0');

        return $dau . ($nguyen === '' ? '0' : $nguyen) . ($le === '' ? '' : '.' . $le);
    }

    /** Doc cot tu stdClass/mang; thieu cot -> null. */
    private static function giaTri($r, string $cot)
    {
        if (is_array($r)) {
            return $r[$cot] ?? null;
        }

        return isset($r->$cot) ? $r->$cot : null;
    }
}

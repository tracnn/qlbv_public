<?php

namespace App\Services\Import;

use DB;

/**
 * Ghi mot lo dong danh muc: tra mot lan, chen theo lo, chi cap nhat dong THUC SU doi.
 *
 * Do duoc truoc khi sua: 2.000 dong mat 3,11 giay va 4.000 truy van (updateOrCreate tung
 * dong); chen theo lo 500 dong chi mat 0,21 giay va 4 truy van.
 *
 * KHONG dung INSERT ... ON DUPLICATE KEY UPDATE du no chi ton mot truy van moi lo: cach do
 * dua hoan toan vao rang buoc UNIQUE, ma nhieu cot khoa cua ba danh muc cho phep NULL
 * (don_gia_bh, tt_thau, tu_ngay, quy_trinh, ma_cskcb). MySQL coi hai NULL la KHAC NHAU nen
 * rang buoc do khong chan duoc. Muon dung thi phai doi cac cot do thanh NOT NULL tren du
 * lieu san xuat: rui ro lon hon han loi ich, vi chenh lech chi la 3 truy van so voi 1 truy
 * van moi 500 dong.
 *
 * Chi phi moi lo: 1 SELECT + 1 INSERT + so truy van cap nhat bang dung so dong DOI.
 * Nhap lai dung tep cu khong sua gi: 1 truy van moi lo, khong ghi gi.
 *
 * BAT BIEN LOP NAY DUA VAO: gia tri khoa DA LUU phai khong con khoang trang thua.
 * khoaDong dung trim() cua PHP - von cat ca TAB - con truy van tra lai so sanh bang MySQL,
 * ma MySQL khong coi tab la khoang trang. Neu ban ghi da luu con dinh tab thi tra khong ra,
 * dong bi coi la moi va CHEN THEM MOI LAN NHAP. Da gap that: ma dich vu
 * '24.0019.1685.K.01910' dinh tab sinh 5 ban trong service_catalogs.
 *
 * Bat bien duoc GIU boi CatalogImportService::catKhoangTrang() luc ghi, va duoc THIET LAP
 * cho du lieu cu boi migration 2026_07_28_160000.
 */
class GhiTheoLo
{
    /** Ky tu ngan cac phan cua khoa; khong xuat hien trong du lieu danh muc */
    const NGAN = "\x1F";

    protected $bang;
    protected $cotKhoa;
    protected $ketQua;

    /** @var array khoa => true; giu xuyen suot lan nhap de khu trung TRONG CUNG TEP */
    protected $daGap = [];

    public function __construct($bang, array $cotKhoa, KetQuaNhapDanhMuc $ketQua)
    {
        $this->bang = $bang;
        $this->cotKhoa = $cotKhoa;
        $this->ketQua = $ketQua;
    }

    /**
     * Khoa so khop cua mot dong; dung cho ca tra CSDL lan khu trung trong tep.
     *
     * Ham THUAN. Chuan hoa: trim, null va chuoi rong la mot.
     */
    public static function khoaDong(array $dong, array $cotKhoa)
    {
        $phan = [];

        foreach ($cotKhoa as $c) {
            $v = isset($dong[$c]) ? $dong[$c] : null;
            $phan[] = self::chuanHoaKhoa($v);
        }

        return implode(self::NGAN, $phan);
    }

    /**
     * Chuan hoa mot phan cua khoa.
     *
     * Cot decimal(18,2) tra ve '10.00' con gia tri tu Excel la 10 - khong chuan hoa thi hai
     * ben ra hai khoa khac nhau, dong da co bi coi la moi va chen lai (roi vap rang buoc
     * UNIQUE).
     *
     * CHI cat phan thap phan thua, KHONG ep moi so ve dang so: ma co so '01929' ma ep sang
     * so se mat so 0 dan dau va dung voi '1929'.
     */
    protected static function chuanHoaKhoa($v)
    {
        $s = trim((string) $v);

        if ($s === '' || strpos($s, '.') === false) {
            return $s;
        }

        if (!preg_match('/^-?\d+\.\d+$/', $s)) {
            return $s;
        }

        $s = rtrim($s, '0');

        return rtrim($s, '.');
    }

    /**
     * Dong moi co khac ban ghi dang luu khong.
     *
     * Ham THUAN. Chi so cac truong DUOC NHAP; id/created_at cua ban ghi cu khong tinh.
     * So bang chuoi sau trim vi gia tri tu Excel la chuoi con tu CSDL co the la so.
     */
    public static function coThayDoi(array $moi, $cu)
    {
        $cu = (array) $cu;

        foreach ($moi as $cot => $v) {
            $vCu = array_key_exists($cot, $cu) ? $cu[$cot] : null;

            if (self::khac($v, $vCu)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Hai gia tri co khac nhau khong.
     *
     * So THEO SO khi ca hai deu la so: cot decimal(18,2) tra ve '10.00' con gia tri tu
     * Excel la 10 - so bang chuoi se coi la khac va cap nhat oan MOI dong moi lan nhap lai.
     */
    protected static function khac($a, $b)
    {
        $sa = trim((string) $a);
        $sb = trim((string) $b);

        if ($sa === $sb) {
            return false;
        }

        if ($sa !== '' && $sb !== '' && is_numeric($sa) && is_numeric($sb)) {
            return (float) $sa !== (float) $sb;
        }

        return true;
    }

    /**
     * @param array $loDong [['dong_excel' => int, 'du_lieu' => array], ...]
     */
    public function ghi(array $loDong)
    {
        if (empty($loDong)) {
            return;
        }

        // Khu trung TRONG CUNG TEP: dong sau trung khoa dong truoc thi ghi de.
        $theoKhoa = [];

        foreach ($loDong as $x) {
            $theoKhoa[self::khoaDong($x['du_lieu'], $this->cotKhoa)] = $x;
        }

        $daCo = $this->traDaCo($theoKhoa);
        $chen = [];

        foreach ($theoKhoa as $khoa => $x) {
            if (!isset($daCo[$khoa])) {
                if (isset($this->daGap[$khoa])) {
                    // Da chen o lo truoc trong cung lan nhap.
                    $this->ketQua->themKhongDoi();
                    continue;
                }

                $chen[$khoa] = $x['du_lieu'] + ['created_at' => now(), 'updated_at' => now()];
                $this->daGap[$khoa] = true;
                continue;
            }

            $cu = $daCo[$khoa];

            if (!self::coThayDoi($x['du_lieu'], $cu)) {
                $this->ketQua->themKhongDoi();
                continue;
            }

            try {
                DB::table($this->bang)->where('id', $cu->id)
                    ->update($x['du_lieu'] + ['updated_at' => now()]);
                $this->ketQua->themCapNhat();
            } catch (\Exception $e) {
                $this->ketQua->themLoi($x['dong_excel'], $e->getMessage());
            }
        }

        $this->chenTheoLo($chen, $theoKhoa);
    }

    /** Mot truy van cho ca lo: loc theo cot khoa DAN DAU roi so du bo khoa trong bo nho. */
    protected function traDaCo(array $theoKhoa)
    {
        $cotDau = $this->cotKhoa[0];
        $giaTriDau = [];

        foreach ($theoKhoa as $x) {
            $v = isset($x['du_lieu'][$cotDau]) ? trim((string) $x['du_lieu'][$cotDau]) : '';

            if ($v !== '') {
                $giaTriDau[$v] = true;
            }
        }

        if (empty($giaTriDau)) {
            return [];
        }

        $ra = [];

        foreach (DB::table($this->bang)->whereIn($cotDau, array_keys($giaTriDau))->get() as $r) {
            $ra[self::khoaDong((array) $r, $this->cotKhoa)] = $r;
        }

        return $ra;
    }

    protected function chenTheoLo(array $chen, array $theoKhoa)
    {
        if (empty($chen)) {
            return;
        }

        try {
            DB::table($this->bang)->insert(array_values($chen));

            for ($i = 0; $i < count($chen); $i++) {
                $this->ketQua->themNhap();
            }

            return;
        } catch (\Exception $e) {
            // Ca lo hong thi chen lai tung dong de biet DONG NAO loi, thay vi mat ca lo.
        }

        foreach ($chen as $khoa => $d) {
            try {
                DB::table($this->bang)->insert($d);
                $this->ketQua->themNhap();
            } catch (\Exception $e2) {
                $dong = isset($theoKhoa[$khoa]) ? $theoKhoa[$khoa]['dong_excel'] : 0;
                $this->ketQua->themLoi($dong, $e2->getMessage());
            }
        }
    }
}

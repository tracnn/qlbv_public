<?php

namespace App\Services\OrderCheck\Support;

use DB;

/**
 * Tra danh muc BHXH theo LO cho mot phieu.
 *
 * Bai hoc tu dot XML3176: cac checker o do tra danh muc 18 cho theo TUNG DONG, khien mot
 * ho so sinh hang nghin truy van. O day nap mot lan cho ca phieu bang whereIn.
 *
 * Loc hieu luc lam TRONG BO NHO chu khong trong SQL: mot lo y lenh co nhieu ngay chi dinh
 * khac nhau, neu loc ngay trong SQL thi moi ngay thanh mot truy van - pha vo dung cam ket
 * mot truy van cho ca phieu ma lop nay sinh ra de bao ve.
 */
class CatalogLookup
{
    protected $bang;
    protected $cot;
    protected $cotTen;
    protected $cotTu;
    protected $cotDen;

    /** @var array dieu kien loc co dinh, vi du ['is_active' => 1] */
    protected $dieuKien;

    /** @var array ma => [ ['ten'=>?string, 'tu'=>mixed, 'den'=>mixed], ... ] */
    protected $dong = [];

    /** @var bool|null null = chua kiem */
    protected $sanSang;

    /**
     * @param string|null $cotTu null = bang khong co cot ngay, bo loc hieu luc
     * @param array $dieuKien loc co dinh, vi du ['is_active' => 1]
     */
    public function __construct(
        $bang,
        $cot,
        $cotTen = null,
        $cotTu = 'tu_ngay',
        $cotDen = 'den_ngay',
        array $dieuKien = []
    ) {
        $this->bang = $bang;
        $this->cot = $cot;
        $this->cotTen = $cotTen;
        $this->cotTu = $cotTu;
        $this->cotDen = $cotDen;
        $this->dieuKien = $dieuKien;
    }

    /**
     * Bang danh muc co du lieu khong.
     *
     * Bang RONG -> tra false -> quy tac goi PHAI bo qua. Neu khong, don vi chua nhap danh
     * muc se thay MOI dich vu thanh vi pham - sai ma trong nhu dung.
     */
    public function sanSang()
    {
        if ($this->sanSang === null) {
            // Dieu kien PHAI duoc ap o day: bang co 12.229 dong nhung khong dong nao
            // is_active = 1 thi van la "chua co danh muc", khong the de moi ma thanh vi pham.
            $this->sanSang = DB::table($this->bang)
                ->where($this->dieuKien)
                ->limit(1)
                ->exists();
        }

        return $this->sanSang;
    }

    /**
     * Nap mot lo ma bang MOT truy van. Goi nhieu lan thi cong don.
     */
    public function nap(array $ma)
    {
        $ma = array_values(array_unique(array_filter(array_map(function ($m) {
            return trim((string) $m);
        }, $ma), 'strlen')));

        if (empty($ma) || !$this->sanSang()) {
            return;
        }

        $chon = [$this->cot];

        if ($this->cotTu !== null) {
            $chon[] = $this->cotTu;
        }

        if ($this->cotDen !== null) {
            $chon[] = $this->cotDen;
        }

        if ($this->cotTen !== null) {
            $chon[] = $this->cotTen;
        }

        $thay = DB::table($this->bang)
            ->whereIn($this->cot, $ma)
            ->where($this->dieuKien)
            ->select($chon)
            ->get();

        foreach ($thay as $d) {
            $d = (array) $d;
            $khoa = trim((string) $d[$this->cot]);

            if ($khoa === '') {
                continue;
            }

            $this->dong[$khoa][] = [
                'ten' => $this->cotTen === null ? null : trim((string) $d[$this->cotTen]),
                'tu' => $this->cotTu === null ? null : $d[$this->cotTu],
                'den' => $this->cotDen === null ? null : $d[$this->cotDen],
            ];
        }
    }

    /**
     * @param string $ma
     * @param int|null $ngayYmd null = khong loc hieu luc
     * @return bool
     */
    public function coTrongDanhMuc($ma, $ngayYmd = null)
    {
        return !empty($this->dongConHieuLuc($ma, $ngayYmd));
    }

    /**
     * Ten cua cac dong danh muc mang ma nay va CON hieu luc tai $ngayYmd.
     *
     * @return string[] da trim, da bo trung, giu thu tu xuat hien
     */
    public function tenTheoMa($ma, $ngayYmd = null)
    {
        $ten = [];

        foreach ($this->dongConHieuLuc($ma, $ngayYmd) as $d) {
            $t = (string) $d['ten'];

            if ($t !== '' && !in_array($t, $ten, true)) {
                $ten[] = $t;
            }
        }

        return $ten;
    }

    protected function dongConHieuLuc($ma, $ngayYmd)
    {
        $ma = trim((string) $ma);

        if ($ma === '' || !isset($this->dong[$ma])) {
            return [];
        }

        return array_values(array_filter($this->dong[$ma], function ($d) use ($ngayYmd) {
            return NgayHieuLuc::conHieuLuc($d['tu'], $d['den'], $ngayYmd);
        }));
    }

    /**
     * Chi dung trong test: nap thang vao bo nho, khong cham co so du lieu.
     *
     * @param array $ma cac ma khong quan tam ten/ngay
     * @param array $dong ma => [ ['ten'=>, 'tu'=>, 'den'=>], ... ]
     */
    public function datSanChoTest(array $ma, array $dong = [])
    {
        foreach ($ma as $m) {
            $this->dong[trim((string) $m)][] = ['ten' => null, 'tu' => null, 'den' => null];
        }

        foreach ($dong as $m => $ds) {
            foreach ($ds as $d) {
                $this->dong[trim((string) $m)][] = [
                    'ten' => isset($d['ten']) ? trim((string) $d['ten']) : null,
                    'tu' => isset($d['tu']) ? $d['tu'] : null,
                    'den' => isset($d['den']) ? $d['den'] : null,
                ];
            }
        }

        $this->sanSang = true;
    }

    /**
     * Chi dung trong test: ep trang thai "danh muc chua nap" ma khong phu thuoc noi dung
     * bang. Can thiet vi icd10_categories tren co so du lieu that dang co 12.229 dong,
     * icd_yhct_categories 4.144 dong - khong the kiem "bang rong" bang cach tra bang that.
     */
    public function datRongChoTest()
    {
        $this->dong = [];
        $this->sanSang = false;
    }
}

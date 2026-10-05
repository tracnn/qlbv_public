<?php

namespace Tests\Unit;

use App\Exports\KetQuaTraCuuTheExport;
use App\Models\CheckBHYT\check_hein_card;
use App\Services\BHYT\KhoaDieuTriHis;
use Tests\TestCase;

/**
 * Tep xuat Ket qua tra cuu the gan ho so voi khoa dieu tri cuoi trong HIS
 * (his_treatment.last_department_id). Khong cham Oracle: service duoc thay bang ban gia.
 */
class XuatTheKemKhoaHisTest extends TestCase
{
    protected function dong($maLk)
    {
        return (new check_hein_card())->forceFill(['ma_lk' => $maLk]);
    }

    /** Ban gia ghi lai tung lo duoc tra, tra ve khoa theo bang dung san. */
    protected function serviceGhiLo(array $bang = [])
    {
        return new class($bang) extends KhoaDieuTriHis {
            public $cacLo = [];
            protected $bang;

            public function __construct(array $bang)
            {
                $this->bang = $bang;
            }

            protected function traLo(array $lo)
            {
                $this->cacLo[] = $lo;

                return array_intersect_key($this->bang, array_flip($lo));
            }
        };
    }

    protected function viTriCot(KetQuaTraCuuTheExport $x, $tieuDe)
    {
        $i = array_search($tieuDe, $x->headings(), true);
        $this->assertNotFalse($i, "Thieu cot '$tieuDe'");

        return $i;
    }

    /** @test */
    public function service_chia_lo_khong_qua_1000_ma_va_bo_trung_bo_rong()
    {
        $s = $this->serviceGhiLo();
        $ma = array_map(function ($i) { return 'LK' . $i; }, range(1, 2500));

        $s->theoMaLk(array_merge($ma, ['LK1', '', null, '  ']));

        $this->assertSame([1000, 1000, 500], array_map('count', $s->cacLo),
            'Oracle gioi han IN 1000 phan tu - phai chia lo');
        $this->assertSame(2500, count(array_unique(array_merge(...$s->cacLo))));
    }

    /** @test */
    public function service_khong_tra_his_khi_khong_co_ma()
    {
        $s = $this->serviceGhiLo();

        $this->assertSame([], $s->theoMaLk(['', null]));
        $this->assertSame([], $s->cacLo);
    }

    /** @test */
    public function map_hien_ma_va_ten_khoa_theo_ma_lk()
    {
        $s = $this->serviceGhiLo([
            'LK1' => ['ma_khoa' => 'K01', 'ten_khoa' => 'Khoa Noi'],
        ]);
        $x = new KetQuaTraCuuTheExport(check_hein_card::query(), $s);

        $rows = collect([$this->dong('LK1'), $this->dong('LK2')]);
        $x->prepareRows($rows);

        $c1 = $x->map($rows[0]);
        $c2 = $x->map($rows[1]);

        $this->assertSame('K01', $c1[$this->viTriCot($x, 'Mã khoa (HIS)')]);
        $this->assertSame('Khoa Noi', $c1[$this->viTriCot($x, 'Khoa điều trị (HIS)')]);

        // Khong co trong HIS: de trong.
        $this->assertNull($c2[$this->viTriCot($x, 'Mã khoa (HIS)')]);
        $this->assertNull($c2[$this->viTriCot($x, 'Khoa điều trị (HIS)')]);

        $this->assertSame(count($x->headings()), count($c1));
    }

    /** @test */
    public function prepare_rows_tra_his_mot_lan_moi_lo()
    {
        $s = $this->serviceGhiLo();
        $x = new KetQuaTraCuuTheExport(check_hein_card::query(), $s);

        $rows = collect([$this->dong('LK1'), $this->dong('LK2'), $this->dong('LK1')]);
        $ra = $x->prepareRows($rows);

        $this->assertCount(1, $s->cacLo, 'Moi lo chi duoc mot truy van HIS');
        $this->assertSame($rows, $ra, 'prepareRows phai tra lai nguyen cac dong');
    }

    /**
     * Mat ket noi HIS: van xuat, nhung o khoa ghi ro loi - de trong se trong nhu "khong co khoa".
     */
    /** @test */
    public function loi_his_van_xuat_va_ghi_ro_trong_o_khoa()
    {
        $s = new class extends KhoaDieuTriHis {
            public function theoMaLk(array $maLk)
            {
                throw new \RuntimeException('ORA-12541: TNS:no listener');
            }
        };
        $x = new KetQuaTraCuuTheExport(check_hein_card::query(), $s);

        $rows = collect([$this->dong('LK1')]);
        $x->prepareRows($rows);
        $c = $x->map($rows[0]);

        $this->assertSame('Lỗi tra HIS', $c[$this->viTriCot($x, 'Khoa điều trị (HIS)')]);
        $this->assertSame('Lỗi tra HIS', $c[$this->viTriCot($x, 'Mã khoa (HIS)')]);
    }

    /** Hai cot moi o CUOI: khong xe dich cot nguoi dung da quen. */
    /** @test */
    public function hai_cot_khoa_nam_cuoi()
    {
        $h = (new KetQuaTraCuuTheExport(check_hein_card::query()))->headings();

        $this->assertSame(['Mã khoa (HIS)', 'Khoa điều trị (HIS)'], array_slice($h, -2));
        $this->assertSame('Nơi ĐKBĐ đã gửi', $h[count($h) - 3]);
    }
}

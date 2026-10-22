<?php

namespace Tests\Unit\SinhXml3176;

use App\Services\SinhXml3176\Bang\DichVuTongHop;
use App\Services\SinhXml3176\DuLieuHoSo;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;
use Illuminate\Support\Facades\DB;
use Tests\Support\DungBangHisSinhXml3176Sqlite;
use Tests\TestCase;

class DichVuTongHopTest extends TestCase
{
    use DungBangHisSinhXml3176Sqlite;

    const BHYT = 1;
    const VP = 2;

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangHis();
        $his = DB::connection('HISPro');
        $his->table('his_patient_type')->insert([
            ['id' => 1, 'patient_type_code' => 'BHYT', 'patient_type_name' => 'BHYT'],
            ['id' => 2, 'patient_type_code' => 'VP', 'patient_type_name' => 'VP'],
        ]);
        $his->table('his_config')->insert(['id' => 1, 'key' => HisDanhMuc::KEY_MA_DOI_TUONG_BHYT, 'value' => 'BHYT', 'is_active' => 1]);
    }

    private function dv($id, $loai, $hein, $nguonKhac = null, $gia = 100, $khongThucHien = 0)
    {
        return (object) [
            'id' => $id, 'patient_type_id' => $loai, 'vir_total_hein_price' => $hein,
            'other_source_price' => $nguonKhac, 'price' => $gia, 'is_no_execute' => $khongThucHien,
        ];
    }

    private function ids(array $dv)
    {
        $d = new DuLieuHoSo((object) ['id' => 5]);
        $d->dichVu = $dv;

        return array_map(function ($x) {
            return $x->id;
        }, DichVuTongHop::loc($d, HisDanhMuc::napChoLuot('HISPro')));
    }

    public function test_bo_bhyt_khong_tien_bh_khong_nguon_khac()
    {
        $this->assertSame([], $this->ids([
            $this->dv(1, self::BHYT, 0, 0), $this->dv(2, self::BHYT, 0, null), $this->dv(3, self::BHYT, null, 50),
        ]));
    }

    public function test_giu_bhyt_tien_bh_0_co_nguon_khac()
    {
        $this->assertSame([1, 2], $this->ids([
            $this->dv(1, self::BHYT, 0, 50), $this->dv(2, self::BHYT, 80, 0),
        ]));
    }

    public function test_giu_dong_khong_phai_bhyt()
    {
        $this->assertSame([1], $this->ids([$this->dv(1, self::VP, null, null)]));
    }

    public function test_bo_gia_0()
    {
        $this->assertSame([], $this->ids([
            $this->dv(1, self::VP, null, null, 0), $this->dv(2, self::BHYT, 80, 0, 0),
        ]));
    }

    public function test_giu_dong_khong_thuc_hien_du_gia_0()
    {
        $this->assertSame([1], $this->ids([$this->dv(1, self::BHYT, 0, 0, 0, 1), $this->dv(2, self::BHYT, 0, 0, 0, 0)]));
    }

    public function test_giu_thu_tu()
    {
        $this->assertSame([9, 3, 7, 1], $this->ids([
            $this->dv(9, self::VP, null), $this->dv(3, self::BHYT, 10), $this->dv(4, self::BHYT, 0, 0),
            $this->dv(7, self::VP, null), $this->dv(1, self::BHYT, 0, 5),
        ]));
    }
}

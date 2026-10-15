<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;
use App\Services\Xml3176ErrorService;

class Xml3176ErrorServiceGomTest extends TestCase
{
    private function dong($xml, $ma, $stt, $code, $moTa = 'x', $critical = false, $them = [])
    {
        return [
            'xml' => $xml, 'ma_lk' => $ma, 'stt' => $stt,
            'error_code' => $code, 'description' => $moTa,
            'critical_error' => $critical,
            'error_name' => $code . '-ten',
            'them' => $them,
        ];
    }

    /** @test */
    public function loc_bo_cac_ma_bi_tat_kiem_tra()
    {
        $kq = Xml3176ErrorService::chuanBiGhi(
            [$this->dong('XML2', 'A', 1, 'E1'), $this->dong('XML2', 'A', 2, 'E2')],
            ['E2'],
            '2026-07-28 10:00:00'
        );

        $tatCa = call_user_func_array('array_merge', array_values($kq['nhom']));
        $this->assertCount(1, $tatCa);
        $this->assertEquals('E1', $tatCa[0]['error_code']);
    }

    /** @test */
    public function moi_dong_deu_co_dau_thoi_gian()
    {
        // insert() KHONG tu dien created_at/updated_at nhu create(), ma bang co index
        // tren ca hai cot - quen la du lieu sai va bo loc theo ngay hong theo.
        $kq = Xml3176ErrorService::chuanBiGhi([$this->dong('XML2', 'A', 1, 'E1')], [], '2026-07-28 10:00:00');

        $dong = array_values($kq['nhom'])[0][0];
        $this->assertEquals('2026-07-28 10:00:00', $dong['created_at']);
        $this->assertEquals('2026-07-28 10:00:00', $dong['updated_at']);
    }

    /** @test */
    public function gom_theo_bo_cot_khi_additional_data_khac_nhau()
    {
        // insert() nhieu dong lay ten cot tu DONG DAU TIEN - tron lan cac dong khac bo
        // cot se lech du lieu am tham.
        $kq = Xml3176ErrorService::chuanBiGhi(
            [
                $this->dong('XML2', 'A', 1, 'E1'),
                $this->dong('XML3', 'A', 1, 'E2', 'x', false, ['ngay_yl' => '202607011000']),
            ],
            [],
            '2026-07-28 10:00:00'
        );

        $this->assertCount(2, $kq['nhom'], 'Hai bo cot khac nhau phai thanh hai nhom');

        foreach ($kq['nhom'] as $nhom) {
            $bo = array_keys($nhom[0]);
            foreach ($nhom as $d) {
                $this->assertEquals($bo, array_keys($d), 'Trong mot nhom moi dong phai cung bo cot');
            }
        }
    }

    /** @test */
    public function danh_muc_chi_lay_cac_cap_khac_nhau()
    {
        // Ban cu goi updateOrCreate cho TUNG loi: 50 loi cung ma la 50 lan ghi y het nhau.
        $kq = Xml3176ErrorService::chuanBiGhi(
            [
                $this->dong('XML2', 'A', 1, 'E1'),
                $this->dong('XML2', 'A', 2, 'E1'),
                $this->dong('XML2', 'A', 3, 'E1'),
                $this->dong('XML3', 'A', 1, 'E1'),
            ],
            [],
            '2026-07-28 10:00:00'
        );

        $this->assertCount(2, $kq['danhMuc'], 'Chi con hai cap (xml, ma loi) khac nhau');
    }

    /** @test */
    public function bo_dem_rong_khong_no()
    {
        $kq = Xml3176ErrorService::chuanBiGhi([], [], '2026-07-28 10:00:00');

        $this->assertEquals([], $kq['nhom']);
        $this->assertEquals([], $kq['danhMuc']);
    }

    /** @test */
    public function cot_them_khong_lot_vao_ban_ghi_loi()
    {
        // Khoa 'them' va 'error_name' la du lieu noi bo cua bo dem, khong phai cot cua
        // bang xml3176_error_results.
        $kq = Xml3176ErrorService::chuanBiGhi(
            [$this->dong('XML2', 'A', 1, 'E1', 'x', false, ['ngay_yl' => '202607011000'])],
            [],
            '2026-07-28 10:00:00'
        );

        $dong = array_values($kq['nhom'])[0][0];
        $this->assertArrayNotHasKey('them', $dong);
        $this->assertArrayNotHasKey('error_name', $dong);
        $this->assertEquals('202607011000', $dong['ngay_yl']);
    }

    /** @test */
    public function che_do_gom_bat_tat_dung()
    {
        $svc = app(Xml3176ErrorService::class);

        $this->assertFalse($svc->dangGom());

        $svc->batDauGom();
        $this->assertTrue($svc->dangGom());
        $this->assertEquals(0, $svc->soDongTrongBoDem());

        $svc->saveErrors('XML2', 'A', 1, collect([
            (object) ['error_code' => 'E1', 'description' => 'x', 'critical_error' => false],
        ]));

        $this->assertEquals(1, $svc->soDongTrongBoDem(), 'Dang gom thi phai vao bo dem, khong ghi thang');

        $svc->ketThucGom();
        $this->assertFalse($svc->dangGom());
        $this->assertEquals(0, $svc->soDongTrongBoDem(), 'Bo dem phai duoc don sau khi ghi');
    }

    /** @test */
    public function ket_thuc_gom_hai_lan_khong_no()
    {
        $svc = app(Xml3176ErrorService::class);

        $svc->batDauGom();
        $svc->ketThucGom();
        $svc->ketThucGom();

        $this->assertFalse($svc->dangGom());
    }
    private function loiCungMa($n, $ma = 'E1')
    {
        $ds = collect();
        for ($i = 0; $i < $n; $i++) {
            $ds->push((object) ['error_code' => $ma, 'error_name' => $ma . '-ten', 'description' => 'x' . $i, 'critical_error' => true]);
        }

        return $ds;
    }

    /**
     * @test
     * Ho so 000007038497 (15/10/2026): 372.807 loi mot ma lam bo dem tran 512 MB. Tran chung
     * cho moi quy tac de mot quy tac bung no khong lam sap worker.
     */
    public function bo_dem_chan_tran_moi_ho_so_moi_ma()
    {
        $svc = new Xml3176ErrorService();
        $svc->batDauGom();

        $svc->saveErrors('XML3', 'A', 7, $this->loiCungMa(1999), ['ngay_yl' => '202601010800']);
        $svc->saveErrors('XML3', 'A', 8, $this->loiCungMa(6), ['ngay_yl' => '202601010900']);
        $svc->saveErrors('XML3', 'A', 9, $this->loiCungMa(3, 'E2'));
        $svc->saveErrors('XML3', 'B', 1, $this->loiCungMa(4));

        $boDem = $svc->boDemGom();
        $a1 = array_values(array_filter($boDem, function ($d) {
            return $d['ma_lk'] === 'A' && $d['error_code'] === 'E1';
        }));

        $this->assertCount(Xml3176ErrorService::TRAN_MOI_MA + 1, $a1);
        $tomTat = $a1[Xml3176ErrorService::TRAN_MOI_MA];
        $this->assertSame('Đã lược bớt 5 lỗi cùng mã E1 của hồ sơ (chỉ giữ 2.000 dòng đầu).', $tomTat['description']);
        $this->assertSame(8, $tomTat['stt'], 'stt cua loi bi luoc dau tien');
        $this->assertSame('XML3', $tomTat['xml']);
        $this->assertTrue($tomTat['critical_error']);
        $this->assertSame('E1-ten', $tomTat['error_name']);
        $this->assertSame([], $tomTat['them']);

        $this->assertSame(2000 + 1 + 3 + 4, $svc->soDongTrongBoDem(), 'ma khac va ho so khac khong bi anh huong');
    }

    /** @test */
    public function bat_dau_gom_lai_thi_dem_lai()
    {
        $svc = new Xml3176ErrorService();
        $svc->batDauGom();
        $svc->saveErrors('XML3', 'A', 1, $this->loiCungMa(2000));
        $svc->batDauGom();
        $svc->saveErrors('XML3', 'A', 1, $this->loiCungMa(10));

        $this->assertSame(10, $svc->soDongTrongBoDem());
    }
}

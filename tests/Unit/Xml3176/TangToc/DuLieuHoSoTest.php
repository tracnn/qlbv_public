<?php

namespace Tests\Unit\Xml3176\TangToc;

use App\Services\Xml3176\DuLieuHoSo;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class DuLieuHoSoTest extends TestCase
{
    use Xml3176RuleTestSupport;

    private function bootDuBang()
    {
        $this->bootXml3176Sqlite([
            '2026_01_09_152817_create_xml3176_xml1s_table.php',
            '2026_01_09_152826_create_xml3176_xml2s_table.php',
            '2026_01_09_152832_create_xml3176_xml3s_table.php',
            '2026_01_09_152838_create_xml3176_xml4s_table.php',
            '2026_01_09_152843_create_xml3176_xml5s_table.php',
            '2026_01_09_152854_create_xml3176_xml7s_table.php',
            '2026_01_09_152900_create_xml3176_xml8s_table.php',
            '2026_01_09_152906_create_xml3176_xml9s_table.php',
            '2026_01_09_152912_create_xml3176_xml10s_table.php',
            '2026_01_09_152918_create_xml3176_xml11s_table.php',
            '2026_01_09_152930_create_xml3176_xml13s_table.php',
            '2026_01_09_152936_create_xml3176_xml14s_table.php',
        ]);
    }

    private function dong(array $giaTri, $cot = 'thanh_tien_bv')
    {
        return array_map(function ($v) use ($cot) {
            return (object) [$cot => $v];
        }, $giaTri);
    }

    // ---------------- tong ----------------

    /** @test */
    public function tong_bo_null_va_chinh_xac_thap_phan()
    {
        $this->assertSame('1.305000', DuLieuHoSo::tong($this->dong([0.1, 0.2, null, '1.005']), 'thanh_tien_bv'));
    }

    /** @test */
    public function tong_rong_bang_0()
    {
        // SUM tren tap rong / toan NULL -> NULL; Builder::sum() cu tra 0 (`?: 0`)
        $this->assertSame('0.000000', DuLieuHoSo::tong([], 'thanh_tien_bv'));
        $this->assertSame('0.000000', DuLieuHoSo::tong($this->dong([null, null]), 'thanh_tien_bv'));
    }

    /** @test */
    public function tong_so_am_va_so_mu()
    {
        // MySQL tra double dang '1e-7' / '1.5e3' qua giao thuc van ban; PHP float var_export ra '1.0E+25'
        $this->assertSame('1500.000000', DuLieuHoSo::tong($this->dong(['1.5e3']), 'thanh_tien_bv'));
        $this->assertSame('-0.499999', DuLieuHoSo::tong($this->dong(['-1.5', 1, '0.000001']), 'thanh_tien_bv'));
        $this->assertSame('10000000000000000000000000.000000', DuLieuHoSo::tong($this->dong([1e25]), 'thanh_tien_bv'));
        $this->assertSame('0.000010', DuLieuHoSo::tong($this->dong(['1e-5']), 'thanh_tien_bv'));
        $this->assertSame('0.000001', DuLieuHoSo::tong($this->dong([1e-6]), 'thanh_tien_bv'));
    }

    /** @test */
    public function tong_chuoi_kieu_mysql()
    {
        // Ep chuoi -> so kieu MySQL: bo khoang trang dau, lay tien to so, rac/rong -> 0
        $this->assertSame('15.500000', DuLieuHoSo::tong($this->dong(['', '12abc', 'abc', ' 3.5 ', '.']), 'thanh_tien_bv'));
        $this->assertSame('-2.000000', DuLieuHoSo::tong($this->dong(['-2e', '+0e5x']), 'thanh_tien_bv'));
    }

    /** @test */
    public function tong_khop_bieu_thuc_so_sanh_cu()
    {
        // 0.1 + 0.2 trong double = 0.30000000000000004; sau round(...,2) van bang 0.3
        $tong = DuLieuHoSo::tong($this->dong([0.1, 0.2]), 'thanh_tien_bv');
        $this->assertFalse(0.3 != round((float) $tong, 2));
        $this->assertSame(round(0.1 + 0.2, 2), round((float) $tong, 2));
    }

    // ---------------- max ----------------

    /** @test */
    public function max_bo_null()
    {
        $this->assertNull(DuLieuHoSo::max([], 'muc_huong'));
        $this->assertNull(DuLieuHoSo::max($this->dong([null, null], 'muc_huong'), 'muc_huong'));
        $this->assertSame('100', DuLieuHoSo::max($this->dong([null, '80', '100', null, '95'], 'muc_huong'), 'muc_huong'));
    }

    /** @test */
    public function max_so_sanh_so_khong_so_sanh_chuoi()
    {
        // muc_huong la cot double: MAX so sanh so ('100' > '95'), khong theo chuoi
        $this->assertSame('100', DuLieuHoSo::max($this->dong(['95', '100', '9'], 'muc_huong'), 'muc_huong'));
        $this->assertSame('-1', DuLieuHoSo::max($this->dong(['-5', '-1'], 'muc_huong'), 'muc_huong'));
    }

    // ---------------- bang / trongTap ----------------

    /** @test */
    public function bang_khong_phan_biet_hoa_thuong_va_khoang_trang_cuoi()
    {
        $this->assertTrue(DuLieuHoSo::bang('abc', 'ABC'));
        $this->assertTrue(DuLieuHoSo::bang('abc  ', 'ABC'));
        $this->assertTrue(DuLieuHoSo::bang('  ', ''));
        $this->assertFalse(DuLieuHoSo::bang(' abc', 'abc'));
        $this->assertFalse(DuLieuHoSo::bang("abc\t", 'abc'));
        // NULL = x -> NULL -> khong khop
        $this->assertFalse(DuLieuHoSo::bang(null, ''));
        $this->assertFalse(DuLieuHoSo::bang(null, null));
    }

    /** @test */
    public function trong_tap_so_va_chuoi()
    {
        // ma_nhom la varchar; config la so nguyen -> Laravel bind PARAM_INT -> MySQL so sanh
        // dang so (double): '010', ' 11', '10abc', '10.0' deu = so; '' = 0.
        $this->assertTrue(DuLieuHoSo::trongTap('10', [10, 11]));
        $this->assertTrue(DuLieuHoSo::trongTap('010', [10, 11]));
        $this->assertTrue(DuLieuHoSo::trongTap(' 11', [10, 11]));
        $this->assertTrue(DuLieuHoSo::trongTap('10abc', [10, 11]));
        $this->assertTrue(DuLieuHoSo::trongTap('10.0', [10, 11]));
        $this->assertFalse(DuLieuHoSo::trongTap('12', [10, 11]));
        $this->assertTrue(DuLieuHoSo::trongTap('', [0]));
        $this->assertFalse(DuLieuHoSo::trongTap(null, [10, 11]));
        $this->assertFalse(DuLieuHoSo::trongTap('10', []));

        // Phan tu chuoi -> so chuoi theo collation (khong phan biet hoa thuong, bo cach cuoi)
        $this->assertTrue(DuLieuHoSo::trongTap('ab ', ['AB']));
        $this->assertFalse(DuLieuHoSo::trongTap('010', ['10']));
        $this->assertFalse(DuLieuHoSo::trongTap(null, ['']));
    }

    /** @test */
    public function trong_tap_cot_so()
    {
        // Cot double so voi hang chuoi -> MySQL ep chuoi sang so
        $this->assertTrue(DuLieuHoSo::trongTap('80', ['80.0'], true));
        $this->assertFalse(DuLieuHoSo::trongTap('80', ['81'], true));
        $this->assertFalse(DuLieuHoSo::trongTap(null, ['0'], true));
    }

    // ---------------- nap ----------------

    /** @test */
    public function nap_moi_bang_mot_truy_van()
    {
        $this->bootDuBang();
        DB::table('xml3176_xml1s')->insert(['ma_lk' => 'A', 'stt' => 1]);
        DB::table('xml3176_xml3s')->insert(['ma_lk' => 'A', 'stt' => 1]);

        $dem = 0;
        DB::listen(function () use (&$dem) {
            $dem++;
        });
        $hs = DuLieuHoSo::nap('A');
        $this->assertSame(12, $dem);

        $hs->dong('xml3');
        $hs->xml1();
        $hs->co('xml2');
        $this->assertSame(12, $dem, 'Doc lai khong duoc truy van them');

        $dem = 0;
        DuLieuHoSo::nap('A', ['xml1', 'xml3']);
        $this->assertSame(2, $dem);
    }

    /** @test */
    public function dong_sap_theo_id()
    {
        $this->bootDuBang();
        DB::table('xml3176_xml3s')->insert(['id' => 5, 'ma_lk' => 'A', 'stt' => 2, 'ma_nhom' => '10']);
        DB::table('xml3176_xml3s')->insert(['id' => 2, 'ma_lk' => 'A', 'stt' => 1, 'ma_nhom' => '1']);
        DB::table('xml3176_xml3s')->insert(['id' => 9, 'ma_lk' => 'A', 'stt' => 3, 'ma_nhom' => '11']);
        DB::table('xml3176_xml3s')->insert(['id' => 7, 'ma_lk' => 'B', 'stt' => 1, 'ma_nhom' => '10']);
        DB::table('xml3176_xml1s')->insert(['id' => 4, 'ma_lk' => 'A', 'stt' => 2]);
        DB::table('xml3176_xml1s')->insert(['id' => 3, 'ma_lk' => 'A', 'stt' => 1]);

        $hs = DuLieuHoSo::nap('A');
        $this->assertSame([2, 5, 9], array_map(function ($r) {
            return (int) $r->id;
        }, $hs->dong('xml3')));
        $this->assertSame(3, (int) $hs->xml1()->id);
        $this->assertSame([], $hs->dong('xml2'));
        $this->assertFalse($hs->co('xml2'));
        $this->assertTrue($hs->co('xml3'));

        $vtyt = function ($r) {
            return DuLieuHoSo::trongTap($r->ma_nhom, [10, 11]);
        };
        $this->assertTrue($hs->co('xml3', $vtyt));
        $this->assertSame(2, $hs->dem('xml3', $vtyt));
        $this->assertSame(3, $hs->dem('xml3'));
        $this->assertSame(5, (int) $hs->dau('xml3', $vtyt)->id);
        $this->assertSame(2, (int) $hs->dau('xml3')->id);
        $this->assertNull($hs->dau('xml2'));
        $this->assertSame([5, 9], array_map(function ($r) {
            return (int) $r->id;
        }, $hs->loc('xml3', $vtyt)));
    }

    /** @test */
    public function xml1_rong_tra_null_va_loai_chua_nap_thi_nem()
    {
        $this->bootDuBang();
        $hs = DuLieuHoSo::nap('KHONG_CO', ['xml1']);
        $this->assertNull($hs->xml1());

        $this->expectException(\LogicException::class);
        $hs->dong('xml3');
    }
}

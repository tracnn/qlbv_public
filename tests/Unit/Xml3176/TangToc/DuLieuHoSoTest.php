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

    /** Cong double tuan tu theo dung thu tu dau vao - mo phong SUM(double) cua MariaDB. */
    private function congDouble(array $giaTri)
    {
        $s = 0.0;
        foreach ($giaTri as $v) {
            if ($v !== null) {
                $s += (float) $v;
            }
        }

        return $s;
    }

    /** @test */
    public function tong_bo_null_cong_double_nhu_mariadb()
    {
        $tong = DuLieuHoSo::tong($this->dong([0.1, 0.2, null, '1.005']), 'thanh_tien_bv');
        $this->assertSame($this->congDouble([0.1, 0.2, null, 1.005]), $tong);
        // 0.1 + 0.2 la double, khong phai thap phan chinh xac
        $this->assertSame(0.1 + 0.2, DuLieuHoSo::tong($this->dong([0.1, 0.2]), 'thanh_tien_bv'));
    }

    /** @test */
    public function tong_rong_bang_0()
    {
        // SUM tren tap rong / toan NULL -> NULL; Builder::sum() cu tra 0 (`?: 0`)
        $this->assertSame(0.0, DuLieuHoSo::tong([], 'thanh_tien_bv'));
        $this->assertSame(0.0, DuLieuHoSo::tong($this->dong([null, null]), 'thanh_tien_bv'));
    }

    /** @test */
    public function tong_so_am_va_so_mu()
    {
        // MySQL tra double dang '1e-7' / '1.5e3' qua giao thuc van ban; PHP float var_export ra '1.0E+25'
        $this->assertSame(1500.0, DuLieuHoSo::tong($this->dong(['1.5e3']), 'thanh_tien_bv'));
        $this->assertSame(-1.5 + 1 + 0.000001, DuLieuHoSo::tong($this->dong(['-1.5', 1, '0.000001']), 'thanh_tien_bv'));
        $this->assertSame(1e25, DuLieuHoSo::tong($this->dong([1e25]), 'thanh_tien_bv'));
        $this->assertSame(1e-7, DuLieuHoSo::tong($this->dong(['1e-7']), 'thanh_tien_bv'));
    }

    /** @test */
    public function tong_chuoi_kieu_mysql()
    {
        // Ep chuoi -> so kieu MySQL: bo khoang trang dau, lay tien to so, rac/rong -> 0
        $this->assertSame(15.5, DuLieuHoSo::tong($this->dong(['', '12abc', 'abc', ' 3.5 ', '.']), 'thanh_tien_bv'));
        $this->assertSame(-2.0, DuLieuHoSo::tong($this->dong(['-2e', '+0e5x']), 'thanh_tien_bv'));
    }

    /** @test */
    public function tong_ranh_gioi_xx5_giong_double_khong_giong_thap_phan()
    {
        // 45 dong x 0.135: tong thap phan chinh xac 6.075 (round 2 -> 6.08) nhung double cong
        // tuan tu = 6.074999999999994 (round 2 -> 6.07). MariaDB cho 6.07 -> phai giong double.
        $dong = [];
        for ($i = 1; $i <= 45; $i++) {
            $dong[] = (object) ['id' => $i, 'stt' => $i, 'thanh_tien_bv' => '0.135'];
        }
        $tong = DuLieuHoSo::tong($dong, 'thanh_tien_bv');
        $this->assertSame(round($this->congDouble(array_fill(0, 45, '0.135')), 2), round($tong, 2));
        $this->assertSame(6.07, round($tong, 2));
        $this->assertSame('6.075', bcmul('0.135', '45', 3), 'Tong chinh xac thap phan la 6.075 (se round thanh 6.08)');

        // Nhieu dong (n = 419), gia tri 3 chu so le xen ke, so sanh voi cong double theo stt
        $giaTri = ['0.115', '1.005', '0.335', '2.345', '0.015'];
        $dong = [];
        $theoStt = [];
        for ($i = 1; $i <= 419; $i++) {
            $v = $giaTri[$i % 5];
            $dong[] = (object) ['id' => $i, 'stt' => $i, 'thanh_tien_bv' => $v];
            $theoStt[] = $v;
        }
        $this->assertSame($this->congDouble($theoStt), DuLieuHoSo::tong($dong, 'thanh_tien_bv'));
        $this->assertSame(round($this->congDouble($theoStt), 2), round(DuLieuHoSo::tong($dong, 'thanh_tien_bv'), 2));
    }

    /** @test */
    public function tong_cong_theo_thu_tu_stt_khong_theo_id()
    {
        // MariaDB quet theo khoa (ma_lk, stt) -> cong theo stt. Thu tu cong doi ket qua double:
        // 1e16 + 1 - 1e16 = 0 nhung 1e16 - 1e16 + 1 = 1.
        $dong = [
            (object) ['id' => 1, 'stt' => 1, 'thanh_tien_bv' => '1e16'],
            (object) ['id' => 2, 'stt' => 3, 'thanh_tien_bv' => '1'],
            (object) ['id' => 3, 'stt' => 2, 'thanh_tien_bv' => '-1e16'],
        ];
        // theo id: 1e16 + 1 - 1e16 = 0.0; theo stt: 1e16 - 1e16 + 1 = 1.0
        $this->assertSame(1.0, DuLieuHoSo::tong($dong, 'thanh_tien_bv'));
        $this->assertSame(1.0, DuLieuHoSo::tong(array_reverse($dong), 'thanh_tien_bv'));
        $this->assertSame(0.0, 1e16 + 1 - 1e16);
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

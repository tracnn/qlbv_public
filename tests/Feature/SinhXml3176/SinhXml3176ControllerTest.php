<?php

namespace Tests\Feature\SinhXml3176;

use App\Models\SinhXml3176\LanSinh;
use App\Models\SinhXml3176\Moc;
use Tests\Support\DungBangHisSinhXml3176Sqlite;
use Tests\Support\UserGiaCoQuyen;
use Tests\TestCase;

class SinhXml3176ControllerTest extends TestCase
{
    use DungBangHisSinhXml3176Sqlite;

    private $goc;

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangTheoDoi();
        $this->goc = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sinhxml3176_man_' . uniqid();
        mkdir($this->goc, 0775, true);
        config(['organization.sinh_xml3176' => [
            'thu_muc'          => 'D:\\XML\\3176',
            'thu_muc_tt'       => 'D:\\XML\\3176TT',
            'thu_muc_thu'      => 'D:\\XML\\SinhXml3176Thu',
            'thu_muc_luu'      => $this->goc,
            'ghi_thu_muc_that' => true,
        ]]);
    }

    protected function tearDown()
    {
        $this->xoaThuMuc($this->goc);
        parent::tearDown();
    }

    private function xoaThuMuc($thuMuc)
    {
        if (!is_dir($thuMuc)) {
            return;
        }
        foreach (array_diff(scandir($thuMuc), ['.', '..']) as $f) {
            $p = $thuMuc . DIRECTORY_SEPARATOR . $f;
            is_dir($p) ? $this->xoaThuMuc($p) : @unlink($p);
        }
        @rmdir($thuMuc);
    }

    private function dong($treatmentId, array $ghiDe = [])
    {
        $d = new LanSinh();
        $d->treatment_id = $treatmentId;
        $d->treatment_code = 'HS' . $treatmentId;
        $d->loai_ban = 'chinh_thuc';
        $d->trang_thai = 'da_ghi';
        $d->ten_tep = 'QLBV_HS' . $treatmentId . '.xml';
        $d->thu_muc = 'D:\\XML\\3176';
        $d->cac_bang = 'XML1,XML2';
        $d->created_at = '2026-10-20 10:00:00';
        $d->updated_at = '2026-10-20 10:00:00';
        foreach ($ghiDe as $k => $v) {
            $d->$k = $v;
        }
        $d->save();

        return $d;
    }

    private function goiJson($tham = [])
    {
        $mac = ['tu' => '2026-10-20', 'den' => '2026-10-20', 'draw' => 1, 'start' => 0, 'length' => 25];

        return $this->actingAs(UserGiaCoQuyen::tao())->get(
            '/bhyt/sinh-xml3176/fetch-data?' . http_build_query(array_merge($mac, $tham)),
            ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json']
        );
    }

    private function theoHoSo($data)
    {
        $ra = [];
        foreach ($data as $r) {
            $ra[$r['treatment_id']] = $r;
        }

        return $ra;
    }

    private function tepLuu($ten, $noiDung = null)
    {
        $tuongDoi = '20261020' . DIRECTORY_SEPARATOR . $ten;
        if ($noiDung !== null) {
            @mkdir($this->goc . DIRECTORY_SEPARATOR . '20261020');
            file_put_contents($this->goc . DIRECTORY_SEPARATOR . $tuongDoi, $noiDung);
        }

        return $tuongDoi;
    }

    /** @test */
    public function test_index_200()
    {
        $res = $this->actingAs(UserGiaCoQuyen::tao())->get('/bhyt/sinh-xml3176');
        $res->assertStatus(200);
        $this->assertContains('Theo dõi sinh XML3176', $res->getContent());
        $this->assertContains('/bhyt/sinh-xml3176/fetch-data', $res->getContent());
    }

    /** @test */
    public function test_mot_dong_mot_ho_so_lan_moi_nhat()
    {
        $this->dong(1, ['trang_thai' => 'bi_loai', 'ly_do' => 'cu']);
        $moi = $this->dong(1, ['created_at' => '2026-10-20 12:00:00']);
        $this->dong(2);

        $res = $this->goiJson()->assertStatus(200);
        $data = $res->json()['data'];
        $this->assertCount(2, $data);
        $theoHs = $this->theoHoSo($data);
        $this->assertSame($moi->id, $theoHs[1]['id']);
        $this->assertSame('da_ghi', $theoHs[1]['trang_thai']);
        $this->assertSame('HS1', $theoHs[1]['treatment_code']);
        $this->assertSame('that', $theoHs[1]['thu_muc_loai']);
        $this->assertSame(2, $res->json()['recordsTotal']);
        $this->assertSame(1, $res->json()['draw']);
    }

    /** @test */
    public function test_so_lan_sinh_chi_dem_da_ghi()
    {
        $this->dong(1);
        $this->dong(1, ['trang_thai' => 'loi', 'ly_do' => 'x']);
        $this->dong(1, ['trang_thai' => 'bi_loai']);
        $this->dong(1);
        $this->dong(2, ['trang_thai' => 'loi']);

        $theoHs = $this->theoHoSo($this->goiJson()->json()['data']);
        $this->assertSame(2, $theoHs[1]['so_lan_sinh']);
        $this->assertSame(0, $theoHs[2]['so_lan_sinh']);
    }

    /** @test */
    public function test_loc_ngay_theo_dong_trong_khoang_trang_thai_theo_lan_moi_nhat()
    {
        // HS1: dong trong khoang la da_ghi nhung lan moi nhat (ngoai khoang) la loi -> trang thai hien tai = loi
        $this->dong(1, ['created_at' => '2026-10-20 09:00:00']);
        $this->dong(1, ['created_at' => '2026-10-22 09:00:00', 'trang_thai' => 'loi']);
        // HS2: chi co dong ngoai khoang -> khong hien
        $this->dong(2, ['created_at' => '2026-10-22 09:00:00']);
        // HS3: dong cuoi ngay 20 luc 23:59:59 van trong khoang
        $this->dong(3, ['created_at' => '2026-10-20 23:59:59']);

        $ids = array_column($this->goiJson()->json()['data'], 'treatment_id');
        sort($ids);
        $this->assertSame([1, 3], $ids);

        $loi = $this->goiJson(['trang_thai' => 'loi'])->json();
        $this->assertSame([1], array_column($loi['data'], 'treatment_id'));
        $this->assertSame(1, $loi['recordsFiltered']);

        $daGhi = $this->goiJson(['trang_thai' => 'da_ghi'])->json();
        $this->assertSame([3], array_column($daGhi['data'], 'treatment_id'));

        $this->dong(4, ['loai_ban' => 'thong_tuyen']);
        $this->assertSame([4], array_column($this->goiJson(['loai_ban' => 'thong_tuyen'])->json()['data'], 'treatment_id'));
        $this->assertSame([3, 1], array_column($this->goiJson(['loai_ban' => 'chinh_thuc'])->json()['data'], 'treatment_id'));

        $this->assertSame([3], array_column($this->goiJson(['ma' => 'HS3'])->json()['data'], 'treatment_id'));
    }

    /** @test */
    public function test_loc_ra_vien_va_khoa_vp_theo_khoang_14_so()
    {
        $this->dong(1, ['out_time' => 20261020000000, 'fee_lock_time' => 20261021235959]);
        $this->dong(2, ['out_time' => 20261020235959]);
        $this->dong(3, ['out_time' => 20261019235959]);
        $this->dong(4, ['out_time' => 20261021000000]);

        $ids = array_column($this->goiJson(['kieu_ngay' => 'ra_vien'])->json()['data'], 'treatment_id');
        sort($ids);
        $this->assertSame([1, 2], $ids);

        $vp = $this->goiJson(['kieu_ngay' => 'khoa_vp', 'tu' => '2026-10-21', 'den' => '2026-10-21'])->json()['data'];
        $this->assertSame([1], array_column($vp, 'treatment_id'));
    }

    /** @test */
    public function test_tham_so_sai_422()
    {
        foreach ([
            ['tu' => 'abc'],
            ['den' => '2026-13-45'],
            ['tu' => '2026-10-21', 'den' => '2026-10-20'],
            ['kieu_ngay' => 'khac'],
            ['trang_thai' => 'xyz'],
            ['loai_ban' => 'xyz'],
        ] as $tham) {
            $this->goiJson($tham)->assertStatus(422);
        }
    }

    /** @test */
    public function test_qua_31_ngay_422()
    {
        $this->goiJson(['tu' => '2026-09-01', 'den' => '2026-10-23'])->assertStatus(422);
        $this->goiJson(['tu' => '2026-10-01', 'den' => '2026-10-31'])->assertStatus(200);
        $this->goiJson(['tu' => '2026-10-01', 'den' => '2026-11-01'])->assertStatus(422);
    }

    /** @test */
    public function test_tong_hop_va_moc()
    {
        $this->dong(1);
        $this->dong(2, ['trang_thai' => 'loi']);
        $this->dong(3, ['trang_thai' => 'bi_loai']);
        $this->dong(4, ['trang_thai' => 'loi']);
        Moc::create(['loai_ban' => 'chinh_thuc', 'moc' => 20261020101010, 'updated_at' => '2026-10-20 10:11:12']);

        $tong = $this->goiJson(['trang_thai' => 'loi'])->json()['tong_hop'];
        $this->assertSame(1, $tong['da_ghi']);
        $this->assertSame(2, $tong['loi']);
        $this->assertSame(1, $tong['bi_loai']);
        $this->assertSame(4, $tong['tong']);
        $this->assertSame(20261020101010, $tong['moc']['chinh_thuc']['moc']);
        $this->assertNull($tong['moc']['thong_tuyen']['moc']);
    }

    /** @test */
    public function test_lich_su_gop_hai_loai_ban_theo_id()
    {
        $a = $this->dong(7, ['tep_luu' => $this->tepLuu('x.xml', '<x/>')]);
        $b = $this->dong(7, ['loai_ban' => 'thong_tuyen', 'trang_thai' => 'bi_loai', 'ly_do' => 'LYDO_CHAN_ABC']);
        $c = $this->dong(7, ['xoa_luu_luc' => '2026-10-21 08:00:00', 'xoa_luu_boi' => 5, 'tep_luu' => $this->tepLuu('y.xml')]);
        $this->dong(8);

        $res = $this->actingAs(UserGiaCoQuyen::tao())->get('/bhyt/sinh-xml3176/ho-so/7');
        $res->assertStatus(200);
        $html = $res->getContent();
        $this->assertContains('HS7', $html);
        $this->assertNotContains('HS8', $html);
        $this->assertContains('LYDO_CHAN_ABC', $html);
        $this->assertContains('thong_tuyen', $html);
        $this->assertContains('không có XML lưu trữ', $html);
        $this->assertContains('đã xoá', $html);
        $this->assertContains('khong_doi', $html);
        $this->assertLessThan(strpos($html, 'data-id="' . $b->id . '"'), strpos($html, 'data-id="' . $a->id . '"'));
        $this->assertLessThan(strpos($html, 'data-id="' . $c->id . '"'), strpos($html, 'data-id="' . $b->id . '"'));
        $this->assertContains('/bhyt/sinh-xml3176/lan/' . $a->id . '/xem', $html);
        $this->assertNotContains('/bhyt/sinh-xml3176/lan/' . $b->id . '/xem', $html);
    }

    /** @test */
    public function test_lich_su_tep_khong_con_tren_dia_va_so_voi_lan_truoc()
    {
        $a = $this->dong(9, ['tep_luu' => $this->tepLuu('a.xml', '<x/>')]);
        $b = $this->dong(9, ['tep_luu' => $this->tepLuu('b.xml')]);

        $html = $this->actingAs(UserGiaCoQuyen::tao())->get('/bhyt/sinh-xml3176/ho-so/9')->getContent();
        $this->assertContains('tệp không còn trên đĩa', $html);
        $this->assertNotRegExp('/so-sanh\?a=' . $a->id . '(&|&amp;)b=' . $b->id . '/', $html);

        $this->tepLuu('b.xml', '<x/>');
        $html = $this->actingAs(UserGiaCoQuyen::tao())->get('/bhyt/sinh-xml3176/ho-so/9')->getContent();
        $this->assertRegExp('/so-sanh\?a=' . $a->id . '(&|&amp;)b=' . $b->id . '/', $html);
    }

    /** @test */
    public function test_lich_su_404()
    {
        $this->actingAs(UserGiaCoQuyen::tao())->get('/bhyt/sinh-xml3176/ho-so/999')->assertStatus(404);
    }
}

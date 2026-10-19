<?php

namespace Tests\Unit\Tt12\Bh01;

use App\Services\Tt12\Bh01\Bh01Ky;
use App\Services\Tt12\Bh01\Bh01Nguon;
use Illuminate\Support\Facades\DB;
use Tests\Support\DungBangBh01Sqlite;
use Tests\TestCase;

class Bh01NguonTest extends TestCase
{
    use DungBangBh01Sqlite;

    public function setUp()
    {
        parent::setUp();
        $this->dungBangBh01();
    }

    private function dong(array $ghiDe = array())
    {
        DB::table('xml3176_xml1s')->insert(array_merge(array(
            'ma_lk' => 'LK', 'ma_the_bhyt' => 'DN4010000000001', 'ma_cskcb' => '01929',
            'nam_qt' => '2026', 'thang_qt' => '09', 'ma_loai_kcb' => '01',
            'ngay_ra' => '202609010800',
            't_tongchi_bv' => 0, 't_tongchi_bh' => 0, 't_bhtt' => 0,
            't_bncct' => 0, 't_bntt' => 0, 't_nguonkhac' => 0,
        ), $ghiDe));
    }

    private function ky()
    {
        return Bh01Ky::tu(2026, 9);
    }

    /** @test */
    public function chi_lay_ho_so_bhyt_dung_co_so_dung_ky()
    {
        $this->dong(array('ma_lk' => 'A'));
        $this->dong(array('ma_lk' => 'B'));
        $this->dong(array('ma_lk' => 'C', 'ma_the_bhyt' => ''));
        $this->dong(array('ma_lk' => 'D', 'ma_cskcb' => '37470'));
        $this->dong(array('ma_lk' => 'E', 'thang_qt' => '10'));

        $this->assertSame(2, Bh01Nguon::truyVan('01929', $this->ky())->count());
    }

    /** @test */
    public function thu_tu_theo_muc_roi_ngay_ra_roi_ma_lk()
    {
        $this->dong(array('ma_lk' => 'IV', 'ma_loai_kcb' => '03', 'ngay_ra' => '202609010100'));
        $this->dong(array('ma_lk' => 'I-B', 'ma_loai_kcb' => '01', 'ngay_ra' => '202609050800'));
        $this->dong(array('ma_lk' => 'III', 'ma_loai_kcb' => '04', 'ngay_ra' => '202609010100'));
        $this->dong(array('ma_lk' => 'I-A', 'ma_loai_kcb' => '01', 'ngay_ra' => '202609050800'));
        $this->dong(array('ma_lk' => 'I-0', 'ma_loai_kcb' => '01', 'ngay_ra' => '202609020800'));
        $this->dong(array('ma_lk' => 'LA', 'ma_loai_kcb' => '99', 'ngay_ra' => '202609010100'));

        $this->assertSame(
            array('I-0', 'I-A', 'I-B', 'III', 'IV', 'LA'),
            Bh01Nguon::truyVan('01929', $this->ky())->pluck('ma_lk')->all()
        );
    }

    /** @test */
    public function trung_muc_ngay_ra_ma_lk_thi_xep_theo_stt_roi_id()
    {
        // (ma_lk, stt) duy nhat: cung ma_lk nhieu dong thi stt quyet dinh thu tu. Chen nguoc de
        // thu tu tu nhien cua bang khong lam test xanh gia.
        $this->dong(array('ma_lk' => 'X', 'stt' => 2));
        $this->dong(array('ma_lk' => 'X', 'stt' => 1));

        $this->assertSame(
            array(1, 2),
            array_map('intval', Bh01Nguon::truyVan('01929', $this->ky())->pluck('stt')->all())
        );
    }

    /** @test */
    public function tong_hop_lam_tron_tung_dong()
    {
        // Ban luu tong cua gia tri da lam tron TUNG dong (Bh01ChuanHoa::tien), tong hop phai cung cach.
        $this->dong(array('ma_lk' => 'A', 't_tongchi_bv' => 10.004));
        $this->dong(array('ma_lk' => 'B', 't_tongchi_bv' => 10.004));
        $this->dong(array('ma_lk' => 'C', 't_tongchi_bv' => 10.004));

        $this->assertSame(30.0, Bh01Nguon::tongHop('01929', $this->ky())['t_tongchi_bv']);
    }

    /** @test */
    public function tong_hop_dung_so_lieu()
    {
        $this->dong(array('ma_loai_kcb' => '01', 't_bhtt' => 100.1, 't_tongchi_bv' => 10));
        $this->dong(array('ma_loai_kcb' => '06', 't_bhtt' => 50));
        $this->dong(array('ma_loai_kcb' => '03', 't_bhtt' => 25));
        $this->dong(array('ma_loai_kcb' => '99', 't_bhtt' => 5));
        $this->dong(array('ma_loai_kcb' => '01', 't_bhtt' => 999, 'ma_the_bhyt' => ''));

        $th = Bh01Nguon::tongHop('01929', $this->ky());

        $this->assertSame(4, $th['so_ho_so']);
        $this->assertSame(array(1 => 2, 2 => 0, 3 => 0, 4 => 1, 'khac' => 1), $th['theo_muc']);
        $this->assertSame(180.1, $th['t_bhtt']);
        $this->assertSame(10.0, $th['t_tongchi_bv']);
    }

    /** @test */
    public function tong_hop_khong_co_dong_tra_ve_khong()
    {
        $th = Bh01Nguon::tongHop('01929', $this->ky());

        $this->assertSame(0, $th['so_ho_so']);
        $this->assertSame(0.0, $th['t_bhtt']);
        $this->assertSame(array(1 => 0, 2 => 0, 3 => 0, 4 => 0, 'khac' => 0), $th['theo_muc']);
    }

    /** @test */
    public function thang_mot_chu_so_duoc_dem_so_khong()
    {
        $this->assertSame('09', Bh01Ky::tu(2026, 9)['thang']);
        $this->assertSame('202609', Bh01Ky::tu('2026', '9')['ky_qt']);
    }

    /** @test */
    public function ky_truoc_04_2026_bi_tu_choi()
    {
        $this->expectException(\InvalidArgumentException::class);
        Bh01Ky::tu(2026, 3);
    }

    /** @test */
    public function thang_ngoai_1_den_12_bi_tu_choi()
    {
        $this->expectException(\InvalidArgumentException::class);
        Bh01Ky::tu(2026, 13);
    }

    /** @test */
    public function doc_theo_lo_giu_dung_thu_tu_truy_van_qua_nhieu_lo()
    {
        $this->dong(array('ma_lk' => 'IV', 'ma_loai_kcb' => '03', 'ngay_ra' => '202609010100'));
        $this->dong(array('ma_lk' => 'I-B', 'ma_loai_kcb' => '01', 'ngay_ra' => '202609050800'));
        $this->dong(array('ma_lk' => 'III', 'ma_loai_kcb' => '04', 'ngay_ra' => '202609010100'));
        $this->dong(array('ma_lk' => 'I-A', 'ma_loai_kcb' => '01', 'ngay_ra' => '202609050800'));
        $this->dong(array('ma_lk' => 'I-0', 'ma_loai_kcb' => '01', 'ngay_ra' => '202609020800'));

        $doc = array();
        $soLo = 0;
        Bh01Nguon::docTheoLo('01929', $this->ky(), 2, function ($lo) use (&$doc, &$soLo) {
            $soLo++;
            foreach ($lo as $r) {
                $doc[] = $r->ma_lk;
            }
        });

        $this->assertSame(3, $soLo);
        $this->assertSame(Bh01Nguon::truyVan('01929', $this->ky())->pluck('ma_lk')->all(), $doc);
    }

    /** @test */
    public function doc_theo_lo_dung_som_khi_tra_false()
    {
        foreach (array('A', 'B', 'C', 'D', 'E') as $lk) {
            $this->dong(array('ma_lk' => $lk));
        }

        $soLo = 0;
        Bh01Nguon::docTheoLo('01929', $this->ky(), 2, function ($lo) use (&$soLo) {
            $soLo++;
            return false;
        });

        $this->assertSame(1, $soLo);
    }

    /**
     * Do that 19/10/2026 ky 10/2026 cua 01929 (21.125 ho so): chunk() bang LIMIT/OFFSET mat 37
     * giay MOT luot vi moi lo MySQL sap xep lai ca tap; tao ban doc hai luot nen vuot 60 giay.
     * Doc theo danh sach id chi mat ~2,4 giay. Khoa lai: khong truy van nao duoc dung OFFSET.
     *
     * @test
     */
    public function doc_theo_lo_khong_dung_offset()
    {
        foreach (array('A', 'B', 'C', 'D', 'E') as $lk) {
            $this->dong(array('ma_lk' => $lk));
        }

        DB::enableQueryLog();
        Bh01Nguon::docTheoLo('01929', $this->ky(), 2, function ($lo) {
        });

        foreach (DB::getQueryLog() as $q) {
            $this->assertNotContains('offset', strtolower($q['query']));
        }
    }
}

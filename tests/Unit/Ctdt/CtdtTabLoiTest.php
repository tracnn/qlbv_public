<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use App\Http\Controllers\BHYT\BHYTCtdtController;
use App\Services\Ctdt\CtdtDetailTabs;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;
use App\Models\BHYT\Ctdt\CtdtLoi;

class CtdtTabLoiTest extends TestCase
{
    use DungBangCtdtSqlite;

    /** @var BHYTCtdtController */
    private $controller;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
        $this->controller = new BHYTCtdtController();
    }

    private function hoSoCoLoi(array $cacLoi = [])
    {
        // so_loi chi dem muc CHAN, dung nhu CheckCtdtJob lam. Dat bang count($cacLoi)
        // se lam khoi tom tat tren tab hien so canh bao am.
        $soChan = 0;

        foreach ($cacLoi as $mot) {
            if (!isset($mot['muc_do']) || $mot['muc_do'] === 'chan') {
                $soChan++;
            }
        }

        $hoSo = CtdtHoSo::create([
            'ma_ho_so' => 'YT001', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01929', 'so_chung_tu' => 1, 'so_loi' => $soChan,
        ]);

        $chungTu = CtdtChungTu::create([
            'ho_so_id' => $hoSo->id, 'loai_ho_so' => 'CT03',
            'ma_chung_tu' => 'YT001', 'noi_dung_goc' => '<CT03/>',
        ]);

        foreach ($cacLoi as $mot) {
            CtdtLoi::create(array_merge([
                'ho_so_id' => $hoSo->id, 'chung_tu_id' => $chungTu->id,
                'ma_loi' => 'CTDT001', 'ten_truong' => 'HO_TEN',
                'mo_ta' => 'Thiếu trường bắt buộc HO_TEN', 'muc_do' => 'chan',
            ], $mot));
        }

        return $hoSo->fresh();
    }

    /** @test */
    public function tab_loi_luon_co_ke_ca_khi_khong_co_loi()
    {
        // Hien tab rong de nguoi dung XAC NHAN duoc "ho so nay khong co loi". An tab di
        // thi khong phan biet duoc "khong loi" voi "chua kiem".
        $tabs = CtdtDetailTabs::cua($this->hoSoCoLoi());

        $this->assertContains(CtdtDetailTabs::TAB_LOI, array_column($tabs, 'ma'));
    }

    /** @test */
    public function tab_loi_dung_TRUOC_tab_xml_goc()
    {
        $ma = array_column(CtdtDetailTabs::cua($this->hoSoCoLoi()), 'ma');

        $viTriLoi = array_search(CtdtDetailTabs::TAB_LOI, $ma);
        $viTriXml = array_search(CtdtDetailTabs::TAB_XML, $ma);

        $this->assertNotFalse($viTriLoi);
        $this->assertNotFalse($viTriXml);
        $this->assertLessThan($viTriXml, $viTriLoi, 'Tab Loi phai dung truoc tab XML goc');
    }

    /** @test */
    public function so_luong_tren_tab_loi_dem_ca_canh_bao()
    {
        // Nhan tren tab la "co bao nhieu dong trong tab nay", khac voi so_loi (chi dem muc
        // chan). Hai con so khac nhau la dung, mien la moi con so noi dung viec cua no.
        $tabs = CtdtDetailTabs::cua($this->hoSoCoLoi([
            ['muc_do' => 'chan'],
            ['muc_do' => 'canh_bao', 'ma_loi' => 'CTDT008'],
        ]));

        foreach ($tabs as $tab) {
            if ($tab['ma'] === CtdtDetailTabs::TAB_LOI) {
                $this->assertSame(2, $tab['so_luong']);

                return;
            }
        }

        $this->fail('Khong tim thay tab Loi');
    }

    /** @test */
    public function hop_le_chap_nhan_tab_loi()
    {
        $this->assertTrue(CtdtDetailTabs::hopLe($this->hoSoCoLoi(), CtdtDetailTabs::TAB_LOI));
    }

    /** @test */
    public function controller_tra_view_tab_loi_kem_danh_sach_loi()
    {
        $this->hoSoCoLoi([
            ['muc_do' => 'chan'],
            ['muc_do' => 'canh_bao', 'ma_loi' => 'CTDT008', 'ten_truong' => 'MA_THE',
             'mo_ta' => 'Thiếu MA_THE'],
        ]);

        $view = $this->controller->detailTab('YT001', CtdtDetailTabs::TAB_LOI);
        $duLieu = $view->getData();

        $this->assertSame('bhyt.ctdt.tab-loi', $view->getName());
        $this->assertCount(2, $duLieu['loi']);
    }

    /** @test */
    public function loi_muc_chan_hien_truoc_loi_canh_bao()
    {
        // Nguoi doc can thay ngay thu chan minh gui, khong phai loc bang mat qua mot danh
        // sach tron lan.
        $this->hoSoCoLoi([
            ['muc_do' => 'canh_bao', 'ma_loi' => 'CTDT008'],
            ['muc_do' => 'chan', 'ma_loi' => 'CTDT001'],
        ]);

        $loi = $this->controller->detailTab('YT001', CtdtDetailTabs::TAB_LOI)->getData()['loi'];

        $this->assertSame('chan', $loi->first()->muc_do);
    }

    /** @test */
    public function tab_loi_render_duoc_va_thoat_noi_dung()
    {
        // ten_truong, mo_ta va loai_ho_so deu la gia tri trich thang tu the XML ben ngoai.
        // Bo thoat o BAT KY o nao trong ba o do la mot lo hong XSS - nen ca ba deu phai co
        // test rieng, khong the coi mot o dai dien cho hai o kia.
        $this->hoSoCoLoi([
            [
                'ten_truong' => 'HO<script>alert(1)</script>',
                'mo_ta'      => 'Sai dinh dang <img src=x onerror=alert(1)>',
            ],
        ]);

        $html = $this->controller->detailTab('YT001', CtdtDetailTabs::TAB_LOI)->render();

        $this->assertNotContains('<img src=x', $html, 'mo_ta phai duoc thoat');
        $this->assertNotContains('<script>', $html, 'ten_truong cung tu XML ngoai, phai thoat');
        $this->assertContains('&lt;img', $html);
        $this->assertContains('&lt;script&gt;', $html);
    }

    /** @test */
    public function ho_so_chua_kiem_bao_ro_la_chua_kiem()
    {
        // 'Chua kiem' va 'khong co loi' la hai chuyen khac nhau. Hien giong nhau se lam
        // nguoi dung tuong ho so da qua kiem trong khi job con nam trong hang doi.
        $this->hoSoCoLoi();

        $html = $this->controller->detailTab('YT001', CtdtDetailTabs::TAB_LOI)->render();

        $this->assertContains('chưa được kiểm', $html);
    }

    /** @test */
    public function ho_so_da_kiem_va_khong_loi_bao_khac_voi_chua_kiem()
    {
        $hoSo = $this->hoSoCoLoi();
        $hoSo->update(['checked_at' => '2026-08-20 08:00:00']);

        $html = $this->controller->detailTab('YT001', CtdtDetailTabs::TAB_LOI)->render();

        $this->assertNotContains('chưa được kiểm', $html);
        $this->assertContains('Không có lỗi', $html);
    }

    /** @test */
    public function khoi_tom_tat_phan_biet_chua_kiem_voi_khong_loi()
    {
        // Con so 0 duoi nhan "So lo" tren mot ho so CHUA KIEM la mot loi noi doi: no doc
        // ra la "da kiem, khong loi". Nguoi van hanh nhin thay no se khong bao gio nghi
        // toi chuyen worker JobCtdt da chet.
        //
        // Va nhan phai la "Loi chan gui" chu khong phai "So loi": so_loi CHI dem loi muc
        // chan, con badge tren tab Loi dem CA canh bao - hai con so khac nhau ma cung mot
        // nhan thi nguoi doc se tuong mot trong hai cho dang hong.
        $nguon = file_get_contents(base_path('resources/views/bhyt/ctdt/detail.blade.php'));

        $this->assertContains(
            'Lỗi chặn gửi',
            $nguon,
            'Khoi tom tat phai dat nhan "Loi chan gui", khong phai "So loi"'
        );
        $this->assertNotContains(
            '<strong>Số lỗi:</strong>',
            $nguon,
            'Nhan cu "So loi" gay hieu nham voi badge tren tab Loi (badge dem ca canh bao)'
        );

        $this->assertContains(
            "empty(\$hoSo->checked_at) ? 'Chưa kiểm' : \$hoSo->so_loi",
            $nguon,
            'Khoi tom tat phai hien "Chua kiem" khi checked_at rong, khong phai con so 0'
        );
    }
}

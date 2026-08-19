<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use App\Services\Ctdt\CtdtDetailTabs;
use App\Services\Ctdt\CtdtNhanTruong;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;

class CtdtDetailTabsTest extends TestCase
{
    use DungBangCtdtSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
    }

    private function hoSoVoi(array $loai)
    {
        $hoSo = CtdtHoSo::create([
            'ma_ho_so' => 'YT001', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01929', 'so_chung_tu' => count($loai),
        ]);

        foreach ($loai as $l) {
            CtdtChungTu::create([
                'ho_so_id' => $hoSo->id, 'loai_ho_so' => $l, 'noi_dung_goc' => '<' . $l . '/>',
            ]);
        }

        return $hoSo->fresh();
    }

    /** @test */
    public function chi_hien_tab_cua_loai_ho_so_THUC_CO()
    {
        // Khac XML3176 von co dinh XML1-15: mot ho so chung tu hiem khi co du chin loai, va
        // chin tab trong la chin lan nguoi dung bam vao roi thay khong co gi.
        $tabs = CtdtDetailTabs::cua($this->hoSoVoi(['CT03', 'CT04']));

        $ma = array_column($tabs, 'ma');

        $this->assertContains('CT03', $ma);
        $this->assertContains('CT04', $ma);
        $this->assertNotContains('CT06', $ma);
        $this->assertNotContains('GIAYBAOTU', $ma);
    }

    /** @test */
    public function luon_co_tab_xml_goc_o_cuoi()
    {
        // Khi cong bao 205 (fileBase64Str khong hop le), xem XML nguyen van la cach duy nhat
        // doi chieu.
        $tabs = CtdtDetailTabs::cua($this->hoSoVoi(['CT03']));

        $cuoi = end($tabs);

        $this->assertSame('__XML__', $cuoi['ma']);
        $this->assertNotEmpty($cuoi['nhan']);
    }

    /** @test */
    public function moi_tab_co_nhan_tieng_viet_lay_tu_lop_loai()
    {
        $tabs = CtdtDetailTabs::cua($this->hoSoVoi(['CT03']));

        $this->assertSame('Giấy ra viện', $tabs[0]['nhan']);
    }

    /** @test */
    public function dem_so_chung_tu_cung_loai()
    {
        $tabs = CtdtDetailTabs::cua($this->hoSoVoi(['CT03', 'CT03', 'CT04']));

        $theoMa = [];

        foreach ($tabs as $t) {
            $theoMa[$t['ma']] = $t['so_luong'];
        }

        $this->assertSame(2, $theoMa['CT03']);
        $this->assertSame(1, $theoMa['CT04']);
    }

    /** @test */
    public function ho_so_khong_co_chung_tu_nao_van_co_tab_xml_goc()
    {
        $tabs = CtdtDetailTabs::cua($this->hoSoVoi([]));

        $this->assertCount(1, $tabs);
        $this->assertSame('__XML__', $tabs[0]['ma']);
    }

    /** @test */
    public function hop_le_chan_tab_ho_so_khong_co()
    {
        // Tham so {loai} den tu URL. Khong doi chieu thi bat ky ai cung ep duoc controller
        // truy van mot bang khong lien quan toi ho so dang xem.
        $hoSo = $this->hoSoVoi(['CT03']);

        $this->assertTrue(CtdtDetailTabs::hopLe($hoSo, 'CT03'));
        $this->assertTrue(CtdtDetailTabs::hopLe($hoSo, '__XML__'));
        $this->assertFalse(CtdtDetailTabs::hopLe($hoSo, 'CT04'));
        $this->assertFalse(CtdtDetailTabs::hopLe($hoSo, 'khong_ton_tai'));
    }

    /** @test */
    public function nhan_truong_dich_cac_the_pho_bien()
    {
        $this->assertSame('Họ tên', CtdtNhanTruong::cua('HO_TEN'));
        $this->assertSame('Mã thẻ BHYT', CtdtNhanTruong::cua('MA_THE'));
        $this->assertSame('Ngày vào', CtdtNhanTruong::cua('NGAY_VAO'));
    }

    /** @test */
    public function the_chua_co_trong_tu_dien_thi_lui_ve_chinh_ten_the()
    {
        // BHXH them the moi truoc khi ta kip cap nhat tu dien la chuyen se xay ra. Hien ten
        // the con hon hien o trong.
        $this->assertSame('THE_MOI_TINH', CtdtNhanTruong::cua('THE_MOI_TINH'));
    }
}

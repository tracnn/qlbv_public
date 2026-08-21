<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use App\Services\Ctdt\CtdtDetailTabs;
use App\Services\Ctdt\CtdtNhanTruong;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;
use App\Services\Ctdt\Loai\GiayChungSinh;
use App\Services\Ctdt\Loai\GiayBaoTu;
use App\Http\Controllers\BHYT\BHYTCtdtController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        $ma = array_column($tabs, 'ma');

        // Bat bien that su la "tab XML goc LUON co va LUON dung cuoi", khong phai "chi co
        // dung mot tab". Giai doan 3 them tab Loi dung truoc no; dem so tab la dem mot con
        // so tinh co, se vo moi lan them tab moi ma khong bat duoc loi nao.
        $this->assertContains('__XML__', $ma);
        $this->assertSame('__XML__', end($ma), 'Tab XML goc phai dung cuoi');
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

    /**
     * @test
     *
     * I6: hopLe() lui ve chinh ma loai khi registry khong biet no, nen no van tra true cho
     * mot ma da bi go khoi registry. Ho so cu mang loai do (vd sau khi Giai doan 3/4 thu
     * hep dang ky) phai cho ra 404 tu te, khong duoc de CtdtLoaiRegistry::cho() nem
     * LoaiKhongBietException thanh 500.
     */
    public function detailTab_tra_404_cho_loai_da_bi_go_khoi_registry()
    {
        $hoSo = $this->hoSoVoi(['LOAI_DA_GO_KHOI_DANG_KY']);

        $this->expectException(NotFoundHttpException::class);

        (new BHYTCtdtController())->detailTab($hoSo->ma_ho_so, 'LOAI_DA_GO_KHOI_DANG_KY');
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

    /** @test */
    public function hau_to_nhom_nguoi_duoc_thu_dung_thu_tu()
    {
        // '_CHA_MTH' PHAI duoc thu TRUOC '_MTH', khong thi 'HO_TEN_CHA_MTH' bi cat sai
        // thanh 'HO_TEN_CHA' va gan nhan cua nhom nguoi khac - du lieu cua nguoi nay hien
        // duoi ten nguoi kia. Day la rang buoc quan trong nhat cua lop nay.
        $this->assertSame('Họ tên (cha của mang thai hộ)', CtdtNhanTruong::cua('HO_TEN_CHA_MTH'));
        $this->assertSame('Số giấy tờ (cha của người đẻ)', CtdtNhanTruong::cua('SO_CCCD_CHA_NND'));
    }

    /** @test */
    public function nhan_truong_dich_ca_kieu_viet_lien_khong_gach_duoi()
    {
        // PL02 dung ca hai cach viet o cac loai khac nhau: HO_TEN (co gach duoi) va HOTEN
        // (viet lien) deu ton tai. Ho ten nguoi de la truong nguoi dung nhin dau tien tren
        // giay chung sinh, khong the de no roi ve ten the tho.
        $this->assertSame('Họ tên (người đẻ)', CtdtNhanTruong::cua('HOTEN_NND'));
        $this->assertSame('Họ tên (mang thai hộ)', CtdtNhanTruong::cua('HOTEN_MTH'));
    }

    /** @test */
    public function khong_the_nao_cua_giay_chung_sinh_va_giay_bao_tu_con_roi_ve_ten_tho()
    {
        // Test tinh chat: duyet toan bo the khai bao trong truong() cua hai loai chung tu
        // dung nhieu nhat, dam bao moi the deu co nhan tieng Viet. Lan sau BHXH them the
        // moi ma tu dien chua theo kip la bai test nay do do ngay, neu ten the tra ra
        // dung bang chinh no.
        $theoLoai = [
            'GiayChungSinh' => GiayChungSinh::truong(),
            'GiayBaoTu'     => GiayBaoTu::truong(),
        ];

        foreach ($theoLoai as $tenLoai => $truong) {
            foreach (array_keys($truong) as $the) {
                $this->assertNotSame(
                    $the,
                    CtdtNhanTruong::cua($the),
                    "The '{$the}' cua {$tenLoai} chua co nhan trong CtdtNhanTruong::TU_DIEN"
                );
            }
        }
    }
}

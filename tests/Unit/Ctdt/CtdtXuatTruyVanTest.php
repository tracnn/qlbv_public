<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use App\Exports\CtdtDanhSachExport;
use App\Exports\CtdtLoiExport;
use App\Exports\CtdtNhatKyGuiExport;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtLichSuGui;
use App\Services\Ctdt\CtdtDanhSach;

/**
 * Tang query() cua ba lop Export - tang KHONG co phep kiem nao truoc dot nay.
 *
 * CtdtXuatExcelTest chi cham map(), headings() va file_get_contents() ma nguon. Ca hai loi
 * nang nhat cua tinh nang lai nam trong query(): moc thoi gian hong lam MySQL tra ve rong,
 * va thieu khoa pha hoa lam chunk() lap/mat dong. Khong test nao chay query() nghia la ca
 * hai loi do di qua duoc toan bo bo test.
 */
class CtdtXuatTruyVanTest extends TestCase
{
    use DungBangCtdtSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
    }

    /**
     * Tao mot dong nhat ky THAT voi created_at dat tay.
     *
     * Phai update rieng created_at: Eloquent tu ghi de cot nay luc create(), va chinh moc
     * thoi gian la thu dang duoc kiem o day.
     */
    private function dongNhatKy($maHoSo, $thoiDiem)
    {
        $hoSo = CtdtHoSo::create([
            'ma_ho_so' => $maHoSo, 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01929', 'so_chung_tu' => 1, 'so_loi' => 0,
        ]);

        $dong = CtdtLichSuGui::create([
            'ho_so_id' => $hoSo->id, 'ma_ho_so' => $maHoSo, 'nguoi_gui' => 'tracnn',
            'nguon' => CtdtLichSuGui::NGUON_CONSOLE, 'ma_gd' => 'GD-' . $maHoSo,
            'ma_ket_qua' => '200', 'thanh_cong' => true, 'thong_diep' => 'OK',
        ]);

        CtdtLichSuGui::where('id', $dong->id)->update(['created_at' => $thoiDiem]);

        return $dong->id;
    }

    /** @return array danh sach ma_ho_so trong ket qua, da sap xep */
    private function maTraVe($truyVan)
    {
        $ma = $truyVan->get()->pluck('ma_ho_so')->all();
        sort($ma);

        return $ma;
    }

    /** @test */
    public function nhat_ky_nhan_khoang_ngay_dang_co_gio_ma_khong_lam_rong_ket_qua()
    {
        // Man hinh LUON gui dang 'YYYY-MM-DD HH:mm:ss' (partials.load_data_button), nen day
        // la duong di THUC TE chu khong phai truong hop bien. Noi them ' 00:00:00' vao mot
        // gia tri da co gio cho ra '2026-08-01 00:00:00 00:00:00' - MySQL doc khong ra va
        // tra ve rong, nguoi van hanh ket luan "dem qua khong gui gi" trong khi bang day du
        // lieu.
        $this->dongNhatKy('YT_DAU', '2026-08-01 00:00:00');
        $this->dongNhatKy('YT_GIUA', '2026-08-15 12:00:00');
        $this->dongNhatKy('YT_TRUOC', '2026-07-31 23:59:59');

        $xuat = new CtdtNhatKyGuiExport('2026-08-01 00:00:00', '2026-08-31 23:59:59');

        $this->assertSame(
            ['YT_DAU', 'YT_GIUA'],
            $this->maTraVe($xuat->query()),
            'Khoang ngay dang co gio phai lay dung dong trong khoang, ke ca dong dung moc dau'
        );
    }

    /** @test */
    public function nhat_ky_khong_gui_moc_thoi_gian_hong_xuong_csdl()
    {
        // Kiem thang tham so nem xuong CSDL: SQLite so sanh chuoi nen van tra ve dong, con
        // MySQL doc khong ra va tra ve RONG. Khong the trong cho vao so dong tra ve de bat
        // het truong hop - phai kiem chinh dinh dang moc.
        $xuat = new CtdtNhatKyGuiExport('2026-08-01 00:00:00', '2026-08-31 23:59:59');

        foreach ($xuat->query()->getBindings() as $moc) {
            $this->assertRegExp(
                '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
                (string) $moc,
                'Moc thoi gian gui xuong CSDL phai la datetime hop le, khong duoc noi doi hau to'
            );
        }
    }

    /** @test */
    public function nhat_ky_van_nhan_khoang_ngay_dang_chi_co_ngay()
    {
        // Goi thang URL (khong qua man hinh) van truyen dang 'YYYY-MM-DD'. Ca hai dang phai
        // chay dung - dung nhu CtdtDanhSach::mocDau/mocCuoi da giai cho man danh sach.
        $this->dongNhatKy('YT_DAU', '2026-08-01 00:00:00');
        $this->dongNhatKy('YT_CUOI', '2026-08-31 23:59:59');
        $this->dongNhatKy('YT_SAU', '2026-09-01 00:00:00');

        $xuat = new CtdtNhatKyGuiExport('2026-08-01', '2026-08-31');

        $this->assertSame(
            ['YT_CUOI', 'YT_DAU'],
            $this->maTraVe($xuat->query()),
            'Dang chi co ngay phai phu tron ngay dau den het ngay cuoi'
        );
    }

    /** @test */
    public function nhat_ky_chan_khoang_ngay_qua_rong()
    {
        // Bang nay chi tang, khong bao gio giam. Mot khoang mot nam la mot truy van gay het
        // bo nho tren may chu 128MB - phai bao ro thay vi chet giua chung.
        $this->expectException(\InvalidArgumentException::class);

        new CtdtNhatKyGuiExport('2026-01-01', '2026-12-31');
    }

    /**
     * @test
     * Sheet::fromQuery() duyet bang chunk(100), tuc LIMIT/OFFSET. Sap xep theo mot cot
     * KHONG duy nhat (created_at / imported_at trung hang loat: mot te nap ra nhieu ho so
     * cung giay) thi thu tu cac dong trung giua hai trang khong duoc bao dam - dong bi lap
     * o trang sau hoac mat han, ma tep xuat trong van binh thuong.
     */
    public function ca_ba_lop_xuat_deu_co_khoa_pha_hoa_khi_sap_xep()
    {
        $cacXuat = [
            'danh sach' => new CtdtDanhSachExport(CtdtHoSo::query()),
            'loi'       => new CtdtLoiExport(CtdtHoSo::query()),
            'nhat ky'   => new CtdtNhatKyGuiExport('2026-08-01', '2026-08-31'),
        ];

        foreach ($cacXuat as $ten => $xuat) {
            $cot = array_column($xuat->query()->getQuery()->orders, 'column');

            $this->assertContains(
                'id',
                $cot,
                'Lop xuat "' . $ten . '" phai sap xep kem cot id lam khoa pha hoa'
            );
        }
    }

    /** @test */
    public function xuat_danh_sach_va_loi_co_khoang_ngay_mac_dinh()
    {
        // Goi thang URL khong tham so - hoac ctdtRange con null - se quet toan bang
        // ctdt_ho_so. xuatNhatKy da lui 30 ngay, hai cai kia phai giong the.
        $daBu = CtdtDanhSach::khoangMacDinh(['tu_ngay' => null, 'den_ngay' => '']);

        $this->assertNotEmpty($daBu['tu_ngay'], 'Thieu tu_ngay phai duoc bu mac dinh');
        $this->assertNotEmpty($daBu['den_ngay'], 'Thieu den_ngay phai duoc bu mac dinh');

        $this->assertSame(
            30,
            \Carbon\Carbon::parse($daBu['tu_ngay'])->diffInDays(\Carbon\Carbon::parse($daBu['den_ngay'])),
            'Mac dinh phai lui dung 30 ngay, giong xuatNhatKy'
        );
    }

    /** @test */
    public function khoang_ngay_mac_dinh_khong_de_len_lua_chon_cua_nguoi_dung()
    {
        // Bu mac dinh ma de len gia tri nguoi dung chon la bo loc bi bo qua am tham - te hon
        // han loi no dang chua.
        $daBu = CtdtDanhSach::khoangMacDinh(['tu_ngay' => '2026-08-01', 'den_ngay' => '2026-08-05']);

        $this->assertSame('2026-08-01', $daBu['tu_ngay']);
        $this->assertSame('2026-08-05', $daBu['den_ngay']);
    }

    /** @test */
    public function xuat_danh_sach_giu_nguyen_bo_loc_cua_man_hinh()
    {
        // Lop xuat nhan thang truy van da loc. Neu no lo tay bo bot dieu kien thi tep xuat
        // khac han man hinh - va khong co dau hieu gi.
        CtdtHoSo::create([
            'ma_ho_so' => 'YT001', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01929', 'so_chung_tu' => 1, 'so_loi' => 0,
        ]);
        CtdtHoSo::create([
            'ma_ho_so' => 'YT002', 'dich_vu' => 'CT2026', 'loai_hs' => '39',
            'macskcb' => '01929', 'so_chung_tu' => 1, 'so_loi' => 0,
        ]);

        $xuat = new CtdtDanhSachExport(CtdtDanhSach::truyVan(['dich_vu' => 'CT2025']));

        $this->assertSame(['YT001'], $this->maTraVe($xuat->query()));
    }

    /**
     * @test
     * Anh chup bo loc phai lay LUC TAI DU LIEU, khong phai luc bam nut xuat.
     *
     * thamSoLoc() doc DOM tai thoi diem goi. DataTable goi no luc ajax.reload(); nut xuat
     * goi no luc bam. Nguoi dung doi o "Dich vu" ma CHUA bam "Tai du lieu" roi bam "Xuat
     * danh sach" se nhan tep mang bo loc MOI trong khi man hinh van la ket qua CU - khong
     * mot canh bao nao. Tro treu la loi nay sinh ra tu viec dung chung HAM getter ma khong
     * dung chung ANH CHUP.
     */
    public function ba_nut_xuat_dung_anh_chup_bo_loc_da_tai()
    {
        $nguon = file_get_contents(base_path('resources/views/bhyt/ctdt/index.blade.php'));

        $this->assertContains('ctdtLocDaTai', $nguon,
            'Phai luu anh chup bo loc cua lan tai gan nhat');

        $this->assertSame(
            1,
            substr_count($nguon, 'ctdtLocDaTai = thamSoLoc()'),
            'Anh chup phai duoc dat dung mot cho - trong ajax.data cua DataTable'
        );

        // Nut xuat KHONG duoc goi lai thamSoLoc(): moi lan goi lai la mot lan doc DOM moi,
        // tuc lai lech voi bang dang hien.
        $this->assertNotContains(
            '$.param(thamSoLoc())',
            $nguon,
            'Nut xuat phai dung anh chup ctdtLocDaTai, khong duoc doc lai DOM luc bam'
        );
    }

    /**
     * @test
     * O "Search" mac dinh cua DataTables khong di vao tep xuat.
     *
     * Khong tat searching thi hop Search van hien, va yajra ap dieu kien do len truy van
     * phia may chu: man hinh thu hep lai con tep xuat thi khong. Man nay da co o #tim rieng
     * lam dung viec do va di kem duoc vao URL xuat.
     */
    public function tat_o_search_mac_dinh_cua_datatables()
    {
        $nguon = file_get_contents(base_path('resources/views/bhyt/ctdt/index.blade.php'));

        $this->assertRegExp('/searching:\s*false/', $nguon,
            'Phai tat searching: o Search mac dinh loc man hinh nhung khong loc tep xuat');
    }

    /**
     * @test
     * FromQuery CHI giam bo nho hydrate Eloquent. PhpSpreadsheet van giu TOAN BO sheet
     * trong RAM truoc khi ghi, va ShouldAutoSize do be rong tung o cua 18 cot x N dong.
     * Tren may chu 128MB/120s, vai nghin dong la gay giua chung.
     */
    public function ba_lop_xuat_deu_noi_gioi_han_bo_nho_va_thoi_gian()
    {
        foreach (['CtdtDanhSachExport', 'CtdtLoiExport', 'CtdtNhatKyGuiExport'] as $lop) {
            $nguon = file_get_contents(base_path('app/Exports/' . $lop . '.php'));

            $this->assertContains('set_time_limit(1800)', $nguon,
                $lop . ' phai noi gioi han thoi gian nhu 15 lop Export con lai');
            $this->assertContains("ini_set('memory_limit', '4096M')", $nguon,
                $lop . ' phai noi gioi han bo nho nhu 15 lop Export con lai');
        }
    }

    /** @test */
    public function ba_sheet_deu_dat_ten_co_dau()
    {
        // 'Nhat ky gui' khong dau canh 'Hồ sơ' va 'Lỗi' co dau la mot su khong nhat quan
        // nguoi dung nhin thay ngay tren tab cua tep.
        $this->assertSame('Hồ sơ', (new CtdtDanhSachExport(CtdtHoSo::query()))->title());
        $this->assertSame('Lỗi', (new CtdtLoiExport(CtdtHoSo::query()))->title());
        $this->assertSame('Nhật ký gửi', (new CtdtNhatKyGuiExport('2026-08-01', '2026-08-31'))->title());
    }
}

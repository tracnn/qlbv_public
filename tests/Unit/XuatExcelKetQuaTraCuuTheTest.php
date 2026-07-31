<?php

namespace Tests\Unit;

use App\Exports\KetQuaTraCuuTheExport;
use App\Http\Controllers\BHYT\CheckHeinCardController;
use App\Models\CheckBHYT\check_hein_card;
use Tests\TestCase;
use Tests\Support\LocComment;

class XuatExcelKetQuaTraCuuTheTest extends TestCase
{
    use LocComment;

    const CONTROLLER = 'app/Http/Controllers/BHYT/CheckHeinCardController.php';
    const BLADE = 'resources/views/bhyt/check-hein-card/index.blade.php';

    /**
     * Diem cot loi: neu moi ben tu dung truy van thi them mot bo loc ma quen ben kia se lam
     * tep xuat khac han man hinh - khong co dau hieu gi cho toi luc ai do ngoi doi chieu.
     */
    /** @test */
    public function fetch_va_xuat_dung_chung_mot_nguon_truy_van()
    {
        $ma = $this->maKhongComment(base_path(self::CONTROLLER));

        $this->assertSame(2, substr_count($ma, '$this->locTheoYeuCau($request)'),
            'Ca fetch() lan xuatExcel() phai goi locTheoYeuCau(), khong ben nao tu dung truy van');

        // Chi duoc dung check_hein_card::query() DUNG MOT lan - trong locTheoYeuCau().
        $this->assertSame(1, substr_count($ma, 'check_hein_card::query()'),
            'Co noi tu dung truy van rieng thay vi dung locTheoYeuCau()');
    }

    /** @test */
    public function route_va_nut_xuat_ton_tai()
    {
        $this->assertNotEmpty(route('bhyt.check-hein-card.export'));

        $blade = file_get_contents(base_path(self::BLADE));

        $this->assertContains("route('bhyt.check-hein-card.export')", $blade);
        $this->assertContains('btn-xuat', $blade);
    }

    /**
     * Nut xuat va DataTables phai lay tham so tu CUNG mot ham o phia trinh duyet.
     */
    /** @test */
    public function nut_xuat_dung_chung_tham_so_voi_datatables()
    {
        $blade = file_get_contents(base_path(self::BLADE));

        $this->assertContains('function thamSoLoc()', $blade);
        $this->assertContains('$.param(thamSoLoc())', $blade);
        $this->assertContains('$.extend(d, thamSoLoc())', $blade);
    }

    /** @test */
    public function so_tieu_de_khop_so_cot_du_lieu()
    {
        $x = new KetQuaTraCuuTheExport(check_hein_card::query());
        $r = check_hein_card::first();

        if (!$r) {
            $this->markTestSkipped('Bang chua co du lieu de doi chieu so cot');
        }

        $this->assertSame(count($x->headings()), count($x->map($r)),
            'So tieu de khac so cot du lieu - tep xuat se lech cot');
    }

    /** @test */
    public function hai_cot_ma_hien_nhan_tieng_viet()
    {
        config([
            '__tech.insurance_error_code' => ['000' => 'Chinh xac'],
            '__tech.check_insurance_code' => ['00' => 'The chinh xac'],
        ]);

        $r = new check_hein_card(['ma_tracuu' => '000', 'ma_kiemtra' => '00', 'ma_ketqua' => '000']);
        $d = (new KetQuaTraCuuTheExport(check_hein_card::query()))->map($r);

        $this->assertContains('Chinh xac', $d);
        $this->assertContains('The chinh xac', $d);

        // Ma la van khong nem, hien ma tran.
        $r2 = new check_hein_card(['ma_tracuu' => '997', 'ma_kiemtra' => '88', 'ma_ketqua' => '997']);
        $this->assertContains('997', (new KetQuaTraCuuTheExport(check_hein_card::query()))->map($r2));
    }

    /**
     * Bang chung truc tiep cho "xuat dung thu dang nhin thay".
     */
    /** @test */
    public function so_dong_xuat_khop_so_dong_tren_man()
    {
        foreach ([[], ['trang_thai' => 'hop_le'], ['trang_thai' => 'loi'], ['ma_cskcb' => '01929']] as $loc) {
            $req = \Illuminate\Http\Request::create('/x', 'GET', array_merge([
                'draw' => 1, 'start' => 0, 'length' => 100,
                'columns' => [[
                    'data' => 'ma_lk', 'name' => '', 'searchable' => 'true',
                    'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false'],
                ]],
                'order' => [['column' => 0, 'dir' => 'asc']],
                'search' => ['value' => '', 'regex' => 'false'],
            ], $loc));

            app()->instance('request', $req);

            $c = new CheckHeinCardController();
            $tren = json_decode($c->fetch($req)->getContent(), true)['recordsFiltered'];

            $m = new \ReflectionMethod($c, 'locTheoYeuCau');
            $m->setAccessible(true);
            $xuat = (new KetQuaTraCuuTheExport($m->invoke($c, $req)))->query()->count();

            $this->assertSame($tren, $xuat,
                'Lech voi bo loc ' . json_encode($loc) . ': man ' . $tren . ', xuat ' . $xuat);
        }
    }

    /**
     * HeinCardErrorExport TRONG NHU mo coi nhung la mot SHEET trong hai bo xuat nhieu sheet
     * dang chay. Toi da tung ket luan nham no la ma chet - chot lai de khong ai xoa.
     */
    /** @test */
    public function hein_card_error_export_van_duoc_dung_lam_sheet()
    {
        $this->assertFileExists(base_path('app/Exports/HeinCardErrorExport.php'));

        foreach (['Qd130ErrorMultiSheetExport', 'Xml3176ErrorMultiSheetExport'] as $bo) {
            $ma = $this->maKhongComment(base_path('app/Exports/' . $bo . '.php'));

            $this->assertContains('HeinCardErrorExport', $ma,
                $bo . ' khong con dung HeinCardErrorExport - kiem lai truoc khi xoa lop do');
        }
    }
}

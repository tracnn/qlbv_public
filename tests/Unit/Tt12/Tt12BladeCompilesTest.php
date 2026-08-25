<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;

/**
 * Blade sai cu phap chi vo khi nguoi dung MO TRANG, khong phai luc build. Bien dich san
 * o day de loi lo ra trong bo test.
 */
class Tt12BladeCompilesTest extends TestCase
{
    public function cacBlade()
    {
        return array(
            array('import'),
            array('index'),
            array('detail'),
            array('tab-dong'),
            array('tab-loi'),
            array('tab-xml'),
            array('tab-lich-su'),
            array('partials/search'),
        );
    }

    /**
     * @test
     * @dataProvider cacBlade
     */
    public function blade_bien_dich_duoc($ten)
    {
        $duongDan = resource_path('views/bhyt/tt12/' . $ten . '.blade.php');

        $this->assertFileExists($duongDan);

        $php = app('blade.compiler')->compileString(file_get_contents($duongDan));

        $tam = tempnam(sys_get_temp_dir(), 'blade') . '.php';
        file_put_contents($tam, $php);

        exec('php -l ' . escapeshellarg($tam), $ra, $ma);
        unlink($tam);

        $this->assertSame(0, $ma, $ten . ': ' . implode(PHP_EOL, $ra));
    }

    /** @test */
    public function moi_route_tt12_deu_duoc_dang_ky()
    {
        $ten = array(
            'bhyt.tt12.import.index', 'bhyt.tt12.upload', 'bhyt.tt12.index',
            'bhyt.tt12.fetch-data', 'bhyt.tt12.detail', 'bhyt.tt12.detail.tab',
            'bhyt.tt12.ky-va-gui', 'bhyt.tt12.ky-va-gui-nhieu',
            'bhyt.tt12.xuat.danh-sach', 'bhyt.tt12.xuat.loi', 'bhyt.tt12.delete',
            'bhyt.tt12.dong-bo-lai', 'bhyt.tt12.kiem-lai',
            'bhyt.tt12.bieu-mau', 'bhyt.tt12.xuat.nhat-ky',
        );

        foreach ($ten as $mot) {
            $this->assertNotNull(
                app('router')->getRoutes()->getByName($mot),
                'Thieu route ' . $mot
            );
        }
    }

    /** @test */
    public function man_chi_tiet_in_ca_ba_loai_loi_ke_ca_loi_nap()
    {
        // import_error tung duoc GHI ma khong man hinh nao in ra. Ba cot loi phai duoc doi
        // xu nhu nhau, khong thi mot cai lai am tham.
        $noiDung = file_get_contents(resource_path('views/bhyt/tt12/detail.blade.php'));

        foreach (array('import_error', 'signed_error', 'submit_error') as $cot) {
            $this->assertContains('$hoSo->' . $cot, $noiDung, 'Man chi tiet khong in ' . $cot);
        }
    }

    /** @test */
    public function man_danh_sach_co_cot_loi_nap()
    {
        $noiDung = file_get_contents(resource_path('views/bhyt/tt12/index.blade.php'));

        $this->assertContains('co_loi_nap', $noiDung);
    }

    /** @test */
    public function hai_route_cuu_ho_deu_nam_trong_chot_quyen_xml_man()
    {
        // dong-bo-lai GHI DE bang danh muc - chinh bang Xml3176Xml3Checker doc de giam
        // dinh. Nut bi an tren blade KHONG phai la chot quyen: ai cung goi thang POST
        // duoc. Khoi route 'bhyt/' da boc san checkrole:xml-man o tang nhom; test nay chot
        // rang hai route moi that su nam trong khoi do, khong bi ai keo ra ngoai.
        foreach (array('bhyt.tt12.dong-bo-lai', 'bhyt.tt12.kiem-lai') as $ten) {
            $route = app('router')->getRoutes()->getByName($ten);

            $this->assertNotNull($route, 'Thieu route ' . $ten);
            $this->assertContains(
                'checkrole:xml-man',
                $route->gatherMiddleware(),
                $ten . ' phai nam trong chot quyen xml-man'
            );
        }
    }

    /** @test */
    public function man_nap_co_nut_tai_bieu_mau()
    {
        $noiDung = file_get_contents(resource_path('views/bhyt/tt12/import.blade.php'));

        $this->assertContains("route('bhyt.tt12.bieu-mau')", $noiDung,
            'Man nap phai co nut goi den route tai bieu mau');
    }

    /** @test */
    public function man_danh_sach_co_nut_xuat_nhat_ky_gui()
    {
        $noiDung = file_get_contents(resource_path('views/bhyt/tt12/index.blade.php'));

        $this->assertContains("route('bhyt.tt12.xuat.nhat-ky')", $noiDung,
            'Man danh sach phai co nut xuat nhat ky gui');
    }

    /** @test */
    public function nut_xuat_nhat_ky_dung_lai_tt12LocDaTai_khong_tu_tao_o_ngay_rieng()
    {
        // Truoc day bai nay quet CA TEP: chuoi 'tt12LocDaTai' da co san o hai nut xuat cu
        // nam NGOAI pham vi thay doi, nen du handler #btn-xuat-nhat-ky bi viet lai de tu
        // tao mot o ngay rieng - dung dieu yeu cau cam - bai van xanh. Phai trich RIENG
        // doan handler cua nut nay roi moi khang dinh tren doan do.
        $noiDung = file_get_contents(resource_path('views/bhyt/tt12/index.blade.php'));

        $khopDuoc = preg_match(
            "/\#btn-xuat-nhat-ky'\)\.on\('click', function \(\) \{(.*?)\}\);/s",
            $noiDung,
            $khop
        );

        $this->assertSame(1, $khopDuoc, 'Khong tim thay handler cua #btn-xuat-nhat-ky');
        $this->assertContains('tt12LocDaTai', $khop[1],
            'Handler nut xuat nhat ky phai dung lai tt12LocDaTai tu partials.date_range, khong tu tao o ngay rieng');
    }

    /** @test */
    public function route_bieu_mau_dat_truoc_khoi_tham_so_ma_ho_so()
    {
        // Rieng cac route GET, khong co va cham that su hien nay: khong ton tai route GET
        // dang tran 'tt12/{ma_ho_so}' - moi route GET mang tham so deu nam duoi
        // 'tt12/detail/{ma_ho_so}...', con 'tt12/{ma_ho_so}' chi khai o POST/DELETE nen
        // khac method, khong va cham voi GET 'tt12/bieu-mau'.
        //
        // Van giu chot nay lam PHONG NGUA: neu sau nay co ai them mot route GET dang
        // 'tt12/{ma_ho_so}' (giong khuon cua bhyt.ctdt.detail) ma khai TRUOC bieu-mau, thi
        // request 'tt12/bieu-mau' se bi tham so bat-tat-ca do nuot mat - dung Laravel khop
        // theo THU TU khai bao. Chot som re hon rat nhieu so voi phat hien qua mot bao loi
        // "tai bieu mau khong duoc".
        $routes = app('router')->getRoutes()->get('GET');

        $viTriBieuMau = null;
        $viTriThamSo = null;

        foreach (array_values($routes) as $i => $route) {
            if ($route->getName() === 'bhyt.tt12.bieu-mau') {
                $viTriBieuMau = $i;
            }
            if ($route->getName() === 'bhyt.tt12.detail') {
                $viTriThamSo = $i;
            }
        }

        $this->assertNotNull($viTriBieuMau, 'Thieu route bhyt.tt12.bieu-mau');
        $this->assertNotNull($viTriThamSo, 'Thieu route bhyt.tt12.detail');
        $this->assertLessThan($viTriThamSo, $viTriBieuMau,
            'Route bieu-mau phai khai TRUOC cac route mang tham so {ma_ho_so}');
    }

    /** @test */
    public function route_xoa_phai_co_chot_quyen_superadministrator()
    {
        // Xoa la thao tac khong hoan tac duoc va cham vao ho so cua NGUOI KHAC. Khuon da
        // co san ngay tren cung tep: bhyt.ctdt.delete mang dung middleware nay.
        $route = app('router')->getRoutes()->getByName('bhyt.tt12.delete');

        $this->assertNotNull($route);
        $this->assertContains(
            'checkrole:superadministrator',
            $route->gatherMiddleware(),
            'Route xoa TT12 phai co chot quyen giong bhyt.ctdt.delete'
        );
    }
}

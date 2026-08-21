<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Canh gac cho chot an toan CSDL cua bo test.
 *
 * NGAY 2026-08-21 mot test dung trait DatabaseMigrations da goi migrate:fresh thang vao
 * co so du lieu phat trien qlbv (.env tro vao do, va luc ay phpunit.xml khong ghi de
 * DB_CONNECTION). Toan bo bang bi DROP. Khong co binlog, khong co ban dump nao chua bang
 * ctdt - du lieu doi soat voi cong BHXH mat vinh vien.
 *
 * Ba lop chan duoc dung sau su co do, va tep nay canh hai lop dau:
 *   1. phpunit.xml ghi de DB_DATABASE sang schema vut di qlbv_test
 *   2. TestCase::setUp() nem neu ket noi mac dinh tro vao mot ten trong CSDL_CAM
 *   3. tep nay: chan viec dung lai trait nguy hiem, va chan viec go mat lop 1
 *
 * Khong lop nao trong ba lop nay du mot minh. Lop 1 la mot tep van ban ai cung sua duoc;
 * lop 2 chi chan luc CHAY, khong ngan ai viet them mot test dung trait do roi chay tren
 * may khac; lop 3 bat ca hai chuyen do ngay trong bo test.
 */
class ChotAnToanCsdlTest extends TestCase
{
    /**
     * Hai trait goi migrate:fresh. Tren du an nay chung tuong duong lenh xoa du lieu.
     *
     * Muon dung bang that trong test thi dung mot trait trong tests/Support/ - chung dung
     * bang tren SQLite bo nho va khong bao gio cham toi may chu that. Xem
     * tests/Support/DungBangCtdtSqlite.php.
     */
    const TRAIT_CAM = ['RefreshDatabase', 'DatabaseMigrations'];

    /** @test */
    public function bo_test_KHONG_duoc_tro_vao_csdl_phat_trien()
    {
        // Lop 2 (TestCase::setUp) da nem truoc khi toi duoc day, nen test nay xanh nghia la
        // lop do dang song. Giu lai de mot ai do doc danh sach test cung thay chot ton tai.
        $macDinh = config('database.default');
        $ten = config('database.connections.' . $macDinh . '.database');

        $this->assertNotContains($ten, TestCase::CSDL_CAM,
            'Bo test dang tro vao CSDL phat trien - xem chu thich CHOT AN TOAN trong phpunit.xml');
    }

    /** @test */
    public function phpunit_xml_van_giu_hai_dong_ghi_de()
    {
        // Lop 2 mot minh khong du: no chi chan luc chay. Neu ai go hai dong nay ra thi moi
        // test se DO hang loat voi thong bao cua lop 2 - kho chiu nhung an toan. Test nay
        // noi thang ly do, de nguoi sua khong di tim nham cho.
        $xml = file_get_contents(base_path('phpunit.xml'));

        $this->assertContains('<env name="DB_DATABASE"', $xml,
            'phpunit.xml phai ghi de DB_DATABASE - khong thi test tro thang vao qlbv theo .env');
        $this->assertNotContains('<env name="DB_DATABASE" value="qlbv"/>', $xml,
            'phpunit.xml KHONG duoc tro bo test vao CSDL phat trien');
    }

    /** @test */
    public function hai_dong_ghi_de_phai_co_force_true()
    {
        // PHPUnit 6 (Util/Configuration::handlePHPConfiguration()) chi putenv() khi
        // getenv($name) === false. May nao da dat DB_DATABASE o bien moi truong he thong thi
        // dong ghi de trong phpunit.xml AM THAM khong co tac dung - lop chan so 1 bien mat
        // ma khong ai hay. force="true" bat PHPUnit ghi de vo dieu kien.
        $xml = file_get_contents(base_path('phpunit.xml'));

        foreach (['DB_CONNECTION', 'DB_DATABASE'] as $bien) {
            $this->assertRegExp(
                '/<env\s+name="' . $bien . '"[^>]*\sforce="true"/',
                $xml,
                'Dong ghi de ' . $bien . ' trong phpunit.xml phai co force="true", khong thi'
                . ' PHPUnit 6 bo qua no tren may da co san bien moi truong do'
            );
        }
    }

    /** @test */
    public function khong_test_nao_dung_RefreshDatabase_hay_DatabaseMigrations()
    {
        // Quet CA thu muc tests/, khong chi tests/Unit: tep gay ra su co nam trong
        // tests/Unit/Ctdt/, nhung tests/Feature/ cung chay tren cung ket noi ay.
        $viPham = [];

        foreach ($this->cacTepTest() as $tep) {
            foreach ($this->traitCamTrongMa(file_get_contents($tep)) as $ten) {
                $viPham[] = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $tep)
                    . ' dung ' . $ten;
            }
        }

        $this->assertSame([], $viPham,
            'Hai trait nay goi migrate:fresh. Dung mot trait trong tests/Support/ thay the - '
            . 'xem tests/Support/DungBangCtdtSqlite.php'
        );
    }

    /**
     * Chinh canh gac phai duoc canh gac: mot regex bo sot thi tep nay van XANH trong khi
     * chot da thung.
     *
     * Cac mau duoi day deu viet trong dau nhay don, nen dong bat dau bang ' chu khong bang
     * `use` - traitCamTrongMa() khong tu bat chinh tep nay lam vi pham.
     *
     * @test
     */
    public function chinh_phep_do_bat_duoc_moi_dang_khai_bao_trait()
    {
        $phaiBat = [
            'use RefreshDatabase;',
            '    use DatabaseMigrations;',
            'use Illuminate\Foundation\Testing\RefreshDatabase;',
            // NHIEU TRAIT MOT CAU LENH - dang regex cu bo sot hoan toan.
            '    use DatabaseMigrations, WithoutMiddleware;',
            '    use WithoutMiddleware, DatabaseMigrations;',
            '    use WithFaker, RefreshDatabase, WithoutEvents;',
            // DANG ALIAS - `as` lam regex cu truot vi no doi dau ; ngay sau ten trait.
            'use Illuminate\Foundation\Testing\RefreshDatabase as Lam;',
            '    use DatabaseMigrations as Migrate;',
        ];

        foreach ($phaiBat as $mau) {
            $this->assertNotSame([], $this->traitCamTrongMa($mau),
                'Phep do phai bat duoc: ' . $mau);
        }

        $khongDuocBat = [
            // Chu thich nhac ten trait - moi trait trong tests/Support/ deu co mot doan
            // giai thich vi sao khong dung chung; chan chinh loi canh bao do thi vo ly.
            ' * VI SAO KHONG DUNG RefreshDatabase: no goi migrate:fresh.',
            '// use DatabaseMigrations; -- da bo, xem tests/Support/',
            // Ten khac co chua ten cam nhu mot phan - khong duoc bat nham.
            'use Tests\Support\KhongPhaiRefreshDatabaseGiaDinh;',
            'use Tests\Support\DungBangCtdtSqlite;',
            'use Illuminate\Foundation\Testing\WithoutMiddleware;',
        ];

        foreach ($khongDuocBat as $mau) {
            $this->assertSame([], $this->traitCamTrongMa($mau),
                'Phep do KHONG duoc bat nham: ' . $mau);
        }
    }

    /**
     * Tim cac trait cam duoc khai bao THAT trong mot doan ma PHP.
     *
     * Khong dung mot regex duy nhat cho ca ten trait: `use A, B;` va `use A as B;` deu hop
     * le, va nhoi ca hai dang vao mot mau se cho ra thu khong ai doc lai duoc. Tach lam hai
     * buoc - lay ca cau lenh `use ...;`, roi soi tung muc trong danh sach.
     *
     * @param  string $noiDung
     * @return array ten cac trait cam tim thay
     */
    private function traitCamTrongMa($noiDung)
    {
        $thay = [];

        // ^\s*use ... ; tren mot dong: bo qua chu thich (dong chu thich bat dau bang * hoac
        // //) va bo qua `use` cua closure (`function () use ($x) {` - `use` khong o dau
        // dong, va lop ky tu [^;{(]+ loai luon dau mo ngoac).
        if (!preg_match_all('/^[ \t]*use\s+([^;{(]+);/m', $noiDung, $khop)) {
            return [];
        }

        foreach ($khop[1] as $danhSach) {
            foreach (explode(',', $danhSach) as $muc) {
                // Bo phan alias: `X as Y` - cai bi cam la X, con Y la ten nguoi ta tu dat.
                $muc = trim(preg_replace('/\s+as\s+.*$/i', '', trim($muc)));

                // Chi lay doan sau dau \ cuoi cung: ten ngan cua trait.
                $tenNgan = substr($muc, strrpos('\\' . $muc, '\\'));

                if (in_array($tenNgan, self::TRAIT_CAM, true) && !in_array($tenNgan, $thay, true)) {
                    $thay[] = $tenNgan;
                }
            }
        }

        return $thay;
    }

    /** @return array duong dan tuyet doi moi tep .php trong tests/ */
    private function cacTepTest()
    {
        $tep = [];

        $duyet = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(base_path('tests'), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($duyet as $muc) {
            if ($muc->isFile() && strtolower($muc->getExtension()) === 'php') {
                $tep[] = $muc->getPathname();
            }
        }

        return $tep;
    }
}

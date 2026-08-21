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
    public function khong_test_nao_dung_RefreshDatabase_hay_DatabaseMigrations()
    {
        // Quet CA thu muc tests/, khong chi tests/Unit: tep gay ra su co nam trong
        // tests/Unit/Ctdt/, nhung tests/Feature/ cung chay tren cung ket noi ay.
        $viPham = [];

        foreach ($this->cacTepTest() as $tep) {
            $noiDung = file_get_contents($tep);

            foreach (self::TRAIT_CAM as $ten) {
                // Chi bat cau lenh `use X;` THAT, khong bat chu thich nhac ten trait - cac
                // trait trong tests/Support/ deu co chu thich giai thich vi sao khong dung
                // chung, va chan chinh loi canh bao do thi vo ly.
                if (preg_match('/^\s*use\s+[\\\\A-Za-z]*' . $ten . '\s*;/m', $noiDung)) {
                    $viPham[] = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $tep)
                        . ' dung ' . $ten;
                }
            }
        }

        $this->assertSame([], $viPham,
            'Hai trait nay goi migrate:fresh. Dung mot trait trong tests/Support/ thay the - '
            . 'xem tests/Support/DungBangCtdtSqlite.php'
        );
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

<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Ten co so du lieu CAM tuyet doi. Day la CSDL phat trien that cua du an.
     *
     * Chan theo TEN chu khong theo driver: mot bo test tro vao sqlite thi an toan nhung lam
     * tat tieng khoang 55 test doc/ghi bang that (medicine_catalogs). Cai phai chan la
     * chinh cai ten nay, khong phai ca MySQL.
     */
    const CSDL_CAM = ['qlbv'];

    /**
     * Chan ngay neu bo test dang tro vao mot co so du lieu that.
     *
     * VI SAO CAN LOP NAY du phpunit.xml da ghi de DB_CONNECTION: phpunit.xml la mot tep van
     * ban ai cung sua duoc, va mot lan sua nham la mat sach du lieu. Ngay 2026-08-21 mot test
     * dung DatabaseMigrations da goi migrate:fresh thang vao co so du lieu phat trien qlbv:
     * DROP toan bo bang, khong co binlog, khong co ban dump nao chua bang ctdt de lui ve.
     *
     * Chan o setUp() nghia la moi test - ke ca test khong dung toi CSDL - deu di qua cho nay.
     * Mot phep so sanh chuoi doi lay viec khong bao gio lap lai chuyen do la re.
     *
     * KHONG dung markTestSkipped: bo qua im lang la cach de ca bo test xanh trong khi khong
     * kiem gi ca. Nem de nguoi chay biet ngay.
     */
    protected function setUp()
    {
        parent::setUp();

        $macDinh = config('database.default');
        $ten = config('database.connections.' . $macDinh . '.database');

        if (in_array($ten, self::CSDL_CAM, true)) {
            $this->fail(
                'CHAN AN TOAN: bo test dang tro vao co so du lieu "' . $ten . '" - day la '
                . 'CSDL PHAT TRIEN THAT.'
                . PHP_EOL . PHP_EOL
                . 'Nhieu test trong bo nay GHI thang vao bang that (vd. tests/Unit/Import xoa '
                . 'medicine_catalogs), va mot trait nhu RefreshDatabase se goi migrate:fresh '
                . 'tuc DROP TOAN BO BANG. Chuyen do da xay ra that ngay 2026-08-21 va lam mat '
                . 'sach CSDL phat trien.'
                . PHP_EOL . PHP_EOL
                . 'Kiem lai hai dong nay trong phpunit.xml:' . PHP_EOL
                . '  <env name="DB_CONNECTION" value="mysql"/>' . PHP_EOL
                . '  <env name="DB_DATABASE" value="qlbv_test"/>'
            );
        }
    }
}

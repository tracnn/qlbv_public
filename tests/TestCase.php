<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Ten cac driver duoc phep chay test. Chi driver nao KHONG the cham toi mot may chu
     * that moi co mat o day.
     */
    const DRIVER_AN_TOAN = ['sqlite'];

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
        $driver = config('database.connections.' . $macDinh . '.driver');

        if (!in_array($driver, self::DRIVER_AN_TOAN, true)) {
            $ten = config('database.connections.' . $macDinh . '.database');

            $this->fail(
                'CHAN AN TOAN: bo test dang tro vao ket noi "' . $macDinh . '" (driver '
                . $driver . ', database "' . $ten . '"). Test CHI duoc chay tren sqlite.'
                . PHP_EOL . PHP_EOL
                . 'Mot trait nhu RefreshDatabase hay DatabaseMigrations se goi migrate:fresh '
                . 'tren ket noi nay va DROP TOAN BO BANG. Chuyen do da xay ra that ngay '
                . '2026-08-21 va lam mat sach co so du lieu phat trien.'
                . PHP_EOL . PHP_EOL
                . 'Kiem lai hai dong nay trong phpunit.xml:' . PHP_EOL
                . '  <env name="DB_CONNECTION" value="sqlite"/>' . PHP_EOL
                . '  <env name="DB_DATABASE" value=":memory:"/>'
            );
        }
    }

    /**
     * Dong moi ket noi sau MOI test.
     *
     * SQLite trong bo nho song theo ket noi: khong dong thi bang cua test truoc con nguyen
     * o test sau, va mot test co the xanh nho du lieu nguoi khac de lai - kieu phu thuoc
     * chi lo ra khi ai do chay rieng mot tep.
     */
    protected function tearDown()
    {
        DB::disconnect();

        parent::tearDown();
    }
}

<?php

namespace Tests\Support;

use App\CustomUser;
use Illuminate\Support\Facades\DB;

/**
 * Dung bang phan quyen (roles, role_user, permissions, ...) trong SQLite bo nho.
 *
 * VI SAO KHONG DUNG RefreshDatabase: .env cua du an tro DB_DATABASE=qlbv - co so du
 * lieu phat trien that. RefreshDatabase se xoa sach no. Trait nay khong dung toi co
 * so du lieu that.
 *
 * VI SAO PHAI GHI DE KET NOI TEN 'mysql' chu khong doi database.default: App\Role va
 * App\RoleUser ghim cung protected $connection = 'mysql', nen doi mac dinh khong co
 * tac dung gi voi chung.
 */
trait DungBangPhanQuyenSqlite
{
    protected function chuanBiBangPhanQuyen()
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => [
                'driver'   => 'sqlite',
                'database' => ':memory:',
                'prefix'   => '',
            ],
        ]);

        DB::purge('mysql');

        // GHI CHU (khac voi dac ta goc): dac ta ban dau dung
        // $this->artisan('migrate', ['--path' => '<duong dan toi 1 tep migration>']).
        // Tren Laravel 5.5, Migrator::getMigrationFiles() luon glob($path.'/*_*.php'),
        // tuc --path bat buoc la MOT THU MUC chu khong phai duong dan toi mot tep. Truyen
        // thang duong dan tep khien glob() khong khop gi ca: lenh migrate bao "thanh cong"
        // (exit 0) nhung khong tao bang nao, va moi test deu do vi "no such table: roles".
        // Vi thu muc that (database/migrations) chua nhieu migration phu thuoc cu phap
        // MySQL va cac ket noi Oracle khac (dung nhu dac ta da canh bao, khong the tro
        // --path vao do), o day nap thang tep migration roi goi up() thu cong. Van giu
        // dung tinh than "chay dung mot migration duy nhat", chi khong di qua lenh
        // artisan migrate.
        require_once base_path('database/migrations/2017_10_23_052501_laratrust_setup_tables.php');

        (new \LaratrustSetupTables())->up();
    }

    /**
     * CustomUser khong luu vao Oracle. Chi can khoa chinh de attachRole() ghi duoc
     * ban ghi pivot; quan he morphToMany dung ket noi cua App\Role (mysql), khong
     * cham toi ACS_RS.
     */
    protected function nguoiDungGia($id = 1001)
    {
        $nguoiDung = new CustomUser();
        $nguoiDung->id = $id;
        $nguoiDung->loginname = 'nguoicai' . $id;
        $nguoiDung->exists = true;

        return $nguoiDung;
    }
}

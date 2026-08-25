<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;

/**
 * Dung nam bang cua module TT12 trong SQLite bo nho.
 *
 * VI SAO KHONG DUNG RefreshDatabase: .env cua du an tro DB_DATABASE=qlbv - co so du lieu
 * phat trien that. RefreshDatabase se xoa sach no.
 *
 * VI SAO KHONG DUNG artisan migrate --path: tren Laravel 5.5, Migrator::getMigrationFiles()
 * luon glob($path.'/*_*.php'), tuc --path bat buoc la MOT THU MUC. Truyen duong dan tep
 * khien lenh bao "thanh cong" (exit 0) nhung khong tao bang nao. Thu muc that
 * database/migrations lai chua nhieu migration phu thuoc cu phap MySQL va ket noi Oracle,
 * khong tro --path vao do duoc. Nen nap thang tung tep roi goi up().
 *
 * VI SAO GHI DE KET NOI TEN 'mysql' chu khong doi database.default: giu dung khuon cua
 * DungBangCtdtSqlite, va phong khi model ve sau ghim cung $connection.
 */
trait DungBangTt12Sqlite
{
    /**
     * Thu tu QUAN TRONG: bang khung truoc, bang chi tiet sau.
     *
     * @return array [ten tep migration => ten lop]
     */
    protected function cacMigrationTt12()
    {
        return [
            '2026_08_25_100001_create_tt12_ho_so_table'         => 'CreateTt12HoSoTable',
            '2026_08_25_100002_create_tt12_dong_table'          => 'CreateTt12DongTable',
            '2026_08_25_100003_create_tt12_dong_thuoc_px_table' => 'CreateTt12DongThuocPxTable',
            '2026_08_25_100004_create_tt12_loi_table'           => 'CreateTt12LoiTable',
            '2026_08_25_100005_create_tt12_lich_su_gui_table'   => 'CreateTt12LichSuGuiTable',
        ];
    }

    /**
     * Nem neu thieu tep migration, KHONG bo qua im lang: doi ten mot tep migration ma
     * quen sua danh sach o day se lam trait bo qua thay vi bao loi ngay, va sai lech
     * se lo ra o mot Schema::hasTable() that bai cach do rat xa.
     */
    protected function dungBangTt12()
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

        foreach ($this->cacMigrationTt12() as $tep => $lop) {
            $duongDan = base_path('database/migrations/' . $tep . '.php');

            if (!file_exists($duongDan)) {
                throw new \RuntimeException('Thieu tep migration: ' . $duongDan);
            }

            require_once $duongDan;

            (new $lop())->up();
        }
    }
}

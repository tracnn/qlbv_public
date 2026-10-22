<?php

namespace Tests\Support;

/**
 * Dung bang theo doi cua module sinh XML3176 trong SQLite bo nho (ghi de ket noi 'mysql').
 * Nap migration that; ke ca thieu tep thi nem, khong bo qua im lang.
 * Khong dung RefreshDatabase vi .env co the tro CSDL that.
 */
trait DungBangHisSinhXml3176Sqlite
{
    protected function cacMigrationSinhXml3176()
    {
        return [
            '2026_10_22_100001_create_sinh_xml3176_lan_sinh_table' => 'CreateSinhXml3176LanSinhTable',
            '2026_10_22_100002_create_sinh_xml3176_moc_table'      => 'CreateSinhXml3176MocTable',
        ];
    }

    protected function dungBangTheoDoi()
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => [
                'driver'   => 'sqlite',
                'database' => ':memory:',
                'prefix'   => '',
            ],
        ]);

        \Illuminate\Support\Facades\DB::purge('mysql');

        foreach ($this->cacMigrationSinhXml3176() as $tep => $lop) {
            $duongDan = base_path('database/migrations/' . $tep . '.php');

            if (!file_exists($duongDan)) {
                throw new \RuntimeException('Thieu tep migration: ' . $duongDan);
            }

            require_once $duongDan;

            (new $lop())->up();
        }
    }
}

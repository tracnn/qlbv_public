<?php

namespace Tests\Support;

use Illuminate\Support\Facades\Schema;

/**
 * Dung cac bang cua ho so tong hop 01/BH trong SQLite bo nho.
 *
 * VI SAO MIRROR DungBangTt12Sqlite: cach noi SQLite (ghi de ket noi 'mysql', khong dung
 * RefreshDatabase vi .env tro CSDL that) lay y nguyen tu do; khong nap trait do vi no dung them bang tt12 khong can.
 *
 * VI SAO KHONG NAP MIGRATION THAT CUA xml3176_xml1s: no dung cu phap MySQL. Chi dung bang
 * toi thieu voi dung cac cot nguon ma 01/BH doc (spec muc 2.2). Migration chi muc 100004
 * cung khong nap: no chi chay tren MySQL.
 */
trait DungBangBh01Sqlite
{
    protected function cacMigrationBh01()
    {
        return [
            '2026_10_19_100001_create_bh01_ban_table'         => 'CreateBh01BanTable',
            '2026_10_19_100002_create_bh01_dong_table'        => 'CreateBh01DongTable',
            '2026_10_19_100003_create_bh01_lich_su_gui_table' => 'CreateBh01LichSuGuiTable',
            '2026_10_19_100005_them_nguon_vao_bh01_ban'       => 'ThemNguonVaoBh01Ban',
        ];
    }

    /**
     * Nem neu thieu tep migration, khong bo qua im lang (cung ly do voi dungBangTt12).
     */
    protected function dungBangBh01()
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

        foreach ($this->cacMigrationBh01() as $tep => $lop) {
            $duongDan = base_path('database/migrations/' . $tep . '.php');

            if (!file_exists($duongDan)) {
                throw new \RuntimeException('Thieu tep migration: ' . $duongDan);
            }

            require_once $duongDan;

            (new $lop())->up();
        }

        $this->dungBangNguonXml1();
    }

    protected function dungBangNguonXml1()
    {
        Schema::connection('mysql')->create('xml3176_xml1s', function ($t) {
            $t->increments('id');
            $t->string('ma_lk')->nullable();
            $t->integer('stt')->nullable();
            foreach (['ho_ten', 'ngay_sinh', 'gioi_tinh', 'ma_the_bhyt', 'ma_benh_chinh',
                'ngay_vao', 'ngay_vao_noi_tru', 'ngay_ra', 'ma_loai_kcb', 'ma_cskcb',
                'nam_qt', 'thang_qt'] as $cot) {
                $t->string($cot)->nullable();
            }
            $t->integer('so_ngay_dtri')->nullable();
            foreach (['t_tongchi_bv', 't_tongchi_bh', 't_bhtt', 't_bncct', 't_bntt', 't_nguonkhac'] as $cot) {
                $t->double($cot)->nullable();
            }
        });
    }
}

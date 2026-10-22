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

    /** Dung bang HIS (ket noi HISPro, SQLite bo nho). Khong doi database.default. */
    protected function dungBangHis()
    {
        config([
            'database.connections.HISPro' => [
                'driver'   => 'sqlite',
                'database' => ':memory:',
                'prefix'   => '',
            ],
        ]);

        \Illuminate\Support\Facades\DB::purge('HISPro');

        $s = \Illuminate\Support\Facades\Schema::connection('HISPro');

        $s->create('his_treatment', function ($t) {
            $t->increments('id');
            $t->string('treatment_code', 50);
            $t->unsignedInteger('branch_id')->nullable();
            $t->unsignedInteger('tdl_patient_type_id')->nullable();
            $t->unsignedInteger('tdl_treatment_type_id')->nullable();
            $t->unsignedBigInteger('fee_lock_time')->nullable();
            $t->unsignedBigInteger('out_time')->nullable();
            $t->unsignedTinyInteger('is_delete')->default(0);
        });

        $s->create('his_branch', function ($t) {
            $t->increments('id');
            $t->string('hein_medi_org_code', 20)->nullable();
        });

        $s->create('his_patient_type', function ($t) {
            $t->increments('id');
            $t->string('patient_type_code', 20);
        });

        $s->create('his_treatment_type', function ($t) {
            $t->increments('id');
            $t->string('treatment_type_code', 20);
        });
    }
}

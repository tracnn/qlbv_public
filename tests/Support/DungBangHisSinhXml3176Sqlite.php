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
            '2026_10_23_100003_add_modify_time_hash_to_sinh_xml3176_lan_sinh' => 'AddModifyTimeHashToSinhXml3176LanSinh',
            '2026_10_23_100004_add_luu_tru_to_sinh_xml3176_lan_sinh' => 'AddLuuTruToSinhXml3176LanSinh',
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
            $t->unsignedBigInteger('modify_time')->nullable();
            $t->unsignedTinyInteger('is_delete')->default(0);
        });

        $s->create('his_branch', function ($t) {
            $t->increments('id');
            $t->string('hein_medi_org_code', 20)->nullable();
        });

        $s->create('his_patient_type', function ($t) {
            $t->increments('id');
            $t->string('patient_type_code', 20);
            $t->string('patient_type_name', 200)->nullable();
        });

        $s->create('his_treatment_type', function ($t) {
            $t->increments('id');
            $t->string('treatment_type_code', 20);
        });

        $this->dungBangNapHoSo($s);
    }

    /**
     * Bang/view HIS ma HisNapHoSo va HisDanhMuc doc: tao tu chinh hang so cot cua hai lop
     * (chi cac cot duoc select + cot loc), ten viet thuong. Bang da co thi bo qua.
     */
    protected function dungBangNapHoSo($s)
    {
        $bang = [];
        foreach (\App\Services\SinhXml3176\Nguon\HisNapHoSo::cacNguon() as $n) {
            $bang[$n['bang']] = array_merge($n['cot'], $n['cot_loc']);
        }
        foreach (\App\Services\SinhXml3176\Nguon\HisDanhMuc::cacBang() as $ten => $n) {
            $bang[$ten] = array_merge($n['cot'], $n['cot_loc']);
        }

        foreach ($bang as $ten => $cot) {
            if ($s->hasTable($ten)) {
                continue;
            }
            $cot = array_values(array_unique($cot));
            $s->create($ten, function ($t) use ($cot) {
                foreach ($cot as $c) {
                    if ($c === 'id' || preg_match('/(_id|_time|_date)$|^is_/', $c)) {
                        $t->bigInteger($c)->nullable();
                    } else {
                        $t->string($c, 500)->nullable();
                    }
                }
            });
        }
    }
}

<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dung 5 bang cua ket noi HISPro trong SQLite bo nho.
 *
 * VI SAO GHI DE CHINH KET NOI TEN 'HISPro': service goi
 * DB::connection('HISPro'), nen ghi de cau hinh cua dung ten do la cach duy nhat chan
 * duong ra Oracle that. Khong doi database.default o day - ket noi mac dinh 'mysql'
 * danh cho ba bang loi, hai trait co the dung chung trong mot test.
 */
trait DungBangHoSoHisSqlite
{
    protected function chuanBiBangHoSo()
    {
        config([
            'database.connections.HISPro' => [
                'driver'   => 'sqlite',
                'database' => ':memory:',
                'prefix'   => '',
            ],
        ]);

        DB::purge('HISPro');

        $s = Schema::connection('HISPro');

        $s->create('his_treatment', function ($t) {
            $t->increments('id');
            $t->string('treatment_code', 50);
            $t->string('tdl_patient_name', 200)->nullable();
            $t->string('tdl_patient_dob', 20)->nullable();
            $t->unsignedInteger('tdl_patient_gender_id')->nullable();
            $t->string('tdl_hein_card_number', 50)->nullable();
            $t->string('tdl_hein_medi_org_code', 20)->nullable();
            $t->string('tdl_hein_card_from_time', 20)->nullable();
            $t->string('tdl_hein_card_to_time', 20)->nullable();
            $t->unsignedInteger('branch_id')->nullable();
            $t->unsignedInteger('last_department_id')->nullable();
            $t->unsignedInteger('tdl_treatment_type_id')->nullable();
            $t->string('in_time', 20)->nullable();
            $t->string('out_time', 20)->nullable();
        });

        $s->create('his_gender', function ($t) {
            $t->increments('id');
            $t->string('gender_code', 10);
            $t->string('gender_name', 50);
        });

        $s->create('his_branch', function ($t) {
            $t->increments('id');
            $t->string('hein_medi_org_code', 20)->nullable();
        });

        $s->create('his_department', function ($t) {
            $t->increments('id');
            $t->string('department_name', 200);
        });

        $s->create('his_treatment_type', function ($t) {
            $t->increments('id');
            $t->string('treatment_type_name', 100);
        });

        DB::connection('HISPro')->table('his_gender')->insert([
            ['id' => 1, 'gender_code' => '1', 'gender_name' => 'Nam'],
            ['id' => 2, 'gender_code' => '2', 'gender_name' => 'Nữ'],
        ]);
        DB::connection('HISPro')->table('his_branch')->insert([
            ['id' => 10, 'hein_medi_org_code' => '01001'],
        ]);
        DB::connection('HISPro')->table('his_department')->insert([
            ['id' => 20, 'department_name' => 'Khoa Nội'],
        ]);
        DB::connection('HISPro')->table('his_treatment_type')->insert([
            ['id' => 30, 'treatment_type_name' => 'Nội trú'],
        ]);
    }

    /** Them mot ho so. $ghiDe ghi de bat ky cot nao. */
    protected function themHoSo(array $ghiDe = [])
    {
        DB::connection('HISPro')->table('his_treatment')->insert(array_merge([
            'treatment_code'          => '01013250800123',
            'tdl_patient_name'        => 'Nguyễn Văn A',
            'tdl_patient_dob'         => '19790220',
            'tdl_patient_gender_id'   => 1,
            'tdl_hein_card_number'    => 'DN4010112345678',
            'tdl_hein_medi_org_code'  => '01005',
            'tdl_hein_card_from_time' => '20260101',
            'tdl_hein_card_to_time'   => '20261231',
            'branch_id'               => 10,
            'last_department_id'      => 20,
            'tdl_treatment_type_id'   => 30,
            'in_time'                 => '202608050830',
            'out_time'                => null,
        ], $ghiDe));
    }
}

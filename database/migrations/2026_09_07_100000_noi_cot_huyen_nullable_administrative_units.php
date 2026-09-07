<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bo cap huyen: danh muc 2 cap khong con ma/ten huyen, nhung KHONG xoa cot -
 * chung con giu du lieu cua cac dong da nghi huu (is_active = 0).
 *
 * Dung DB::statement chu khong dung ->change(): du an KHONG cai doctrine/dbal.
 */
class NoiCotHuyenNullableAdministrativeUnits extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE administrative_units MODIFY district_code VARCHAR(10) NULL');
        DB::statement('ALTER TABLE administrative_units MODIFY district_name VARCHAR(255) NULL');
    }

    public function down()
    {
        DB::statement('ALTER TABLE administrative_units MODIFY district_code VARCHAR(10) NOT NULL');
        DB::statement('ALTER TABLE administrative_units MODIFY district_name VARCHAR(255) NOT NULL');
    }
}

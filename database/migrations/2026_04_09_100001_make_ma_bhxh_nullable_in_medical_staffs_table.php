<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MakeMaBhxhNullableInMedicalStaffsTable extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE medical_staffs MODIFY ma_bhxh VARCHAR(255) NULL');
    }

    public function down()
    {
        DB::statement('ALTER TABLE medical_staffs MODIFY ma_bhxh VARCHAR(255) NOT NULL');
    }
}

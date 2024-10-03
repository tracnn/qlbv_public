<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateNhomVatTuColumnInMedicalSupplyCatalogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE medical_supply_catalogs MODIFY nhom_vat_tu VARCHAR(1024)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE medical_supply_catalogs MODIFY nhom_vat_tu VARCHAR(255)');
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdministrativeUnitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('administrative_units', function (Blueprint $table) {
            $table->increments('id');
            $table->string('province_code', 10);
            $table->string('province_name');
            $table->string('district_code', 10);
            $table->string('district_name');
            $table->string('commune_code', 10);            
            $table->string('commune_name');
            $table->timestamps();

            $table->index('province_code');
            $table->index('district_code');
            $table->index('commune_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('administrative_units');
    }
}

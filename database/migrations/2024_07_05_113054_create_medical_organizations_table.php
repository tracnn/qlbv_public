<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMedicalOrganizationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('medical_organizations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_cskcb', 5)->unique();
            $table->string('ten_cskcb');
            $table->string('dia_chi_cskcb');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('medical_organizations');
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIcdyhctCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('icd_yhct_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('icd_code', 10)->unique();
            $table->string('icd_name');
            $table->string('icd_yhct_name');
            $table->string('icd10_code', 10)->nullable();
            $table->string('icd10_name')->nullable();
            $table->boolean('is_active')->default(true);
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
        Schema::dropIfExists('icdyhct_categories');
    }
}

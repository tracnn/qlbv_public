<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateXml3176ErrorCatalogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xml3176_error_catalogs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('xml');
            $table->string('error_code');
            $table->string('error_name')->nullable();
            $table->text('description')->nullable();
            $table->boolean('critical_error')->default(false);
            $table->boolean('is_check')->default(true);
            $table->timestamps();

            $table->unique(['xml', 'error_code']);
            $table->index('error_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('xml3176_error_catalogs');
    }
}

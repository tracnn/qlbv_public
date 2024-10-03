<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsCheckToQd130XmlErrorCatalogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qd130_xml_error_catalogs', function (Blueprint $table) {
            $table->boolean('is_check')->default(true)->after('critical_error');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('qd130_xml_error_catalogs', function (Blueprint $table) {
            $table->dropColumn('is_check');
        });
    }
}

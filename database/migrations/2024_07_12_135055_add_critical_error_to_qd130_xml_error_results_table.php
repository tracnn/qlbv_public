<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCriticalErrorToQd130XmlErrorResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qd130_xml_error_results', function (Blueprint $table) {
            $table->boolean('critical_error')->default(false)->after('description');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('qd130_xml_error_results', function (Blueprint $table) {
            $table->dropColumn('critical_error');
        });
    }
}

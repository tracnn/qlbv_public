<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColunmsToQd130XmlInformationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qd130_xml_informations', function (Blueprint $table) {
            $table->string('imported_by')->nullable();
            $table->string('exported_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('qd130_xml_informations', function (Blueprint $table) {
            $table->dropColumn('exported_by');
            $table->dropColumn('imported_by');
        });
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnToQd130XmlInformation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qd130_xml_informations', function (Blueprint $table) {
            $table->text('signed_error')->nullable()->after('is_signed');
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
            $table->dropColumn('signed_error');
        });
    }
}

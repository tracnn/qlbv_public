<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSubmittedMessageToQd130XmlInformation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qd130_xml_informations', function (Blueprint $table) {
            $table->text('submitted_message')->nullable()->after('submitted_at');
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
            $table->dropColumn('submitted_message');
        });
    }
}

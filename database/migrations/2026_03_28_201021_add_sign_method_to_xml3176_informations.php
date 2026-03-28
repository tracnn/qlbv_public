<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSignMethodToXml3176Informations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('xml3176_informations', function (Blueprint $table) {
            $table->string('sign_method')->nullable()->after('is_signed');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('xml3176_informations', function (Blueprint $table) {
            $table->dropColumn('sign_method');
        });
    }
}

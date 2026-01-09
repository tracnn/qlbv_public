<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnToXml3176XmlInformation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('xml3176_informations', function (Blueprint $table) {
            if (!Schema::hasColumn('xml3176_informations', 'signed_error')) {
                $table->text('signed_error')->nullable()->after('is_signed');
            }
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
            $table->dropColumn('signed_error');
        });
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSubmittedMessageToXml3176XmlInformation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('xml3176_informations', function (Blueprint $table) {
            if (!Schema::hasColumn('xml3176_informations', 'submitted_message')) {
                $table->text('submitted_message')->nullable()->after('submitted_at');
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
            $table->dropColumn('submitted_message');
        });
    }
}

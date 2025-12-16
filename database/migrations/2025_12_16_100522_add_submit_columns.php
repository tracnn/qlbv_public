<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSubmitColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qd130_xml_informations', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable()->after('exported_at')->index();
            $table->string('submitted_by')->nullable()->after('submitted_at');
            $table->string('submit_error')->nullable()->after('submitted_by')->index();
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
            $table->dropColumn('submitted_at');
            $table->dropColumn('submitted_by');
            $table->dropColumn('submit_error');
        });
    }
}

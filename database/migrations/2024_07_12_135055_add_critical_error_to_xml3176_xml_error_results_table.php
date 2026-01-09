<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCriticalErrorToXml3176XmlErrorResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('xml3176_error_results', function (Blueprint $table) {
            if (!Schema::hasColumn('xml3176_error_results', 'critical_error')) {
                $table->boolean('critical_error')->default(false)->after('description');
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
        Schema::table('xml3176_error_results', function (Blueprint $table) {
            $table->dropColumn('critical_error');
        });
    }
}

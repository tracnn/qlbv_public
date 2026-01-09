<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCriticalErrorToXml3176XmlErrorCatalogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('xml3176_error_catalogs', function (Blueprint $table) {
            if (!Schema::hasColumn('xml3176_error_catalogs', 'critical_error')) {
                $table->boolean('critical_error')->after('description')->default(false);
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
        Schema::table('xml3176_error_catalogs', function (Blueprint $table) {
            $table->dropColumn('critical_error');
        });
    }
}

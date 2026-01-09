<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsCheckToXml3176XmlErrorCatalogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('xml3176_error_catalogs', function (Blueprint $table) {
            if (!Schema::hasColumn('xml3176_error_catalogs', 'is_check')) {
                $table->boolean('is_check')->default(true)->after('critical_error');
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
            $table->dropColumn('is_check');
        });
    }
}

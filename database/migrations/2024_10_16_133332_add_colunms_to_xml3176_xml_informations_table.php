<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColunmsToXml3176XmlInformationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('xml3176_informations', function (Blueprint $table) {
            if (!Schema::hasColumn('xml3176_informations', 'imported_by')) {
                $table->string('imported_by')->nullable();
            }
            if (!Schema::hasColumn('xml3176_informations', 'exported_by')) {
                $table->string('exported_by')->nullable();
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
            $table->dropColumn('exported_by');
            $table->dropColumn('imported_by');
        });
    }
}

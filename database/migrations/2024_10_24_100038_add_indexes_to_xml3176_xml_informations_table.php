<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexesToXml3176XmlInformationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('xml3176_informations', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('xml3176_informations');
            
            if (!isset($indexesFound['xml3176_informations_imported_by_index'])) {
                $table->index('imported_by');
            }
            if (!isset($indexesFound['xml3176_informations_exported_by_index'])) {
                $table->index('exported_by');
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
            $table->dropIndex(['imported_by']);
            $table->dropIndex(['exported_by']);
        });
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexToXml3176Xml3sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('xml3176_xml3s', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('xml3176_xml3s');
            
            if (!isset($indexesFound['xml3176_xml3s_ma_khoa_index'])) {
                $table->index('ma_khoa');
            }
            if (!isset($indexesFound['xml3176_xml3s_ma_giuong_index'])) {
                $table->index('ma_giuong');
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
        Schema::table('xml3176_xml3s', function (Blueprint $table) {
            $table->dropIndex(['ma_khoa']);
            $table->dropIndex(['ma_giuong']);
        });
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsToQd130XmlInformationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qd130_xml_informations', function (Blueprint $table) {
            $table->integer('soluonghoso')->nullable()->after('macskcb');
            $table->text('import_error')->nullable()->after('exported_at');
            $table->text('export_error')->nullable()->after('import_error');
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
            $table->dropColumn(['soluonghoso', 'import_error', 'export_error']);
        });
    }
}

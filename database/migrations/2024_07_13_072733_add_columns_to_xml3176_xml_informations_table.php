<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsToXml3176XmlInformationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('xml3176_informations', function (Blueprint $table) {
            if (!Schema::hasColumn('xml3176_informations', 'soluonghoso')) {
                $table->integer('soluonghoso')->nullable()->after('macskcb');
            }
            if (!Schema::hasColumn('xml3176_informations', 'import_error')) {
                $table->text('import_error')->nullable()->after('exported_at');
            }
            if (!Schema::hasColumn('xml3176_informations', 'export_error')) {
                $table->text('export_error')->nullable()->after('import_error');
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
            $table->dropColumn(['soluonghoso', 'import_error', 'export_error']);
        });
    }
}

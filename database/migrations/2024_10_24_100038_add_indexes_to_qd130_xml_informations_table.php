<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexesToQd130XmlInformationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qd130_xml_informations', function (Blueprint $table) {
            $table->index('imported_by'); // Tạo index cho cột imported_by
            $table->index('exported_by'); // Tạo index cho cột exported_by
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
            $table->dropIndex(['imported_by']); // Xóa index của cột imported_by
            $table->dropIndex(['exported_by']); // Xóa index của cột exported_by
        });
    }
}

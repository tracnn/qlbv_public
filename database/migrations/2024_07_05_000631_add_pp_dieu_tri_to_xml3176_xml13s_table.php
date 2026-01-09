<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPpDieuTriToXml3176Xml13sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('xml3176_xml13s', function (Blueprint $table) {
            $table->text('pp_dieu_tri')->nullable()->after('ten_thuoc');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('xml3176_xml13s', function (Blueprint $table) {
            $table->dropColumn('pp_dieu_tri');
        });
    }
}

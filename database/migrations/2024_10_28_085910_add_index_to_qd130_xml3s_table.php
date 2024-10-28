<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexToQd130Xml3sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qd130_xml3s', function (Blueprint $table) {
            $table->index('ma_khoa');
            $table->index('ma_giuong');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('qd130_xml3s', function (Blueprint $table) {
            $table->dropIndex(['ma_khoa']);
            $table->dropIndex(['ma_giuong']);
        });
    }
}

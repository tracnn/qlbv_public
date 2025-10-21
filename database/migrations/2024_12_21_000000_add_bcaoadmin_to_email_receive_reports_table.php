<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBcaoadminToEmailReceiveReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('email_receive_reports', function (Blueprint $table) {
            $table->boolean('bcaoadmin')->default(false)->after('period');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('email_receive_reports', function (Blueprint $table) {
            $table->dropColumn('bcaoadmin');
        });
    }
}

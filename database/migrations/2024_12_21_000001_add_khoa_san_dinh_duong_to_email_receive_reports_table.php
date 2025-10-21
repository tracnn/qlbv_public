<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddKhoaSanDinhDuongToEmailReceiveReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('email_receive_reports', function (Blueprint $table) {
            $table->boolean('khoa_san')->default(false)->after('bcaoadmin');
            $table->boolean('dinh_duong')->default(false)->after('khoa_san');
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
            $table->dropColumn(['khoa_san', 'dinh_duong']);
        });
    }
}

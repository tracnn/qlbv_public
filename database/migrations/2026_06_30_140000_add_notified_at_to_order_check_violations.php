<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNotifiedAtToOrderCheckViolations extends Migration
{
    public function up()
    {
        Schema::table('order_check_violations', function (Blueprint $table) {
            $table->dateTime('notified_at')->nullable()->after('processed_at');
            $table->index('notified_at');
        });
    }

    public function down()
    {
        Schema::table('order_check_violations', function (Blueprint $table) {
            $table->dropIndex(['notified_at']);
            $table->dropColumn('notified_at');
        });
    }
}

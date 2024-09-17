<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Permission;

class AddEmrCheckBbhcInfo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Permission::create([
            'name' => 'emr-check-bbhc-info',
            'display_name' => 'Kiểm tra biên bản hội chẩn', // optional
            'description' => 'Kiểm tra biên bản hội chẩn', // optional
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Permission::where('name', 'emr-check-bbhc-info')->delete();
    }
}

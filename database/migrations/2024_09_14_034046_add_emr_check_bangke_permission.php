<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Permission;

class AddEmrCheckBangkePermission extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Permission::create([
            'name' => 'emr-check-bangke',
            'display_name' => 'Kiểm tra tồn tại bảng kê', // optional
            'description' => 'Kiểm tra tồn tại bảng kê', // optional
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Permission::where('name', 'emr-check-bangke')->delete();
    }
}

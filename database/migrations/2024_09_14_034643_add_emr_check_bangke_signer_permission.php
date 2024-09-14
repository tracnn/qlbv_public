<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Permission;

class AddEmrCheckBangkeSignerPermission extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Permission::create([
            'name' => 'emr-check-bangke-signer',
            'display_name' => 'Kiểm tra chữ ký bảng kê', // optional
            'description' => 'Kiểm tra chữ ký bảng kê', // optional
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Permission::where('name', 'emr-check-bangke-signer')->delete();
    }
}

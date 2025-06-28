<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Role;

class AddBhxhRoleToRoleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Role::create([
            'name' => 'bhxh',
            'display_name' => 'Bảo hiểm xã hội', // optional
            'description' => 'Bảo hiểm xã hội', // optional
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Role::where('name', 'bhxh')->delete();
    }
}

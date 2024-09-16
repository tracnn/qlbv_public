<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Permission;

class AddEmrCheckMedicineOutpatientPermission extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Permission::create([
            'name' => 'emr-check-medicine-outpatient',
            'display_name' => 'Kiểm tra đơn phòng khám', // optional
            'description' => 'Kiểm tra đơn phòng khám', // optional
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Permission::where('name', 'emr-check-medicine-outpatient')->delete();
    }
}

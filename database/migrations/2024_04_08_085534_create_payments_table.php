<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('treatment_code');
            $table->string('patient_name');
            $table->date('patient_dob');
            $table->string('patient_address');
            $table->string('patient_mobile')->nullable();
            $table->string('patient_relative_mobile')->nullable();
            $table->integer('is_payment');
            $table->decimal('amount', 18, 2);
            $table->string('login_name');
            $table->string('user_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
}

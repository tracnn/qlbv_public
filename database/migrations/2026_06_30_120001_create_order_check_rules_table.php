<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderCheckRulesTable extends Migration
{
    public function up()
    {
        Schema::create('order_check_rules', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 100)->unique();
            $table->string('family', 1)->default('A');
            $table->string('rule_type', 150);
            $table->string('name', 255);
            $table->string('severity', 20)->default('warning');
            $table->text('params')->nullable();
            $table->text('scope')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_check_rules');
    }
}

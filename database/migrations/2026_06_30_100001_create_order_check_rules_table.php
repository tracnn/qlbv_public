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
            $table->string('family', 1)->default('A');   // 'A' lam sang | 'B' cau truc/hardcode
            $table->string('rule_type', 150);            // ten class handler/scanner
            $table->string('name', 255);
            $table->string('severity', 20)->default('warning'); // info|warning|critical
            $table->text('params')->nullable();          // JSON cau hinh
            $table->text('scope')->nullable();           // JSON loc khoa/nhom DV
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_check_rules');
    }
}

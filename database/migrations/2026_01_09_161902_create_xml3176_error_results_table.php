<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateXml3176ErrorResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xml3176_error_results', function (Blueprint $table) {
            $table->increments('id');
            $table->string('xml');
            $table->string('ma_lk');
            $table->integer('stt');
            $table->string('ngay_yl')->nullable();
            $table->string('ngay_kq')->nullable();
            $table->string('error_code');
            $table->text('description')->nullable();
            $table->boolean('critical_error')->default(false);
            $table->timestamps();

            //Add index
            $table->index(['xml']);
            $table->index(['ma_lk']);
            $table->index(['error_code']);
            $table->index(['created_at']);
            $table->index(['updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('xml3176_error_results');
    }
}

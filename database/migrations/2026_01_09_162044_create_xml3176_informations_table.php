<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateXml3176InformationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xml3176_informations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_lk', 100)->unique();
            $table->string('macskcb', 5)->index();
            $table->integer('soluonghoso')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->string('imported_by')->nullable()->index();
            $table->timestamp('exported_at')->nullable();
            $table->string('exported_by')->nullable()->index();
            $table->boolean('is_signed')->default(false);
            $table->text('signed_error')->nullable();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->text('submitted_message')->nullable();
            $table->string('submitted_by')->nullable()->index();
            $table->string('submit_error')->nullable()->index();
            $table->text('import_error')->nullable();
            $table->text('export_error')->nullable();
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
        Schema::dropIfExists('xml3176_informations');
    }
}

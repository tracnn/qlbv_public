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
            $table->string('macskcb', 5);
            $table->integer('soluonghoso')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamp('exported_at')->nullable();
            $table->text('import_error')->nullable();
            $table->text('export_error')->nullable();
            $table->string('imported_by')->nullable();
            $table->string('exported_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('submitted_by')->nullable();
            $table->text('submit_error')->nullable();
            $table->boolean('is_signed')->default(false);
            $table->text('signed_error')->nullable();
            $table->text('submitted_message')->nullable();
            $table->timestamps();

            //Adding index
            $table->index('ma_lk');
            $table->index('macskcb');
            $table->index('imported_at');
            $table->index('exported_at');
            $table->index('submitted_at');
            $table->index('imported_by');
            $table->index('created_at');
            $table->index('updated_at');
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

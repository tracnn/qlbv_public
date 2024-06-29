<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDanhMucDichVuTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_catalogs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_dich_vu');
            $table->string('ten_dich_vu');
            $table->decimal('don_gia', 18, 2);
            $table->string('quy_trinh');
            $table->string('cskcb_cgkt')->nullable();
            $table->string('cskcb_cls')->nullable();
            $table->string('tu_ngay');
            $table->string('den_ngay')->nullable();
            $table->timestamps();

             // Adding unique constraint
            $table->unique(['ma_dich_vu', 'don_gia', 'quy_trinh', 'tu_ngay']);

            //Adding index
            $table->index(['ma_dich_vu', 'quy_trinh']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('service_catalogs');
    }
}

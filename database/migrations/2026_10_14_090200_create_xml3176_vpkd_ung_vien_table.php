<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ung vien cho duyet: vat pham gan du dieu kien hoc. Thay toan bo sau moi luot hoc.
 * ma_vat_pham/ten_khop dung utf8_bin nhu bang tra (so khop theo byte).
 */
class CreateXml3176VpkdUngVienTable extends Migration
{
    public function up()
    {
        Schema::create('xml3176_vpkd_ung_vien', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('lan_hoc_id')->nullable()->index();
            $table->string('loai', 10);
            $table->string('ma_vat_pham', 100)->collation('utf8_bin');
            $table->string('ten_vat_pham', 1024)->nullable();
            $table->string('ten_khop', 191)->collation('utf8_bin')->default('');
            $table->integer('so_dong');
            $table->integer('so_lien_ket');
            $table->decimal('ty_le_gan', 5, 4);
            $table->text('dvkt');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('xml3176_vpkd_ung_vien');
    }
}

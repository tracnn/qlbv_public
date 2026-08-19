<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCtdtLoiTable extends Migration
{
    /*
     * chung_tu_id can khoa ngoai cascade toi ctdt_chung_tu.id vi ly do sau:
     * khi nap lai mot ho so, importer giu lai ban ghi ctdt_ho_so (vi no mang
     * lich_su_gui, ma_gd, ma_ket_qua) nhung xoa va tao lai cac hang ctdt_chung_tu.
     * Neu khong co cascade tu chung_tu_id, cac hang ctdt_loi cu se song sot; MySQL/
     * InnoDB cap lai dung nhung AUTO_INCREMENT vua giai phong cho chung tu moi, nen
     * loi cua lan kiem TRUOC hien len tab chi tiet cua mot chung tu KHAC da hop le.
     */
    public function up()
    {
        Schema::create('ctdt_loi', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ho_so_id')->index();
            $table->unsignedInteger('chung_tu_id')->nullable()->index();

            $table->string('ma_loi', 20)->index();
            $table->string('ten_truong', 50)->nullable();
            $table->string('mo_ta', 255)->nullable();
            $table->string('muc_do', 10)->index();   // chan | canh_bao

            $table->timestamps();

            $table->foreign('ho_so_id')->references('id')->on('ctdt_ho_so')->onDelete('cascade');
            $table->foreign('chung_tu_id')->references('id')->on('ctdt_chung_tu')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ctdt_loi');
    }
}

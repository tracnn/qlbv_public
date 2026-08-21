<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Nhat ky TUNG LAN goi cong BHXH.
 *
 * VI SAO KHONG DUNG cot ctdt_ho_so.lich_su_gui san co: cot do la van ban tu do, moi lan gui
 * noi them mot dong chu. Khong loc duoc theo ngay, khong dem duoc, khong biet lan nao do
 * nguoi bam va lan nao do lenh nen. Tu Giai doan 5A lenh ctdt:import gui tu dong khong co
 * nguoi truc, nen cau hoi "dem qua gui bao nhieu, bao nhieu cai bi tu choi" tro thanh cau
 * hoi van hanh thuong xuyen.
 *
 * Cot lich_su_gui GIU NGUYEN: no con mang dau vet cua nhung lan gui truoc khi co bang nay,
 * va khong the dung lai duoc tu van ban tu do.
 *
 * Do rong cot lay theo migration 2026_08_20_100001 da noi rong ctdt_ho_so: ma_gd 100 (cong
 * tra ve 52 ky tu that), ma_ket_qua 20, thoi_gian_tiep_nhan 20.
 */
class CreateCtdtLichSuGuiTable extends Migration
{
    public function up()
    {
        Schema::create('ctdt_lich_su_gui', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ho_so_id')->index();

            // Giu ca ma_ho_so ben canh khoa ngoai: doc nhat ky ma phai join moi biet ho so
            // nao la mot buoc thua trong moi truy van van hanh.
            $table->string('ma_ho_so', 100)->index();

            $table->string('nguoi_gui')->nullable()->index();
            $table->string('nguon', 10)->index();   // man_hinh | console

            $table->string('ma_gd', 100)->nullable();
            $table->string('ma_ket_qua', 20)->nullable()->index();
            $table->string('thoi_gian_tiep_nhan', 20)->nullable();

            $table->boolean('thanh_cong')->index();
            $table->text('thong_diep')->nullable();

            $table->timestamps();

            $table->foreign('ho_so_id')->references('id')->on('ctdt_ho_so')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ctdt_lich_su_gui');
    }
}

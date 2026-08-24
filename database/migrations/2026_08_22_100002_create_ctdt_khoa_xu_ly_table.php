<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Khoa chong xu ly trung MOT ho so, thay cho Cache::add().
 *
 * VI SAO PHAI DOI: khoa cu la Cache::add() tren CACHE_DRIVER=file, va tren FileStore cua
 * Laravel 5.5 lenh do KHONG NGUYEN TU - Repository::add() rot ve
 * `if (is_null($this->get($k))) put()`, giua get() va put() co mot khe cho hai luong cung
 * thay "chua co khoa" roi cung xep hang. Hai chuoi ky-gui cho MOT ho so la HAI LAN POST
 * that len cong BHXH, va chung tu PL02 khong mang ma giao dich phia nguoi gui nen cong
 * KHONG the nhan ra ban trung.
 *
 * Cua so do truoc day hep: hai luong dua nhau chi co the la mot NGUOI bam nut hai lan trong
 * vai chuc mili giay, hoac nguoi va lenh nen cham dung mot ho so. Nut gui HANG LOAT lam no
 * rong ra bang so ho so trong mot lo.
 *
 * UNIQUE INDEX cua MySQL thi nguyen tu that: chen duoc la giu duoc khoa, trung khoa la da
 * co nguoi chay. Khong phu thuoc CACHE_DRIVER nua, nen nut don le va lenh Console cung
 * duoc va theo.
 *
 * KHONG DUNG BANG jobs LAM KHOA: mot job da chay xong nhung that bai se roi khoi bang do,
 * trong khi khoa can song het ngan sach thu lai cua ca chuoi ky-gui.
 */
class CreateCtdtKhoaXuLyTable extends Migration
{
    public function up()
    {
        Schema::create('ctdt_khoa_xu_ly', function (Blueprint $table) {
            $table->increments('id');

            // Do dai KHOP ctdt_ho_so.ma_ho_so (varchar 100). Ngan hon la khoa cua hai ho so
            // co ma dai giong nhau o 100 ky tu dau se cat thanh mot - va ho so thu hai bi
            // tu choi gui ma khong ai hieu vi sao.
            $table->string('ma_ho_so', 100)->unique();

            // Het han thay cho co che tu xoa: giu dung ngu nghia cua khoa cu (Cache::add co
            // thoi han 30 phut). Khong co cot nay thi mot tien trinh bi giet giua chuoi se
            // de lai khoa mo coi chan ho so do VINH VIEN, va trieu chung la mot ho so khong
            // bao gio gui duoc ma khong co dong log nao.
            $table->timestamp('het_han_luc')->index();

            // De doc log khi phai di go khoa mo coi: khoa nay do man hinh hay lenh nen dat.
            $table->string('nguon', 20)->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ctdt_khoa_xu_ly');
    }
}

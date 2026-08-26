<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Khoa chong xu ly trung MOT ho so danh muc TT12.
 *
 * VI SAO CAN TU LUC NAY: truoc day nut "Ky va gui" moi lan bam chi lam MOT buoc, nen muon
 * gui that phai bam hai lan dung thu tu - ban than viec do da la mot phanh. Nay mot lan bam
 * xep ca chuoi ky-gui, cua so de hai luot cua CUNG mot ho so chong len nhau rong han ra:
 * mot nguoi bam nhanh hai lan, hoac nut gui HANG LOAT lam no rong ra bang so ho so mot lo.
 *
 * Hai chuoi cho mot ho so la HAI LAN POST that len cong BHXH. Cong co nhan ra ban trung hay
 * khong thi khong ai dam chac, va mot ho so danh muc co the la hang nghin dong.
 *
 * DUNG UNIQUE INDEX chu khong Cache::add(): tren FileStore cua Laravel 5.5 lenh do KHONG
 * NGUYEN TU - Repository::add() rot ve `if (is_null($this->get($k))) put()`, giua get() va
 * put() co mot khe cho hai luong cung thay "chua co khoa" roi cung xep hang. Unique index
 * cua MySQL thi nguyen tu that. Day la bai hoc da rut tu ctdt_khoa_xu_ly.
 *
 * KHONG DUNG BANG jobs LAM KHOA: mot job da chay xong nhung that bai se roi khoi bang do,
 * trong khi khoa can song het ngan sach thu lai cua ca chuoi ky-gui.
 */
class CreateTt12KhoaXuLyTable extends Migration
{
    public function up()
    {
        Schema::create('tt12_khoa_xu_ly', function (Blueprint $table) {
            $table->increments('id');

            // Do dai KHOP tt12_ho_so.ma_ho_so. Ngan hon la khoa cua hai ho so co ma dai
            // giong nhau o phan dau se cat thanh mot - va ho so thu hai bi tu choi gui ma
            // khong ai hieu vi sao.
            $table->string('ma_ho_so', 100)->unique();

            // Het han thay cho co che tu xoa. Khong co cot nay thi mot tien trinh bi giet
            // giua chuoi de lai khoa mo coi chan ho so do VINH VIEN, va trieu chung la mot
            // ho so khong bao gio gui duoc ma khong co dong log nao.
            $table->timestamp('het_han_luc')->index();

            // De doc log khi phai di go khoa mo coi: khoa nay do man hinh don le hay nut
            // gui hang loat dat.
            $table->string('nguon', 20)->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tt12_khoa_xu_ly');
    }
}

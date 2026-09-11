<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Nhat ky tung lan SUA XML GOC cua mot chung tu.
 *
 * VI SAO LUU CA BAN TRUOC chu khong chi ghi "ai sua luc nao": nguoi dung sua VAN BAN THO
 * trong mot o nhap. Mot lan dan de len toan bo noi dung la mat han ban goc do phan mem HIS
 * sinh ra, va khong con duong nao dung lai - noi_dung_goc la ban duy nhat trong CSDL.
 *
 * Day cung la chung tu PHAP LY se nam tren cong BHXH. Khi doi soat ma cong va don vi lech
 * nhau, cau hoi dau tien la "ban gui di khac ban HIS sinh ra o cho nao" - khong luu ban
 * truoc thi khong ai tra loi duoc.
 *
 * KHONG co khoa ngoai toi ctdt_chung_tu: nhat ky phai song sot qua viec xoa ho so. Mot ho
 * so bi xoa xong thi cang can biet no da tung bi sua gi. Luu ma_ho_so/ma_chung_tu dang chuoi
 * de tra cuu duoc ke ca khi ban ghi goc da bien mat.
 */
class CreateCtdtLichSuSuaTable extends Migration
{
    public function up()
    {
        Schema::create('ctdt_lich_su_sua', function (Blueprint $table) {
            $table->increments('id');

            // Do dai KHOP ctdt_ho_so.ma_ho_so (varchar 100) va ctdt_chung_tu.ma_chung_tu.
            $table->string('ma_ho_so', 100)->index();
            $table->string('ma_chung_tu', 100)->nullable();
            $table->string('loai_ho_so', 30);

            // chung_tu_id de doi chieu khi ban ghi con song; KHONG dat khoa ngoai - xem
            // chu thich o tren.
            $table->unsignedInteger('chung_tu_id')->nullable()->index();

            $table->longText('noi_dung_truoc')->nullable();
            $table->longText('noi_dung_sau')->nullable();

            // loginname nguoi sua. Nullable vi mot lan sua tu Console (neu sau nay co) se
            // khong co nguoi dang nhap - de trong con hon ghi mot cai ten bia.
            $table->string('nguoi_sua', 100)->nullable();

            // Ho so DA co ma giao dich luc sua hay chua. Ghi lai TAI THOI DIEM SUA chu khong
            // suy ra ve sau: ma_gd cua ho so co the doi tiep sau lan sua nay, va luc doi soat
            // cau hoi la "luc sua thi cong da nhan chua".
            $table->string('ma_gd_luc_sua', 100)->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ctdt_lich_su_sua');
    }
}

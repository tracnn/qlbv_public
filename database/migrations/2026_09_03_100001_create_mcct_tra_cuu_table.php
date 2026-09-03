<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Moi lan bam tra them mot dong, KE CA lan hong (204/400/401/500).
 *
 * Giu dau vet lan hong la giu dung thu can den khi di hoi cong: khong co no thi cau hoi
 * "hom qua tra thay gi" khong tra loi duoc.
 */
class CreateMcctTraCuuTable extends Migration
{
    public function up()
    {
        Schema::create('mcct_tra_cuu', function (Blueprint $table) {
            $table->increments('id');

            $table->string('ma_cskcb', 10)->index();
            $table->string('ma_the', 20)->index();
            $table->string('ho_ten')->nullable();
            $table->string('ngay_sinh', 10)->nullable();

            $table->string('ma_ket_qua', 10)->nullable()->index();

            // NGUYEN VAN GhiChu - chua moc "du lieu tinh den dd/MM/yyyy HH:mm". Thieu no thi
            // so luy ke luu lai khong giai thich duoc khi doi soat.
            $table->text('ghi_chu')->nullable();

            $table->string('the_ho_ten')->nullable();
            $table->string('the_ngay_sinh', 10)->nullable();
            $table->date('the_ngay_ket_thuc')->nullable();
            $table->string('the_ma_bhxh', 15)->nullable()->index();

            $table->decimal('luy_ke_lon_nhat', 15, 2)->nullable();

            // Nguong TAI THOI DIEM TRA. Khong tinh lai luc doc: luong co so se tang, va tinh
            // lai nghia la moi ban ghi cu dot ngot doi ket luan du/chua du.
            $table->decimal('nguong_ap_dung', 15, 2)->nullable();
            $table->boolean('du_dieu_kien_mien')->nullable();

            // thu_cong | hang_loat | api_his - chua san cho giai doan 2 va 3.
            $table->string('nguon', 20)->default('thu_cong')->index();

            $table->string('tra_boi')->nullable();
            $table->timestamp('tra_luc')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mcct_tra_cuu');
    }
}

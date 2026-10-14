<?php

use App\Models\BHYT\Xml3176ErrorCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DVKT bat buoc kem vat pham (quy tac R3): hoc trong luot hoc bang tra vat pham kem DVKT me.
 * Kem nap ma loi XMLComplete_DVKT_THIEU_VAT_PHAM_KEM muc CANH BAO (khong chan gui) - chua co
 * trong danh muc thi getCriticalErrorStatus() mac dinh la nghiem trong. firstOrCreate: khong
 * ghi de muc nguoi van hanh da chinh.
 */
class CreateXml3176VpkdDvktBatBuocTable extends Migration
{
    public function up()
    {
        Schema::create('xml3176_vpkd_dvkt_bat_buoc', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_dvkt', 50)->collation('utf8_bin')->unique();
            $table->string('ten_dvkt', 1024)->nullable();
            $table->integer('so_ho_so')->default(0);
            $table->integer('so_ho_so_co_kem')->default(0);
            $table->decimal('ty_le', 5, 4)->nullable();
            $table->string('nguon', 10);
            $table->boolean('hieu_luc')->default(true);
            $table->string('ghi_chu', 1024)->nullable();
            $table->unsignedInteger('nguoi_sua_id')->nullable();
            $table->timestamp('sua_luc')->nullable();
            $table->timestamps();
        });

        Xml3176ErrorCatalog::firstOrCreate(
            ['xml' => 'XMLComplete', 'error_code' => 'XMLComplete_DVKT_THIEU_VAT_PHAM_KEM'],
            [
                'error_name' => 'DVKT bắt buộc kèm vật phẩm nhưng không có vật phẩm',
                'description' => 'DVKT mà hầu hết hồ sơ BHYT đều kèm vật phẩm (vd CLVT có tiêm thuốc cản quang) được quỹ thanh toán nhưng hồ sơ không có vật phẩm đi kèm được quỹ thanh toán. Kiểm tra kê đúng mã DVKT (vd có/không cản quang).',
                'critical_error' => false,
                'is_check' => true,
            ]
        );
    }

    public function down()
    {
        Schema::dropIfExists('xml3176_vpkd_dvkt_bat_buoc');
    }
}

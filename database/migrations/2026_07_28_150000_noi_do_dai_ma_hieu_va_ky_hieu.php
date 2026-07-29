<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

/**
 * Noi do dai hai cot bi chat khi nhap danh muc:
 *   medical_supply_catalogs.ma_hieu  VARCHAR(255) -> VARCHAR(2000)
 *   equipment_catalogs.ky_hieu       VARCHAR(255) -> VARCHAR(2000)
 *
 * Do tren du lieu that: ma_hieu da co dong dai 250 ky tu - sat tran 255. Dong nao vuot se
 * bi MySQL tu choi voi "Data too long", loi do bi bat va dong bi bo qua, nen hai tep VTYT
 * bao 19 va 22 dong loi.
 *
 * Chon 2000 cho bang voi ten_vat_tu von da la VARCHAR(2000) trong cung bang.
 *
 * Dung DB::statement chu khong dung ->change(): doctrine/dbal KHONG duoc cai trong du an,
 * ma Laravel 5.5 bat buoc phai co no moi doi kieu cot duoc. Day cung la le san co - xem
 * 2025_09_18_073514_medical_supply_update_ten_vat_tu_length.php.
 *
 * An toan voi chi muc: ca hai cot deu KHONG nam trong index nao.
 * Giu nguyen tinh nullable: ma_hieu cho phep NULL, ky_hieu thi KHONG.
 */
class NoiDoDaiMaHieuVaKyHieu extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE medical_supply_catalogs MODIFY ma_hieu VARCHAR(2000) NULL');
        DB::statement('ALTER TABLE equipment_catalogs MODIFY ky_hieu VARCHAR(2000) NOT NULL');
    }

    public function down()
    {
        // Thu hep lai se CAT du lieu dai hon 255 ky tu. Chi lam khi that su can quay lai.
        DB::statement('ALTER TABLE medical_supply_catalogs MODIFY ma_hieu VARCHAR(255) NULL');
        DB::statement('ALTER TABLE equipment_catalogs MODIFY ky_hieu VARCHAR(255) NOT NULL');
    }
}

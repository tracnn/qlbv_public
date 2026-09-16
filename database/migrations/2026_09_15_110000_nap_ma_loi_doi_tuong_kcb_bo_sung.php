<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Nap them 3 ma loi ma doi tuong KCB bo sung (1.1, 3.6, 1.17) vao danh muc ma loi.
 *
 * Cac migration nap danh muc truoc do da chay tren moi truong da trien khai nen khong chay
 * lai; can migration rieng de dong moi den duoc CSDL that.
 *
 * BAT BUOC chay TRUOC khi quy tac no lan dau: thieu dong danh muc thi
 * getCriticalErrorStatus() tra mac dinh TRUE va chan xuat XML.
 *
 * Seeder idempotent (updateOrCreate) nen chay lai ca 13 dong la an toan.
 */
class NapMaLoiDoiTuongKcbBoSung extends Migration
{
    public function up()
    {
        require_once database_path('seeds/Xml3176ErrorCatalogDoiTuongKcbSeeder.php');

        (new Xml3176ErrorCatalogDoiTuongKcbSeeder())->run();
    }

    public function down()
    {
        // Co Y KHONG lui: xoa dong danh muc se lam getCriticalErrorStatus() quay ve mac
        // dinh TRUE va chan xuat XML.
    }
}

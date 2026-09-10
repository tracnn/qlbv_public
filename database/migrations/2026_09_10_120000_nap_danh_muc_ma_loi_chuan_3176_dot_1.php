<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Noi seeder danh muc ma loi Dot 1 vao migration.
 *
 * Neu khong chay seeder truoc khi bat quy tac: lan dau mot quy tac no,
 * Xml3176ErrorService::getCriticalErrorStatus() khong tim thay dong danh muc va tra ve
 * mac dinh TRUE -> dong danh muc TU SINH voi critical_error = true -> ExportXml3176Job
 * chan xuat XML ca lo, va cac dong xml3176_error_results da ghi van mang true ke ca khi
 * sau do co chay seeder tay. Migration nay xoa dieu kien "nho chay seeder truoc".
 *
 * Seeder da idempotent (updateOrCreate) nen chay lai an toan.
 */
class NapDanhMucMaLoiChuan3176Dot1 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        require_once database_path('seeds/Xml3176ErrorCatalogChuan3176Dot1Seeder.php');
        (new Xml3176ErrorCatalogChuan3176Dot1Seeder())->run();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Co y KHONG lui: xoa dong danh muc se lam getCriticalErrorStatus() quay ve
        // mac dinh TRUE va chan xuat XML, nen down() de rong.
    }
}

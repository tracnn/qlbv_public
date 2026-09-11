<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Nap danh muc 10 ma loi cua bo quy tac ma doi tuong KCB.
 *
 * Nap bang migration chu khong phai lenh chay tay: dieu kien "nho chay seeder truoc" ma
 * chi ton tai trong tri nho nguoi trien khai da tung gay hau qua khong lui lai duoc -
 * quy tac no lan dau khi thieu dong danh muc se tu ghi dong o muc nghiem trong va chan
 * xuat XML ca lo.
 */
class NapDanhMucMaLoiDoiTuongKcb extends Migration
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

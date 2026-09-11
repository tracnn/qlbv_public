<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Nap them ma loi XML1_DOI_TUONG_KCB_THIEU_GIAY_CHUYEN_TUYEN vao danh muc ma loi.
 *
 * Migration truoc (nap_danh_muc_ma_loi_doi_tuong_kcb) da chay tren cac moi truong da
 * trien khai nen khong chay lai; can mot migration rieng de dong moi den duoc CSDL that.
 *
 * BAT BUOC chay TRUOC khi quy tac no lan dau: thieu dong danh muc thi
 * getCriticalErrorStatus() tra mac dinh TRUE, quy tac se TU GHI dong danh muc o muc
 * nghiem trong va chan xuat XML - do tren du lieu that la 938 ho so.
 *
 * Seeder idempotent (updateOrCreate) nen chay lai ca 10 dong la an toan.
 */
class NapMaLoiThieuGiayChuyenTuyen extends Migration
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

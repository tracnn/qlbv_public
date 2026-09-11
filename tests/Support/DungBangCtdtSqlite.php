<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;

/**
 * Dung 13 bang cua module chung tu dien tu trong SQLite bo nho.
 *
 * VI SAO KHONG DUNG RefreshDatabase: .env cua du an tro DB_DATABASE=qlbv - co so du
 * lieu phat trien that. RefreshDatabase se xoa sach no.
 *
 * VI SAO KHONG DUNG artisan migrate --path: tren Laravel 5.5,
 * Migrator::getMigrationFiles() luon glob($path.'/*_*.php'), tuc --path bat buoc la
 * MOT THU MUC. Truyen duong dan tep khien lenh bao "thanh cong" (exit 0) nhung khong
 * tao bang nao. Thu muc that database/migrations lai chua nhieu migration phu thuoc
 * cu phap MySQL va ket noi Oracle, khong tro --path vao do duoc. Nen nap thang tung
 * tep roi goi up().
 *
 * VI SAO GHI DE KET NOI TEN 'mysql' chu khong doi database.default: giu dung khuon
 * cua DungBangPhanQuyenSqlite, va phong khi model ve sau ghim cung $connection.
 */
trait DungBangCtdtSqlite
{
    /**
     * Thu tu QUAN TRONG: bang khung truoc, bang chi tiet sau - khoa ngoai cua bang
     * chi tiet tro toi ctdt_chung_tu.
     *
     * @return array [ten tep migration => ten lop]
     */
    protected function cacMigrationCtdt()
    {
        return [
            '2026_08_19_100001_create_ctdt_ho_so_table'              => 'CreateCtdtHoSoTable',
            '2026_08_21_100001_create_ctdt_lich_su_gui_table'        => 'CreateCtdtLichSuGuiTable',
            '2026_08_19_100002_create_ctdt_chung_tu_table'           => 'CreateCtdtChungTuTable',
            '2026_08_19_100003_create_ctdt_loi_table'                => 'CreateCtdtLoiTable',
            '2026_08_19_100011_create_ctdt_ct03_table'               => 'CreateCtdtCt03Table',
            '2026_08_19_100012_create_ctdt_ct04_table'               => 'CreateCtdtCt04Table',
            '2026_08_19_100013_create_ctdt_ct06_table'               => 'CreateCtdtCt06Table',
            '2026_08_19_100014_create_ctdt_ct07_table'               => 'CreateCtdtCt07Table',
            '2026_08_19_100015_create_ctdt_dieu_tri_noi_tru_table'   => 'CreateCtdtDieuTriNoiTruTable',
            '2026_08_19_100016_create_ctdt_dieu_tri_vo_sinh_table'   => 'CreateCtdtDieuTriVoSinhTable',
            '2026_08_19_100017_create_ctdt_suc_khoe_me_table'        => 'CreateCtdtSucKhoeMeTable',
            '2026_08_19_100018_create_ctdt_giay_bao_tu_table'        => 'CreateCtdtGiayBaoTuTable',
            '2026_08_19_100019_create_ctdt_giay_chung_sinh_table'    => 'CreateCtdtGiayChungSinhTable',
            '2026_08_22_100001_add_cccd_ma_bhxh_to_ctdt_chung_tu_table' => 'AddCccdMaBhxhToCtdtChungTuTable',
            '2026_08_22_100002_create_ctdt_khoa_xu_ly_table'         => 'CreateCtdtKhoaXuLyTable',
            '2026_09_11_100002_create_ctdt_lich_su_sua_table'        => 'CreateCtdtLichSuSuaTable',
        ];
    }

    /**
     * Dung ca 13/13 bang cua module, roi chay tiep cac migration SUA BANG (vi du
     * them cot rut gon) theo dung thu tu khai o tren - danh sach nay khong con la
     * "13 tep tao 13 bang" nua.
     *
     * Tung dung "bo qua tep chua ton tai" de trait dung duoc tu Task 2 khi chi co
     * 3/13 migration - nay du bo nen dieu kien do thanh diem mu: doi ten mot tep
     * migration ma quen sua danh sach o day se bi
     * trait AM THAM bo qua thay vi bao loi ngay. Nem ngoai le de sai lech lo ra tai
     * cho, thay vi roi vao mot Schema::hasTable() that bai o mot test khac xa.
     */
    protected function chuanBiBangCtdt()
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => [
                'driver'   => 'sqlite',
                'database' => ':memory:',
                'prefix'   => '',
            ],
        ]);

        DB::purge('mysql');

        foreach ($this->cacMigrationCtdt() as $tep => $lop) {
            $duongDan = base_path('database/migrations/' . $tep . '.php');

            if (!file_exists($duongDan)) {
                throw new \RuntimeException('Thieu tep migration: ' . $duongDan);
            }

            require_once $duongDan;

            (new $lop())->up();
        }
    }
}

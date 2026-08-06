<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dung 4 bang lien quan toi API tra cuu loi cua mot dot dieu tri, trong SQLite bo nho.
 *
 * VI SAO KHONG DUNG RefreshDatabase: .env cua du an tro DB_DATABASE=qlbv - co so du lieu
 * phat trien that. RefreshDatabase se xoa sach no.
 *
 * VI SAO GHI DE KET NOI TEN 'mysql' chu khong doi database.default: cac model va cau
 * DB::table() deu di theo ket noi mac dinh, ma mac dinh do ten la 'mysql'. Ghi de chinh
 * ket noi ay la cach duy nhat chan moi duong ra CSDL that.
 *
 * KHONG chay migration that: thu muc database/migrations chua nhieu migration phu thuoc
 * cu phap MySQL va ket noi Oracle, khong chay duoc tren SQLite.
 */
trait DungBangLoiDotDieuTriSqlite
{
    protected function chuanBiBangLoi()
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

        Schema::create('order_check_violations', function ($t) {
            $t->increments('id');
            $t->string('rule_code', 100);
            $t->unsignedBigInteger('treatment_id')->nullable();
            $t->string('treatment_code', 50)->nullable();
            $t->string('order_ref_type', 30);
            $t->unsignedBigInteger('order_ref_id');
            $t->string('severity', 20)->default('warning');
            $t->text('message');
            $t->text('detail')->nullable();
            $t->string('dedup_key', 200);
            $t->string('status', 20)->default('new');
            $t->dateTime('detected_at');
            $t->timestamps();
        });

        Schema::create('check_hein_cards', function ($t) {
            $t->increments('id');
            $t->string('ma_lk', 100);
            $t->string('ma_tracuu', 10);
            $t->string('ma_kiemtra', 10);
            $t->string('ma_ketqua', 255)->nullable();
            $t->text('ghi_chu')->nullable();
            $t->string('ma_the', 255)->nullable();
            $t->timestamps();
        });

        Schema::create('xml3176_error_results', function ($t) {
            $t->increments('id');
            $t->string('xml');
            $t->string('ma_lk');
            $t->integer('stt');
            $t->string('ngay_yl')->nullable();
            $t->string('ngay_kq')->nullable();
            $t->string('error_code');
            $t->text('description')->nullable();
            $t->boolean('critical_error')->default(false);
            $t->timestamps();
        });

        Schema::create('xml3176_error_catalogs', function ($t) {
            $t->increments('id');
            $t->string('xml');
            $t->string('error_code');
            $t->string('error_name')->nullable();
            $t->text('description')->nullable();
            $t->boolean('critical_error')->default(false);
            $t->boolean('is_check')->default(true);
            $t->timestamps();
        });
    }

    /** Them mot vi pham y lenh. $ghiDe ghi de bat ky cot nao. */
    protected function themViPham(array $ghiDe = [])
    {
        static $dem = 0;
        $dem++;

        DB::table('order_check_violations')->insert(array_merge([
            'rule_code'      => 'REQ_TIME_INVALID',
            'treatment_id'   => 9001,
            'treatment_code' => '01013250800123',
            'order_ref_type' => 'service_req',
            'order_ref_id'   => 123456,
            'severity'       => 'warning',
            'message'        => 'Loi y lenh ' . $dem,
            'detail'         => null,
            'dedup_key'      => 'dedup-' . $dem,
            'status'         => 'new',
            'detected_at'    => '2026-08-06 09:00:00',
            'created_at'     => '2026-08-06 09:00:00',
            'updated_at'     => '2026-08-06 09:00:00',
        ], $ghiDe));
    }
}

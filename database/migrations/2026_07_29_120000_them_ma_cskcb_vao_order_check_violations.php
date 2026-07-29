<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Them cot ma_cskcb vao vi pham y lenh, va va nguoc du lieu cu tu HIS.
 *
 * Vi sao phai va nguoc: bo quet chi chay TOI TRUOC theo moc thoi gian, khong bao gio quay
 * lai ho so cu. Khong va thi 1.065 dong dang co se vinh vien trong va bo loc bo sot chung.
 *
 * Duong tra: order_check_violations.treatment_id -> HIS_TREATMENT.BRANCH_ID
 *            -> HIS_BRANCH.HEIN_MEDI_ORG_CODE
 *
 * Do truoc khi viet: 890 treatment_id phan biet, tra ra 829, khong tra ra 61 (dot dieu tri
 * da bien mat khoi his_treatment) — ung voi 72/1.065 dong se de TRONG. Khong gan mac dinh
 * cho 72 dong do: gan bua thi chung trong nhu da biet chac thuoc co so nao, trong khi
 * khong ai kiem chung duoc nua.
 */
class ThemMaCskcbVaoOrderCheckViolations extends Migration
{
    /** Menh de IN cua Oracle gioi han 1000 phan tu */
    const CO_LO = 900;

    public function up()
    {
        if (!Schema::hasColumn('order_check_violations', 'ma_cskcb')) {
            Schema::table('order_check_violations', function (Blueprint $t) {
                $t->string('ma_cskcb', 20)->nullable()->after('treatment_code');
                $t->index('ma_cskcb');
            });
        }

        // Loi HIS khong duoc lam migration chet giua chung: cot da them ma du lieu chua va.
        // De trong roi chay lai migration sau van va tiep duoc, vi buoc nay chi dung dong
        // dang NULL.
        try {
            $this->vaNguoc();
        } catch (\Exception $e) {
            echo '  [canh bao] Khong va nguoc duoc ma co so tu HIS: ' . $e->getMessage() . PHP_EOL;
            echo '  Chay lai migration nay sau khi HIS truy cap duoc.' . PHP_EOL;
        }
    }

    protected function vaNguoc()
    {
        $ids = DB::table('order_check_violations')
            ->whereNull('ma_cskcb')
            ->whereNotNull('treatment_id')
            ->distinct()
            ->pluck('treatment_id')
            ->all();

        if (empty($ids)) {
            return;
        }

        $theoMa = [];

        foreach (array_chunk($ids, self::CO_LO) as $lo) {
            $rows = DB::connection('HISPro')
                ->table('his_treatment as t')
                ->leftJoin('his_branch as br', 'br.id', '=', 't.branch_id')
                ->whereIn('t.id', $lo)
                ->select('t.id', DB::raw('br.hein_medi_org_code ma'))
                ->get();

            foreach ($rows as $r) {
                if ($r->ma === null || $r->ma === '') {
                    continue;
                }

                $theoMa[$r->ma][] = $r->id;
            }
        }

        $daVa = 0;

        // Cap nhat GOM NHOM theo ma co so: moi ma mot cau UPDATE thay vi mot cau moi dong.
        foreach ($theoMa as $ma => $dsId) {
            foreach (array_chunk($dsId, self::CO_LO) as $lo) {
                $daVa += DB::table('order_check_violations')
                    ->whereNull('ma_cskcb')
                    ->whereIn('treatment_id', $lo)
                    ->update(['ma_cskcb' => $ma]);
            }
        }

        $conTrong = DB::table('order_check_violations')->whereNull('ma_cskcb')->count();

        echo '  Da va nguoc ' . $daVa . ' dong; con trong ' . $conTrong . ' dong.' . PHP_EOL;
    }

    public function down()
    {
        if (!Schema::hasColumn('order_check_violations', 'ma_cskcb')) {
            return;
        }

        Schema::table('order_check_violations', function (Blueprint $t) {
            $t->dropIndex(['ma_cskcb']);
            $t->dropColumn('ma_cskcb');
        });
    }
}

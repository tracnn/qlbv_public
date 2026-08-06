<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * API tra cuu loi loc theo treatment_code, nhung bang chi co index tren treatment_id -
 * moi lan goi la mot lan quet toan bang.
 *
 * Chi index don. Composite (treatment_code, status) de lai toi khi do thay can: loc
 * treatment_code truoc thi so dong con lai cua mot dot dieu tri von rat nho.
 */
class ThemIndexTreatmentCodeVaoOrderCheckViolations extends Migration
{
    const TEN = 'order_check_violations_treatment_code_index';

    public function up()
    {
        if ($this->coIndex()) {
            return;
        }

        Schema::table('order_check_violations', function (Blueprint $t) {
            $t->index('treatment_code');
        });
    }

    public function down()
    {
        if (!$this->coIndex()) {
            return;
        }

        Schema::table('order_check_violations', function (Blueprint $t) {
            $t->dropIndex(['treatment_code']);
        });
    }

    /**
     * Doc het roi so trong PHP thay vi SHOW INDEX ... WHERE: menh de WHERE cua SHOW
     * khong nhan tham so gan san mot cach dang tin cay tren moi phien ban MySQL.
     */
    protected function coIndex()
    {
        foreach (DB::select('SHOW INDEX FROM order_check_violations') as $dong) {
            if ($dong->Key_name === self::TEN) {
                return true;
            }
        }

        return false;
    }
}

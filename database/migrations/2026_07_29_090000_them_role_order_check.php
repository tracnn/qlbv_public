<?php

use App\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

/**
 * Role rieng cho module kiem tra sai sot y lenh (order-check).
 *
 * Vi sao la ROLE chu khong phai PERMISSION: menu di qua AppServiceProvider::filterMenu,
 * ham do CHI kiem hasRole(), khong co nhanh can(). Cap bang permission thi route cho vao
 * nhung menu van an.
 *
 * Vi sao PHAI gan role: filterMenu cho superadministrator xem toan bo menu khong loc,
 * nhung middleware CheckRole KHONG co ngoai le cho superadministrator. Superadmin thieu
 * role nay se THAY menu nhung bam vao la 403.
 */
class ThemRoleOrderCheck extends Migration
{
    const TEN = 'order-check';

    public function up()
    {
        $role = Role::where('name', self::TEN)->first();

        if (!$role) {
            $role = Role::create([
                'name' => self::TEN,
                'display_name' => 'Kiểm tra sai sót y lệnh',
                'description' => 'Kiểm tra sai sót y lệnh',
            ]);
        }

        $xmlMan = Role::where('name', 'xml-man')->first();

        if (!$xmlMan) {
            return;
        }

        // Gan cho dung nhung nguoi dang co xml-man, giu nguyen user_type cua ban ghi goc.
        foreach (DB::table('role_user')->where('role_id', $xmlMan->id)->get() as $r) {
            $daCo = DB::table('role_user')
                ->where('role_id', $role->id)
                ->where('user_id', $r->user_id)
                ->where('user_type', $r->user_type)
                ->exists();

            if ($daCo) {
                continue;
            }

            DB::table('role_user')->insert([
                'role_id' => $role->id,
                'user_id' => $r->user_id,
                'user_type' => $r->user_type,
            ]);
        }
    }

    public function down()
    {
        $role = Role::where('name', self::TEN)->first();

        if (!$role) {
            return;
        }

        DB::table('role_user')->where('role_id', $role->id)->delete();

        // Khong dung Eloquent $role->delete(): Laratrust bat su kien "deleting" tren
        // Role va tu sync rong bang pivot role_user qua quan he morphedByMany, nhung
        // cau SQL sinh ra bi viet hoa ten bang thanh "ROLE_USER" trong khi bang that
        // la chu thuong "role_user" -> Oracle nem ORA-00942 (table or view does not
        // exist). Xoa thang bang query builder de tranh kich hoat event loi nay.
        DB::table('roles')->where('id', $role->id)->delete();
    }
}

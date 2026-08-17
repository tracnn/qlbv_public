<?php

use App\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Migrations\Migration;

/**
 * Role rieng cho man tra cuu loi ho so theo ma dieu tri.
 *
 * Vi sao la ROLE chu khong phai PERMISSION: menu di qua
 * AppServiceProvider::filterMenu, ham do CHI kiem hasRole(), khong co nhanh can(). Cap
 * bang permission thi route cho vao nhung menu van an.
 *
 * Vi sao gan theo order-check: do la nhom dang lam viec voi chinh ba nguon loi nay.
 * CheckRole KHONG mien tru superadministrator, nen superadmin nao thieu role se thay
 * menu nhung bam vao la 403 - ho nam trong nhom order-check nen duoc gan o day.
 */
class ThemRoleTraCuuLoiHoSo extends Migration
{
    const TEN = 'tra-cuu-loi-ho-so';

    public function up()
    {
        $role = Role::where('name', self::TEN)->first();

        if (!$role) {
            $role = Role::create([
                'name' => self::TEN,
                'display_name' => 'Tra cứu lỗi hồ sơ',
                'description' => 'Tra cứu lỗi hồ sơ theo mã điều trị',
            ]);
        }

        $nguon = Role::where('name', 'order-check')->first();

        if (!$nguon) {
            return;
        }

        foreach (DB::table('role_user')->where('role_id', $nguon->id)->get() as $r) {
            $daCo = DB::table('role_user')
                ->where('role_id', $role->id)
                ->where('user_id', $r->user_id)
                ->where('user_type', $r->user_type)
                ->exists();

            if ($daCo) {
                continue;
            }

            DB::table('role_user')->insert([
                'role_id'   => $role->id,
                'user_id'   => $r->user_id,
                'user_type' => $r->user_type,
            ]);

            // BAT BUOC: Laratrust cache hasRole() 60 PHUT, va insert qua query builder
            // khong ban su kien nen cache khong tu xoa. Thieu dong nay thi nguoi dang
            // dang nhap thay menu nhung bam vao bi 403 toi 60 phut.
            Cache::forget('laratrust_roles_for_user_' . $r->user_id);
        }
    }

    public function down()
    {
        $role = Role::where('name', self::TEN)->first();

        if (!$role) {
            return;
        }

        $userIds = DB::table('role_user')->where('role_id', $role->id)->pluck('user_id');

        DB::table('role_user')->where('role_id', $role->id)->delete();

        // KHONG dung $role->delete(): su kien "deleting" cua Laratrust goi quan he
        // users() toi App\CustomUser von nam tren ket noi Oracle ACS_RS, lam Laravel tim
        // bang role_user trong Oracle -> ORA-00942.
        DB::table('roles')->where('id', $role->id)->delete();

        foreach ($userIds as $userId) {
            Cache::forget('laratrust_roles_for_user_' . $userId);
        }
    }
}

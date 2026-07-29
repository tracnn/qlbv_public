<?php

use App\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
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

            // Bat buoc xoa cache: Laratrust cache hasRole() 60 PHUT (Cache::remember(...,
            // 60, ...) tai LaratrustUserTrait, tham so 60 la phut trong Laravel 5.5). Insert
            // qua query builder KHONG bat su kien "role attached" nen Laratrust khong tu xoa
            // cache. Neu khong Cache::forget() o day, nguoi dang dang nhap se van bi
            // hasRole('order-check') = false toi 60 phut sau khi migrate xong -> thay menu
            // nhung bam vao bi 403.
            Cache::forget('laratrust_roles_for_user_' . $r->user_id);
        }
    }

    public function down()
    {
        $role = Role::where('name', self::TEN)->first();

        if (!$role) {
            return;
        }

        // Thu danh sach user_id bi anh huong TRUOC khi xoa, de con xoa cache sau do.
        $userIds = DB::table('role_user')->where('role_id', $role->id)->pluck('user_id');

        DB::table('role_user')->where('role_id', $role->id)->delete();

        // Khong dung Eloquent $role->delete(): LaratrustRoleTrait::bootLaratrustRoleTrait()
        // dang ky su kien "deleting" tren Role, goi $role->users()->sync([]). Quan he
        // users() la morphedByMany toi App\CustomUser, ma model nay khai
        // protected $connection = 'ACS_RS' (ket noi Oracle). Laravel chay truy van cua
        // quan he tren KET NOI CUA MODEL LIEN QUAN (CustomUser), nen bang role_user bi
        // tim trong Oracle thay vi MySQL -> Oracle bao khong co bang do (ORA-00942).
        // Ten bang in hoa "ROLE_USER" trong thong bao loi chi la cach Oracle hien thi
        // dinh danh, KHONG PHAI nguyen nhan. Xoa thang bang query builder de tranh
        // kich hoat event nay.
        DB::table('roles')->where('id', $role->id)->delete();

        // Xoa cache role 60 phut (xem giai thich o up()) cho tung nguoi bi mat role,
        // neu khong rollback xong ho van con quyen order-check trong cache toi 60 phut.
        foreach ($userIds as $userId) {
            Cache::forget('laratrust_roles_for_user_' . $userId);
        }
    }
}

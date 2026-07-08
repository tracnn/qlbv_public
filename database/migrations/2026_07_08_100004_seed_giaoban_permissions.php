<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class SeedGiaobanPermissions extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');

        $permIds = [];
        foreach ([
            'giaoban'       => 'Báo cáo giao ban - Xem/nhập theo khoa',
            'giaoban-admin' => 'Báo cáo giao ban - Quản trị (lấy số liệu, chốt, cấu hình)',
        ] as $name => $display) {
            $id = DB::table('permissions')->where('name', $name)->value('id');
            if (!$id) {
                $id = DB::table('permissions')->insertGetId([
                    'name' => $name, 'display_name' => $display,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            $permIds[$name] = $id;
        }

        $roles = [
            'giaoban_khoa'  => ['display' => 'Giao ban - Nhập liệu khoa', 'perms' => ['giaoban']],
            'giaoban_admin' => ['display' => 'Giao ban - Quản trị',       'perms' => ['giaoban', 'giaoban-admin']],
        ];
        foreach ($roles as $name => $def) {
            $roleId = DB::table('roles')->where('name', $name)->value('id');
            if (!$roleId) {
                $roleId = DB::table('roles')->insertGetId([
                    'name' => $name, 'display_name' => $def['display'],
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            foreach ($def['perms'] as $p) {
                $exists = DB::table('permission_role')
                    ->where('permission_id', $permIds[$p])->where('role_id', $roleId)->exists();
                if (!$exists) {
                    DB::table('permission_role')->insert(['permission_id' => $permIds[$p], 'role_id' => $roleId]);
                }
            }
        }

        // administrator sẵn có được full quyền giao ban
        $adminRoleId = DB::table('roles')->where('name', 'administrator')->value('id');
        if ($adminRoleId) {
            foreach ($permIds as $pid) {
                $exists = DB::table('permission_role')
                    ->where('permission_id', $pid)->where('role_id', $adminRoleId)->exists();
                if (!$exists) {
                    DB::table('permission_role')->insert(['permission_id' => $pid, 'role_id' => $adminRoleId]);
                }
            }
        }
    }

    public function down()
    {
        $ids = DB::table('permissions')->whereIn('name', ['giaoban', 'giaoban-admin'])->pluck('id');
        DB::table('permission_role')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
        DB::table('roles')->whereIn('name', ['giaoban_khoa', 'giaoban_admin'])->delete();
    }
}

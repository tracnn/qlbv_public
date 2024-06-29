<?php

use Illuminate\Database\Seeder;

class LaratrustSeederSuperUser extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $superadminRole = Role::where('name', 'superadministrator')->first();

        // Nếu role chưa tồn tại, bạn có thể tạo mới
        if (!$superadminRole) {
            $superadminRole = Role::create(['name' => 'superadministrator', 'display_name' => 'Super Administrator', 'description' => 'Highest level administrator']);
        }

        // Gán vai trò 'superadministrator' cho user có ID = 473
        $user = User::find(473);
        if ($user) {
            $user->attachRole($superadminRole);
        }
    }
}

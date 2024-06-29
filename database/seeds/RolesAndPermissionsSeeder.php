<?php

use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Thêm quyền 'duoc'
        DB::table('permissions')->insert([
            'name' => 'duoc',
            'display_name' => 'Dược',
            'description' => 'Dược',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Thêm vai trò 'duoc'
        DB::table('roles')->insert([
            'name' => 'duoc',
            'display_name' => 'Dược',
            'description' => 'Dược',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

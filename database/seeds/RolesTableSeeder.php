<?php

use Illuminate\Database\Seeder;
use App\Role;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::create([
            'name' => 'vaccination',
            'display_name' => 'Vaccination',
            'description' => 'Vaccination'
        ]);
    }
}

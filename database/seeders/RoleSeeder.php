<?php

namespace Database\Seeders;

use App\Models\Settings\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = new Role();
        $data->role_code = 'SU';
        $data->role_desc = 'Super User';
        // $data->created_by = 'System';
        $data->created_by = 1;
        $data->save();
    }
}

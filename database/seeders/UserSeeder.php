<?php

namespace Database\Seeders;

use App\Models\Settings\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = new User();
        $user->name = 'IMI';
        $user->username = 'IMI';
        $user->email = 'andrew@ptimi.co.id';
        $user->role_id = 1;
        $user->is_super_user = 'Yes';
        $user->is_active = 'Active';
        $user->created_by = 'System';
        $user->updated_by = 'System';
        $user->password = Hash::make('password');
    }
}

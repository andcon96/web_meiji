<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Settings\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(DomainSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(MenuMasterSeeder::class);
        $this->call(IconMasterSeeder::class);
        $this->call(MenuStructureMasterSeeder::class);
        User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}

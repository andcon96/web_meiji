<?php

namespace Database\Seeders;

use App\Models\Test;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::disableQueryLog();
        $chunks = 1000; // Insert 1000 records per batch
        for ($i = 0; $i < 100; $i++) {
            Test::factory()->count($chunks)->create();
        }
    }
}

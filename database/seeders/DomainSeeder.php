<?php

namespace Database\Seeders;

use App\Models\Settings\Domain;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DomainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = new Domain();
        $data->domain = 'RISIS';
        $data->domain_desc = 'RISIS';
        $data->save();
    }
}

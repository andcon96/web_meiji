<?php

namespace Database\Seeders;

use App\Models\Settings\Icon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IconMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['icon_name' => 'Settings', 'icon_desc' => 'Icon for settings menu', 'icon_value' => 'fa-solid fa-gears'],
            ['icon_name' => 'Sales', 'icon_desc' => 'Icon for sales', 'icon_value' => 'fa-solid fa-file-lines'],
            ['icon_name' => 'Warehouse', 'icon_desc' => 'Icon for warehouse', 'icon_value' => 'fa-solid fa-warehouse'],
            ['icon_name' => 'Production', 'icon_desc' => 'Icon for production', 'icon_value' => 'fa-solid fa-people-carry-box'],
            ['icon_name' => 'Purchasing', 'icon_desc' => 'Icon for purchasing', 'icon_value' => 'fa-solid fa-building-user'],
        ];

        Icon::insert($data);
    }
}

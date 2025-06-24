<?php

namespace Database\Seeders;

use App\Models\Settings\MenuStructure;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MenuStructureMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['menu_id' => 1, 'menu_icon_id' => 1, 'menu_parent_id' => NULL, 'menu_sequence' => 1],
            ['menu_id' => 2, 'menu_icon_id' => NULL, 'menu_parent_id' => 1, 'menu_sequence' => 1],
            ['menu_id' => 3, 'menu_icon_id' => NULL, 'menu_parent_id' => 1, 'menu_sequence' => 2],
            ['menu_id' => 4, 'menu_icon_id' => NULL, 'menu_parent_id' => 1, 'menu_sequence' => 3],
            ['menu_id' => 5, 'menu_icon_id' => NULL, 'menu_parent_id' => 1, 'menu_sequence' => 4],
            ['menu_id' => 6, 'menu_icon_id' => NULL, 'menu_parent_id' => 1, 'menu_sequence' => 5],
            ['menu_id' => 7, 'menu_icon_id' => NULL, 'menu_parent_id' => 1, 'menu_sequence' => 6],
            ['menu_id' => 8, 'menu_icon_id' => NULL, 'menu_parent_id' => 1, 'menu_sequence' => 7],

            ['menu_id' => 9, 'menu_icon_id' => 1, 'menu_parent_id' => NULL, 'menu_sequence' => 8],
            ['menu_id' => 10, 'menu_icon_id' => NULL, 'menu_parent_id' => 9, 'menu_sequence' => 9],
            ['menu_id' => 11, 'menu_icon_id' => NULL, 'menu_parent_id' => 9, 'menu_sequence' => 10],
            ['menu_id' => 12, 'menu_icon_id' => NULL, 'menu_parent_id' => 9, 'menu_sequence' => 11],
        ];

        MenuStructure::insert($data);
    }
}

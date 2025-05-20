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
            [ 'menu_id' => 1, 'menu_icon_id' => 1, 'menu_parent_id' => NULL, 'menu_sequence' => 1, 'created_by' =>  1],
            [ 'menu_id' => 2, 'menu_icon_id' => 3, 'menu_parent_id' => NULL, 'menu_sequence' => 2, 'created_by' =>  1],
            [ 'menu_id' => 3, 'menu_icon_id' => 4, 'menu_parent_id' => NULL, 'menu_sequence' => 3, 'created_by' =>  1],
            [ 'menu_id' => 4, 'menu_icon_id' => 2, 'menu_parent_id' => NULL, 'menu_sequence' => 4, 'created_by' =>  1],
            [ 'menu_id' => 5, 'menu_icon_id' => 5, 'menu_parent_id' => NULL, 'menu_sequence' => 5, 'created_by' =>  1],
            [ 'menu_id' => 14, 'menu_icon_id' => NULL, 'menu_parent_id' => 1, 'menu_sequence' => 6, 'created_by' =>  1],
            [ 'menu_id' => 9, 'menu_icon_id' => NULL, 'menu_parent_id' => 15, 'menu_sequence' => 10, 'created_by' =>  1],
            [ 'menu_id' => 8, 'menu_icon_id' => NULL, 'menu_parent_id' => 15, 'menu_sequence' => 11, 'created_by' =>  1],
            [ 'menu_id' => 11, 'menu_icon_id' => NULL, 'menu_parent_id' => 15, 'menu_sequence' => 12, 'created_by' =>  1],
            [ 'menu_id' => 10, 'menu_icon_id' => NULL, 'menu_parent_id' => 16, 'menu_sequence' => 13, 'created_by' =>  1],
            [ 'menu_id' => 6, 'menu_icon_id' => NULL, 'menu_parent_id' => 16, 'menu_sequence' => 14, 'created_by' =>  1],
            [ 'menu_id' => 13, 'menu_icon_id' => NULL, 'menu_parent_id' => 16, 'menu_sequence' => 15, 'created_by' =>  1],
            [ 'menu_id' => 15, 'menu_icon_id' => NULL, 'menu_parent_id' => 5, 'menu_sequence' => 8, 'created_by' =>  1],
            [ 'menu_id' => 7, 'menu_icon_id' => NULL, 'menu_parent_id' => 15, 'menu_sequence' => 9, 'created_by' =>  1],
            [ 'menu_id' => 16, 'menu_icon_id' => NULL, 'menu_parent_id' => 5, 'menu_sequence' => 16, 'created_by' =>  1],
            [ 'menu_id' => 18, 'menu_icon_id' => NULL, 'menu_parent_id' => 23, 'menu_sequence' => 21, 'created_by' =>  1],
            [ 'menu_id' => 19, 'menu_icon_id' => NULL, 'menu_parent_id' => 5, 'menu_sequence' => 17, 'created_by' =>  1],
            [ 'menu_id' => 21, 'menu_icon_id' => NULL, 'menu_parent_id' => 5, 'menu_sequence' => 19, 'created_by' =>  1],
            [ 'menu_id' => 23, 'menu_icon_id' => NULL, 'menu_parent_id' => 5, 'menu_sequence' => 18, 'created_by' =>  1],
            [ 'menu_id' => 24, 'menu_icon_id' => NULL, 'menu_parent_id' => 16, 'menu_sequence' => 24, 'created_by' =>  1],
            [ 'menu_id' => 25, 'menu_icon_id' => NULL, 'menu_parent_id' => 5, 'menu_sequence' => 25, 'created_by' =>  1],
            [ 'menu_id' => 26, 'menu_icon_id' => 5, 'menu_parent_id' => 3, 'menu_sequence' => 26, 'created_by' =>  1],
            [ 'menu_id' => 26, 'menu_icon_id' => NULL, 'menu_parent_id' => 2, 'menu_sequence' => 27, 'created_by' =>  1],
            [ 'menu_id' => 27, 'menu_icon_id' => NULL, 'menu_parent_id' => 1, 'menu_sequence' => 28, 'created_by' =>  1],
            [ 'menu_id' => 28, 'menu_icon_id' => NULL, 'menu_parent_id' => 2, 'menu_sequence' => 29, 'created_by' =>  1],
            [ 'menu_id' => 27, 'menu_icon_id' => NULL, 'menu_parent_id' => 2, 'menu_sequence' => 30, 'created_by' =>  1],
            [ 'menu_id' => 29, 'menu_icon_id' => NULL, 'menu_parent_id' => 2, 'menu_sequence' => 31, 'created_by' =>  1],
            [ 'menu_id' => 30, 'menu_icon_id' => NULL, 'menu_parent_id' => 2, 'menu_sequence' => 32, 'created_by' =>  1],
            [ 'menu_id' => 31, 'menu_icon_id' => NULL, 'menu_parent_id' => 3, 'menu_sequence' => 33, 'created_by' =>  1],
            [ 'menu_id' => 32, 'menu_icon_id' => NULL, 'menu_parent_id' => 3, 'menu_sequence' => 34, 'created_by' =>  1],
            [ 'menu_id' => 33, 'menu_icon_id' => NULL, 'menu_parent_id' => 4, 'menu_sequence' => 35, 'created_by' =>  1],
            [ 'menu_id' => 34, 'menu_icon_id' => NULL, 'menu_parent_id' => 3, 'menu_sequence' => 36, 'created_by' =>  1],
            [ 'menu_id' => 35, 'menu_icon_id' => NULL, 'menu_parent_id' => 1, 'menu_sequence' => 37, 'created_by' =>  1],
            [ 'menu_id' => 35, 'menu_icon_id' => NULL, 'menu_parent_id' => 4, 'menu_sequence' => 38, 'created_by' =>  1],
            [ 'menu_id' => 36, 'menu_icon_id' => NULL, 'menu_parent_id' => 4, 'menu_sequence' => 39, 'created_by' =>  1],
            [ 'menu_id' => 37, 'menu_icon_id' => NULL, 'menu_parent_id' => 3, 'menu_sequence' => 41, 'created_by' =>  1],
            [ 'menu_id' => 38, 'menu_icon_id' => NULL, 'menu_parent_id' => 3, 'menu_sequence' => 42, 'created_by' =>  1],
            [ 'menu_id' => 39, 'menu_icon_id' => NULL, 'menu_parent_id' => 3, 'menu_sequence' => 43, 'created_by' =>  1],
            [ 'menu_id' => 40, 'menu_icon_id' => NULL, 'menu_parent_id' => 5, 'menu_sequence' => 44, 'created_by' =>  1]
        ];

        MenuStructure::insert($data);
    }
}

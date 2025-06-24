<?php

namespace Database\Seeders;

use App\Models\Settings\Menu;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MenuMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = array(
            ['id' => 1, 'menu_name' => 'Settings', 'menu_route' =>  NULL, 'created_by' => '1'],
            ['id' => 2, 'menu_name' => 'Menu Management', 'menu_route' =>  'menus', 'created_by' => '1'],
            ['id' => 3, 'menu_name' => 'Role Management', 'menu_route' =>  'roles', 'created_by' => '1'],
            ['id' => 4, 'menu_name' => 'Icon Management', 'menu_route' => 'icons', 'created_by' => '1'],
            ['id' => 5, 'menu_name' => 'User Management', 'menu_route' => 'users', 'created_by' => '1'],
            ['id' => 6, 'menu_name' => 'Menu Structure Management', 'menu_route' => 'menuStructure', 'created_by' => '1'],
            ['id' => 7, 'menu_name' => 'Connection Management', 'menu_route' => 'connections', 'created_by' => '1'],

            ['id' => 8, 'menu_name' => 'Prefix Management', 'menu_route' => 'prefix', 'created_by' => '1'],
            ['id' => 9, 'menu_name' => 'Setting QAD', 'menu_route' => NULL, 'created_by' => '1'],
            ['id' => 10, 'menu_name' => 'Item Management', 'menu_route' => 'items', 'created_by' => '1'],
            ['id' => 11, 'menu_name' => 'Location Management', 'menu_route' => 'locations', 'created_by' => '1'],
            ['id' => 12, 'menu_name' => 'Item Location Management', 'menu_route' => 'itemlocation', 'created_by' => '1'],
        );
        Menu::insert($data);
    }
}

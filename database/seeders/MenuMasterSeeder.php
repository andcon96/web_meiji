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
            ['id' => 1,  'menu_name' => 'Sales', 'menu_route' => NULL, 'has_approval' => 'No', 'created_by' => '1'],
            ['id' => 2,  'menu_name' => 'Production', 'menu_route' => NULL,'has_approval' => 'No','created_by' => '1'],
            ['id' => 3,  'menu_name' => 'Purchasing', 'menu_route' =>  NULL,'has_approval' => 'No','created_by' => '1'],
            ['id' => 4,  'menu_name' => 'Warehouse', 'menu_route' =>  NULL,'has_approval' => 'No','created_by' => '1'],
            ['id' => 5,  'menu_name' => 'Settings', 'menu_route' =>  NULL,'has_approval' => 'No','created_by' => '1'],
            ['id' => 6,  'menu_name' => 'Menu Management','menu_route' =>  'menus','has_approval' => 'No','created_by' => '1'],
            ['id' => 7,  'menu_name' => 'Role Management','menu_route' =>  'roles','has_approval' => 'No','created_by' => '1'],
            ['id' => 8,  'menu_name' => 'Domain Management','menu_route' =>  'domains','has_approval' => 'No','created_by' => '1'],
            ['id' => 9,  'menu_name' => 'Department Management', 'menu_route' => 'departments','has_approval' => 'No','created_by' => '1'],
            ['id' => 10, 'menu_name' =>  'Icon Management', 'menu_route' => 'icons','has_approval' => 'No','created_by' => '1'],
            ['id' => 11, 'menu_name' =>  'User management', 'menu_route' => 'users','has_approval' => 'No','created_by' => '1'],
            ['id' => 13, 'menu_name' =>  'Menu Structure Management', 'menu_route' => 'menuStructure','has_approval' => 'No','created_by' => '1'],
            ['id' => 14, 'menu_name' =>  'Sales Order','menu_route' =>  'salesOrder','has_approval' => 'No','created_by' => '1'],
            ['id' => 15, 'menu_name' =>  'User','menu_route' =>  NULL,'has_approval' => 'No','created_by' => '1'],
            ['id' => 16, 'menu_name' =>  'Menu','menu_route' =>  NULL,'has_approval' => 'No','created_by' => '1'],
            ['id' => 17, 'menu_name' =>  'Supplier Management','menu_route' =>  'suppliers','has_approval' => 'No','created_by' => '1'],
            ['id' => 18, 'menu_name' =>  'Item Management','menu_route' =>  'items','has_approval' => 'No','created_by' => '1'],
            ['id' => 19, 'menu_name' =>  'Connection Management','menu_route' =>  'connections','has_approval' => 'No','created_by' => '1'],
            ['id' => 20, 'menu_name' =>  'Approval','menu_route' =>  NULL,'has_approval' => 'No','created_by' => '1'],
            ['id' => 21, 'menu_name' =>  'Approval Code','menu_route' =>  'approvalCodes','has_approval' => 'No','created_by' => '1'],
            ['id' => 23, 'menu_name' =>  'Load Data','menu_route' =>  NULL,'has_approval' => 'No','created_by' => '1'],
            ['id' => 24, 'menu_name' =>  'Menu Access','menu_route' =>  'menuAccess','has_approval' => 'No','created_by' => '1'],
            ['id' => 25, 'menu_name' =>  'Prefix Management','menu_route' =>  'prefix','has_approval' => 'No','created_by' => '1'],
            ['id' => 26, 'menu_name' =>  'Purchase Requisition','menu_route' =>  'purchaseRequisition','has_approval' => 'Yes','created_by' => '1'],
            ['id' => 27, 'menu_name' =>  'Item Transfer','menu_route' =>  'itemTransfer','has_approval' => 'Yes','created_by' => '1'],
            ['id' => 28, 'menu_name' =>  'Planned Order','menu_route' =>  'plannedOrder','has_approval' => 'No','created_by' => '1'],
            ['id' => 29, 'menu_name' =>  'Picklist','menu_route' =>  'picklist','has_approval' => 'Yes','created_by' => '1'],
            ['id' => 30, 'menu_name' =>  'Labor Feedback','menu_route' =>  'laborFeedback','has_approval' => 'Yes','created_by' => '1'],
            ['id' => 31, 'menu_name' =>  'Build PO from PR','menu_route' =>  'buildPOFromPR','has_approval' => 'No','created_by' => '1'],
            ['id' => 32, 'menu_name' =>  'PO Print/Re-print','menu_route' =>  'poPrint','has_approval' => 'Yes','created_by' => '1'],
            ['id' => 33, 'menu_name' =>  'Stock In/Out Request','menu_route' =>  'stockRequest','has_approval' => 'Yes','created_by' => '1'],
            ['id' => 34, 'menu_name' =>  'PR Approval','menu_route' =>  'prApproval','has_approval' => 'No','created_by' => '1'],
            ['id' => 35, 'menu_name' =>  'Item Transfer Confirm','menu_route' =>  'itemTransferConfirm','has_approval' => 'No','created_by' => '1'],
            ['id' => 36, 'menu_name' =>  'Stock In/Out Approval','menu_route' =>  'stockRequestApproval','has_approval' => 'No','created_by' => '1'],
            ['id' => 37, 'menu_name' =>  'PO Reprint Approval','menu_route' =>  'poReprintApproval','has_approval' => 'No','created_by' => '1'],
            ['id' => 38, 'menu_name' =>  'Qxtend Log','menu_route' =>  'qxtendLog','has_approval' => 'No','created_by' => '1'],
            ['id' => 39, 'menu_name' =>  'Purchase Order','menu_route' =>  'purchaseOrder','has_approval' => 'No','created_by' => '1'],
            ['id' => 40, 'menu_name' =>  'Cost Control','menu_route' =>  'costControl','has_approval' => 'No','created_by' => '1'],
        );
        
        // dd($data);
        Menu::insert($data);
    }
}

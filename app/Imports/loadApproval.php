<?php

namespace App\Imports;

use App\Models\Settings\ApprovalCodeDet;
use App\Models\Settings\ApprovalCodeMstr;
use App\Models\Settings\Department;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class loadApproval implements ToCollection
{
    /**
    * @param Collection $collection
    */
    protected $menu_id;
    protected $domain_id;

    public function __construct($menu_id, $domain_id)
    {
        $this->menu_id = $menu_id;
        $this->domain_id = $domain_id;
    }

    public function collection(Collection $collection)
    {
        dd($collection);
        // foreach ($collection as $row) {
        //     $approvalCodeMstr = ApprovalCodeMstr::where('domain_id', $this->domain_id)
        //         ->where('department_id', $row[3])
        //         ->where('menu_id', $this->menu_id)
        //         ->first();

        //     $deptMaster = Department::where('id', $row[3])->first();

        //     if (!$approvalCodeMstr) {
        //         ApprovalCodeMstr::create([
        //             'domain_id' => $this->domain_id,
        //             'department_id' => $row[3],
        //             'menu_id' => $this->menu_id,
        //             'acm_code' => 'PR' . substr($deptMaster->department_desc, 4),
        //             'acm_desc' => 'PR Approval for department : ' . $deptMaster->department_code,
        //         ]);
        //     }

        //     if ($approvalCodeMstr) {
        //         $approvalCodeDetail = ApprovalCodeDet::where('acm_id', $approvalCodeMstr->id)
        //             ->first();
        //         if ($approvalCodeDetail) {
        //             $approvalCodeDetail->
        //         } else {

        //         }
        //     }
        // }
    }
}

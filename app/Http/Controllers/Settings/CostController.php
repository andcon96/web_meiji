<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\CostControlDet;
use App\Models\Settings\CostControlMstr;
use App\Models\Settings\Department;
use App\Models\Settings\Domain;
use App\Models\Settings\qxwsa;
use App\Services\ServerURL;
use App\Services\WSAServices;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class CostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $menuMaster = (new ServerURL())->currentURL($request);
        $costControl = CostControlMstr::with(['getDomain'])->groupBy('domain_id')->get('domain_id');

        return view('setting.costControl.index', compact('menuMaster', 'costControl'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $domains = Domain::orderBy('domain')->get();
        $departments = Department::orderBy('department_code')->get();

        return view('setting.costControl.create', compact('domains', 'departments'));
    }

    public function fetchItemGroup(Request $request)
    {
        $domain = $request->domain;
        $domainMaster = Domain::where('domain', $domain)->first();
        $wsa = qxwsa::where('domain_id', $domainMaster->id)->first();

        $wsaItemGroups = (new WSAServices())->wsaGetItemGroup($wsa, $domain);

        return $wsaItemGroups;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $domain = $request->domain;
        $detailDepartment = $request->detailDepartment;
        $user = Auth::user()->id;

        DB::beginTransaction();

        try {
            foreach ($detailDepartment as $key => $department) {
                $explodeData = explode('|', $key);
                $costControlMstr = CostControlMstr::where('domain_id', $domain)
                    ->where('ccm_prod_line', $explodeData[0])
                    ->where('ccm_group', $explodeData[1])
                    ->where('ccm_type', $explodeData[2])
                    ->where(function($sub) use ($explodeData) {
                        $sub->where('ccm_promo', $explodeData[3])
                            ->orWhereNull('ccm_promo');
                    })->first();

                if (!$costControlMstr) {
                    $costControlMstr = new CostControlMstr();
                    $costControlMstr->domain_id = $domain;
                    $costControlMstr->ccm_prod_line = $explodeData[0];
                    $costControlMstr->ccm_group = $explodeData[1];
                    $costControlMstr->ccm_type = $explodeData[2];
                    $costControlMstr->ccm_promo = $explodeData[3];
                    $costControlMstr->created_by = $user;
                    $costControlMstr->save();
                }

                $costControlDet = new CostControlDet();
                $costControlDet->ccm_id = $costControlMstr->id;
                $costControlDet->department_id = $department;
                $costControlDet->created_by = $user;
                $costControlDet->save();
            }

            DB::commit();
            toast('Successfully created cost control', 'success');
            return redirect()->route('costControl.index');
        } catch (Exception $err) {
            DB::rollBack();
            toast('Failed to create cost control', 'error');
            return redirect()->back();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $domain_id = $id;
        $user = Auth::user()->id;
        $domainMaster = Domain::where('id', $domain_id)->first();
        $wsa = qxwsa::where('domain_id', $domainMaster->id)->first();
        $departments = Department::orderBy('department_code')->get();

        $wsaItemGroups = (new WSAServices())->wsaGetItemGroup($wsa, $domainMaster->domain);

        if ($wsaItemGroups[0] == 'true') {

            DB::beginTransaction();

            try {
                foreach ($wsaItemGroups[1] as $itemGroup) {
                    // dd($itemGroup);
                    $costControlExists = CostControlMstr::where('domain_id', $domain_id)
                        ->where('ccm_prod_line', $itemGroup['t_pt_prod_line'])
                        ->where('ccm_group', $itemGroup['t_pt_group'])
                        ->where('ccm_type', $itemGroup['t_pt_part_type'])
                        ->where(function($sub) use ($itemGroup) {
                            $sub->where('ccm_promo', $itemGroup['t_pt_promo'])
                                ->orWhereNull('ccm_promo');
                        })
                        // ->where('ccm_promo', $itemGroup['t_pt_promo'])
                        ->first();

                    // dd($costControlExists);

                    if (!$costControlExists) {
                        $costControlMstr = new CostControlMstr();
                        $costControlMstr->domain_id = $domain_id;
                        $costControlMstr->ccm_prod_line = $itemGroup['t_pt_prod_line'];
                        $costControlMstr->ccm_group = $itemGroup['t_pt_group'];
                        $costControlMstr->ccm_type = $itemGroup['t_pt_part_type'];
                        $costControlMstr->ccm_promo = $itemGroup['t_pt_promo'];
                        $costControlMstr->created_by = $user;
                        $costControlMstr->save();

                        DB::commit();
                    }
                }
            } catch (Exception $err) {
                DB::rollBack();

                toast('Failed while trying to load item group', 'error');

                return redirect()->back();
            }
        }

        $costControl = CostControlMstr::with(['getCostControlDet'])
            ->where('domain_id', Session::get('domain'))
            ->orderBy('ccm_prod_line')
            ->orderBy('ccm_group')
            ->orderBy('ccm_type')
            ->orderBy('ccm_promo')
            ->get();

        return view('setting.costControl.edit', compact('departments', 'wsaItemGroups', 'domainMaster', 'costControl'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $domain_id = $request->domain_id;
        $detailDepartment = $request->detailDepartment;
        $user = Auth::user()->id;

        DB::beginTransaction();

        try {
            // Delete all detail first then delete the masters, then load all new one
            $costControlMstr = CostControlMstr::where('domain_id', $domain_id)->get();
            foreach ($costControlMstr as $controlMstr) {
                CostControlDet::where('ccm_id', $controlMstr->id)->delete();

                $controlMstr->delete();
            }

            foreach ($detailDepartment as $key => $department) {
                $explodeData = explode('|', $key);
                $costControlMstr = CostControlMstr::where('domain_id', $domain_id)
                    ->where('ccm_prod_line', $explodeData[0])
                    ->where('ccm_group', $explodeData[1])
                    ->where('ccm_type', $explodeData[2])
                    ->where(function ($sub) use ($explodeData) {
                        $sub->where('ccm_promo', $explodeData[3])
                            ->orWhereNull('ccm_promo');
                    })->first();

                if (!$costControlMstr) {
                    $costControlMstr = new CostControlMstr();
                    $costControlMstr->domain_id = $domain_id;
                    $costControlMstr->ccm_prod_line = $explodeData[0];
                    $costControlMstr->ccm_group = $explodeData[1];
                    $costControlMstr->ccm_type = $explodeData[2];
                    $costControlMstr->ccm_promo = $explodeData[3];
                    $costControlMstr->created_by = $user;
                    $costControlMstr->save();
                }

                $costControlDet = new CostControlDet();
                $costControlDet->ccm_id = $costControlMstr->id;
                $costControlDet->department_id = $department;
                $costControlDet->created_by = $user;
                $costControlDet->save();
            }

            DB::commit();

            toast('Successfully updated cost control', 'success');

        } catch (Exception $err) {
            DB::rollBack();
            dd($err);
            toast('Failed to update cost control', 'error');
        }

        return redirect()->route('costControl.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function deleteCostControl(Request $request)
    {

    }

    public function allowSeePrice(Request $request)
    {
        // dd($request->all());
        $domain_id = $request->domain_id;
        $prodLine = $request->prodLine;
        $group = $request->group;
        $partType = $request->partType;
        $promo = $request->promo;
        $department_id = $request->department_id;

        if ($prodLine == NULL) {
            $prodLine = '';
        }

        if ($group == NULL) {
            $group = '';
        }

        if ($partType == NULL) {
            $partType = '';
        }

        if ($promo == NULL) {
            $promo = '';
        }

        $result = 'No';

        if (Cache::has('allow_see_price_' . $domain_id . '_' . $department_id . '_' . $prodLine . '_' . $group . '_' . $partType . '_' . $promo)) {

            $result = Cache::get('allow_see_price_' . $domain_id . '_' . $department_id . '_' . $prodLine . '_' . $group . '_' . $partType . '_' . $promo);

        } else {
            $costControl = CostControlMstr::query()->with(['getCostControlDet' => function ($q) use ($department_id) {
                $q->where('department_id', $department_id);
            }])
                ->where('domain_id', $domain_id)
                ->where('ccm_prod_line', $prodLine)
                ->where('ccm_group', $group)
                ->where('ccm_type', $partType)
                ->where(function ($sub) use ($promo) {
                    $sub->where('ccm_promo', $promo)->orWhereNull('ccm_promo');
                })
                ->first();

            if ($costControl && $costControl->getCostControlDet->count() > 0) {
                $result = 'Yes';

                Cache::put('allow_see_price_' . $domain_id . '_' . $department_id . '_' . $prodLine . '_' . $group . '_' . $partType . '_' . $promo, $result, now()->addMinutes(10));
            }
        }

        return $result;
    }
}

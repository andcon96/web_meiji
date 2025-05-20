<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\Department;
use App\Models\Settings\Domain;
use App\Models\Settings\qxwsa;
use App\Models\Settings\RetailPriceControlDet;
use App\Models\Settings\RetailPriceControlMstr;
use App\Services\ServerURL;
use App\Services\WSAServices;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class RetailPriceController extends Controller
{
    public function index(Request $request)
    {
        $menuMaster = (new ServerURL())->currentURL($request);
        $retailPrice = RetailPriceControlMstr::with(['getDomain'])->groupBy('domain_id')->get('domain_id');

        return view('setting.retailPriceControl.index', compact('menuMaster', 'retailPrice'));
    }

    public function create()
    {
        $domains = Domain::orderBy('domain')->get();
        $departments = Department::orderBy('department_code')->get();

        return view('setting.retailPriceControl.create', compact('domains', 'departments'));
    }

    public function fetchItemGroupForRetail(Request $request)
    {
        $domain = $request->domain;
        $domainMaster = Domain::where('domain', $domain)->first();
        $wsa = qxwsa::where('domain_id', $domainMaster->id)->first();

        $wsaItemGroups = (new WSAServices())->wsaGetItemGroup($wsa, $domain);

        return $wsaItemGroups;
    }

    public function store(Request $request)
    {
        $domain = $request->domain;
        $detailDepartment = $request->detailDepartment;
        $user = Auth::user()->id;

        DB::beginTransaction();

        try {
            foreach ($detailDepartment as $key => $department) {
                $explodeData = explode('|', $key);
                $retailPriceControlMstr = RetailPriceControlMstr::where('domain_id', $domain)
                    ->where('rpcm_prod_line', $explodeData[0])
                    ->where('rpcm_group', $explodeData[1])
                    ->where('rpcm_type', $explodeData[2])
                    ->where(function ($sub) use ($explodeData) {
                        $sub->where('rpcm_promo', $explodeData[3])
                            ->orWhereNull('rpcm_promo');
                    })->first();

                if (!$retailPriceControlMstr) {
                    $retailPriceControlMstr = new RetailPriceControlMstr();
                    $retailPriceControlMstr->domain_id = $domain;
                    $retailPriceControlMstr->rpcm_prod_line = $explodeData[0];
                    $retailPriceControlMstr->rpcm_group = $explodeData[1];
                    $retailPriceControlMstr->rpcm_type = $explodeData[2];
                    $retailPriceControlMstr->rpcm_promo = $explodeData[3];
                    $retailPriceControlMstr->created_by = $user;
                    $retailPriceControlMstr->save();
                }

                $retailPriceControlDet = new RetailPriceControlDet();
                $retailPriceControlDet->rpcm_id = $retailPriceControlMstr->id;
                $retailPriceControlDet->department_id = $department;
                $retailPriceControlDet->created_by = $user;
                $retailPriceControlDet->save();
            }

            DB::commit();
            toast('Successfully created cost control', 'success');
            return redirect()->route('retailPriceControl.index');
        } catch (Exception $err) {
            DB::rollBack();
            dd($err);
            toast('Failed to create retail price control', 'error');
            return redirect()->back();
        }
    }

    public function deleteRetailPriceControl()
    {

    }

    public function allowRetailPrice(Request $request)
    {
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

        $retailPriceControl = RetailPriceControlMstr::query()->with(['getRetailPriceControlDet' => function ($q) use ($department_id) {
            $q->where('department_id', $department_id);
        }])
            ->where('domain_id', $domain_id)
            ->where('rpcm_prod_line', $prodLine)
            ->where('rpcm_group', $group)
            ->where('rpcm_type', $partType)
            ->where('rpcm_promo', $promo)
            ->first();

        if ($retailPriceControl && $retailPriceControl->getRetailPriceControlDet->count() > 0) {
            $result = 'Yes';
        }

        return $result;
    }

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
                    $retailPriceControlMstr = RetailPriceControlMstr::where('domain_id', $domain_id)
                        ->where('rpcm_prod_line', $itemGroup['t_pt_prod_line'])
                        ->where('rpcm_group', $itemGroup['t_pt_group'])
                        ->where('rpcm_type', $itemGroup['t_pt_part_type'])
                        ->where(function ($sub) use ($itemGroup) {
                            $sub->where('rpcm_promo', $itemGroup['t_pt_promo'])
                            ->orWhereNull('rpcm_promo');
                        })
                        ->first();

                    if (!$retailPriceControlMstr) {
                        $retailPriceControlMstr = new RetailPriceControlMstr();
                        $retailPriceControlMstr->domain_id = $domain_id;
                        $retailPriceControlMstr->rpcm_prod_line = $itemGroup['t_pt_prod_line'];
                        $retailPriceControlMstr->rpcm_group = $itemGroup['t_pt_group'];
                        $retailPriceControlMstr->rpcm_type = $itemGroup['t_pt_part_type'];
                        $retailPriceControlMstr->rpcm_promo = $itemGroup['t_pt_promo'];
                        $retailPriceControlMstr->created_by = $user;
                        $retailPriceControlMstr->save();

                        DB::commit();
                    }
                }
            } catch (Exception $err) {
                DB::rollBack();

                toast('Failed while trying to load item group', 'error');

                return redirect()->back();
            }

            $retailPrice = RetailPriceControlMstr::with(['getRetailPriceControlDet'])
            ->where('domain_id', Session::get('domain'))
            ->orderBy('rpcm_prod_line')
            ->orderBy('rpcm_group')
            ->orderBy('rpcm_type')
            ->orderBy('rpcm_promo')
            ->get();

            return view('setting.retailPriceControl.edit', compact('departments', 'wsaItemGroups', 'domainMaster', 'retailPrice'));
        }
    }

    public function update(Request $request)
    {
        $domain_id = $request->domain_id;
        $detailDepartment = $request->detailDepartment;
        $user = Auth::user()->id;

        DB::beginTransaction();

        try {
            // Delete all detail first then delete the masters, then load all new one
            $retailPriceControlMstr = RetailPriceControlMstr::where('domain_id', $domain_id)->get();
            foreach ($retailPriceControlMstr as $retailPrice) {
                RetailPriceControlDet::where('rpcm_id', $retailPrice->id)->delete();

                $retailPrice->delete();
            }

            foreach ($detailDepartment as $key => $department) {
                $explodeData = explode('|', $key);
                $retailPriceControlMstr = RetailPriceControlMstr::where('domain_id', $domain_id)
                    ->where('rpcm_prod_line', $explodeData[0])
                    ->where('rpcm_group', $explodeData[1])
                    ->where('rpcm_type', $explodeData[2])
                    ->where(function ($sub) use ($explodeData) {
                        $sub->where('rpcm_promo', $explodeData[3])
                            ->orWhereNull('rpcm_promo');
                    })->first();

                if (!$retailPriceControlMstr) {
                    $retailPriceControlMstr = new RetailPriceControlMstr();
                    $retailPriceControlMstr->domain_id = $domain_id;
                    $retailPriceControlMstr->rpcm_prod_line = $explodeData[0];
                    $retailPriceControlMstr->rpcm_group = $explodeData[1];
                    $retailPriceControlMstr->rpcm_type = $explodeData[2];
                    $retailPriceControlMstr->rpcm_promo = $explodeData[3];
                    $retailPriceControlMstr->created_by = $user;
                    $retailPriceControlMstr->save();
                }

                $retailPriceControlDet = new RetailPriceControlDet();
                $retailPriceControlDet->rpcm_id = $retailPriceControlMstr->id;
                $retailPriceControlDet->department_id = $department;
                $retailPriceControlDet->created_by = $user;
                $retailPriceControlDet->save();
            }

            DB::commit();

            toast('Successfully updated retail price control', 'success');
        } catch (Exception $err) {
            DB::rollBack();
            dd($err);
            toast('Failed to update retail price control', 'error');
        }

        return redirect()->route('retailPriceControl.index');
    }
}

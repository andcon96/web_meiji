<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\Domain;
use App\Models\Settings\Prefix;
use App\Services\ServerURL;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrefixController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $menuMaster = (new ServerURL())->currentURL($request);
        $prefixes = Prefix::with(['getDomain' => function ($query) {
            $query->orderBy('domain');
        }])->get();

        return view('setting.prefix.index', compact('menuMaster', 'prefixes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $domains = Domain::orderBy('domain')->get();

        return view('setting.prefix.create', compact('domains'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $domain_id = $request->domain_id;
        $prefixYear = $request->prefixYear;
        $prefixSO = $request->prefixSO;
        $prefixPR = $request->prefixPR;
        $prefixPO = $request->prefixPO;
        $prefixPOSOOtomatis = $request->prefixPOSOOtomatis;
        $prefixSOPOOtomatis = $request->prefixSOPOOtomatis;
        $prefixSOPOMonthly = $request->prefixSOPOMonthly;
        $prefixItemTransfer = $request->prefixItemTransfer;
        $prefixStockRequest = $request->prefixStockRequest;
        $prefixPicklist = $request->prefixPicklist;
        $prefixComplain = $request->prefixComplain;
        $prefixSOErb = $request->prefixSOERB;
        $prefixSOErbResult = $request->prefixSOERBResult;

        $runningNbrSO = $request->runningNbrSO;
        $runningNbrPR = $request->runningNbrPR;
        $runningNbrPO = $request->runningNbrPO;
        $runningNbrPOSOOtomatis = $request->runningNbrPOSOOtomatis;
        $runningNbrItemTransfer = $request->runningNbrItemTransfer;
        $runningNbrStockRequest = $request->runningNbrStockRequest;
        $runningNbrPicklist = $request->runningNbrPicklist;
        $runningNbrComplain = $request->runningNbrComplain;
        $runningNbrErb = $request->runningNbrSOERB;
        $runningNbrErbResult = $request->runningNbrSOERBResult;

        DB::beginTransaction();

        try {
            // Cek kalau domainnya sudah ada
            $domainPrefixExists = Prefix::where('domain_id', $domain_id)->first();
            if ($domainPrefixExists) {
                toast('Prefix for this domain already exists', 'info');

                return redirect()->back()->withInput();
            }

            $domainPrefix = new Prefix();
            $domainPrefix->domain_id = $domain_id;
            $domainPrefix->prefix_year = $prefixYear;
            $domainPrefix->prefix_so_po_monthly = $prefixSOPOMonthly;
            $domainPrefix->prefix_so = $prefixSO;
            $domainPrefix->prefix_pr = $prefixPR;
            $domainPrefix->prefix_po = $prefixPO;
            $domainPrefix->prefix_po_for_so_auto = $prefixPOSOOtomatis;
            $domainPrefix->prefix_so_auto = $prefixSOPOOtomatis;
            $domainPrefix->prefix_item_transfer = $prefixItemTransfer;
            $domainPrefix->prefix_stock_request = $prefixStockRequest;
            $domainPrefix->prefix_picklist = $prefixPicklist;
            $domainPrefix->prefix_complain = $prefixComplain;
            $domainPrefix->prefix_so_ERB = $prefixSOErb;
            $domainPrefix->prefix_so_ERB_result = $prefixSOErbResult;
            $domainPrefix->running_nbr_so = $runningNbrSO;
            $domainPrefix->running_nbr_pr = $runningNbrPR;
            $domainPrefix->running_nbr_po = $runningNbrPO;
            $domainPrefix->running_nbr_po_for_so_auto = $runningNbrPOSOOtomatis;
            $domainPrefix->running_nbr_item_transfer = $runningNbrItemTransfer;
            $domainPrefix->running_nbr_stock_request = $runningNbrStockRequest;
            $domainPrefix->running_nbr_picklist = $runningNbrPicklist;
            $domainPrefix->running_nbr_so_ERB = $runningNbrErb;
            $domainPrefix->running_nbr_so_ERB_result = $runningNbrErbResult;
            $domainPrefix->running_nbr_complain = $runningNbrComplain;

            $domainPrefix->save();

            DB::commit();
            toast('Prefix saved successfully', 'success');

        } catch (Exception $err) {
            DB::rollBack();

            toast('Failed to save prefix', 'error');
        }

        return redirect()->back();
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
    public function edit(string $id)
    {
        $prefix = Prefix::where('id', $id)->first();

        return view('setting.prefix.edit', compact('prefix'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {

        $id = $request->u_id;
        $prefixYear = $request->prefixYear;
        $prefixSOPOMonthly = $request->prefixSOPOMonthly;
        $prefixSO = $request->prefixSO;
        $prefixPR = $request->prefixPR;
        $prefixPO = $request->prefixPO;
        $prefixPOSOOtomatis = $request->prefixPOSOOtomatis;
        $prefixPOMemo = $request->prefixPOMemo;
        $prefixSOErb = $request->prefixSOERB;
        $prefixSOErbResult = $request->prefixSOERBResult;
        $prefixItemTransfer = $request->prefixItemTransfer;
        $prefixStockRequest = $request->prefixStockRequest;
        $prefixPicklist = $request->prefixPicklist;
        $prefixComplain = $request->prefixComplain;
        $runningNbrSO = $request->runningNbrSO;
        $runningNbrPR = $request->runningNbrPR;
        $runningNbrPO = $request->runningNbrPO;
        $runningNbrPOSOOtomatis = $request->runningNbrPOSOOtomatis;
        $runningNbrPOMemo = $request->runningNbrPOMemo;
        $runningNbrItemTransfer = $request->runningNbrItemTransfer;
        $runningNbrStockRequest = $request->runningNbrStockRequest;
        $runningNbrPicklist = $request->runningNbrPicklist;
        $runningNbrErb = $request->runningNbrSOERB;
        $runningNbrErbResult = $request->runningNbrSOERBResult;
        $runningNbrComplain = $request->runningNbrComplain;

        DB::beginTransaction();

        try {
            $prefix = Prefix::where('id', $id)->first();
            $prefix->prefix_year = $prefixYear;
            $prefix->prefix_so_po_monthly = $prefixSOPOMonthly;
            $prefix->prefix_so = $prefixSO;
            $prefix->prefix_pr = $prefixPR;
            $prefix->prefix_po = $prefixPO;
            $prefix->prefix_po_for_so_auto = $prefixPOSOOtomatis;
            $prefix->prefix_po_memo = $prefixPOMemo;
            $prefix->prefix_item_transfer = $prefixItemTransfer;
            $prefix->prefix_stock_request = $prefixStockRequest;
            $prefix->prefix_picklist = $prefixPicklist;
            $prefix->prefix_so_ERB = $prefixSOErb;
            $prefix->prefix_so_ERB_result = $prefixSOErbResult;
            $prefix->prefix_complain = $prefixComplain;
            $prefix->running_nbr_so = $runningNbrSO;
            $prefix->running_nbr_pr = $runningNbrPR;
            $prefix->running_nbr_po = $runningNbrPO;
            $prefix->running_nbr_po_for_so_auto = $runningNbrPOSOOtomatis;
            $prefix->running_nbr_po_memo = $runningNbrPOMemo;
            $prefix->running_nbr_item_transfer = $runningNbrItemTransfer;
            $prefix->running_nbr_stock_request = $runningNbrStockRequest;
            $prefix->running_nbr_picklist = $runningNbrPicklist;
            $prefix->running_nbr_so_ERB = $runningNbrErb;
            $prefix->running_nbr_so_ERB_result = $runningNbrErbResult;
            $prefix->running_nbr_complain = $runningNbrComplain;
            $prefix->save();

            DB::commit();

            toast('Prefix updated successfully', 'success');
        } catch (Exception $err) {
            DB::rollBack();
            dd($err);
            toast('Failed to update prefix', 'error');
        }

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete(Request $request)
    {
        $id = $request->d_id;

        DB::beginTransaction();

        try {
            Prefix::where('id', $id)->delete();

            DB::commit();

            toast('Prefix deleted successfully', 'success');
        } catch (\Exception $err) {
            DB::rollBack();

            toast('Failed to delete prefix', 'error');
        }

        return redirect()->back();
    }
}

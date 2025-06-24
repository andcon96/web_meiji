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
        $prefixes = Prefix::get();

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
        $prefixReceipt = $request->prefixReceipt;
        $prefixBukuPenerimaan = $request->prefixBukuPenerimaan;

        $runningNbrReceipt = $request->runningNbrReceipt;
        $runningNbrBukuPenerimaan = $request->runningNbrBukuPenerimaan;

        DB::beginTransaction();

        try {
            $domainPrefix = new Prefix();
            $domainPrefix->prefix_receipt = $prefixReceipt;
            $domainPrefix->prefix_buku_penerimaan = $prefixBukuPenerimaan;
            $domainPrefix->running_nbr_receipt = $runningNbrReceipt;
            $domainPrefix->running_nbr_buku_penerimaan = $runningNbrBukuPenerimaan;

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
        $prefixReceipt = $request->prefixReceipt;
        $prefixBukuPenerimaan = $request->prefixBukuPenerimaan;

        $runningNbrReceipt = $request->runningNbrReceipt;
        $runningNbrBukuPenerimaan = $request->runningNbrBukuPenerimaan;

        DB::beginTransaction();

        try {
            $prefix = Prefix::where('id', $id)->first();
            $prefix->prefix_receipt = $prefixReceipt;
            $prefix->prefix_buku_penerimaan = $prefixBukuPenerimaan;
            $prefix->running_nbr_receipt = $runningNbrReceipt;
            $prefix->running_nbr_buku_penerimaan = $runningNbrBukuPenerimaan;
            $prefix->save();

            DB::commit();

            toast('Prefix updated successfully', 'success');
        } catch (Exception $err) {
            DB::rollBack();

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

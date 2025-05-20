<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\Domain;
use App\Services\ServerURL;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DomainController extends Controller
{
    public function index(Request $request)
    {
        $menuMaster = (new ServerURL())->currentURL($request);

        $domains = Domain::orderBy('domain_desc')->get();

        return view('setting.domain.index', compact('domains', 'menuMaster'));
    }

    public function create(Request $request)
    {
        return view('setting.domain.create');
    }

    public function edit($id)
    {
        $domain = Domain::where('id', $id)->first();

        return view('setting.domain.edit', compact('domain'));
    }

    public function store(Request $request)
    {
        $domainCode = $request->domainCode;
        $domainDesc = $request->domainDesc;

        // Cek domain nya udah ada atau belum
        $domainExists = Domain::where('domain', $domainCode)->first();
        if ($domainExists) {
            toast('Domain already exists', 'info');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();

        try {
            $domain = new Domain();
            $domain->domain = $domainCode;
            $domain->domain_desc = $domainDesc;
            $domain->save();

            DB::commit();

            toast('Domain saved successfully', 'success');
        } catch (\Exception $err) {
            DB::rollBack();

            toast('Failed to save domain', 'error');
        }

        return redirect()->back();
    }

    public function update(Request $request)
    {
        $id = $request->u_id;
        $domainDesc = $request->domainDesc;

        DB::beginTransaction();

        try {
            $domain = Domain::where('id', $id)->first();
            $domain->domain_desc = $domainDesc;
            $domain->save();

            DB::commit();

            toast('Domain updated successfully', 'success');
        } catch (\Exception $err) {
            DB::rollBack();

            toast('Failed to update domain', 'error');
        }
        
        return redirect()->route('domains.index');
    }

    public function delete(Request $request)
    {
        $id = $request->d_id;

        DB::beginTransaction();

        try {
            Domain::where('id', $id)->delete();

            DB::commit();

            toast('Domain deleted successfully', 'success');
        } catch (\Exception $err) {
            DB::rollBack();

            toast('Failed to delete domain', 'error');
        }

        return redirect()->back();
    }
}

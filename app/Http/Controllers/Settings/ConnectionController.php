<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\Domain;
use App\Models\Settings\qxwsa;
use App\Services\ServerURL;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class ConnectionController extends Controller
{
    public function index(Request $request)
    {
        $menuMaster = (new ServerURL())->currentURL($request);
        $userDomain = Session::get('domain');
        $connections = qxwsa::with(['getDomain' => function ($query) {
            $query->orderBy('domain');
        }])->get();

        return view('setting.connection.index', compact('userDomain', 'menuMaster', 'connections'));
    }

    public function create(Request $request)
    {
        $domains = Domain::orderBy('domain')->get();

        return view('setting.connection.create', compact('domains'));
    }

    public function edit($id)
    {
        $connection = qxwsa::where('id', $id)->first();

        return view('setting.connection.edit', compact('connection'));
    }

    public function store(Request $request)
    {
        $domain_id = $request->domain_id;
        $wsaURL = $request->wsaURL;
        $wsaPath = $request->wsaPath;
        $qxURL = $request->qxURL;
        $qxPath = $request->qxPath;

        $currentUser = Auth::user()->id;

        // Cek connection untuk domain itu udah ada atau belum
        $connectionExists = qxwsa::where('domain_id', $domain_id)->first();

        $domain = Domain::where('id', $domain_id)->first();
        if ($connectionExists) {
            toast('Connection for ' . $domain->domain . ' already exists', 'info');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();

        try {
            $connection = new qxwsa();
            $connection->domain_id = $domain_id;
            $connection->wsa_url = $wsaURL;
            $connection->wsa_path = $wsaPath;
            $connection->qx_url = $qxURL;
            $connection->qx_path = $qxPath;
            $connection->created_by = $currentUser;

            $connection->save();

            DB::commit();
            
            toast('Connection saved successfully', 'success');

            return redirect()->back();
        } catch (\Exception $err) {
            DB::rollback();

            toast('Failed to save connection settings', 'error');

            return redirect()->back()->withInput();
        }
    }

    public function update(Request $request)
    {
        $id = $request->u_id;
        $domain_id = $request->domain_id;
        $wsaURL = $request->wsaURL;
        $wsaPath = $request->wsaPath;
        $qxURL = $request->qxURL;
        $qxPath = $request->qxPath;

        $currentUser = Auth::user()->id;

        // Cek connection untuk domain itu udah ada atau belum
        $connectionExists = qxwsa::where('domain_id', $domain_id)->where('id', '!=', $id)->first();
        if ($connectionExists) {
            $domain = Domain::where('id', $domain_id)->first();
            toast('Connection for ' . $domain->domain . ' already exists', 'info');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();

        try {
            $connection = qxwsa::where('id', $id)->first();
            $connection->domain_id = $domain_id;
            $connection->wsa_url = $wsaURL;
            $connection->wsa_path = $wsaPath;
            $connection->qx_url = $qxURL;
            $connection->qx_path = $qxPath;
            if ($connection->isDirty()) {
                $connection->updated_by = $currentUser;
                $connection->save();

                DB::commit();

                toast('Connection settings updated successfully', 'success');
            } else {
                toast('No changes were made', 'info');
            }
        } catch (\Exception $err) {
            DB::rollBack();

            toast('Failed to update connection settings', 'error');
        }
        return redirect()->back();
    }

    public function delete(Request $request)
    {
        $id = $request->d_id;

        DB::beginTransaction();

        try {
            qxwsa::where('id', $id)->delete();

            DB::commit();

            toast('Connection settings deleted successfully', 'success');
        } catch (\Exception $err) {
            DB::rollBack();

            toast('Failed to delete connection setting', 'error');
        }

        return redirect()->back();
    }
}

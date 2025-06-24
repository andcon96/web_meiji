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
        $wsaURL = $request->wsaURL;
        $wsaPath = $request->wsaPath;
        $qxURL = $request->qxURL;

        DB::beginTransaction();

        try {
            $connection = new qxwsa();
            $connection->wsa_url = $wsaURL;
            $connection->wsa_path = $wsaPath;
            $connection->qx_url = $qxURL;
            $connection->save();

            DB::commit();

            toast('Connection saved successfully', 'success');

            return redirect()->back();
        } catch (\Exception $err) {
            DB::rollback();

            toast('Failed to save connection settings', 'error');
            dd($err);
            return redirect()->back()->withInput();
        }
    }

    public function update(Request $request)
    {
        $id = $request->u_id;
        $wsaURL = $request->wsaURL;
        $wsaPath = $request->wsaPath;
        $qxURL = $request->qxURL;


        DB::beginTransaction();

        try {
            $connection = qxwsa::where('id', $id)->first();
            $connection->wsa_url = $wsaURL;
            $connection->wsa_path = $wsaPath;
            $connection->qx_url = $qxURL;
            if ($connection->isDirty()) {
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

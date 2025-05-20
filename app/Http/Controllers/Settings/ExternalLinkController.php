<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\ExternalLink;
use App\Services\ServerURL;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExternalLinkController extends Controller
{
    public function index(Request $request)
    {
        $menuMaster = (new ServerURL())->currentURL($request);

        $externalLink = ExternalLink::first();

        return view('setting.externalLink.index', compact('externalLink'));
    }

    public function store(Request $request)
    {
        $externalLink = $request->externalLink;
        $user = Auth::user()->id;

        DB::beginTransaction();

        try {
            $link = ExternalLink::first();
            if ($link) {
                $link->external_link = $externalLink;
                $link->updated_by = $user;
                $link->save();
            } else {
                $link = new ExternalLink();
                $link->external_link = $externalLink;
                $link->created_by = $user;
                $link->save();
            }

            DB::commit();

            toast('Successfully updated external link', 'success');
        } catch (Exception $err) {
            DB::rollBack();
            dd($err);

            toast('Failed to update external link', 'error');
            return redirect()->back();
        }

        return redirect()->back();
    }
}

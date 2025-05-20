<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\EmailForPOSOMonthly;
use App\Services\ServerURL;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MonthlyPOSOEmailController extends Controller
{
    public function index(Request $request)
    {
        $menuMaster = (new ServerURL())->currentURL($request);
        $emailMonthly = EmailForPOSOMonthly::first();

        return view('setting.emailMonthlyPOSO.index', compact('emailMonthly'));
    }

    public function store(Request $request)
    {
        $emailRISIS = $request->emailRISIS;
        $emailSilvador = $request->emailSilvador;
        $user = Auth::user()->id;

        DB::beginTransaction();

        try {
            $emailMonthly = EmailForPOSOMonthly::first();
            if (!$emailMonthly) {
                $emailMonthly = new EmailForPOSOMonthly();
                $emailMonthly->created_by = $user;
            }
            $emailMonthly->email_po_from_risis = $emailRISIS;
            $emailMonthly->email_so_from_silvador = $emailSilvador;
            $emailMonthly->updated_by = $user;
            $emailMonthly->save();

            DB::commit();

            toast('Successfully saved monthly email', 'success');
        } catch (Exception $err) {
            DB::rollBack();

            toast('Failed to save monthly email', 'error');
        }

        return redirect()->back();
    }
}

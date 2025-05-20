<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\Domain;
use App\Models\Settings\FinanceEmailDet;
use App\Models\Settings\FinanceEmailMstr;
use App\Models\Settings\User;
use App\Services\ServerURL;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinanceEmailController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $menuMaster = (new ServerURL())->currentURL($request);

        $financeEmailHeader = FinanceEmailMstr::with(['getDomain'])->get();

        return view('setting.financeEmail.index', compact('menuMaster', 'financeEmailHeader'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $domains = Domain::get();
        $users = User::get();

        return view('setting.financeEmail.create', compact('domains', 'users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request->all());
        $domain_id = $request->domain;
        $emails = $request->financeEmails;

        DB::beginTransaction();

        try {
            if (count($emails) > 0) {
                // Save to finance email header
                $financeEmailHeader = new FinanceEmailMstr();
                $financeEmailHeader->domain_id = $domain_id;
                $financeEmailHeader->created_by = Auth::user()->id;
                $financeEmailHeader->save();

                foreach ($emails as $email) {
                    $financeEmailDetail = new FinanceEmailDet();
                    $financeEmailDetail->fem_id = $financeEmailHeader->save();
                    $financeEmailDetail->fed_email = $email;
                    $financeEmailDetail->created_by = Auth::user()->id;
                    $financeEmailDetail->save();
                }

                DB::commit();

                toast('Successfully created finance email', 'success');

                return redirect()->route('financeEmail.index');
            } else {
                toast('No emails registered', 'error');

                return redirect()->back();
            }
        } catch (Exception $err) {
            DB::rollback();

            // dd($err);
            toast('Failed to create finance email', 'error');

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
    public function edit(string $id)
    {
        $domains = Domain::get();
        $users = User::get();
        $fem = FinanceEmailMstr::with(['getDomain', 'getFinanceEmails'])->where('id', $id)->first();

        return view('setting.financeEmail.edit', compact('domains', 'users', 'fem'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // dd($request->all(), $id);
        $financeEmails = $request->financeEmails;

        if (count($financeEmails) < 1) {
            toast('No emails registered', 'error');

            return redirect()->back();
        }

        DB::beginTransaction();

        try {
            // Delete emails that is not listed.
            $unlistedEmails = FinanceEmailDet::where('fem_id', $id)
                ->whereNotIn('fed_email', $financeEmails)
                ->get();

            if ($unlistedEmails->count()) {
                foreach ($unlistedEmails as $unlistedEmail) {
                    $unlistedEmail->delete();
                }
            }

            // Add new emails
            $newEmails = array_diff($financeEmails, FinanceEmailDet::where('fem_id', $id)->pluck('fed_email')->toArray());

            foreach ($newEmails as $newEmail) {
                $financeEmailDetail = new FinanceEmailDet();
                $financeEmailDetail->fem_id = $id;
                $financeEmailDetail->fed_email = $newEmail;
                $financeEmailDetail->created_by = Auth::user()->id;
                $financeEmailDetail->save();
            }

            // Update finance email header
            $financeEmailHeader = FinanceEmailMstr::find($id);
            $financeEmailHeader->updated_by = Auth::user()->id;
            $financeEmailHeader->save();

            DB::commit();

            toast('Successfully updated finance emails', 'success');

            return redirect()->route('financeEmail.index');
        } catch (Exception $err) {
            DB::rollBack();

            toast('Failed to update finance emails', 'error');

            return redirect()->back();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function deleteFinanceEmail(Request $request)
    {
        dd($request->all());
    }
}

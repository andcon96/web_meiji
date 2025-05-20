<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\BuyerDet;
use App\Models\Settings\BuyerMstr;
use App\Models\Settings\Department;
use App\Models\Settings\User;
use App\Services\ServerURL;
use Exception;
use Illuminate\Contracts\Session\Session as SessionSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class BuyerManagementController extends Controller
{
    public function index(Request $request)
    {
        $menuMaster = (new ServerURL())->currentURL($request);

        $buyerMstr = BuyerMstr::with('getBuyerDet')->get();

        return view('setting.buyer.index', compact('menuMaster', 'buyerMstr'));
    }

    public function create(Request $request)
    {
        $users = User::where('domain_id', Session::get('domain'))->get();
        $deparments = Department::get();

        return view('setting.buyer.create', compact('users', 'deparments'));
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $domain_id = Session::get('domain');
        $type = $request->type;
        $buyerBy = $request->buyerBy;
        $buyers = $request->buyers;
        $departments = $request->departments;

        DB::beginTransaction();

        try {
            $buyerMstr = new BuyerMstr();
            $buyerMstr->buyer_for = $type;
            $buyerMstr->buyer_by = $buyerBy;
            $buyerMstr->save();

            if (isset($buyers) && count($buyers) > 0) {
                foreach ($buyers as $buyer) {
                    $buyerDet = new BuyerDet();
                    $buyerDet->buyer_mstr_id = $buyerMstr->id;
                    $buyerDet->buyer_id = $buyer;
                    $buyerDet->save();
                }
            }

            if (isset($departments) && count($departments) > 0) {
                foreach ($departments as $department) {
                    // Get users from the department
                    $userDept = User::where('domain_id', $domain_id)
                        ->where('department_id', $department)
                        ->get();

                    if ($userDept->count() > 0) {
                        foreach ($userDept as $user) {
                            $newBuyer = new BuyerDet();
                            $newBuyer->buyer_mstr_id = $buyerMstr->id;
                            $newBuyer->buyer_id = $user->id;
                            $newBuyer->save();
                        }
                    }
                }
            }

            DB::commit();

            toast('Successfully created a new buyer', 'success');

            return redirect()->route('buyerManagement.index');
        } catch (Exception $err) {
            DB::rollBack();
            dd($err);

            toast('Failed to create buyer', 'error');
            return redirect()->back()->withInput();
        }
    }

    public function edit($id)
    {
        $buyer = BuyerMstr::with(['getBuyerDet.getUser'])->where('id', $id)->first();
        $users = User::where('domain_id', Session::get('domain'))->get();
        $departments = Department::get();

        return view('setting.buyer.edit', compact('buyer', 'users', 'departments'));
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());
        $domain_id = Session::get('domain');
        $type = $request->type;
        $buyerBy = $request->buyerBy;
        $buyers = $request->buyers;
        $departments = $request->departments;

        DB::beginTransaction();

        try {
            $buyerMstr = BuyerMstr::with(['getBuyerDet'])->where('id', $id)->first();
            $buyerMstr->buyer_for = $type;
            $buyerMstr->buyer_by = $buyerBy;
            $buyerMstr->save();

            if ($buyerMstr->getBuyerDet->count() > 0) {
                foreach ($buyerMstr->getBuyerDet as $buyerDetail) {
                    $buyerDetail->delete();
                }
            }

            // foreach ($buyers as $buyer) {
            //     $buyerDet = new BuyerDet();
            //     $buyerDet->buyer_mstr_id = $buyerMstr->id;
            //     $buyerDet->buyer_id = $buyer;
            //     $buyerDet->save();
            // }

            if (isset($buyers) && count($buyers) > 0) {
                foreach ($buyers as $buyer) {
                    $buyerDet = new BuyerDet();
                    $buyerDet->buyer_mstr_id = $buyerMstr->id;
                    $buyerDet->buyer_id = $buyer;
                    $buyerDet->save();
                }
            }

            if (isset($departments) && count($departments) > 0) {
                foreach ($departments as $department) {
                    // Get users from the department
                    $userDept = User::where('domain_id', $domain_id)
                        ->where('department_id', $department)
                        ->get();

                    if ($userDept->count() > 0) {
                        foreach ($userDept as $user) {
                            $newBuyer = new BuyerDet();
                            $newBuyer->buyer_mstr_id = $buyerMstr->id;
                            $newBuyer->buyer_id = $user->id;
                            $newBuyer->save();
                        }
                    }
                }
            }

            DB::commit();
            toast('Successfully updated buyers', 'success');

            return redirect()->route('buyerManagement.index');
        } catch (Exception $err) {
            DB::rollBack();
            dd($err);

            toast('Failed to update buyer', 'error');
            return redirect()->back()->withInput();
        }
    }

    public function deleteBuyer(Request $request)
    {
        $id = $request->d_id;
        DB::beginTransaction();

        try {
            $buyerMstr = BuyerMstr::with(['getBuyerDet'])->where('id', $id)->first();

            if ($buyerMstr->getBuyerDet->count() > 0) {
                foreach ($buyerMstr->getBuyerDet as $buyerDet) {
                    $buyerDet->delete();
                }
            }

            $buyerMstr->delete();

            DB::commit();
            toast('Successfully deleted buyer', 'success');
        } catch (Exception $err) {
            DB::rollBack();

            toast('Failed to delete buyer', 'error');
        }
        return redirect()->route('buyerManagement.index');
    }
}

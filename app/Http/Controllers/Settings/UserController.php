<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\Department;
use App\Models\Settings\Domain;
use App\Models\Settings\Role;
use App\Models\Settings\User;
use App\Models\Settings\UserWorkCenter;
use App\Services\ServerURL;
use App\Services\WSAServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $menuMaster = (new ServerURL())->currentURL($request);
        $users = User::where('domain_id', Session::get('domain'))->with(['getDomain', 'getDepartment', 'getRole'])->orderBy('name')->get();
        $currentUser = Auth::user()->id;

        return view('setting.user.index', compact('users', 'currentUser', 'menuMaster'));
    }

    public function create(Request $request)
    {
        $domains = Domain::orderBy('domain')->get();
        $roles = Role::where('domain_id', Session::get('domain'))->orderBy('role_code')->get();
        $departments = Department::orderBy('department_code')->get();
        $currentDomainID = Session::get('domain');
        $currentDomain = Session::get('domain_name');

        $workCenters = [];
        // $wsaWorkCenter = (new WSAServices())->getWorkCenter($currentDomainID, $currentDomain);
        $wsaWorkCenter = (new WSAServices())->getWorkCenter($currentDomainID, 'Silvador');
        if ($wsaWorkCenter[0] == 'true') {
            $workCenters = $wsaWorkCenter[1];
        }

        return view('setting.user.create', compact('domains', 'roles', 'departments', 'workCenters'));
    }

    public function edit($id)
    {
        $user = User::with(['getWorkCenter'])->where('id', $id)->first();
        // dd($user);
        $domains = Domain::orderBy('domain')->get();
        $roles = Role::where('domain_id', Session::get('domain'))->orderBy('role_code')->get();
        $departments = Department::orderBy('department_code')->get();
        $currentDomainID = Session::get('domain');
        $currentDomain = Session::get('domain_name');

        $workCenters = [];
        // $wsaWorkCenter = (new WSAServices())->getWorkCenter($currentDomainID, $currentDomain);
        $wsaWorkCenter = (new WSAServices())->getWorkCenter($currentDomainID, 'Silvador');
        if ($wsaWorkCenter[0] == 'true') {
            $workCenters = $wsaWorkCenter[1];
        }

        return view('setting.user.edit', compact('user', 'domains', 'roles', 'departments', 'workCenters'));
    }

    public function store(Request $request)
    {
        $username = $request->username;
        $name = $request->name;
        $email = $request->email;
        $domain = $request->domain_id;
        $role = $request->role_id;
        $department = $request->department_id;
        $workCenter = $request->workCenter;
        $workCenterDesc = $request->workCenterDesc;
        $isSuperUser = $request->isSuperUser;
        $canAccessAllDomain = $request->accessAllDomain;
        $isActive = $request->isActive;
        $password = $request->password;
        $currentUser = Auth::user()->id;

        // Cek username sudah ada atau belum
        $usernameExists = User::where('username', $username)->first();
        if ($usernameExists) {
            toast('Username already exists', 'info');

            return redirect()->back()->withInput();
        }

        // if ($email != '') {
        //     $emailExists = User::where('email', $email)->first();
        //     if ($emailExists) {
        //         toast('Email already exists', 'info');

        //         return redirect()->back()->withInput();
        //     }
        // }

        DB::beginTransaction();

        try {
            $user = new User();
            $user->domain_id = $domain;
            $user->role_id = $role;
            $user->department_id = $department;
            $user->username = $username;
            $user->name = $name;
            $user->email = $email;
            $user->is_super_user = $isSuperUser;
            $user->can_access_all_domains = $canAccessAllDomain;
            $user->is_active = $isActive;
            $user->password = Hash::make($password);
            $user->created_by = $currentUser;
            $user->updated_by = $currentUser;
            $user->save();

            if (isset($workCenter) && count($workCenter) > 0) {
                foreach ($workCenter as $key => $wc) {
                    if ($workCenterDesc != '') {
                        $decodedWorkCenterDesc = json_decode($workCenterDesc);
                    }

                    $userWorkCenter = new UserWorkCenter();
                    $userWorkCenter->user_id = $user->id;
                    $userWorkCenter->work_center_code = $wc;
                    $userWorkCenter->work_center_desc = $decodedWorkCenterDesc[$key]->description;
                    $userWorkCenter->created_by = Auth::user()->id;
                    $userWorkCenter->save();
                }
            }

            DB::commit();

            toast('User saved successfully', 'success');

            return redirect()->back();
        } catch (\Exception $err) {
            DB::rollBack();

            toast('Failed to save user', 'error');
            return redirect()->back()->withInput();
        }
    }

    public function update(Request $request)
    {
        // dd($request->all(), ($request->workCenterDesc));
        $id = $request->u_id;
        $name = $request->name;
        $email = $request->email;
        $domain = $request->domain_id;
        $role = $request->role_id;
        $department = $request->department_id;
        $workCenter = $request->workCenter;
        $workCenterDesc = $request->workCenterDesc;
        $password = $request->password;
        $isSuperUser = $request->isSuperUser;
        $canAccessAllDomain = $request->accessAllDomain;
        $isActive = $request->isActive;
        $currentUser = Auth::user()->id;

        DB::beginTransaction();

        try {
            $user = User::where('id', $id)->first();
            $user->name = $name;
            $user->email = $email;
            $user->domain_id = $domain;
            $user->role_id = $role;
            $user->department_id = $department;
            if ($password != '') {
                $user->password = Hash::make($password);
            }
            $user->is_super_user = $isSuperUser;
            $user->can_access_all_domains = $canAccessAllDomain;
            $user->is_active = $isActive;
            $user->updated_by = $currentUser;

            if (isset($workCenter) && count($workCenter) > 0) {
                // Check the work center that has been removed
                UserWorkCenter::where('user_id', $user->id)
                    ->whereNotIn('work_center_code', $workCenter)
                    ->delete();

                foreach ($workCenter as $key => $wc) {
                    if ($workCenterDesc != '') {
                        $decodedWorkCenterDesc = json_decode($workCenterDesc);
                    }

                    $checkUserWorkCenter = UserWorkCenter::where('user_id', $user->id)
                        ->where('work_center_code', $wc)
                        ->first();

                    if (!$checkUserWorkCenter) {
                        $userWorkCenter = new UserWorkCenter();
                        $userWorkCenter->user_id = $user->id;
                        $userWorkCenter->work_center_code = $wc;
                        $userWorkCenter->work_center_desc = $decodedWorkCenterDesc[$key]->description;
                        $userWorkCenter->created_by = Auth::user()->id;
                        $userWorkCenter->save();
                    }
                }
            }
            $user->save();

            DB::commit();
            toast('User updated successfully', 'success');

            return redirect()->route('users.index');
        } catch (\Exception $err) {
            DB::rollBack();
            dd($err);

            toast('Failed to update user', 'error');

            return redirect()->back()->withInput();
        }
    }

    public function delete(Request $request)
    {
        $id = $request->d_id;

        DB::beginTransaction();

        try {

            UserWorkCenter::where('user_id', $id)->delete();

            User::where('id', $id)->delete();

            DB::commit();

            toast('User deleted successfully', 'success');
        } catch (\Exception $err) {
            DB::rollBack();

            toast('Failed to delete user', 'error');
        }

        return redirect()->back();
    }

    public function resetPassword(Request $request)
    {
        $id = $request->rpw_id;

        $currentUser = Auth::user()->id;

        DB::beginTransaction();

        try {
            $user = User::where('id', $id)->first();
            $user->password = Hash::make('12345678');
            $user->updated_by = $currentUser;
            $user->save();

            DB::commit();

            toast('Reset password successful', 'success');
        } catch (\Exception $err) {
            DB::rollBack();

            toast('Failed to reset password', 'error');
        }

        return redirect()->back();
    }

    public function changePassword(Request $request)
    {
        $idUser = $request->id;
        $password = $request->password;

        DB::beginTransaction();

        try {
            $user = User::where('id', $idUser)->first();
            $user->password = Hash::make($password);
            $user->updated_by = Auth::user()->id;
            $user->save();

            DB::commit();
            toast('Successfully changed password', 'success');
        } catch (\Exception $err) {
            DB::rollBack();

            toast('Failed to change password', 'error');
        }

        return redirect()->back();
    }
}

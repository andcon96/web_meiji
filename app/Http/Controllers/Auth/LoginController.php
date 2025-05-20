<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\MenuMaster\MenuMaster;
use App\Models\Settings\Domain;
use App\Models\Settings\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Hashing\BcryptHasher;

use Auth;
use Illuminate\Support\Facades\Auth as FacadesAuth;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //session(['url.intended' => url()->previous()]);
        //$this->redirectTo = session()->get('url.intended');
        $this->middleware('guest')->except('logout');
    }

    public function username(){
        return 'username';
    }

    protected function authenticated(Request $request)
    {
        // Ambil Username Session
        $domain = $request->input('domain');
        $username = $request->input('username');
        $id = Auth::id();
        // Set Session Username & ID
        Session::put('userid', $id);
        //$request->session()->put('username', $username);

        $user = User::where('username', $username)->first();
        if ($user->is_super_user != 'Yes') {
            if ($user->domain_id != $domain) {
                Auth::logout();
                return redirect()->back()->with(['error' => 'User not exists in this domain']);
            }
        }

        $domainMaster = Domain::where('id', $domain)->first();

        if (!$user) {
            Auth::logout();
            return redirect()->back()->with(['error' => 'User not found']);
        }

        if ($user->is_active != 'Active') {
            Auth::logout();
            return redirect()->back()->with(['error' => 'User is not active']);
        } else {
            Session::put('domain', $domain);
            Session::put('domain_name', $domainMaster->domain);
            Session::put('username', $user->username);
            Session::put('role', $user->role_id);
        }

        // $user = DB::table('users')
        //             ->join('roles','users.role_user','roles.role_code')
        //             ->where('users.id','=',$id)
        //             // ->where('active','=','yes')
        //             ->first();

        // $cekAktif = DB::table('users')
        //     ->where('username','=',$username)
        //     ->first();

        // if(!is_null($user)){
        //     if ($cekAktif->active == 'No') {
        //         Auth::logout();
        //         return redirect()->back()->with(['error'=>'Username tidak aktif.']);
        //     }
        //     else{
        //         Session::put('username',$user->username);
        //         Session::put('menu_access', $user->menu_access);
        //         Session::put('name', $user->name);
        //         Session::put('department', $user->dept_user);
        //         Session::put('sub_dept', $user->dept_sub_user);
        //         Session::put('role', $user->role_user);

        //         $menuSideBar = [];

        //         $dataMenu = MenuMaster::with('getMenuType.getIcon', 'getIcon')
        //         ->where('menu_level', 1)
        //         ->orderBy('menu_sort_header', 'asc')
        //         ->orderBy('menu_code', 'asc')
        //         ->get();

        //         foreach ($dataMenu as $headerMenu) {
        //             $subLevel = MenuMaster::with('getMenuType.getIcon', 'getIcon')->where('menu_parent_id', $headerMenu->id)
        //                 ->where('menu_level', '=', 2)
        //                 ->orderBy('menu_sort_sub')
        //                 ->orderBy('menu_code')->get();

        //             $level3 = [];

        //             foreach ($subLevel as $sub) {
        //                 $thirdLevel = MenuMaster::with('getMenuType.getIcon', 'getIcon')->where('menu_parent_id', $sub->id)
        //                 ->where('menu_level', '>', $sub->menu_level)
        //                     ->orderBy('menu_sort_child')
        //                     ->orderBy('menu_code')->get();

        //                 if ($thirdLevel->count() > 0) {
        //                     array_push($level3, $thirdLevel);
        //                 }
        //             }

        //             array_push($menuSideBar, [
        //                 'level1' => $headerMenu,
        //                 'level2' => $subLevel,
        //                 'level3' => $level3
        //             ]);
        //         }

        //         if (Session::has('sideBar')) {
        //             Session::forget('sideBar');
        //         }

        //         Session::put('sideBar', $menuSideBar);
        //     }
        // } else {
        //     return redirect()->back()->with(['error'=>'Username salah / tidak terdaftar']);
        //     //dd($request->all());
        // }
    }

    protected function sendLoginResponse(Request $request)
    {
        $request->session()->regenerate();

        $previous_session = FacadesAuth::User()->session_id;
        if ($previous_session) {
            Session::getHandler()->destroy($previous_session);
        }

        FacadesAuth::user()->session_id = Session::getId();

        FacadesAuth::user()->save();
        $this->clearLoginAttempts($request);
        // session_destroy();
        // dd(session()->all());
        return $this->authenticated($request, $this->guard()->user())
                ?: redirect()->intended($this->redirectPath());
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        // dd($request->all());
        $data = DB::table('users')
                    ->where('username','=',$request->username)
                    ->get();

        if(count($data) == 0){
            return redirect()->back()->with(['error'=>'User is not found / not registered']);
        } else {

            $cekAktif = DB::table('users')
                    ->where('username','=',$request->username)
                    ->first();

            if ($cekAktif->is_active != 'Active') {
                return redirect()->back()->with(['error'=>'User is not active.']);
            }
        }

        $hasher = app('hash');

        $users = DB::table("users")
                    ->select('id','password')
                    ->where("users.username",$request->username)
                    ->first();

        if(!$hasher->check($request->password,$users->password))
        {
            return redirect()->back()->with(['error'=>'Incorrect password']);
        }
    }

}

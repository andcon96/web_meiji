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

use Illuminate\Support\Facades\Auth;

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

    public function username()
    {
        return 'username';
    }

    protected function authenticated(Request $request)
    {
        // Ambil Username Session
        $username = $request->input('username');
        $id = Auth::id();

        $user = User::where('username', $username)->first();

        if (!$user) {
            Auth::logout();
            return redirect()->back()->with(['error' => 'User not found']);
        }

        if ($user->is_active != 'Active') {
            Auth::logout();
            return redirect()->back()->with(['error' => 'User is not active']);
        }
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        $data = DB::table('users')
            ->where('username', '=', $request->username)
            ->get();

        if (count($data) == 0) {
            return redirect()->back()->with(['error' => 'User is not found / not registered']);
        } else {

            $cekAktif = DB::table('users')
                ->where('username', '=', $request->username)
                ->first();

            if ($cekAktif->is_active != 'Active') {
                return redirect()->back()->with(['error' => 'User is not active.']);
            }
        }

        $hasher = app('hash');

        $users = DB::table("users")
            ->select('id', 'password')
            ->where("users.username", $request->username)
            ->first();

        if (!$hasher->check($request->password, $users->password)) {
            return redirect()->back()->with(['error' => 'Incorrect password']);
        }
    }
}

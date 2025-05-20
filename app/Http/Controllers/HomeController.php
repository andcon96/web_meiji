<?php

namespace App\Http\Controllers;

use App\Models\ServiceReqMaster;
use App\Models\WOMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DateTime;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $req)
    {
        $id = Auth::id();

        try {
            return view("home", compact(
                'id'
            ));   
        } catch (\Exception $err) {
            dd('stop');
            Log::error('Error: ' . $err->getMessage());
            abort(500, 'Internal Server Error');
        }
    }
}

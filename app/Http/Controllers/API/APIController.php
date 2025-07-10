<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Settings\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Services\RunningNumberServices;
use App\Services\WSAServices;
use App\Services\APIServices;
use App\Models\QadData;
use App\Models\SalesOrderShopify;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\QadDataResources;
use App\Jobs\API\LoadShopifySO;
use App\Jobs\API\EmailPOS;
use App\Jobs\API\PendingInvoiceEpointJob;
use App\Models\API\SummaryDetailEpoint;
use App\Models\API\SummaryEpoint;
use Carbon\Carbon;

class APIController extends Controller
{
    public $successStatus = 200;

    public function login(Request $request)
    {
        $usercheck = User::where('username', request('username'))->where('is_super_user', 'Yes')->first();

        if ($usercheck) {
            if (Auth::attempt(['username' => request('username'), 'password' => request('password')])) {
                if ($usercheck->android_acc_user != null && $usercheck->android_acc_user != '') {
                    $menuaccess = $usercheck->android_acc_user;
                } else {
                    $menuaccess = [];
                }

                $objToken = $usercheck->createToken('nApp');
                $strToken = $objToken->accessToken;
                $expiration = $objToken->token->expires_at->toDateString();

                $success['token'] =  $strToken;
                $success['expirationDate'] = $expiration;
                return response()->json(
                    [
                        'message' => 'Sukses',
                        'user' => $usercheck,
                        'username' => $usercheck->id,
                        'success' => $success,
                        'menuaccess' => $menuaccess
                    ],
                    $this->successStatus
                );
            } else {
                $response = ["message" => "Error"];
                return response($response, 422);
            }
        } else {
            return response()->json(['message' => 'Error', 'error' => 'Unauthorised'], 401);
        }
    }

    public function resetPass(Request $request)
    {
        $username = $request->input('username');
        $password = $request->input('password');
        $confpass = $request->input('confpass');
        $oldpass = $request->input('oldpass');

        $hasher = app('hash');

        $users = DB::table("users")
            ->select('id', 'password')
            ->where("users.username", $username)
            ->first();

        if ($hasher->check($oldpass, $users->password)) {
            if ($password != $confpass) {
                return response()->json(['message' => 'Error', 'error' => 'Confirm & New Doesnt Match'], 401);
            } else {
                DB::table('users')
                    ->where('username', $username)
                    ->update(['password' => Hash::make($password)]);

                return response()->json([
                    'message' => 'Success',
                ], 200);
            }
        } else {
            return response()->json(['message' => 'Error', 'error' => 'Old Pass is wrong'], 401);
        }
    }
}

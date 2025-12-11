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
use App\Models\API\WorkOrderQAD;
use Carbon\Carbon;

class APIController extends Controller
{
    public $successStatus = 200;

    public function login(Request $request)
    {
        $usercheck = User::with('getRole')->where('username', request('username'))->first();

        if ($usercheck) {
            if (Auth::attempt(['username' => request('username'), 'password' => request('password')])) {
                // if ($usercheck->android_acc_user != null || $usercheck->android_acc_user != '') {
                $menuaccess = $usercheck->getRole->role_android_acc;
                // } else {
                //     $menuaccess = [];
                // }

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

    public function getWorkOrderQad(Request $request)
    {
        Log::info($request->getContent());
        // Ambil Data Outbound
        $xml = simplexml_load_string($request->getContent());

        $data = $xml->children('soapenv', true)->Body->children('qdoc', true)->meiji_wo->dsWo_mstr->wo_mstr;

        $dataArray = [];
        $dataDetail = [];

        foreach ($data as $datas) {
            foreach ($datas->wod_det as $detailData) {
                $dataDetail[] = [
                    'wodPart' => (string)$detailData->wodPart,
                    'wodQtyReq' => (string)$detailData->wodQtyReq,
                ];
            }

            $dataArray[] = [
                'operation' => (string)$datas->operation,
                'woDomain' => (string)$datas->woDomain,
                'woNbr' => (string)$datas->woNbr,
                'woLot' => (string)$datas->woLot,
                'woOrdDate' => (string)$datas->woOrdDate,
                'woDueDate' => (string)$datas->woDueDate,
                'woPart' => (string)$datas->woPart,
                'woQtyOrd' => (string)$datas->woQtyOrd,
                'woStatus' => (string)$datas->woStatus,
                'detail' => $dataDetail
            ];
        }


        $flagKirimData = 1;
        // Check Existing ato ga 
        $checkData = WorkOrderQAD::where('wo_nbr', (string)$datas->woNbr)->where('wo_lot', (string)$datas->woLot)->orderBy('id', 'DESC')->first();
        if ($checkData) {
            if ($checkData->wo_status == 'R') {
                $flagKirimData = 0;
            }
        }

        // Save Data ke DB
        $newdata = new WorkOrderQAD();
        $newdata->wo_nbr = (string)$datas->woNbr;
        $newdata->wo_lot = (string)$datas->woLot;
        $newdata->wo_status = (string)$datas->woStatus;
        $newdata->wo_qad_data = json_encode($dataArray);
        $newdata->save();

        // Kirim ke Luar
        if ($flagKirimData == 1) {
        }


        return response($request->getContent(), 200)->header('Content-Type', 'text/xml;charset="utf-8"')->header('Accept', 'text/xml')->header('SOAPAction', '""');
    }

    public function getInvWms(Request $req)
    {
        /* dd($req->query('inppart')); */

        try {
            /* throw new Exception('test exception'); */

            $items = (new WSAServices)->wsaInvWms($req->query('inppart') ?? '', $req->query('inplot') ?? '');

           /*  dd($items); */

            if ($items == false) { //jika error koneksi wsa
                return response()->json([
                    'Status' => 'Error',
                    'Message' => "WSA Error Connection"
                ], 500);
            }

            if ($items[0] == "false") { //jika error response wsa
                return response()->json([
                    'Status' => 'Not found',
                    'Message' => "Data not found"
                ], 404); //not found
            }

            return response()->json([
                'Status' => 'Not found',
                'Message' => "Get inventory WMS successfully",
                'Items' => $items[1]
            ], 200);

        } catch (\Exception $e) {
            /* dd($e); */
            Log::error($e);
            return response()->json([
                'Status' => 'Error',
                'Message' => 'Internal server error',
            ], 500);
        }
    }

    public function checkPallet(Request $req)
    {
        /* dd($request->lotpallet); */

        try {
            /* throw new Exception('test internal server error'); */

            $checkpallet = (new WSAServices())->wsaLotSerialLdDetail($req->lotpallet);
            /*  dd($checkpallet[0]); */

            if ($checkpallet == false) { //jika error koneksi wsa
                return response()->json([
                    'Status' => 'Error',
                    'Message' => 'WSA Error Connection'
                ], 500);
            }

            if ($checkpallet[0] == "true") { //jika lot ada

                $result = [];

                foreach ($checkpallet[1] as $item) {
                    $result[] = [
                        't_domain' => (string)$item->t_domain,
                        't_part' => (string)$item->t_part,
                        't_partdesc' => (string)$item->t_partdesc,  // Otomatis jadi "" kalau kosong
                        't_site' => (string)$item->t_site,
                        't_loc' => (string)$item->t_loc,
                        't_lot' => (string)$item->t_lot,
                        't_ref' => (string)$item->t_ref,  // Otomatis jadi "" kalau kosong
                        't_qtyoh' => (string)$item->t_qtyoh,
                        't_balancetotalstok' => (float)$item->t_balancetotalstok,
                        't_supplier' => (string)$item->t_supplier,  // Otomatis jadi "" kalau kosong
                    ];
                }

                return response()->json([
                    'Status' => 'success',
                    'Available' => True,
                    'Data' => $result
                ], 200);
            } else { //jika lot tidak ada
                return response()->json([
                    'Status' => 'success',
                    'Available' => False,
                    'Data' => ''
                ], 200);
            }
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'Status' => 'Error',
                'Message' => 'Internal server error',
            ], 500);
        }
    }

    public function checkLoc(Request $req)
    {
        /* dd($req->location); */
        try {
            /* throw new Exception('test internal server error'); */

            $isLocExist = (new WSAServices)->wsaCheckLocation($req->location);

            /* dd($isLocExist); */

            if ($isLocExist == false) { //jika error koneksi wsa
                return response()->json([
                    'Status' => 'Error',
                    'Message' => "WSA Error Connection"
                ], 500);
            }

            if ($isLocExist[0] == "false") { //jika error response wsa
                return response()->json([
                    'Status' => 'Not found',
                    'Message' => "Location doesn't exist!"
                ], 404); //not found
            }

            return response()->json([
                'Status' => 'success',
                'Message' => 'Location exist'
            ], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'Status' => 'Error',
                'Message' => 'Internal server error',
            ], 500);
        }
    }

    public function checkItem(Request $req)
    {
        /* dd($req->item); */
        try {
            /* throw new Exception('test internal server error'); */

            $isItemExist = (new WSAServices)->wsaCheckItem($req->item);

            /* dd($isItemExist); */

            if ($isItemExist == false) { //jika error koneksi wsa
                return response()->json([
                    'Status' => 'Error',
                    'Message' => "WSA Item Error Connection"
                ], 500);
            }

            if ($isItemExist[0] == "false") { //jika error response wsa
                return response()->json([
                    'Status' => 'Not found',
                    'Message' => "Item doesn't exist!"
                ], 404); //not found
            }

            return response()->json([
                'Status' => 'success',
                'Message' => 'Item exist',
                'Item' => $isItemExist[1][0]
            ], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'Status' => 'Error',
                'Message' => 'Item Internal server error',
            ], 500);
        }
    }

    public function checkSupplier(Request $req)
    {
        /* dd($req->supplier); */
        try {
            /*  throw new Exception('test internal server error'); */

            $isSupplierExist = (new WSAServices)->wsaCheckSupplier($req->supplier);

            /* dd($isSupplierExist); */

            if ($isSupplierExist == false) { //jika error koneksi wsa
                return response()->json([
                    'Status' => 'Error',
                    'Message' => "WSA Supplier Error Connection"
                ], 500);
            }

            if ($isSupplierExist[0] == "false") { //jika error response wsa
                return response()->json([
                    'Status' => 'Not found',
                    'Message' => "Supplier doesn't exist!"
                ], 404); //not found
            }

            return response()->json([
                'Status' => 'success',
                'Message' => 'Supplier exist'
            ], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'Status' => 'Error',
                'Message' => 'Supplier Internal server error',
            ], 500);
        }
    }

    public function getDataInquiry(Request $req)
    {
        /* dd($req->all()); */
        try {
            /* throw new Exception('test internal server error'); */

            $getdatainquiry = (new WSAServices())->wsaDataInquiry($req->item, $req->location);

            if ($getdatainquiry == false) { //jika error koneksi wsa
                return response()->json([
                    'Status' => 'Error',
                    'Message' => 'WSA Error Connection'
                ], 500);
            }

            if ($getdatainquiry[0] == "true") { //jika lot ada

                $part = $getdatainquiry[1];
                $partdesc = $getdatainquiry[2];
                $totalstok = $getdatainquiry[3];

                // Master: t_part dan t_balancetotalstok
                $master = [
                    't_part' => $part,
                    't_partdesc' => $partdesc,
                    't_balancetotalstok' => $totalstok,
                ];

                // Detail: loop semua item, ambil field selain master
                $detail = [];
                foreach ($getdatainquiry[4] as $item) {
                    $detail[] = [
                        't_domain' => (string)$item->t_domain,
                        't_partdesc' => (string)$item->t_partdesc,
                        't_site' => (string)$item->t_site,
                        't_loc' => (string)$item->t_loc,
                        't_lot' => (string)$item->t_lot,
                        't_ref' => (string)$item->t_ref,
                        't_qtyoh' => (string)$item->t_qtyoh,
                        't_supplier' => (string)$item->t_supplier,
                        't_createdate' => (string)$item->t_create_date,
                        't_createtime' => sprintf('%02d:%02d', 
                            floor($item->t_create_time / 3600), 
                            floor(($item->t_create_time % 3600) / 60)
                        ),
                    ];
                }

                // Gabungkan jadi 1 array
                $result = [
                    'master' => $master,
                    'detail' => $detail,
                ];

                return response()->json([
                    'Status' => 'success',
                    'Available' => True,
                    'Data' => $result
                ], 200);
            } else { //jika lot tidak ada
                return response()->json([
                    'Status' => 'success',
                    'Available' => False,
                    'Data' => ''
                ], 200);
            }
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'Status' => 'Error',
                'Message' => 'Internal server error',
            ], 500);
        }
    }

    public function checkPalletLoc(Request $req)
    {
        /* dd($request->lotpallet); */

        try {
            /* throw new Exception('test internal server error'); */

            $checkpallet = (new WSAServices())->wsaLotLoc($req->lotpallet, $req->location);
            /*  dd($checkpallet[0]); */

            if ($checkpallet == false) { //jika error koneksi wsa
                return response()->json([
                    'Status' => 'Error',
                    'Message' => 'WSA Error Connection'
                ], 500);
            }

            if ($checkpallet[0] == "true") { //jika lot ada

                $result = [];

                foreach ($checkpallet[1] as $item) {
                    $result[] = [
                        't_domain' => (string)$item->t_domain,
                        't_part' => (string)$item->t_part,
                        't_partdesc' => (string)$item->t_partdesc,  // Otomatis jadi "" kalau kosong
                        't_site' => (string)$item->t_site,
                        't_loc' => (string)$item->t_loc,
                        't_lot' => (string)$item->t_lot,
                        't_ref' => (string)$item->t_ref,  // Otomatis jadi "" kalau kosong
                        't_qtyoh' => (string)$item->t_qtyoh,
                        't_balancetotalstok' => (float)$item->t_balancetotalstok,
                        't_supplier' => (string)$item->t_supplier,  // Otomatis jadi "" kalau kosong
                    ];
                }

                return response()->json([
                    'Status' => 'success',
                    'Available' => True,
                    'Data' => $result
                ], 200);
            } else { //jika lot tidak ada
                return response()->json([
                    'Status' => 'success',
                    'Available' => False,
                    'Data' => ''
                ], 200);
            }
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'Status' => 'Error',
                'Message' => 'Internal server error',
            ], 500);
        }
    }

    public function getLocData(Request $req)
    {
        /* dd($req->loc); */

        $hasil = (new WSAServices())->wsaGetLocData($req->loc);

        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Data Not Found."
            ], 422);
        } else {
            $listData = $hasil[1];

            return response()->json(['DataWSA' => $listData], 200);
        }
        
    }


    public function getSites(Request $req)
    {
        /* dd($req->loc); */

        $hasil = (new WSAServices())->wsaGetSites($req->site);

        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Data Not Found."
            ], 422);
        } else {
            $listData = $hasil[1];

            return response()->json(['DataWSA' => $listData], 200);
        }
        
    }


    public function getWrhData(Request $req)
    {
        /* dd($req->loc); */

        $hasil = (new WSAServices())->wsaGetWrhData($req->wrh);

        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Data Not Found."
            ], 422);
        } else {
            $listData = $hasil[1];

            return response()->json(['DataWSA' => $listData], 200);
        }
        
    }


    public function getLevelData(Request $req)
    {
        /* dd($req->loc); */

        $hasil = (new WSAServices())->wsaGetLevelData($req->level);

        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Data Not Found."
            ], 422);
        } else {
            $listData = $hasil[1];

            return response()->json(['DataWSA' => $listData], 200);
        }
        
    }


    public function getBinData(Request $req)
    {
        /* dd($req->loc); */

        $hasil = (new WSAServices())->wsaGetBinData($req->bin);

        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Data Not Found."
            ], 422);
        } else {
            $listData = $hasil[1];

            return response()->json(['DataWSA' => $listData], 200);
        }
        
    }
}

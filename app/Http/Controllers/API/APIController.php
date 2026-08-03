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
use App\Models\API\xxinvDet;
use App\Models\API\xxinvDetApproval;
use App\Services\APIServices;
use App\Services\QxtendServices;
use App\Models\QadData;
use App\Models\SalesOrderShopify;
use App\Models\API\MobileApk;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\QadDataResources;
use App\Jobs\API\LoadShopifySO;
use App\Jobs\API\EmailPOS;
use App\Jobs\API\PendingInvoiceEpointJob;
use App\Models\API\SummaryDetailEpoint;
use App\Models\API\SummaryEpoint;
use App\Models\API\WorkOrderQAD;
use App\Models\API\TransactionHistory;
use App\Http\Resources\GeneralResources;
use App\Http\Requests\SendQxCompIssueRequest;

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

          /*  $items = (new WSAServices)->wsaInvWms($req->query('inppart') ?? '', $req->query('inplot') ?? '');
*/
            
            $items = DB::table('xxinv_det')
                       ->join('item_master','item_master.im_item_part','=','xxinv_det.xxinv_part')
                       ->where('xxinv_part', $req->query('inppart') ?? '')
                       ->where('xxinv_lot', $req->query('inplot') ?? '')
					   ->get();     

                      //  dd($items); 
            // if ($items == false) { //jika error koneksi wsa
            //     return response()->json([
            //         'Status' => 'Error',
            //         'Message' => "WSA Error Connection"
            //     ], 500);
            // }

            // if ($items[0] == "false") { //jika data tidak ada
            //     return response()->json([
            //         'Status' => 'Not found',
            //         'Message' => "Data not found"
            //     ], 404); //not found
            // }

            return response()->json([
                // 'Status' => 'Not found',
                // 'Message' => "Get inventory WMS successfully",
                'Items' => $items
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
                        't_createtime' => sprintf(
                            '%02d:%02d',
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

        $hasil = (new WSAServices())->wsaGetLocData(
            $req->query('inppart') ?? '',
            $req->query('inplot') ?? '',
            $req->query('inpsite') ?? ''
        );

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

        $hasil = (new WSAServices())->wsaGetSites(
            $req->query('inppart') ?? '',
            $req->query('inplot') ?? ''
        );

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

        $hasil = (new WSAServices())->wsaGetWrhData(
            $req->query('inppart') ?? '',
            $req->query('inplot') ?? '',
            $req->query('inpsite') ?? '',
            $req->query('inploc') ?? ''
        );

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

        $hasil = (new WSAServices())->wsaGetLevelData(
            $req->query('inppart') ?? '',
            $req->query('inplot') ?? '',
            $req->query('inpsite') ?? '',
            $req->query('inploc') ?? '',
            $req->query('inpwrh') ?? '',
        );

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

        $hasil = (new WSAServices())->wsaGetBinData(
            $req->query('inppart') ?? '',
            $req->query('inplot') ?? '',
            $req->query('inpsite') ?? '',
            $req->query('inploc') ?? '',
            $req->query('inpwrh') ?? '',
            $req->query('inplevel') ?? ''
        );

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

    public function getHistoryData(Request $req)
    {
        $number = $req->query('trnbr') ?? '';
        $part = $req->query('part') ?? '';
        $program = $req->query('program') ?? '';
        $lot = $req->query('lot') ?? '';
        $page    = $req->query('page')    ?? 1;
        $perPage = $req->query('per_page') ?? 20; // let frontend control this
        $data = TransactionHistory::query();
        if (!empty($number)) {
            $data->where('tr_nbr', 'LIKE', '%' . $number . '%');
        }
        if (!empty($part)) {
            $data->where('tr_part', 'LIKE', '%' . $part . '%');
        }
        if (!empty($program)) {
            $data->where('tr_program', 'LIKE', '%' . $program . '%');
        }
        if (!empty($lot)) {
            $data->where('tr_lot', 'LIKE', '%' . $lot . '%');
        }

        // $data = $data->orderBy('id', 'DESC')->get();
        $data = $data->orderBy('id', 'DESC')->paginate($perPage, ['*'], 'page', $page);
        // dd($data); // Remove this when done debugging

        return GeneralResources::collection($data);
    }

    public function cekItemLot(Request $req)
    {
        /* dd($req->loc); */

        $hasil = (new WSAServices())->wsaCekItemLot($req->query('inppart') ?? '', $req->query('inplot') ?? '');


        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => $hasil[1] //Data Not Found
            ], 422);
        } else {
            // $listData = $hasil[1];

            return response()->json([
                'Status' => 'Success',
                'Message' => $hasil[1], //Data Found
                'DataWSA' => $hasil[0]
            ], 200);
        }
    }

    public function sendQxCompIssue(SendQxCompIssueRequest $request)
    {
        Log::info($request->all());


        $wonbr      = $request->wonbr;
        $location   = $request->location;
        $lot        = $request->lot;
        $effdate    = $request->effdate;
        $part       = $request->part;
        $qty        = $request->qty;
        $site       = $request->site;
        $lotserial  = $request->lotserial;
        // $wonbr    = $data['wonbr'] ?? null;
        // $location = $data['location'] ?? null;
        // $lot      = $data['lot'] ?? null;
        // $effdate  = $data['effdate'] ?? null;
        // $part     = isset($data['part']) &&  $data['part'] !== '' ? explode(';', $data['part']) : [];
        // $qty      = isset($data['qty']) && $data['qty'] !== '' ? explode(';', $data['qty']) : [];
        // $site     = isset($data['site']) && $data['site'] !== '' ? explode(';', $data['site']) : [];
        // $lotserial = isset($data['lotserial']) && $data['lotserial'] !== '' ? explode(';', $data['lotserial']) : [];


        $sendQxCompIssue = (new QxtendServices())->qxWorkOrderComponentIssue($wonbr, $location, $lot, $effdate, $part, $qty, $site, $lotserial);
        if ($sendQxCompIssue[0] == 'true') {
            return response()->json([
                'Status' => 'Success',
                'Message' => $sendQxCompIssue[1]
            ], 200);
        } else {
            return response()->json([
                'Status' => 'Error',
                'Message' => $sendQxCompIssue[1]
            ], 422);
        }
    }

    public function getAPKLatestVersion()
    {
        $data = MobileApk::where('apk_is_active', 1)
            ->orderByDesc('apk_updated_number')
            ->first();

        return response()->json($data);
    }

    public function outboundxxinvDet(Request $req)
    {
        Log::channel('customlog')->info('masuk');
        DB::beginTransaction();
        try {
            $xml = simplexml_load_string($req->getContent());
            if ($xml === false) {
                throw new \Exception('Malformed XML payload');
            }

            $body       = $xml->children('soapenv', true)->Body;
            $xxlddetwms = $body->children('qdoc', true)->xxlddetwms;
            $dsLdDet    = $xxlddetwms->children('qdoc', true)->dsLd_det;
            $ldDet      = $dsLdDet->children('qdoc', true)->ld_det;
            $fields     = $ldDet->children('qdoc', true);

            $data = [
                'operation' => (string) $fields->operation,
                'ldDomain'  => (string) $fields->ldDomain,
                'ldLoc'     => (string) $fields->ldLoc,
                'ldLot'     => (string) $fields->ldLot,
                'ldPart'    => (string) $fields->ldPart,
                'ldRef'     => (string) $fields->ldRef,
                'ldSite'    => (string) $fields->ldSite,
            ];

            Log::channel('customlog')->info(json_encode($data['ldDomain']));

            $xxinvDet = xxinvDet::where('xxinv_domain', $data['ldDomain'])
                ->where('xxinv_site', $data['ldSite'])
                ->where('xxinv_lot', $data['ldLot'])
                ->where('xxinv_part', $data['ldPart'])
                ->get();
            // $xxinvDet2 = xxinvDet::where('xxinv_domain', $data['ldDomain'])
            //     ->where('xxinv_site', $data['ldSite'])
            //     ->where('xxinv_lot', $data['ldLot'])
            //     ->where('xxinv_part', $data['ldPart'])
            //     ->toSql();
            // Log::channel('customlog')->info($xxinvDet2. ' '.$data['ldDomain']. ' '.$data['ldSite']. ' '.$data['ldLot']. ' '.$data['ldPart']);
            foreach ($xxinvDet as $det) {
                $det->xxinv_loc = $data['ldLoc'];
                $det->save();
            }

            // $xxinvDetApproval = xxinvDetApproval::where('xxinv_domain', $data['ldDomain'])
            //     ->where('xxinv_siteto', $data['ldSite'])
            //     ->where('xxinv_lot', $data['ldLot'])
            //     ->where('xxinv_part', $data['ldPart'])
            //     ->get();
            // foreach ($xxinvDetApproval as $detapproval) {
            //     $detapproval->xxinv_loc = $data['ldLoc'];
            //     $detapproval->save();
            // }
            DB::commit();

            Log::channel('customlog')->info(json_encode($data));

            return response($this->soapAck(), 200)
                ->header('Content-Type', 'text/xml; charset=utf-8');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::channel('customlog')->error('outboundxxinvDet error: ' . $e->getMessage());

            return response($this->soapAck(false, $e->getMessage()), 500)
                ->header('Content-Type', 'text/xml; charset=utf-8');
        }
    }
    private function soapAck(bool $success = true, string $message = 'Success')
    {
        $status  = $success ? 'SUCCESS' : 'ERROR';
        $escaped = htmlspecialchars($message);

        return <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
        <soapenv:Body>
            <status>{$status}</status>
            <message>{$escaped}</message>
        </soapenv:Body>
        </soapenv:Envelope>
        XML;
    }
}

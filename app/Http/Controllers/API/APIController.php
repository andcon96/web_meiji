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

                $success['token'] =  $usercheck->createToken('nApp')->accessToken;
                return response()->json(
                    [
                        'message' => 'Sukses',
                        // 'user' => $usercheck,
                        // 'username' => $usercheck->id,
                        'success' => $success,
                        // 'user_approver' => $usercheck->user_approver
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

    // QXO for Item Master
    public function itemmaster(Request $request)
    {
        try {
            // Ambil Running Number
            $runningNumber = (new RunningNumberServices())->getRunningNumber();
            if ($runningNumber == false) {
                // error hardcode ke 0
                $runningNumber = '0000000000';
            }

            // Ambil Data Outbound
            $xml = simplexml_load_string($request->getContent());

            $data = $xml->children('soapenv', true)->Body->children('qdoc', true)->Ris_Epoint_Item_maint->dsPt_mstr->pt_mstr;

            $dataArray = [];

            foreach ($data as $datas) {
                $formattedDate = Carbon::createFromFormat('m/d/y', (string)$datas->changeDate)->format('Y-m-d');
                $dataArray[] = [
                    'operation' => (string)$datas->operation,
                    'domain' => (string)$datas->ptDomain,
                    'changeddate' => $formattedDate,
                    'SKU' => (string)$datas->ptPart,
                    'description' => (string)$datas->ptDesc1 . ' ' . (string)$datas->ptDesc2,
                    'barcode' => (string)$datas->ptBarcode1 . ' ' . (string)$datas->ptBarcode2,
                    'UOM' => (string)$datas->ptUm,
                    'item_type' => (string)$datas->ptPartType,
                    'consign' => (string)$datas->ptGroup,
                    'price' => (string)$datas->ptPrice,
                ];
            }

            // Save Data ke DB
            $newdata = new QadData();
            $newdata->qd_rn = $runningNumber;
            $newdata->qd_data = json_encode($dataArray);
            $newdata->qd_url = 'ItemMaster';
            $newdata->save();

            return response($request->getContent(), 200)->header('Content-Type', 'text/xml;charset="utf-8"')->header('Accept', 'text/xml')->header('SOAPAction', '""');
        } catch (Exception $e) {
            Log::channel('itemTransfer')->info($e);

            return response($request->getContent(), 400)->header('Content-Type', 'text/xml;charset="utf-8"')->header('Accept', 'text/xml')->header('SOAPAction', '""');
        }
    }

    // Endpoint User Get Data QAD
    public function getQadData(Request $request)
    {
        $isnewdata  = $request->isnewdata;
        $dateFrom = $request->datefrom;
        $dateTo   = Carbon::parse($request->dateto)->addDay()->toDateString();

        $data = QadData::query()
            ->when($dateFrom && $dateTo, function ($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('created_at', [$dateFrom, $dateTo]);
            })
            ->when($dateFrom && !$dateTo, function ($query) use ($dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when(!$dateFrom && $dateTo, function ($query) use ($dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            });

        // if($isnewdata == 'true'){
        //     $data->where('qd_is_sent','=','0');
        // }else{
        //     $data->where('qd_is_sent','=','1');
        // }

        // if($datefrom){
        //     $data->where('created_at','>=',$datefrom);
        // }

        // if($dateto){
        //     $data->where('created_at','<=',$dateto);
        // }

        $data = $data->get();

        // Update Data
        foreach ($data as $datas) {
            if ($datas->qd_is_sent == 0) {
                $datas->qd_is_sent = 1; // Tanda sudah pernah diambil datanya
                $datas->qd_ip_from = $request->ip();
                $datas->save();
            }
        }

        return QadDataResources::collection($data);
    }

    // WSA for Inventory per Item
    public function getDataLocationDetailQAD(Request $request)
    {
        // dd('stop');
        $domain = $request->domain;
        $part = $request->part;
        $site = $request->site;
        $loc = $request->loc;
        $lot = $request->lot;

        $getdata = (new WSAServices())->getLocationDetail($domain, $part, $site, $loc, $lot);
        if ($getdata == false) {
            $response = ["message" => "Error, WSA Failed"];
            return response($response, 422);
        } elseif ($getdata[0] == false) {
            $response = ["message" => "No Data Available"];
            return response($response, 200);
        }

        return response()->json(
            [
                'message' => 'Success',
                'data' => $getdata[1],
            ],
            $this->successStatus
        );
    }

    // Endpoint Shopify to SO
    public function getSoShopify(Request $request)
    {
        $data = $request->all();
        if (empty($data)) {
            return [false, 'No Data Request'];
        }
        $sodata = $data['sodata'] ?? '';
        if (!$sodata) {
            return response()->json([
                'Status' => 'Error',
                'Message' => 'The `sodata` field is required and was not provided.'
            ], 400);
        }

        // Remove Any Unnecessary Space or Enter
        $sodataToArray = json_decode($sodata);
        $sodata = json_encode($sodataToArray);


        $newdata = new SalesOrderShopify();
        $newdata->ss_data = $sodata;
        $newdata->ss_url = 'Sales Order Shopify';
        $newdata->ss_ip_from = $request->ip();
        $newdata->save();

        // Bkin Customer & SO QAD
        foreach ($sodataToArray as $key => $datas) {
            dispatch(new LoadShopifySO($datas, Auth::user()->id, $key));
        }

        return response()->json([
            'Status' => 'Success',
            'Message' => 'Sales Order Shopify Saved'
        ]);
    }

    // Summary Sales Order EPOINT -- Single Customer
    // public function getSummarySO(Request $request)
    // {
    //     $dateFilter = Carbon::yesterday()->format('Ymd'); // format parameter EPOINT
    //     if($request->date){
    //         $dateFilter = Carbon::createFromFormat('Y-m-d',$request->date)->format('Ymd');
    //     }

    //     $getEPointData = (new APIServices())->getEpointAPI($dateFilter);
    //     if($getEPointData[0] != 200){
    //         return ['status' => 'Error', 'message' => 'Epoint Error Status Return :'.$getEPointData[0]];
    //     }
    //     if($getEPointData[1]['status'] == 'fail'){
    //         return ['status' => 'Error', 'message' => 'Epoint Error Status Return :'.$getEPointData[1]['response']];
    //     }
    //     if($getEPointData[1]['response'] == 'No Sales Data'){
    //         return ['status' => 'Error', 'message' => 'No Sales Data on '.Carbon::createFromFormat('Ymd',$dateFilter)->format('Y-m-d')];
    //     }

    //     $data = $getEPointData[1];

    //     $lokasi = trim($data['response'][0]['LOCATION'] ?? '');

    //     $salesPerson = (new APIServices())->getSalesPersonEPoint($lokasi);
    //     if($salesPerson == false){
    //         return ['status' => 'Error', 'message' => 'No Sales Person Found for Location : '.$lokasi];
    //     }

    //     $nextPendingInvoice = '';
    //     dd($getEPointData);
    //     try{
    //         DB::beginTransaction();

    //         // Get Mail POS from Comment Master
    //         $getdata = (new APIServices())->wsaGetMailPOS(1,'RISIS','US','pos-mail');
    //         if($getdata[0] == 'false'){
    //             Log::channel('SummaryPendingInvoice')->info('No Data from WSA');
    //         }else{
    //             $error_title = (String) $getdata[1][0]->t_error_msg;
    //             $error_email = (String) $getdata[1][0]->t_error_email_addr;
    //             $success_title = (String) $getdata[1][0]->t_success_msg;
    //             $success_email = (String) $getdata[1][0]->t_success_email_addr;
    //         }   

    //         // Get Next SO Number 
    //         $getSoNumber = (new APIServices())->wsaGetNextPendingInvoice(1, 'RISIS');
    //         if($getSoNumber[0] == 'true'){
    //             foreach($getSoNumber[1] as $flag => $getSoNumbers){
    //                 $nextPendingInvoice = (String) $getSoNumbers->t_sonbr ?? '';
    //             }
    //         }

    //         // Validasi apakah ada error QAD (xxpinv2.p)
    //         $error_list = [];
    //         foreach($data['response'] as $key => $datas){
    //             $validate = (new APIServices())->wsaValidasiMailPOS(1,'RISIS',$datas);
    //             if($validate[0] == 'true'){
    //                 foreach($validate[1] as $flag => $errors){
    //                     $error_list[] = (String) $errors->t_error_msg ?? '';
    //                 }
    //             }
    //         }

    //         if(!empty($error_list)){
    //             // Email Penerima, Title Email, Action, Error List, dateFilter, lokasi, Next Pending Invoice Number

    //             dispatch(new EmailPOS($error_email, $error_title, 'error',$error_list, $dateFilter, $lokasi, $nextPendingInvoice ));
    //             DB::commit(); // Biar Mzsuk ke table Jobs
    //             return [
    //                 'status' => 'Error',
    //                 'message' => 'Errors in validation, check Email' 
    //             ];
    //         }

    //         // Insert data to DB
    //         $savedata = new SummaryEpoint();
    //         $savedata->se_date = Carbon::createFromFormat('Ymd',$dateFilter)->format('Y-m-d');
    //         $savedata->se_data = json_encode($data);
    //         $savedata->se_url = 'Summary Pending Invoice';
    //         $savedata->se_ip_from = $request->ip();

    //         // Send Data to Qxtend Pending Invoice
    //         $pendingInvoice = (new APIServices())->qxPendingInvoice($data, 1); // Hardcode 1 = RISIS
    //         $msg = 'Data Saved, SO Number : '.$nextPendingInvoice;
    //         $status = 'Success';
    //         if($pendingInvoice == false){
    //             $savedata->se_error_msg = 'Qxtend Returns False, No Response';
    //             $msg = 'Qxtend Returns False, No Response';
    //             $status = 'Error';
    //         }else if($pendingInvoice[0] == 'error'){
    //             $savedata->se_error_msg = $pendingInvoice[1];
    //             $msg = 'ERROR : '.$pendingInvoice[1];
    //             $status = 'Error';
    //         }else{
    //             $savedata->se_is_sent = 1;

    //             // Email Penerima, Title Email, Action, Error List
    //             dispatch(new EmailPOS($success_email, $success_title, 'success' ,$error_list, $dateFilter, $lokasi, $nextPendingInvoice));
    //         }

    //         $savedata->save();
    //         DB::commit();

    //         return [
    //             'status' => $status,
    //             'message' => $msg
    //         ];
    //     }catch(Exception $e){
    //         Log::channel('SummaryPendingInvoice')->info('Try Catch Error : '. $e);
    //         DB::rollback();
    //     }

    // }

    // Summary Sales Order EPOINT -- Multiple Customer
    public function getSummarySO(Request $request)
    {
        $dateFilter = Carbon::yesterday()->format('Ymd'); // format parameter EPOINT
        if ($request->date) {
            $dateFilter = Carbon::createFromFormat('Y-m-d', $request->date)->format('Ymd');
        }

        $getEPointData = (new APIServices())->getEpointAPI($dateFilter);
        if ($getEPointData[0] != 200) {
            return ['status' => 'Error', 'message' => 'Epoint Error Status Return :' . $getEPointData[0]];
        }
        if ($getEPointData[1]['status'] == 'fail') {
            return ['status' => 'Error', 'message' => 'Epoint Error Status Return :' . $getEPointData[1]['response']];
        }
        if ($getEPointData[1]['response'] == 'No Sales Data') {
            return ['status' => 'Error', 'message' => 'No Sales Data on ' . Carbon::createFromFormat('Ymd', $dateFilter)->format('Y-m-d')];
        }

        $data = $getEPointData[1];

        $response = $data['response'];

        usort($response, function ($a, $b) {
            return strcmp(trim($a['LOCATION']), trim($b['LOCATION']));
        });

        // dd($response);

        $nextPendingInvoice = '';
        try {
            // Get Mail POS from Comment Master
            $getdata = (new APIServices())->wsaGetMailPOS(1, 'RISIS', 'US', 'pos-mail');
            if ($getdata[0] == 'false') {
                Log::channel('SummaryPendingInvoice')->info('No Data from WSA');
            } else {
                $error_title = (string) $getdata[1][0]->t_error_msg;
                $error_email = (string) $getdata[1][0]->t_error_email_addr;
                $success_title = (string) $getdata[1][0]->t_success_msg;
                $success_email = (string) $getdata[1][0]->t_success_email_addr;
            }

            // Get Next SO Number 
            $getSoNumber = (new APIServices())->wsaGetNextPendingInvoice(1, 'RISIS');
            if ($getSoNumber[0] == 'true') {
                foreach ($getSoNumber[1] as $flag => $getSoNumbers) {
                    $nextPendingInvoice = (string) $getSoNumbers->t_sonbr ?? '';
                }
            }

            // Validasi apakah ada error QAD (xxpinv2.p)
            $error_list = [];
            foreach ($data['response'] as $key => $datas) {
                $validate = (new APIServices())->wsaValidasiMailPOS(1, 'RISIS', $datas);
                if ($validate[0] == 'true') {
                    foreach ($validate[1] as $flag => $errors) {
                        $error_list[] = (string) $errors->t_error_msg ?? '';
                    }
                }
            }
            // dd($error_list);
            if (!empty($error_list)) {
                // Email Penerima, Title Email, Action, Error List, dateFilter, lokasi, Next Pending Invoice Number
                // Send Email error tidak butuh lokasi.
                dispatch(new EmailPOS($error_email, $error_title, 'error', $error_list, $dateFilter, '', $nextPendingInvoice));
                DB::commit(); // Biar Masuk ke table Jobs
                return [
                    'status' => 'Error',
                    'message' => 'Errors in validation, check Email'
                ];
            }


            DB::beginTransaction();
            // Insert data to DB
            $savedata = new SummaryEpoint();
            $savedata->se_date = Carbon::createFromFormat('Ymd', $dateFilter)->format('Y-m-d');
            $savedata->se_data = json_encode($data);
            $savedata->se_url = 'Summary Pending Invoice';
            $savedata->se_ip_from = $request->ip();
            $savedata->save();

            $jumlahArray = count($response);
            $location = '';
            $output = [];
            foreach ($response as $key => $responses) {
                if ($location != '' && $location != trim($responses['LOCATION'])) {
                    $salesPerson = (new APIServices())->getSalesPersonEPoint(trim($response[$key - 1]['LOCATION']));
                    // save ke summary_detail_epoint
                    $saveDetail = new SummaryDetailEpoint();
                    $saveDetail->sde_se_id = $savedata->id;
                    $saveDetail->sde_customer = $response[$key - 1]['LOC_INFO1'];
                    $saveDetail->sde_location = trim($response[$key - 1]['LOCATION']);
                    $saveDetail->sde_sales_person = trim($response[$key - 1]['LOC_INFO2']) == "" ? $salesPerson : trim($response[$key - 1]['LOC_INFO2']);
                    $saveDetail->sde_date = Carbon::createFromFormat('Ymd', substr($response[$key - 1]['SHIFTCODE'], 0, 8))->format('Y-m-d');
                    $saveDetail->sde_shift = substr($response[$key - 1]['SHIFTCODE'], 8, 1);
                    $saveDetail->save();

                    // Get Running Number QAD
                    $getSoNumber = (new APIServices())->wsaGetNextPendingInvoice(1, 'RISIS');
                    if ($getSoNumber[0] == 'true') {
                        foreach ($getSoNumber[1] as $flag => $getSoNumbers) {
                            $nextPendingInvoice = (string) $getSoNumbers->t_sonbr ?? '';
                        }
                    }

                    // kirim pending invoice & email
                    dispatch(new PendingInvoiceEpointJob($output, 1, $saveDetail->id, $success_email, $success_title, 'success', $error_list, $dateFilter, $response[$key - 1]['LOCATION'], $nextPendingInvoice, trim($response[$key - 1]['LOC_INFO2']) == "" ? $salesPerson : trim($response[$key - 1]['LOC_INFO2'])));

                    $output = [];
                }

                $output[] = $responses;
                $location = trim($responses['LOCATION']);

                if ($jumlahArray == $key + 1) {
                    $salesPerson = (new APIServices())->getSalesPersonEPoint(trim($responses['LOCATION']));
                    // save ke summary_detail_epoint
                    $saveDetail = new SummaryDetailEpoint();
                    $saveDetail->sde_se_id = $savedata->id;
                    $saveDetail->sde_customer = trim($responses['LOC_INFO1']);
                    $saveDetail->sde_location = trim($responses['LOCATION']);
                    $saveDetail->sde_sales_person = trim($responses['LOC_INFO2']) == '' ? $salesPerson : trim($responses['LOC_INFO2']);
                    $saveDetail->sde_date = Carbon::createFromFormat('Ymd', substr($responses['SHIFTCODE'], 0, 8))->format('Y-m-d');
                    $saveDetail->sde_shift = substr($responses['SHIFTCODE'], 8, 1);
                    $saveDetail->save();

                    // Get Running Number QAD
                    $getSoNumber = (new APIServices())->wsaGetNextPendingInvoice(1, 'RISIS');
                    if ($getSoNumber[0] == 'true') {
                        foreach ($getSoNumber[1] as $flag => $getSoNumbers) {
                            $nextPendingInvoice = (string) $getSoNumbers->t_sonbr ?? '';
                        }
                    }
                    // kirim pending invoice & email
                    dispatch(new PendingInvoiceEpointJob($output, 1, $saveDetail->id, $success_email, $success_title, 'success', $error_list, $dateFilter, $responses['LOCATION'], $nextPendingInvoice, trim($responses['LOC_INFO2']) == '' ? $salesPerson : trim($responses['LOC_INFO2'])));


                    $output = [];
                }
            }

            DB::commit();

            return [
                'status' => 'success',
                'message' => 'Data Saved'
            ];
        } catch (Exception $e) {
            DB::rollBack();
            dd($e);
        }
    }
}

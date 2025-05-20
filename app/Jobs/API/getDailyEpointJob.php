<?php

namespace App\Jobs\API;

use App\Models\API\SummaryDetailEpoint;
use App\Models\API\SummaryEpoint;
use App\Services\APIServices;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class getDailyEpointJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::channel('SummaryPendingInvoice')->info('Request Sent to EPOINT, waiting for response');

        $dateFilter = Carbon::yesterday()->format('Ymd'); // format parameter EPOINT

        $getEPointData = (new APIServices())->getEpointAPI($dateFilter);
        if ($getEPointData[0] != 200) {
            Log::channel('SummaryPendingInvoice')->info('Epoint Error Status Return :' . $getEPointData[0]);
            return;
        }
        if ($getEPointData[1]['status'] == 'fail') {
            Log::channel('SummaryPendingInvoice')->info('Epoint Error Status Return :' . $getEPointData[1]['response']);
            return;
        }
        if ($getEPointData[1]['response'] == 'No Sales Data') {
            Log::channel('SummaryPendingInvoice')->info('No Sales Data on ' . Carbon::createFromFormat('Ymd', $dateFilter)->format('Y-m-d'));
            return;
        }

        $data = $getEPointData[1];

        $response = $data['response'];

        usort($response, function ($a, $b) {
            return strcmp(trim($a['LOCATION']), trim($b['LOCATION']));
        });

        $nextPendingInvoice = '';
        try {
            // Get Mail POS from Comment Master
            $getdata = (new APIServices())->wsaGetMailPOS(1, 'RISIS', 'US', 'pos-mail');
            if ($getdata == 'false') {
                Log::channel('SummaryPendingInvoice')->info('Error Connection WSA');
            } elseif (is_array($getdata) && isset($getdata[0]) && $getdata[0] == 'false') {
                Log::channel('SummaryPendingInvoice')->info('No Data from WSA');
            } elseif (is_array($getdata) && isset($getdata[1][0])) {
                Log::channel('SummaryPendingInvoice')->info('Mail Get');
                $error_title = (string) $getdata[1][0]->t_error_msg ?? '';
                $error_email = (string) $getdata[1][0]->t_error_email_addr ?? '';
                $success_title = (string) $getdata[1][0]->t_success_msg ?? '';
                $success_email = (string) $getdata[1][0]->t_success_email_addr ?? '';
            } else {
                Log::channel('SummaryPendingInvoice')->info('Get Mail Return not false.');
                return;
            }

            // Get Next SO Number 
            $getSoNumber = (new APIServices())->wsaGetNextPendingInvoice(1, 'RISIS');
            if ($getSoNumber[0] == 'true') {
                foreach ($getSoNumber[1] as $flag => $getSoNumbers) {
                    $nextPendingInvoice = (string) $getSoNumbers->t_sonbr ?? '';
                }
                Log::channel('SummaryPendingInvoice')->info('Next Pending Invoice : ' . $nextPendingInvoice);
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

            if (!empty($error_list)) {
                // Email Penerima, Title Email, Action, Error List, dateFilter, lokasi, Next Pending Invoice Number
                // Send Email error tidak butuh lokasi.
                dispatch(new EmailPOS($error_email, $error_title, 'error', $error_list, $dateFilter, '', $nextPendingInvoice));
                DB::commit(); // Biar Masuk ke table Jobs
                Log::channel('SummaryPendingInvoice')->info('Errors in validation, check Email');
                return;
            }


            Log::channel('SummaryPendingInvoice')->info('Start Save DB');
            // DB::beginTransaction();
            // Insert data to DB
            $savedata = new SummaryEpoint();
            $savedata->se_date = Carbon::createFromFormat('Ymd', $dateFilter)->format('Y-m-d');
            $savedata->se_data = json_encode($data);
            $savedata->se_url = 'Summary Pending Invoice';
            $savedata->save();

            $jumlahArray = count($response);
            $location = '';
            $output = [];
            foreach ($response as $key => $responses) {
                if ($location != '' && $location != trim($responses['LOCATION'])) {
                    $salesPerson = (new APIServices())->getSalesPersonEPoint(trim($response[$key - 1]['LOCATION']));
                    $getCustomer = (new APIServices())->getCustomerCodeEPoint(trim($response[$key - 1]['LOCATION']));
                    $customerCode = $getCustomer == false ? $response[$key - 1]['LOC_INFO1'] : $getCustomer;
                    // save ke summary_detail_epoint
                    $saveDetail = new SummaryDetailEpoint();
                    $saveDetail->sde_se_id = $savedata->id;
                    // $saveDetail->sde_customer = $response[$key - 1]['LOC_INFO1'];
                    $saveDetail->sde_customer = $customerCode;
                    $saveDetail->sde_location = trim($response[$key - 1]['LOCATION']);
                    $saveDetail->sde_sales_person = trim($response[$key - 1]['LOC_INFO2']) == "" ? $salesPerson : trim($response[$key - 1]['LOC_INFO2']);
                    $saveDetail->sde_date = Carbon::createFromFormat('Ymd', substr($response[$key - 1]['SHIFTCODE'], 0, 8))->format('Y-m-d');
                    $saveDetail->sde_shift = substr($response[$key - 1]['SHIFTCODE'], 8, 1);
                    $saveDetail->save();

                    // kirim pending invoice
                    $getSoNumber = (new APIServices())->wsaGetNextPendingInvoice(1, 'RISIS');
                    if ($getSoNumber[0] == 'true') {
                        foreach ($getSoNumber[1] as $flag => $getSoNumbers) {
                            $nextPendingInvoice = (string) $getSoNumbers->t_sonbr ?? '';
                        }
                    }
                    $pendingInvoice = (new APIServices())->qxPendingInvoice($output, 1, trim($response[$key - 1]['LOC_INFO2']) == "" ? $salesPerson : trim($response[$key - 1]['LOC_INFO2'])); // Hardcode 1 = RISIS
                    if ($pendingInvoice == false) {
                        $saveDetail->sde_error_qxtend = 'Qxtend Returns False, No Response';
                        $saveDetail->save();
                    } else if ($pendingInvoice[0] == 'error') {
                        $saveDetail->sde_error_qxtend = $pendingInvoice[1];
                        $saveDetail->save();
                    } else {
                        // Email Penerima, Title Email, Action, Error List
                        dispatch(new EmailPOS($success_email, $success_title, 'success', $error_list, $dateFilter, $response[$key - 1]['LOCATION'], $nextPendingInvoice));
                    }

                    $output = [];
                }

                $output[] = $responses;
                $location = trim($responses['LOCATION']);

                if ($jumlahArray == $key + 1) {
                    $salesPerson = (new APIServices())->getSalesPersonEPoint(trim($responses['LOCATION']));
                    $getCustomer = (new APIServices())->getCustomerCodeEPoint(trim($responses['LOCATION']));
                    $customerCode = $getCustomer == false ? trim($responses['LOC_INFO1']) : $getCustomer;
                    // save ke summary_detail_epoint
                    $saveDetail = new SummaryDetailEpoint();
                    $saveDetail->sde_se_id = $savedata->id;
                    // $saveDetail->sde_customer = trim($responses['LOC_INFO1']);
                    $saveDetail->sde_customer = $customerCode;
                    $saveDetail->sde_location = trim($responses['LOCATION']);
                    $saveDetail->sde_sales_person = trim($responses['LOC_INFO2']) == '' ? $salesPerson : trim($responses['LOC_INFO2']);
                    $saveDetail->sde_date = Carbon::createFromFormat('Ymd', substr($responses['SHIFTCODE'], 0, 8))->format('Y-m-d');
                    $saveDetail->sde_shift = substr($responses['SHIFTCODE'], 8, 1);
                    $saveDetail->save();

                    // kirim pending invoice
                    $getSoNumber = (new APIServices())->wsaGetNextPendingInvoice(1, 'RISIS');
                    if ($getSoNumber[0] == 'true') {
                        foreach ($getSoNumber[1] as $flag => $getSoNumbers) {
                            $nextPendingInvoice = (string) $getSoNumbers->t_sonbr ?? '';
                        }
                    }
                    $pendingInvoice = (new APIServices())->qxPendingInvoice($output, 1, trim($responses['LOC_INFO2']) == '' ? $salesPerson : trim($responses['LOC_INFO2'])); // Hardcode 1 = RISIS
                    if ($pendingInvoice == false) {
                        $saveDetail->sde_error_qxtend = 'Qxtend Returns False, No Response';
                        $saveDetail->save();
                    } else if ($pendingInvoice[0] == 'error') {
                        $saveDetail->sde_error_qxtend = $pendingInvoice[1];
                        $saveDetail->save();
                    } else {
                        // Email Penerima, Title Email, Action, Error List
                        dispatch(new EmailPOS($success_email, $success_title, 'success', $error_list, $dateFilter, $responses['LOCATION'], $nextPendingInvoice));
                    }

                    $output = [];
                }
            }

            // DB::commit();

        } catch (Exception $e) {
            // DB::rollBack();
            Log::channel('SummaryPendingInvoice')->info($e);
        }
    }
}

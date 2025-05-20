<?php

namespace App\Jobs\API;

use App\Services\APIServices;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\API\SummaryDetailEpoint;
use App\Jobs\API\EmailPOS;

class PendingInvoiceEpointJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data, $domain, $iddetail, $success_email, $success_title, $type, $error_list, $dateFilter, $location, $nextPendingInvoice, $salesperson;

    /**
     * Create a new job instance.
     */
    public function __construct($data, $domain, $iddetail, $success_email, $success_title, $type, $error_list, $dateFilter, $location, $nextPendingInvoice, $salesperson)
    {
        $this->data = $data;
        $this->domain = $domain;
        $this->iddetail = $iddetail;
        $this->success_email = $success_email;
        $this->success_title = $success_title;
        $this->type = $type;
        $this->error_list = $error_list;
        $this->dateFilter = $dateFilter;
        $this->location = $location;
        $this->nextPendingInvoice = $nextPendingInvoice;           
        $this->salesperson = $salesperson;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $output = $this->data;
        $domain = $this->domain;
        $iddetail = $this->iddetail;
        $success_email = $this->success_email;
        $success_title = $this->success_title;
        $type = $this->type;
        $error_list = $this->error_list;
        $dateFilter = $this->dateFilter;
        $location = $this->location;
        $nextPendingInvoice = $this->nextPendingInvoice;      
        $salesperson = $this->salesperson;

        $saveDetail = SummaryDetailEpoint::find($iddetail);

        $pendingInvoice = (new APIServices())->qxPendingInvoice($output, 1, $salesperson); // Hardcode 1 = RISIS
        if($pendingInvoice == false){
            $saveDetail->sde_error_qxtend = 'Qxtend Returns False, No Response';
            $saveDetail->save();
        }else if($pendingInvoice[0] == 'error'){
            $saveDetail->sde_error_qxtend = $pendingInvoice[1];
            $saveDetail->save();
        }else{
            // Email Penerima, Title Email, Action, Error List
            dispatch(new EmailPOS($success_email, $success_title, $type ,$error_list, $dateFilter, $location, $nextPendingInvoice));
        }
    }
}

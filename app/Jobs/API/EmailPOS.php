<?php

namespace App\Jobs\API;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class EmailPOS implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $listemail, $title, $action, $errorList, $dateFilter, $lokasi, $nextSO;

    /**
     * Create a new job instance.
     */
    public function __construct($listemail, $title, $action, $errorList, $dateFilter, $lokasi, $nextSO)
    {
        $this->listemail = $listemail;
        $this->title = $title;
        $this->action = $action;
        $this->errorList = $errorList;
        $this->dateFilter = $dateFilter;
        $this->lokasi = $lokasi;
        $this->nextSO = $nextSO;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $listemail = $this->listemail;
        $title = $this->title;
        $action = $this->action;
        $errorList = $this->errorList;
        $dateFilter = $this->dateFilter;
        $lokasi = $this->lokasi;
        $nextSO = $this->nextSO;

        switch ($action) {
            case 'error':
                $message1 = 'Please do not reply to this email.';
                $message2 = 'Please find List of POS ERROR on attacment above.';
                $message3 = 'This is an automated notification from Web System [PRODUCTION].';
                $fileContent = '';

                foreach ($errorList as $errorLists) {
                    $fileContent .= $errorLists . PHP_EOL;
                }


                $arrayEmail = explode(', ', $listemail);

                Mail::send('email.emailEPoint', [
                    'message1' => $message1,
                    'message2' => $message2,
                    'message3' => $message3,
                    'message4' => '',
                    'note' => 'Please Check',
                ], function ($message) use ($arrayEmail, $title, $fileContent) {
                    $message->subject($title);
                    $message->to($arrayEmail);

                    $message->attachData($fileContent, 'ErrorList - ' . \Carbon\Carbon::now()->toDateString() . '.txt', [
                        'mime' => 'text/plain'
                    ]);
                });
                break;

            case 'success':
                $message1 = 'Please do not reply to this email.';
                $message2 = 'This is an automated notification from Web System [PRODUCTION].';
                $message3 = 'POS Data from location ' . $lokasi . ' Date : ' . $dateFilter . ' has been loaded in to the QAD system';
                $message4 = 'Order Number : ' . $nextSO;

                $arrayEmail = explode(', ', $listemail);

                Mail::send('email.emailEPoint', [
                    'message1' => $message1,
                    'message2' => $message2,
                    'message3' => $message3,
                    'message4' => $message4,
                    'note' => 'Please Check',
                ], function ($message) use ($arrayEmail, $title) {
                    $message->subject($title);
                    $message->to($arrayEmail);
                });
                break;
        }
    }
}

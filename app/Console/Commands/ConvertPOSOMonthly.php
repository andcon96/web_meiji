<?php

namespace App\Console\Commands;

use App\Http\Controllers\PurchaseOrder\ConvertPOMonthlyController;
use Illuminate\Console\Command;

class ConvertPOSOMonthly extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monthly:convertPOSO';
    protected $description = 'Transfer outstanding PO in risis & outstanding SO in RISIS';

    /**
     * The console command description.
     *
     * @var string
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $controller = new ConvertPOMonthlyController();
        $controller->convertPOMonthly();
    }
}

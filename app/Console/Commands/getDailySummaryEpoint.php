<?php

namespace App\Console\Commands;

use App\Jobs\API\getDailyEpointJob;
use App\Jobs\TestDelayJob;
use Illuminate\Console\Command;

class getDailySummaryEpoint extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-daily-summary-epoint';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get Daily Summary from EPOINT API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // dispatch(new getDailyEpointJob());
        dispatch(new TestDelayJob());
    }
}

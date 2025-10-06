<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class SSDExport implements FromView
{
    private array $ssdRows;

    public function __construct(array $rows)
    {
        $this->ssdRows = $rows;
    }

    public function view() : View
    {
        return view('ShipmentScheduleReport.export', [
            'rows' => $this->ssdRows,
        ]);
    }
}

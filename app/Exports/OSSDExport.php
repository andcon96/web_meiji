<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class OSSDExport implements FromView
{
    private array $ossdRows;

    public function __construct(array $rows)
    {
        $this->ossdRows = $rows;
    }

    public function view() : View
    {
        return view('otherShipmentScheduleReport.export', [
            'rows' => $this->ossdRows,
        ]);
    }
}

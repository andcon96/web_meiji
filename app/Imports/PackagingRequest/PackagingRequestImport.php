<?php

namespace App\Imports\PackagingRequest;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class PackagingRequestImport implements ToCollection
{
    /**
    * @param Collection $collection
    */
    public $rows;

    public function __construct()
    {
        $this->rows = collect();
    }
    public function collection(Collection $rows)
    {
        $this->rows = $rows;
    }

    public function getData()
    {
        return $this->rows;
    }
}

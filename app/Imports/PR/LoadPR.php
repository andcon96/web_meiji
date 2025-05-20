<?php

namespace App\Imports\PR;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LoadPR implements ToCollection, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    private $data;

    public function __construct(&$data)
    {
        $this->data = &$data;
    }

    public function collection(Collection $collection)
    {
        foreach ($collection as $row) {
            if ($row['product_code'] != '') {
                $this->data[] = [
                    'domain' => 'RISIS',
                    'item_code' => $row['product_code'],
                    'item_desc' => $row['description'],
                    'item_um' => $row['uom'],
                    'item_type' => $row['type'],
                    'item_prod_line' => $row['prod_line'],
                    'item_group' => $row['group'],
                    'item_promo' => $row['promo'],
                    'supplier_code' => $row['supplier_code'] ?? '',
                    'supplier_desc' => $row['vendor'] ?? '',
                    'quantity' => $row['qty'],
                    'location' => $row['location'],
                ];
            }
        }
    }
}

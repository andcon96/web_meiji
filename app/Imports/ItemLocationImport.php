<?php

namespace App\Imports;

use App\Models\Settings\Item;
use App\Models\Settings\ItemLocation;
use App\Models\Settings\Location;
use App\Models\Settings\LocationDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ItemLocationImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    /**
     * @param Collection $collection
     */

    public $errorList = [];

    public function collection(Collection $collection)
    {
        $insertDataItemLocation = [];

        foreach ($collection as $datas) {
            $cekMaster = Location::where('location_site', $datas['site'])
                ->where('location_code', $datas['location'])
                ->first();

            if (!$cekMaster) {
                $this->errorList[] = 'Location : ' . $datas['location'] . ', Site : ' . $datas['site'] . ' Not Found';
                // break;
            }

            $cekDetail = LocationDetail::where('ld_location_id', $cekMaster->id ?? '')
                ->where('ld_lot_serial', $datas['lot_serial'])
                ->where('ld_building', $datas['building'])
                ->where('ld_rak', $datas['level'])
                ->where('ld_bin', $datas['bin'])
                ->first();

            if (!$cekDetail && $cekMaster) {
                $this->errorList[] = 'Location : ' . $datas['location'] . ', Site : ' . $datas['site'] . ', Lot Serial : ' . $datas['lot_serial'] . ', Building : ' . $datas['building'] . ', Level : ' . $datas['level'] . ', Bin : ' . $datas['bin'] . ' Not Found';
                // break;
            }

            $cekItem = Item::where('im_item_part', $datas['item_part'])->first();

            if ($cekItem && $cekDetail && $cekMaster) {
                $insertDataItemLocation[] = [
                    'il_ld_id' => $cekDetail->id,
                    'il_item_id' => $cekItem->id,
                ];
            }
        }

        if (count($this->errorList) == 0 && count($insertDataItemLocation) > 0) {
            $chunks = array_chunk($insertDataItemLocation, 1000);
            foreach ($chunks as $chunk) {
                ItemLocation::insert($chunk);
            }
        }

        return;
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}

<?php

namespace App\Imports;

use App\Models\Settings\Location;
use App\Models\Settings\LocationDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LocationDetailImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        $insertDataDetail = [];
        foreach ($collection as $datas) {

            $cekMaster = Location::where('location_site', $datas['site'])
                ->where('location_code', $datas['location'])
                ->first();


            if (!$cekMaster) {
                $newMaster = new Location();
                $newMaster->location_site = $datas['site'];
                $newMaster->location_code = $datas['location'];
                $newMaster->location_desc = $datas['location'];
                $newMaster->save();

                $insertDataDetail[] = [
                    'ld_location_id' => $newMaster->id,
                    'ld_lot_serial' => $datas['lot_serial'],
                    'ld_building' => $datas['building'],
                    'ld_rak' => $datas['level'],
                    'ld_bin' => $datas['bin'],
                ];
            } else {
                $insertDataDetail[] = [
                    'ld_location_id' => $cekMaster->id,
                    'ld_lot_serial' => $datas['lot_serial'],
                    'ld_building' => $datas['building'],
                    'ld_rak' => $datas['level'],
                    'ld_bin' => $datas['bin'],
                ];
            }
        }

        $chunks = array_chunk($insertDataDetail, 1000);

        foreach ($chunks as $chunk) {
            LocationDetail::insert($chunk);
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}

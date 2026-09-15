<?php

namespace App\Imports;

use App\Models\Settings\Location;
use App\Models\Settings\LocationDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CycleDetailImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        $insertDataDetail = [];
       
        foreach ($collection as $datas) {

        DB::table('xxinv_det')->updateOrInsert(
                ['xxinv_part' => $datas['item'], 
                  'xxinv_site' => $datas['site'],
                  'xxinv_lot' => $datas['lot_serial'],
                  'xxinv_wrh' => $datas['building'],
                  'xxinv_level' => $datas['level'],
                  'xxinv_bin' => $datas['bin']
                  ],
                ['xxinv_qtyoh' => $datas['qty']               
             ]);

            // $cekMaster = xxinv_det::where('xxinv_part', $datas['item'])
            //     ->where('xxinv_site', $datas['site'])
            //     ->where('xxinv_lot', $datas['lot_serial'])
            //     ->where('xxinv_wrh', $datas['building'])
            //     ->where('xxinv_level', $datas['level'])
            //     ->where('xxinv_bin', $datas['bin'])
            //     ->first();


            // if (!$cekMaster) {
            //     $newMaster = new xxinv_det();
            //     $newMaster->location_site = $datas['site'];
            //     $newMaster->location_code = $datas['location'];
            //     $newMaster->location_desc = $datas['location'];
            //     $newMaster->save();

            //     $insertDataDetail[] = [
            //         'ld_location_id' => $newMaster->id,
            //         'ld_lot_serial' => $datas['lot_serial'],
            //         'ld_building' => $datas['building'],
            //         'ld_rak' => $datas['level'],
            //         'ld_bin' => $datas['bin'],
            //     ];
            // } else {
            //     $insertDataDetail[] = [
            //         'ld_location_id' => $cekMaster->id,
            //         'ld_lot_serial' => $datas['lot_serial'],
            //         'ld_building' => $datas['building'],
            //         'ld_rak' => $datas['level'],
            //         'ld_bin' => $datas['bin'],
            //     ];
            // }
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

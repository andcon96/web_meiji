<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResources;
use App\Models\API\ReceiptDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class APIZebraPrinterController extends Controller
{
    public function getDataPrintQR(Request $request)
    {
        $poNumber = $request->poNumber;
        $receiverNumber = $request->receiverNumber;
        $bookNumber = $request->bookNumber;

        $data = ReceiptDetail::query()
            ->with(['getMaster.getPurchaseOrderMaster', 'getPurchaseOrderDetail']);

        if ($poNumber) {
            $data->whereRelation('getMaster.getPurchaseOrderMaster', 'po_nbr', '=', $poNumber);
        }

        if ($receiverNumber) {
            $data->whereRelation('getMaster', 'rm_rn_number', '=', $receiverNumber);
        }

        if ($bookNumber) {
            $data->where('rd_nomor_buku', '=', $bookNumber);
        }

        $data = $data->get();

        return GeneralResources::collection($data);
    }

    public function printQRItem(Request $request)
    {
        // Log::channel('customlog')->info('Data : ', ['input' => $request->all()]);

        $data = json_decode($request->data);
        // dd($data);

        $template = file_get_contents(public_path('templateZebra/template1.prn'));

        dump($template);

        $replacements = [
            "ItemNumber"   => "ABC12345",
            "itemDesc"     => "Paracetamol 500mg",
            "lotSerial"    => "LOT-2025-001",
            "itemRef"      => "PO-8899",
            "supplierCode" => "SUP-001",
            "supplierDesc" => "PT Meiji Pharma",
            "receiptDate"  => "2025-08-22",
            "expDate"      => "2027-08-22",
            "currPage"     => "1",
            "totPage"      => "10",
            "qrCodeLabel"  => "ABC12345|LOT-2025-001|2025-08-22",
        ];

        // replace all placeholders in the template
        foreach ($replacements as $key => $value) {
            $template = str_replace($key, $value, $template);
        }

        dd($template);
    }
}

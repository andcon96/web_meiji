<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResources;
use App\Models\API\ReceiptDetail;
use App\Models\Settings\PrinterSetup;
use App\Services\ZebraPrinterServices;
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
        // Get Data
        $data = json_decode($request->data);

        foreach ($data as $datas) {
            for ($i = 1; $i <= $datas->qty_print; $i++) {
                // Assign Value to Template
                $template = file_get_contents(public_path('templateZebra/template1.prn'));

                $qrCodeLabel = $datas->get_purchase_order_detail->pod_part . '|' . $datas->rd_batch . '|' . $datas->rd_ref . '|'
                    . $datas->get_master->get_purchase_order_master->po_nbr . '|' . $datas->rd_tanggal_datang . '|' . $datas->rd_tgl_expire;

                $replacements = [
                    "ItemNumber"   => $datas->get_purchase_order_detail->pod_part,
                    "itemDesc"     => $datas->get_purchase_order_detail->pod_part_desc1,
                    "itemDes2"     => $datas->get_purchase_order_detail->pod_part_desc2,
                    "lotSerial"    => $datas->rd_batch,
                    "itemRef"      => $datas->rd_ref,
                    "supplierCode" => $datas->get_master->get_purchase_order_master->po_vend,
                    "supplierDesc" => $datas->get_master->get_purchase_order_master->po_vend_desc,
                    "receiptDate"  => $datas->rd_tanggal_datang,
                    "expDate"      => $datas->rd_tgl_expire,
                    "CurP"         => $i,
                    "TotP"         => $datas->qty_print,
                    "qrCodeLabel"  => $qrCodeLabel,
                ];

                // Replace all placeholders in the template
                foreach ($replacements as $key => $value) {
                    $template = str_replace($key, $value, $template);
                }

                try {
                    $setupPrinter = PrinterSetup::first();
                    $ip = $setupPrinter->ps_ip_printer;

                    $printerIp = $ip; // Replace with your Zebra printer's IP

                    ZebraPrinterServices::sendPrnToPrinter($template, $printerIp);
                } catch (\Exception $e) {
                    Log::info($e);
                }
            }
        }

        return response()->json(['success' => true, 'message' => 'Label sent to printer!']);
    }
}

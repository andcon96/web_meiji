<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResources;
use App\Models\API\ReceiptDetail;
use App\Models\Settings\PrinterSetup;
use App\Services\ZebraPrinterServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\API\PurchaseOrderMaster;

class APIZebraPrinterController extends Controller
{
    public function getDataPrintQR(Request $request)
    {
        $poNumber = $request->poNumber;
        $receiverNumber = $request->receiverNumber;
        $bookNumber = $request->bookNumber;
        $itemNumber = $request->itemNumber;
        $lotNumber = $request->lotNumber;

        $data = ReceiptDetail::query()
            ->with(['getMaster.getPurchaseOrderMaster', 'getPurchaseOrderDetail','getKemasan']);

        if ($poNumber) {
            $data->whereRelation('getMaster.getPurchaseOrderMaster', 'po_nbr', '=', $poNumber);
        }

        if ($receiverNumber) {
            $data->whereRelation('getMaster', 'rm_rn_number', '=', $receiverNumber);
        }

        if ($bookNumber) {
            $data->where('rd_nomor_buku', '=', $bookNumber);
        }
        if ($itemNumber) {
            // $data->where('rd_nama_barang', '=', $itemNumber);
            $data->whereRelation('getPurchaseOrderDetail', 'pod_part', '=', $itemNumber);
        }
        if ($lotNumber) {
            // $data->where('rd_nama_barang', '=', $itemNumber);
            $data->where('rd_batch', '=', $lotNumber);
        }

        $data = $data->orderBy('created_at','desc')->get();

        return GeneralResources::collection($data);
    }

    public function printQRItem(Request $request)
    {
        // Get Data
        $data = json_decode($request->data);

        foreach ($data as $datas) {
            for ($i = 1; $i <= $datas->qty_print; $i++) {
                // Assign Value to Template
                //$template = file_get_contents(public_path('templateZebra/template1.prn'));
                $template = file_get_contents(public_path('templateZebra/4.prn'));
                $qrCodeLabel = $datas->get_purchase_order_detail->pod_part . '|' . $datas->rd_batch . '|' . $datas->rd_ref . '|'
                    . $datas->get_master->get_purchase_order_master->po_nbr . '|' . $datas->rd_tanggal_datang . '|' . $datas->rd_tgl_expire;

                // $replacements = [
                // "ItemNumber"   => $datas->get_purchase_order_detail->pod_part,
                // "itemDesc"     => $datas->get_purchase_order_detail->pod_part_desc1,
                // "itemDes2"     => $datas->get_purchase_order_detail->pod_part_desc2,
                // "lotSerial"    => $datas->rd_batch,
                // // "itemRef"      => $datas->rd_ref,
                // // "supplierCode" => $datas->get_master->get_purchase_order_master->po_vend,
                // // "supplierDesc" => $datas->get_master->get_purchase_order_master->po_vend_desc,
                // "itemRef"      => $datas->rd_kode_cetak,
                // "supplierCode" => '',
                // "supplierDesc" => '',
                // "receiptDate"  => $datas->rd_tanggal_datang,
                // "expDate"      => $datas->rd_tgl_expire,
                // "CurP"         => $i,
                // "TotP"         => $datas->qty_print,
                // "qrCodeLabel"  => $qrCodeLabel,

                // ];
                $dataExpired = isset($datas->rd_tgl_expire) ? (new \DateTime($datas->rd_tgl_expire))->format('d-m-Y') : '-';
                $dataReceipt = isset($datas->rd_tanggal_datang) ? (new \DateTime($datas->rd_tanggal_datang))->format('d-m-Y') : '-';
                $dataretest = isset($datas->rd_tgl_retest) ? (new \DateTime($datas->rd_tgl_retest))->format('d-m-Y') : '-';
                $replacements = [
                    "xxITEM" => $datas->get_purchase_order_detail->pod_part,
                    "xxDescription1" => $datas->get_purchase_order_detail->pod_part_desc1,
                    "xxlot1234" => $datas->rd_batch,
                    "xxDescription2" => $datas->get_purchase_order_detail->pod_part_desc2,
                    "xxRCPDate" => $dataReceipt,
                    "xxExpDate" => $dataExpired,
                    "hal1" => $i,
                    "hal2" => $datas->qty_print,
                    "xxRTDate" => $dataretest,
                    "qclabel" => $qrCodeLabel
                    ];
                    //"qrCodeLabel" => $qrCodeLabel,
                    
                // Replace all placeholders in the template
                foreach ($replacements as $key => $value) {
                    $template = str_replace($key, $value, $template);
                }

                try {
                    // $setupPrinter = PrinterSetup::first();
                    $setupPrinter = PrinterSetup::where('ps_printer_name', $request->printerName)->first();
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

    //mira
    public function printQRItemWO(Request $request)
    {
        // Get Data
        $woNumber = $request->wo;
        $site = $request->site;
        $itemNumber = $request->part;
        $itemDesc1 = $request->desc1;
        $itemDesc2 = $request->desc2;
        $lotSerial = $request->lot;
        $refNumber = $request->ref;
        $effDate = $request->effDate;
        $expDate = $request->expDate;
        $printerName = $request->printerName;
        $qtyPrint = (int) $request->qtyPrint;

        for ($i = 1; $i <= $qtyPrint; $i++) {
            // Assign Value to Template
            //$template = file_get_contents(public_path('templateZebra/template1.prn'));
            $template = file_get_contents(public_path('templateZebra/6x6=36.prn'));
            //Print QR PO
            // $qrCodeLabel = $datas->get_purchase_order_detail->pod_part . '|' . $datas->rd_batch . '|' . $datas->rd_ref . '|'
            //     . $datas->get_master->get_purchase_order_master->po_nbr . '|' . $datas->rd_tanggal_datang . '|' . $datas->rd_tgl_expire;

            //Print QR WO
            $qrCodeLabel = $itemNumber . '|' . $lotSerial . '|' . $refNumber . '|' . $woNumber . '|' . $effDate . '|' . $expDate;

            //Print QR PO
            // $replacements = [
            //     "xxITEM" => $datas->get_purchase_order_detail->pod_part,
            //     "xxDescription1" => $datas->get_purchase_order_detail->pod_part_desc1,
            //     "xxlot1234" => $datas->rd_batch,
            //     "xxDescription2" => $datas->get_purchase_order_detail->pod_part_desc2,
            //     "qrCodeLabel" => $qrCodeLabel,
            //     "xxRCP02-10-2026" => isset($datas->rd_tanggal_datang) ? (new \DateTime($datas->rd_tanggal_datang))->format('d-m-Y') : '',
            //     "xxEXP02-10-2026" => isset($datas->rd_tgl_exp) ? (new \DateTime($datas->rd_tgl_exp))->format('d-m-Y') : '',
            //     "xhal1" => $i,
            //     "xhal2" => $datas->qty_print,
            //     "xxRTS02-12-2026" => isset($datas->rd_tgl_retest) ? (new \DateTime($datas->rd_tgl_retest))->format('d-m-Y') : ''
            // ];

            //Print QR WO
            $replacements = [
                "xxITEM" => $itemNumber,
                "xxDescription1" => $itemDesc1,
                "xxlot1234" => $lotSerial,
                "xxDescription2" => $itemDesc2,
                "qrCodeLabel" => $qrCodeLabel,

                "xxRCP02-10-2026" => isset($effDate) ? (new \DateTime($effDate))->format('d-m-Y') : '',
                "xxEXP02-10-2026" => isset($expDate) ? (new \DateTime($expDate))->format('d-m-Y') : '',

                "xhal1" => $i,
                "xhal2" => $qtyPrint,
                "xxRTS02-12-2026" => ''
            ];

            // Replace all placeholders in the template
            foreach ($replacements as $key => $value) {
                $template = str_replace($key, $value, $template);
            }

            try {
                // $setupPrinter = PrinterSetup::first();
                $setupPrinter = PrinterSetup::where('ps_printer_name', $printerName)->first();
                $ip = $setupPrinter->ps_ip_printer;

                $printerIp = $ip; // Replace with your Zebra printer's IP

                ZebraPrinterServices::sendPrnToPrinter($template, $printerIp);
                
            } catch (\Exception $e) {
                Log::info($e);
                return response()->json([
                    'success' => false,
                    'Message' => 'Printer error: ' . $e->getMessage()
                ], 500);
            }
        }

        return response()->json(['success' => true, 'Message' => 'Label sent to printer!']);
    }

    public function getPoPrint(Request $request)
    {
        $data = PurchaseOrderMaster::query();

        if ($request->search) {
            $data->where('po_nbr', 'like', '%' . $request->search . '%');
        }


        $data = $data->orderBy('po_nbr')->get();

        return GeneralResources::collection($data);
    }

    public function getBookPrint(Request $request)
    {
        $data = ReceiptDetail::query();

        if ($request->search) {
            $data->where('rd_nomor_buku', 'like', '%' . $request->search . '%');
        }
        $data = $data->select('rd_nomor_buku')->groupBy('rd_nomor_buku')->orderBy('rd_nomor_buku')->get();
        //$data = $data->groupBy('rd_nomor_buku')->orderBy('rd_nomor_buku')->get();

        return GeneralResources::collection($data);
    }

    public function getItemPrint(Request $request)
    {

        $data = ReceiptDetail::query();
        if ($request->search) {
            $data->where('rd_nama_barang', 'like', '%' . $request->search . '%');
        }
        $data = $data->select('rd_nama_barang')
            ->groupBy('rd_nama_barang')->orderBy('rd_nama_barang')->get();
        log:
        info($data);
        //$data = $data->groupBy('rd_nama_barang')->orderBy('rd_nama_barang')->get();

        return GeneralResources::collection($data);
    }
    public function getPrinterPrint(Request $request)
    {

        $data = PrinterSetup::query();
        if ($request->search) {
            $data->where('ps_printer_name', 'like', '%' . $request->search . '%');
        }
        $data = $data
            ->orderBy('ps_printer_name')->get();

        //$data = $data->groupBy('rd_nama_barang')->orderBy('rd_nama_barang')->get();

        return GeneralResources::collection($data);
    }

        public function getLotPrint(Request $request)
    {
        $data = ReceiptDetail::query();

        if ($request->search) {
            $data->where('rd_batch', 'like', '%' . $request->search . '%');
        }
        $data = $data->select('rd_batch')->groupBy('rd_batch')->orderBy('rd_batch')->get();
        //$data = $data->groupBy('rd_nomor_buku')->orderBy('rd_nomor_buku')->get();

        return GeneralResources::collection($data);
    }
}

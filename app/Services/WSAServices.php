<?php

namespace App\Services;

use App\Models\API\PurchaseOrderDetail;
use App\Models\API\PurchaseOrderMaster;
use App\Models\API\ReceiptDetail;
use App\Models\PurchaseOrder\POMstr;
use App\Models\Settings\Domain;
use App\Models\Settings\qxwsa;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WSAServices
{
    private function httpHeader($req)
    {
        return array(
            'Content-type: text/xml;charset="utf-8"',
            'Accept: text/xml',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'SOAPAction: ""',        // jika tidak pakai SOAPAction, isinya harus ada tanda petik 2 --> ""
            'Content-length: ' . strlen(preg_replace("/\s+/", " ", $req))
        );
    }

    private function sendQdocRequest($qdocRequest, $activeConnectionType)
    {
        $wsa_path = $activeConnectionType->wsa_path;
        $wsa_url = $activeConnectionType->wsa_url;
        // $wsa_path = '' . $wsa->wsa_path . '';
        // $wsa_url = 'http://qad2021ee.server:22079/wsa/wsaweb/';

        $timeout = 0;
        $wsaUrl = $wsa_url;
        $curlOptions = array(
            CURLOPT_URL => $wsaUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        if (is_bool($qdocResponse)) {
            return false;
        }

        $xmlResp = simplexml_load_string($qdocResponse);
        $xmlResp->registerXPathNamespace('ns1', $wsa_path);
        $dataloop   = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [$qdocResult, $dataloop];
    }

    public function wsaLastBatch($batch, $item)
    {
        $wsa = qxwsa::first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_last_batch xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainCode . '</inpdomain>' .
            '<inpbatch>' . $batch . '</inpbatch>' .
            '<inpitem>' . $item . '</inpitem>' .
            '</meiji_last_batch>' .
            '</Body>' .
            '</Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];
        $dataBatchWeb = ReceiptDetail::where('rd_status', '!=', 'Approved')->get();

        foreach ($dataBatchWeb as $datas) {
            if ($batch === '' || Str::contains(Str::lower($datas->rd_batch), Str::lower($batch))) {
                $dataloop[] = (object) [
                    't_domain'    => $domainCode,
                    't_site'      => $datas->rd_site_penyimpanan,
                    't_loc'       => $datas->rd_location_penyimpanan,
                    't_warehouse' => $datas->rd_building_penyimpanan,
                    't_level'     => $datas->rd_level_penyimpanan,
                    't_bin'       => $datas->rd_bin_penyimpanan,
                    't_lot'       => $datas->rd_batch,
                ];
            }
        }
        return [
            $qdocResult,
            json_decode(json_encode($dataloop), true),
        ];
    }

    public function wsaGenCode($fldname)
    {
        $wsa = qxwsa::first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_gen_code xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainCode . '</inpdomain>' .
            '<inpfldname>' . $fldname . '</inpfldname>' .
            '</meiji_gen_code>' .
            '</Body>' .
            '</Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            json_decode(json_encode($dataloop), true),
        ];
    }

    public function wsaitem()
    {
        $wsa = qxwsa::first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_item_mstr xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainCode . '</inpdomain>' .
            '</meiji_item_mstr>' .
            '</Body>' .
            '</Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaLocation()
    {
        $wsa = qxwsa::first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_loc_mstr xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainCode . '</inpdomain>' .
            '</meiji_loc_mstr>' .
            '</Body>' .
            '</Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaPurchaseOrder($poNbr)
    {
        $wsa = qxwsa::first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_purchase_order xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainCode . '</inpdomain>' .
            '<inpponbr>' . $poNbr . '</inpponbr>' .
            '</meiji_purchase_order>' .
            '</Body>' .
            '</Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }
        //  dd($qdocRequest,$qdocResponse);
        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
       
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        $dataHeader = [];

        $dataMaster = PurchaseOrderMaster::firstOrNew(
            ['po_nbr' => (string)$dataloop[0]->t_poNbr]
        );
        $dataMaster->po_vend = (string)$dataloop[0]->t_poVend;
        $dataMaster->po_vend_desc = (string)$dataloop[0]->t_poVendDesc;
        $dataMaster->po_ord_date = (string)$dataloop[0]->t_poOrdDate;
        $dataMaster->po_due_date = (string)$dataloop[0]->t_poDueDate;
        $dataMaster->po_rmks = (string)$dataloop[0]->t_poRmks;
        $dataMaster->po_stat = (string)$dataloop[0]->t_poStat;
        $dataMaster->po_site = (string)$dataloop[0]->t_poSite;
        $dataMaster->po_loc_def = (string)$dataloop[0]->t_poLoc;
        $dataMaster->save();

        $dataHeader[] = [
            'id' => $dataMaster->id,
            'po_nbr' => (string)$dataloop[0]->t_poNbr,
            'po_vend' => (string)$dataloop[0]->t_poVend,
            'po_vend_desc' => (string)$dataloop[0]->t_poVendDesc,
            'po_ord_date' => (string)$dataloop[0]->t_poOrdDate,
            'po_due_date' => (string)$dataloop[0]->t_poDueDate,
            'po_stat' => (string)$dataloop[0]->t_poStat,
            'po_site' => (string)$dataloop[0]->t_poSite,
            'po_loc_def' => (string)$dataloop[0]->t_poLoc,
        ];

        $dataDetail = [];
        foreach ($dataloop as $listDatas) {
            $newDataDetail = PurchaseOrderDetail::firstOrNew(
                [
                    'pod_po_mstr_id' => $dataMaster->id,
                    'pod_line' => (string)$listDatas->t_podLine
                ]
            );
            $newDataDetail->pod_part = (string)$listDatas->t_podPart;
            $newDataDetail->pod_part_desc = (string)$listDatas->t_podPartDesc;
            $newDataDetail->pod_part_desc1 = (string)$listDatas->t_partDesc1;
            $newDataDetail->pod_part_desc2 = (string)$listDatas->t_partDesc2;
            $newDataDetail->pod_qty_ord = (string)$listDatas->t_podQtyOrd;
            $newDataDetail->pod_qty_rcpt = (string)$listDatas->t_podQtyRcpt;
            $newDataDetail->pod_qty_potensi = (string)$listDatas->t_potensi;
            $newDataDetail->pod_um = (string)$listDatas->t_podUm;
            $newDataDetail->pod_pt_um = (string)$listDatas->t_ptUm;
            $newDataDetail->pod_pallete = (string)$listDatas->t_ptPallete;
            $newDataDetail->save();

            $dataDetail[] = [
                'id' => $newDataDetail->id,
                'po_mstr_id' => $dataMaster->id,
                'pod_line' => (string)$listDatas->t_podLine,
                'pod_part' => (string)$listDatas->t_podPart,
                'pod_part_desc' => (string)$listDatas->t_podPartDesc,
                'pod_part_desc1' => (string)$listDatas->t_partDesc1,
                'pod_part_desc2' => (string)$listDatas->t_partDesc2,
                'pod_qty_ord' => (string)$listDatas->t_podQtyOrd,
                'pod_qty_rcpt' => (string)$listDatas->t_podQtyRcpt,
                'pod_qty_ongoing' => '0',
                'pod_qty_potensi' =>(string)$listDatas->t_potensi ?? '1',
                'pod_um' => (string)$listDatas->t_podUm,
                'pt_um' => (string)$listDatas->t_ptUm,
                'pt_pallete' => (string)$listDatas->t_ptPallete,
                'is_selected' => false, // Buat Menu Android
                'is_expandable' => false, // Buat Menu Android
            ];
        }

        return [
            $qdocResult,
            $dataHeader,
            $dataDetail
        ];
    }

    public function wsaChangeUmConv($poNbr, $podLine, $qtyUmConv)
    {
        $wsa = qxwsa::first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_update_pod_um_conv xmlns="' . $wsa->wsa_path . '">' .
            '<inpDomain>' . $domainCode . '</inpDomain>' .
            '<inpPoNbr>' . $poNbr . '</inpPoNbr>' .
            '<inpLine>' . $podLine . '</inpLine>' .
            '<inpQtyUmConv>' . $qtyUmConv . '</inpQtyUmConv>' .
            '</meiji_update_pod_um_conv>' .
            '</Body>' .
            '</Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return $qdocResult;
    }

    public function wsaUpdateStockTableCustom($part, $loc, $lot, $bin, $lvl, $site, $building, $qty, $entryDate, $expDate,$potensi,$ref)
    {
        $wsa = qxwsa::first();

        $qxUrl = $wsa->wsa_url;
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_update_xxinv_det xmlns="' . $wsa->wsa_path . '">' .
            '<inpDomain>' . $domainCode . '</inpDomain>' .
            '<inpPart>' . $part . '</inpPart>' .
            '<inpLoc>' . $loc . '</inpLoc>' .
            '<inpLot>' . $lot . '</inpLot>' .
            '<inpSite>' . $site . '</inpSite>' .
            '<inpLvl>' . $lvl . '</inpLvl>' .
            '<inpBin>' . $bin . '</inpBin>' .
            '<inpWrh>' . $building . '</inpWrh>' .
            '<inpQty>' . $qty . '</inpQty>' .
            '<inpEntryDate>' . $entryDate . '</inpEntryDate>' .
            '<inpExpDate>' . $expDate . '</inpExpDate>' .
            '<inpPotensi>' . $potensi . '</inpPotensi>' .
            '<inpRef>' . $ref . '</inpRef>' .
            '</meiji_update_xxinv_det>' .
            '</Body>' .
            '</Envelope>';
        Log::info($qdocRequest);
        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }
        log::info($qdocResponse);
         log::info($qdocRequest);
        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];


        return $qdocResult;
    }

    public function wsaLotSerialLdDetail($itemCode)
    {
        $wsa = qxwsa::first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_ld_det xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainCode . '</inpdomain>' .
            '<inppart>' . $itemCode . '</inppart>' .
            '</meiji_ld_det>' .
            '</Body>' .
            '</Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            json_decode(json_encode($dataloop), true),
        ];
    }

    public function wsaPenyimpanan($site, $itemCode, $lot, $bin, $warehouse, $level)
    {
        $wsa = qxwsa::first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_xxinv_det_wrh xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainCode . '</inpdomain>' .
            '<inpsite>' . $site . '</inpsite>' .
            '<inppart>' . $itemCode . '</inppart>' .
            '<inplot>' . $lot . '</inplot>' .
            '<inpbin>' . $bin . '</inpbin>' .
            '<inpwrh>' . $warehouse . '</inpwrh>' .
            '<inplevel>' . $level . '</inplevel>' .
            
            '</meiji_xxinv_det_wrh>' .
            '</Body>' .
            '</Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }
        log::info($qdocResponse);
         log::info($qdocRequest);
        $xmlResp = simplexml_load_string($qdocResponse);
        // dd($qdocRequest,$qdocResponse);
        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];
        
        return [
            $qdocResult,
            json_decode(json_encode($dataloop), true),
        ];
    }

      public function wsaPenyimpananWrh($site, $itemCode, $lot, $bin, $warehouse, $level)
    {
        $wsa = qxwsa::first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_xxinv_det_wrh xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainCode . '</inpdomain>' .
            '<inpsite>' . $site . '</inpsite>' .
            '<inppart>' . $itemCode . '</inppart>' .
            '<inplot>' . $lot . '</inplot>' .
            '<inpbin>' . $bin . '</inpbin>' .
            '<inpwrh>' . $warehouse . '</inpwrh>' .
            '<inplevel>' . $level . '</inplevel>' .
            
            '</meiji_xxinv_det_wrh>' .
            '</Body>' .
            '</Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);
        // dd($qdocRequest,$qdocResponse);
        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];
        
        return [
            $qdocResult,
            json_decode(json_encode($dataloop), true),
        ];
    }

    public function wsaPenyimpananTransfer($site, $itemCode, $lot, $bin, $warehouse, $level)
    {
        $wsa = qxwsa::first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_xxinv_det_transfer xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainCode . '</inpdomain>' .
            '<inpsite>' . $site . '</inpsite>' .
            '<inppart>' . $itemCode . '</inppart>' .
            '<inplot>' . $lot . '</inplot>' .
            '<inpbin>' . $bin . '</inpbin>' .
            '<inpwrh>' . $warehouse . '</inpwrh>' .
            '<inplevel>' . $level . '</inplevel>' .
            '</meiji_xxinv_det_transfer>' .
            '</Body>' .
            '</Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];
        
        return [
            $qdocResult,
            json_decode(json_encode($dataloop), true),
        ];
    }

    public function wsaSampleLoc()
    {
        $wsa = qxwsa::first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_sample_desti xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainCode . '</inpdomain>' .
            '</meiji_sample_desti>' .
            '</Body>' .
            '</Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            json_decode(json_encode($dataloop), true),
        ];
    }

    public function wsaCustomer($activeConnectionType)
    {
        $wsa = qxwsa::first();
        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';
        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                <Body>
                    <meiji_cust_mstr xmlns="' . $wsa->wsa_path . '">
                        <inpdomain>' . $domainCode . '</inpdomain>
                    </meiji_cust_mstr>
                </Body>
            </Envelope>';

        return $this->sendQdocRequest($qdocRequest, $activeConnectionType);
    }

    public function wsaSalesOrder($customer, $activeConnectionType)
    {
        $wsa = qxwsa::first();
        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';
        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                <Body>
                    <meiji_get_SO xmlns="' . $wsa->wsa_path . '">
                        <inpdomain>' . $domainCode . '</inpdomain>
                        <inpcust>' . $customer . '</inpcust>
                    </meiji_get_SO>
                </Body>
            </Envelope>';

        return $this->sendQdocRequest($qdocRequest, $activeConnectionType);
    }

    public function wsaInventoryDetail($site, $itemCode, $lot, $activeConnectionType)
    {
        $wsa = qxwsa::first();
        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';
        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_xxinv_det_fifo xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainCode . '</inpdomain>' .
            '<inpsite>' . $site . '</inpsite>' .
            '<inppart>' . $itemCode . '</inppart>' .
            '<inplot>' . $lot . '</inplot>' .
            '<inpbin></inpbin>' .
            '<inpwrh></inpwrh>' .
            '<inplevel></inplevel>' .
            '</meiji_xxinv_det_fifo>' .
            '</Body>' .
            '</Envelope>';

        Log::channel('shipmentSchedule')->info($qdocRequest);

        return $this->sendQdocRequest($qdocRequest, $activeConnectionType);
    }

    public function wsaGetShipperNumber($site, $packingReplenishmentID, $activeConnectionType)
    {
        $wsa = qxwsa::first();
        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';
        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                <Body>
                    <meiji_get_shipper_number xmlns="' . $wsa->wsa_path . '">
                        <inpdomain>' . $domainCode . '</inpdomain>
                        <inpship>' . $site . '</inpship>
                        <inpidref>' . $packingReplenishmentID . '</inpidref>
                    </meiji_get_shipper_number>
                </Body>
            </Envelope>';

        return $this->sendQdocRequest($qdocRequest, $activeConnectionType);
    }

    public function wsaGetWOMstr()
    {

        $wsa = qxwsa::first();


        $qxUrl = $wsa->wsa_url;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_get_wo_mstr xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainCode . '</inpdomain>' .

            '</meiji_get_wo_mstr>' .
            '</Body>' .
            '</Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];
        $dataWO = [];
        foreach ($dataloop as $listDatas) {


            $dataWO[] = [

                'id' => (string)$listDatas->t_wo_id,
                'wonbr' => (string)$listDatas->t_wo_nbr,
                'site' => (string)$listDatas->t_wo_site,
                'part' => (string)$listDatas->t_wo_part,
                'part_desc' => (string)$listDatas->t_wo_part_desc ?? '',
                'um' => (string)$listDatas->t_wo_um,
                'qty_ord' => (string)$listDatas->t_wo_qty_ord,

            ];
        }

        return [
            $qdocResult,
            $dataWO,
        ];
    }
    public function wsaGetWODetail($wonbr)
    {

        $wsa = qxwsa::first();


        $qxUrl = $wsa->wsa_url;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_get_wo_det xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainCode . '</inpdomain>' .
            '<inpwo>' . $wonbr . '</inpwo>' .
            '</meiji_get_wo_det>' .
            '</Body>' .
            '</Envelope>';


        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaGetInvWo($wonbr)
    {

        $wsa = qxwsa::first();


        $qxUrl = $wsa->wsa_url;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_get_picklist_detail xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainCode . '</inpdomain>' .
            '<inpwo>' . $wonbr . '</inpwo>' .
            '</meiji_get_picklist_detail>' .
            '</Body>' .
            '</Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaUpdateQtyOHCustom($data)
    {
        $wsa = qxwsa::first();

        $qxUrl = $wsa->wsa_url;
        $timeout = 0;
        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $site = $data['site'];
        $item = $data['item'];
        $lot = $data['lot'];
        $qty = $data['pick'];

        $qdocRequest = '
        <Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <meiji_update_xxinv_qtyoh xmlns="' . $wsa->wsa_path . '">
                    <inpdomain>' . $domainCode . '</inpdomain>
                    <inpsite>' . $site . '</inpsite>
                    <inpitem>' . $item . '</inpitem>
                    <inplot>' . $lot . '</inplot>
                    <inppick>' . $qty . '</inppick>
                </meiji_update_xxinv_qtyoh>
            </Body>
        </Envelope>
        ';

        Log::channel('confirmShipment')->info($qdocRequest);

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];


        return $qdocResult;
    }

    public function wsaGetItemMstrWo()
    {

        $wsa = qxwsa::first();


        $qxUrl = $wsa->wsa_url;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_item_mstr_wo xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainCode . '</inpdomain>' .
            '</meiji_item_mstr_wo>' .
            '</Body>' .
            '</Envelope>';


        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];


        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaGetInvItem($item, $site, $loc)
    {

        $wsa = qxwsa::first();


        $qxUrl = $wsa->wsa_url;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_get_picklist_detail_item xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainCode . '</inpdomain>' .
            '<inpitem>' . $item . '</inpitem>' .
            '<inpsite>' . $site . '</inpsite>' .
            '<inploc>' . $loc . '</inploc>' .
            '</meiji_get_picklist_detail_item>' .
            '</Body>' .
            '</Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaGetPickNbr()
    {

        $wsa = qxwsa::first();


        $qxUrl = $wsa->wsa_url;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_get_xxpick_mstr xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainCode . '</inpdomain>' .
            '<inpstatus></inpstatus>' .
            '</meiji_get_xxpick_mstr>' .
            '</Body>' .
            '</Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaGetPickDetail($status)
    {

        $wsa = qxwsa::first();


        $qxUrl = $wsa->wsa_url;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_get_xxpick_det xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainCode . '</inpdomain>' .
            '<inpstatus>' . $status . '</inpstatus>' .
            '</meiji_get_xxpick_det>' .
            '</Body>' .
            '</Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];
        
        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaUpdateStatusPick($picknbr, $status, $qty, $part, $lot)
    {

        $wsa = qxwsa::first();


        $qxUrl = $wsa->wsa_url;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_update_status_xxpick xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainCode . '</inpdomain>
            <inppick>' . $picknbr . '</inppick>
            <inpstatus>' . $status . '</inpstatus>' .
            '<inpqty>'.$qty.'</inpqty>
            <inppart>'.$part.'</inppart>
            <inplot>'.$lot.'</inplot>'.
            '</meiji_update_status_xxpick>' .
            '</Body>' .
            '</Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);
        
        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];
        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaUpdateQtyPick($picknbr, $qtypick, $wonbr, $wodpart, $site, $loc, $lot, $wrh, $level, $bin)
    {

        $wsa = qxwsa::first();


        $qxUrl = $wsa->wsa_url;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_send_qtypick_xxpick xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainCode . '</inpdomain>
            <inppick>' . $picknbr . '</inppick>
            <inpwo>' . $wonbr . '</inpwo>
            <inpwodpart>' . $wodpart . '</inpwodpart>
            <inpsite>' . $site . '</inpsite>
            <inploc>' . $loc . '</inploc>
            <inplot>' . $lot . '</inplot>
            <inpwrh>' . $wrh . '</inpwrh>
            <inplevel>' . $level . '</inplevel>
            <inpbin>' . $bin . '</inpbin>
            <inpqtypick>' . $qtypick . '</inpqtypick>' .


            '</meiji_send_qtypick_xxpick>' .
            '</Body>' .
            '</Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }
log::info($qdocRequest);
log::info($qdocResponse);
        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return
            $qdocResult;
    }

    public function wsaGetLocationPick($site)
    {

        $wsa = qxwsa::first();


        $qxUrl = $wsa->wsa_url;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_get_loc_xxpick xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainCode . '</inpdomain>
            <inpsite>' . $site . '</inpsite>' .


            '</meiji_get_loc_xxpick>' .
            '</Body>' .
            '</Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaQtyConversion($packingReplenishment, $activeConnectionType)
    {
        $wsa = qxwsa::first();
        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';
        $sodNbr = $packingReplenishment['sodNbr'];
        $sodLine = $packingReplenishment['sodLine'];
        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                <Body>
                    <meiji_uom_conversion xmlns="' . $wsa->wsa_path . '">
                        <inpdomain>' . $domainCode . '</inpdomain>
                        <inpnbr>' . $sodNbr . '</inpnbr>
                        <inpline>' . $sodLine . '</inpline>
                    </meiji_uom_conversion>
                </Body>
            </Envelope>';

        return $this->sendQdocRequest($qdocRequest, $activeConnectionType);
    }

    public function wsaGetLocationTransfer($wonbr,$item,$site)
    {

        $wsa = qxwsa::first();


        $qxUrl = $wsa->wsa_url;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_get_loc_transfer xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>'.$domainCode.'</inpdomain>'.
            '<inpsite>'.$site.'</inpsite>'.
            '<inpwonbr>'.$wonbr.'</inpwonbr>'.
            '<inpitem>'.$item.'</inpitem>' .


            '</meiji_get_loc_transfer>' .
            '</Body>' .
            '</Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    
    public function wsaGetSiteTransfer($site,$item,$location)
    {

        $wsa = qxwsa::first();


        $qxUrl = $wsa->wsa_url;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_get_site_transfer xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainCode . '</inpdomain>
            <inpsite>' . $site . '</inpsite>' .
             '<inpitem>' . $item . '</inpitem>' .
             '<inplocation>' . $location . '</inplocation>' .



            '</meiji_get_site_transfer>' .
            '</Body>' .
            '</Envelope>';
        // dd($qdocRequest);
        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];
        
        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaGetSites(String $inppart, String $inplot)
    {

        $wsa = qxwsa::first();


        $qxUrl = $wsa->wsa_url;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            "<Envelope xmlns='http://schemas.xmlsoap.org/soap/envelope/'>
                <Body>
                    <meiji_get_sites xmlns='urn:imi.co.id:wsaweb'>
                        <inpdomain>$domainCode</inpdomain>
                        <inppart>$inppart</inppart>
                        <inplot>$inplot</inplot>
                    </meiji_get_sites>
                </Body>
            </Envelope>";

        /* dd($qdocRequest); */
        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaGetLocData(String $inppart, String $inplot, String $inpsite)
    {

        $wsa = qxwsa::first();


        $qxUrl = $wsa->wsa_url;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            "<Envelope xmlns='http://schemas.xmlsoap.org/soap/envelope/'>
                <Body>
                    <meiji_get_loc_data xmlns='urn:imi.co.id:wsaweb'>
                        <inpdomain>$domainCode</inpdomain>
                        <inppart>$inppart</inppart>
                        <inplot>$inplot</inplot>
                        <inpsite>$inpsite</inpsite>
                    </meiji_get_loc_data>
                </Body>
            </Envelope>";

        /* dd($qdocRequest); */
        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaGetInvDet($site, $loc, $warehouse,$item)
    {
        $wsa = qxwsa::first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_xxinv_det_pick xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainCode . '</inpdomain>' .
            '<inpsite>' . $site . '</inpsite>' .
            '<inppart>'.$item.'</inppart>' .
            '<inplot></inplot>' .
            '<inpbin/>' .
            '<inpwrh>' . $warehouse . '</inpwrh>' .
            '<inplevel/>' .
            '<inploc>'.$loc.'</inploc>'.
            '</meiji_xxinv_det_pick>' .
            '</Body>' .
            '</Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];
        
        return [
            $qdocResult,
            json_decode(json_encode($dataloop), true),
        ];
    }
    public function wsaInsertPallet($site, $loc, $bin, $wrh, $level, $qty)
    {
        $wsa = qxwsa::first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_insert_xxinvdet xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>'.$domainCode.'</inpdomain>'.
            '<inpsite>'.$site.'</inpsite>'.
            '<inploc>'.$loc.'</inploc>'.

            '<inpbin>'.$bin.'</inpbin>'.
            '<inpwrh>'.$wrh.'</inpwrh>'.
            '<inplevel>'.$level.'</inplevel>'.
            '<inpqty>'.$qty.'</inpqty>' .
            '</meiji_insert_xxinvdet>'.
            '</Body>' .
            '</Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        //$dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return
            $qdocResult;


    }

    public function wsaGetIssueData($wonbr)
    {

        $wsa = qxwsa::first();


        $qxUrl = $wsa->wsa_url;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_get_womstr xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainCode . '</inpdomain>' .
            '<inpwonbr>' . $wonbr . '</inpwonbr>' .
            '</meiji_get_womstr>' .
            '</Body>' .
            '</Envelope>';


        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaGetPickIssue($status,$picknbr)
    {

        $wsa = qxwsa::first();


        $qxUrl = $wsa->wsa_url;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_get_pick_issue xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainCode . '</inpdomain>' .
            '<inpstatus>' . $status . '</inpstatus>' .
            '<inppick>' . $picknbr . '</inppick>' .
            '</meiji_get_pick_issue>' .
            '</Body>' .
            '</Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaInvWms(String $inppart, String $inplot)
    {
        $wsa = qxwsa::first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest = "<Envelope xmlns='http://schemas.xmlsoap.org/soap/envelope/'>
                            <Body>
                                <meiji_inv_wms xmlns='$wsa->wsa_path'>
                                    <inpdomain>$domainCode</inpdomain>
                                    <inppart>$inppart</inppart>
                                    <inplot>$inplot</inplot>
                                </meiji_inv_wms>
                            </Body>
                        </Envelope>";

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        if (is_bool($qdocResponse)) {
            return false;
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop   = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK') [0];

        //ubah {} pada data kosong jadi ""
        $dataloop = array_map(function ($data) {
            $row = [];
            foreach ($data as $key => $value) {
                $row[$key] = (string) $value;
            }
            return $row;
        }, $dataloop);
        
        return [
            $qdocResult,
            $dataloop,
        ];
    }
    
    //     public function wsaLotSerialLdDetail($lotpallet)
    // {
    //     $wsa = qxwsa::first();

    //     $qxUrl = $wsa->wsa_url;
    //     $qxReceiver = '';
    //     $qxSuppRes = 'false';
    //     $qxScopeTrx = '';
    //     $qdocName = '';
    //     $qdocVersion = '';
    //     $dsName = '';
    //     $timeout = 0;

    //     $domain = Domain::first();
    //     $domainCode = $domain->domain ?? '';

    //     $qdocRequest = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
    //                 <Body>
    //                     <rdi_check_lot xmlns="' . $wsa->wsa_path . '">
    //                         <inpdomain>' . $domainCode . '</inpdomain>
    //                         <inplot>' . $lotpallet . '</inplot>
    //                     </rdi_check_lot>
    //                 </Body>
    //             </Envelope>';

    //     $curlOptions = array(
    //         CURLOPT_URL => $qxUrl,
    //         CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
    //         CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
    //         CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
    //         CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
    //         CURLOPT_POST => true,
    //         CURLOPT_RETURNTRANSFER => true,
    //         CURLOPT_SSL_VERIFYPEER => false,
    //         CURLOPT_SSL_VERIFYHOST => false
    //     );

    //     $getInfo = '';
    //     $httpCode = 0;
    //     $curlErrno = 0;
    //     $curlError = '';
    //     $qdocResponse = '';

    //     $curl = curl_init();
    //     if ($curl) {
    //         curl_setopt_array($curl, $curlOptions);
    //         $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
    //         $curlErrno    = curl_errno($curl);
    //         $curlError    = curl_error($curl);
    //         $first        = true;

    //         foreach (curl_getinfo($curl) as $key => $value) {
    //             if (gettype($value) != 'array') {
    //                 if (!$first) $getInfo .= ", ";
    //                 $getInfo = $getInfo . $key . '=>' . $value;
    //                 $first = false;
    //                 if ($key == 'http_code') $httpCode = $value;
    //             }
    //         }
    //         curl_close($curl);
    //     }

    //     if (is_bool($qdocResponse)) {
    //         return false;
    //     }

    //     $xmlResp = simplexml_load_string($qdocResponse);

    //     $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

    //     $dataloop    = $xmlResp->xpath('//ns1:tempRow');
    //     $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

    //     return [
    //         $qdocResult,
    //         $dataloop
    //     ];
    // }

    public function wsaInputSupplier($part, $lot, $inpsupplier) //untuk update insert supplier
    {
        $wsa = qxwsa::first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<rdi_update_supp_ld_det xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainCode . '</inpdomain>' .
            '<inppart>' . $part . '</inppart>' .
            '<inplot>' . $lot . '</inplot>' .
            '<inpsupplier>' . $inpsupplier . '</inpsupplier>' .
            '</rdi_update_supp_ld_det>' .
            '</Body>' .
            '</Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        if (is_bool($qdocResponse)) {
            return false;
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return $qdocResult;
    }

    public function wsaCheckLocation($location) //check lokasi nya ada di qad atau tidak
    {
        $wsa = qxwsa::first();

        $qxUrl = $wsa->wsa_url;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<rdi_check_location xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainCode . '</inpdomain>' .
            '<inploc>' . $location . '</inploc>' .
            '</rdi_check_location>' .
            '</Body>' .
            '</Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        if (is_bool($qdocResponse)) {
            return false;
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaCheckItem($item) //check item nya ada di qad atau tidak + menampilkan balancen stok
    {
        $wsa = qxwsa::first();


        $qxUrl = $wsa->wsa_url;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<rdi_check_item xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainCode . '</inpdomain>' .
            '<inpPart>' . $item . '</inpPart>' .
            '</rdi_check_item>' .
            '</Body>' .
            '</Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        if (is_bool($qdocResponse)) {
            return false;
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaCheckSupplier($supplier) //check supplier ada di qad atau tidak
    {
        $wsa = qxwsa::first();

        $qxUrl = $wsa->wsa_url;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<rdi_check_supplier xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainCode . '</inpdomain>' .
            '<inpSupp>' . $supplier . '</inpSupp>' .
            '</rdi_check_supplier>' .
            '</Body>' .
            '</Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        if (is_bool($qdocResponse)) {
            return false;
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaDataInquiry($item, $location)
    {
        $wsa = qxwsa::first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                    <Body>
                        <rdi_check_item_data_inquiry xmlns="' . $wsa->wsa_path . '">
                            <inpdomain>' . $domainCode . '</inpdomain>
                            <inpitem>' . $item . '</inpitem>
                            <inploc>' . $location . '</inploc>
                        </rdi_check_item_data_inquiry>
                    </Body>
                </Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        if (is_bool($qdocResponse)) {
            return false;
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];
        $itemCode = (string) $xmlResp->xpath('//ns1:ItemCode')[0];
        $itemDesc = (string) $xmlResp->xpath('//ns1:ItemDesc')[0];
        $totalQty = (string) $xmlResp->xpath('//ns1:totalQtyOH')[0];

        return [
            $qdocResult,
            $itemCode,
            $itemDesc,
            $totalQty,
            $dataloop
        ];
    }

    public function wsaLotLoc($lotpallet, $location)
    {
        $wsa = qxwsa::first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                    <Body>
                        <rdi_check_loc_lot xmlns="' . $wsa->wsa_path . '">
                            <inpdomain>' . $domainCode . '</inpdomain>
                            <inplot>' . $lotpallet . '</inplot>
                            <inploc>' .$location. '</inploc>
                        </rdi_check_loc_lot>
                    </Body>
                </Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        if (is_bool($qdocResponse)) {
            return false;
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop
        ];
    }

    public function wsaGetSamplingData($item, $lot,$status)
    {
        $wsa = qxwsa::first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_get_sampling_data xmlns="' . $wsa->wsa_path . '">
                <inpdomain>' . $domainCode . '</inpdomain>
                <inploc>' . $status . '</inploc>
                <inppart>'.$item.'</inppart>
                <inplot>'.$lot.'</inplot>
            </meiji_get_sampling_data>'.
           
            '</Body>' .
            '</Envelope>';
        
        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }
        
        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            json_decode(json_encode($dataloop), true),
        ];
    }

     public function wsaGetWarehouseSampling($item, $lot,$status)
    {
        $wsa = qxwsa::first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_get_warehouse_sampling xmlns="' . $wsa->wsa_path . '">
                <inpdomain>' . $domainCode . '</inpdomain>
                <inploc>' . $status . '</inploc>
                <inppart>'.$item.'</inppart>
                <inplot>'.$lot.'</inplot>
            </meiji_get_warehouse_sampling>'.
           
            '</Body>' .
            '</Envelope>';
        
        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }
        //dd($qdocRequest, $qdocResponse);
        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];
        
        return [
            $qdocResult,
            json_decode(json_encode($dataloop), true),
        ];
    }

     public function wsaGetLotSampling($item, $lot,$status)
    {
        $wsa = qxwsa::first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_get_lot_sampling xmlns="' . $wsa->wsa_path . '">
                <inpdomain>' . $domainCode . '</inpdomain>
                <inploc>' . $status . '</inploc>
                <inppart>'.$item.'</inppart>
                <inplot>'.$lot.'</inplot>
            </meiji_get_lot_sampling>'.
           
            '</Body>' .
            '</Envelope>';
        
        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            json_decode(json_encode($dataloop), true),
        ];
    }

    public function wsaTransferSamplingData($item, $lot,$site,$loc,$locto,$warehouse,$level,$bin,$qtyoh)
    {
        $wsa = qxwsa::first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_transfer_sampling xmlns="' . $wsa->wsa_path . '">
                <inpdomain>'.$domainCode.'</inpdomain>
                <inppart>'.$item.'</inppart>
                <inplot>'.$lot.'</inplot>
                <inpsite>'.$site.'</inpsite>
                <inploc>'.$loc.'</inploc>
                <inplocto>'.$locto.'</inplocto>
                <inpwarehouse>'.$warehouse.'</inpwarehouse>
                <inplevel>'.$level.'</inplevel>
                <inpbin>'.$bin.'</inpbin>
                <inpqtyoh>'.$qtyoh.'</inpqtyoh>
            </meiji_transfer_sampling>'.
            '</Body>' .
            '</Envelope>';
           
        
        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }
        log::channel('samplingLog')->info($qdocRequest);
        log::channel('samplingLog')->info($qdocResponse);
        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        if ($qdocResult === false){
            log::channel('samplingLog')->info('masuk false');
           return 'false';
        }
        else{
            log::channel('samplingLog')->info('masuk true');
            return 'true';
        }
        
    }

   
    public function wsaGetWrhData(
        String $inppart,
        String $inplot,
        String $inpsite,
        String $inploc)
    {

        $wsa = qxwsa::first();


        $qxUrl = $wsa->wsa_url;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            "<Envelope xmlns='http://schemas.xmlsoap.org/soap/envelope/'>
                <Body>
                    <meiji_get_wrh_data xmlns='urn:imi.co.id:wsaweb'>
                        <inpdomain>$domainCode</inpdomain>
                        <inppart>$inppart</inppart>
                        <inplot>$inplot</inplot>
                        <inpsite>$inpsite</inpsite>
                        <inploc>$inploc</inploc>
                    </meiji_get_wrh_data>
                </Body>
            </Envelope>";

        /* dd($qdocRequest); */
        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }


    public function wsaGetLevelData(
        String $inppart,
        String $inplot,
        String $inpsite,
        String $inploc,
        String $inpwrh)
    {

        $wsa = qxwsa::first();


        $qxUrl = $wsa->wsa_url;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            "<Envelope xmlns='http://schemas.xmlsoap.org/soap/envelope/'>
                <Body>
                    <meiji_get_level_data xmlns='urn:imi.co.id:wsaweb'>
                        <inpdomain>$domainCode</inpdomain>
                        <inppart>$inppart</inppart>
                        <inplot>$inplot</inplot>
                        <inpsite>$inpsite</inpsite>
                        <inploc>$inploc</inploc>
                        <inpwrh>$inpwrh</inpwrh>
                    </meiji_get_level_data>
                </Body>
            </Envelope>";

        /* dd($qdocRequest); */
        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }


    public function wsaGetBinData(
        String $inppart,
        String $inplot,
        String $inpsite,
        String $inploc,
        String $inpwrh,
        String $inpLevel)
    {

        $wsa = qxwsa::first();


        $qxUrl = $wsa->wsa_url;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            "<Envelope xmlns='http://schemas.xmlsoap.org/soap/envelope/'>
                <Body>
                    <meiji_get_bin_data xmlns='urn:imi.co.id:wsaweb'>
                        <inpdomain>$domainCode</inpdomain>
                        <inppart>$inppart</inppart>
                        <inplot>$inplot</inplot>
                        <inpsite>$inpsite</inpsite>
                        <inploc>$inploc</inploc>
                        <inpwrh>$inpwrh</inpwrh>
                        <inplevel>$inpLevel</inplevel>
                    </meiji_get_bin_data>
                </Body>
            </Envelope>";

        /* dd($qdocRequest); */
        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaGetLevelForPo($part,$lot,$site,$wrh,$loc,$level)
    {

        $wsa = qxwsa::first();


        $qxUrl = $wsa->wsa_url;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            "<Envelope xmlns='http://schemas.xmlsoap.org/soap/envelope/'>
                <Body>
                    <meiji_get_level_for_po xmlns='".$wsa->wsa_path."'>
                         <inpdomain>$domainCode</inpdomain>
                        <inppart>$part</inppart>
                        <inplot>$lot</inplot>
                        <inpsite>$site</inpsite>
                        <inploc>$loc</inploc>
                        <inpwrh>$wrh</inpwrh>
                        <inplevel>$level</inplevel>
                    </meiji_get_level_for_po>
                </Body>
            </Envelope>";
        

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }


    public function wsaGetBinForPo($part,$lot,$site,$wrh,$loc,$level,$bin)
    {

        $wsa = qxwsa::first();


        $qxUrl = $wsa->wsa_url;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            "<Envelope xmlns='http://schemas.xmlsoap.org/soap/envelope/'>
                <Body>
                    <meiji_get_bin_for_po xmlns='".$wsa->wsa_path."'>
                         <inpdomain>$domainCode</inpdomain>
                        <inppart>$part</inppart>
                        <inplot>$lot</inplot>
                        <inpsite>$site</inpsite>
                        <inploc>$loc</inploc>
                        <inpwrh>$wrh</inpwrh>
                        <inplevel>$level</inplevel>
                                                <inpbin>$bin</inpbin>
                    </meiji_get_bin_for_po>
                </Body>
            </Envelope>";

        
        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }
// dd($qdocRequest,$qdocResponse);
        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaCekItemLot(String $inppart, String $inplot)
    {

        $wsa = qxwsa::first();


        $qxUrl = $wsa->wsa_url;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            "<Envelope xmlns='http://schemas.xmlsoap.org/soap/envelope/'>
                <Body>
                    <meiji_cek_itemlot xmlns='urn:imi.co.id:wsaweb'>
                        <inpdomain>$domainCode</inpdomain>
                        <inppart>$inppart</inppart>
                        <inplot>$inplot</inplot>
                    </meiji_cek_itemlot>
                </Body>
            </Envelope>";

        /* dd($qdocRequest); */
        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        // $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];
        $qdocMsg = (string) $xmlResp->xpath('//ns1:outMsg')[0];

        return [
            $qdocResult,
            $qdocMsg
            // $dataloop,
        ];
    }
     public function wsaGetPotensi($part,$lot,$site,$loc)
    {

        $wsa = qxwsa::first();


        $qxUrl = $wsa->wsa_url;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            "<Envelope xmlns='http://schemas.xmlsoap.org/soap/envelope/'>
                <Body>
                    <meiji_get_potensi xmlns='".$wsa->wsa_path."'>
                         <inpdomain>$domainCode</inpdomain>
                        <inppart>$part</inppart>
                        <inplot>$lot</inplot>
                        <inpsite>$site</inpsite>
                        <inploc>$loc</inploc>

                    </meiji_get_potensi>
                </Body>
            </Envelope>";

        /* dd($qdocRequest); */
        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaWarehouse($site, $itemCode, $lot, $bin, $warehouse, $level)
    {
        $wsa = qxwsa::first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_get_warehouse xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainCode . '</inpdomain>' .
            '<inpsite>' . $site . '</inpsite>' .
            '<inppart>' . $itemCode . '</inppart>' .
            '<inplot>' . $lot . '</inplot>' .
            '<inpbin>' . $bin . '</inpbin>' .
            '<inpwrh>' . $warehouse . '</inpwrh>' .
            '<inplevel>' . $level . '</inplevel>' .
            
            '</meiji_get_warehouse>' .
            '</Body>' .
            '</Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];
        
        return [
            $qdocResult,
            json_decode(json_encode($dataloop), true),
        ];
    }

      public function wsaPenyimpananPalet($site, $itemCode, $lot, $bin, $warehouse, $level)
    {
        $wsa = qxwsa::first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_xxinv_det_palet xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainCode . '</inpdomain>' .
            '<inpsite>' . $site . '</inpsite>' .
            '<inppart>' . $itemCode . '</inppart>' .
            '<inplot>' . $lot . '</inplot>' .
            '<inpbin>' . $bin . '</inpbin>' .
            '<inpwrh>' . $warehouse . '</inpwrh>' .
            '<inplevel>' . $level . '</inplevel>' .
            
            '</meiji_xxinv_det_palet>' .
            '</Body>' .
            '</Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];
        //dd($qdocRequest,$qdocResponse);
        return [
            $qdocResult,
            json_decode(json_encode($dataloop), true),
        ];
    }
    public function wsaGetWlb($part,$lot,$site,$loc,$wrh,$level,$bin)
    {

        $wsa = qxwsa::first();


        $qxUrl = $wsa->wsa_url;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            "<Envelope xmlns='http://schemas.xmlsoap.org/soap/envelope/'>
                <Body>
                    <meiji_get_wlb_transfer xmlns='".$wsa->wsa_path."'>
                         <inpdomain>$domainCode</inpdomain>
                        <inppart>$part</inppart>
                        <inplot>$lot</inplot>
                        <inpsite>$site</inpsite>
                        <inploc>$loc</inploc>
                        <inpwrh>$wrh</inpwrh>
                        <inplevel>$level</inplevel>
                        <inpbin>$bin</inpbin>
                    </meiji_get_wlb_transfer>
                </Body>
            </Envelope>";
        

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }
        
        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
 
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

      public function wsaGetPtUm($part)
    {

        $wsa = qxwsa::first();


        $qxUrl = $wsa->wsa_url;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            "<Envelope xmlns='http://schemas.xmlsoap.org/soap/envelope/'>
                <Body>
                    <meiji_get_pt_um xmlns='".$wsa->wsa_path."'>
                         <inpdomain>$domainCode</inpdomain>
                        <inppart>$part</inppart>
                        
                    </meiji_get_pt_um>
                </Body>
            </Envelope>";
        

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }
        
        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
 
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

         public function wsaGetWarehouseCheckReturn($item, $lot,$status,$warehouse,$level,$bin)
    {
        $wsa = qxwsa::first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_check_warehouse_sampling xmlns="' . $wsa->wsa_path . '">
                <inpdomain>' . $domainCode . '</inpdomain>
                <inploc>' . $status . '</inploc>
                <inppart>'.$item.'</inppart>
                <inplot>'.$lot.'</inplot>
                <inpwarehouse>' . $warehouse . '</inpwarehouse>
                <inplevel>' . $level . '</inplevel>
                <inpbin>' . $bin . '</inpbin>
            </meiji_check_warehouse_sampling>'.
           
            '</Body>' .
            '</Envelope>';
        
        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $getInfo = '';
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = '';
        $qdocResponse = '';

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) $getInfo .= ", ";
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                    if ($key == 'http_code') $httpCode = $value;
                }
            }
            curl_close($curl);
        }
        
        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];
        
        return [
            $qdocResult,
            json_decode(json_encode($dataloop), true),
        ];
    }
}

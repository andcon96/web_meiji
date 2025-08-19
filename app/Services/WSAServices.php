<?php

namespace App\Services;

use App\Models\API\PurchaseOrderDetail;
use App\Models\API\PurchaseOrderMaster;
use App\Models\PurchaseOrder\POMstr;
use App\Models\Settings\Domain;
use App\Models\Settings\qxwsa;
use Illuminate\Support\Facades\Log;

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
        // $wsa_path = 'urn:imi.co.id:wsaweb';
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
            $newDataDetail->pod_qty_ord = (string)$listDatas->t_podQtyOrd;
            $newDataDetail->pod_qty_rcpt = (string)$listDatas->t_podQtyRcpt;
            $newDataDetail->pod_um = (string)$listDatas->t_podUm;
            $newDataDetail->pod_pt_um = (string)$listDatas->t_ptUm;
            $newDataDetail->save();

            $dataDetail[] = [
                'id' => $newDataDetail->id,
                'po_mstr_id' => $dataMaster->id,
                'pod_line' => (string)$listDatas->t_podLine,
                'pod_part' => (string)$listDatas->t_podPart,
                'pod_part_desc' => (string)$listDatas->t_podPartDesc,
                'pod_qty_ord' => (string)$listDatas->t_podQtyOrd,
                'pod_qty_rcpt' => (string)$listDatas->t_podQtyRcpt,
                'pod_qty_ongoing' => '0',
                'pod_um' => (string)$listDatas->t_podUm,
                'pt_um' => (string)$listDatas->t_ptUm,
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

    public function wsaUpdateStockTableCustom($part, $loc, $lot, $bin, $lvl, $site, $building, $qty, $entryDate, $expDate)
    {
        $wsa = qxwsa::first();

        $qxUrl = $wsa->wsa_url;
        $timeout = 0;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_update_xxinv_det xmlns="urn:imi.co.id:wsaweb">' .
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
            '<meiji_ld_det xmlns="urn:imi.co.id:wsaweb">' .
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

    public function wsaPenyimpanan($itemCode, $lot, $bin, $warehouse, $level)
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
            '<meiji_xxinv_det xmlns="urn:imi.co.id:wsaweb">' .
            '<inpdomain>' . $domainCode . '</inpdomain>' .
            '<inppart>' . $itemCode . '</inppart>' .
            '<inplot>' . $lot . '</inplot>' .
            '<inpbin>' . $bin . '</inpbin>' .
            '<inpwrh>' . $warehouse . '</inpwrh>' .
            '<inplevel>' . $level . '</inplevel>' .
            '</meiji_xxinv_det>' .
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
            '<meiji_sample_desti xmlns="urn:imi.co.id:wsaweb">' .
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
        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                <Body>
                    <meiji_cust_mstr xmlns="urn:imi.co.id:wsaweb">
                        <inpdomain>10USA</inpdomain>
                    </meiji_cust_mstr>
                </Body>
            </Envelope>';

        return $this->sendQdocRequest($qdocRequest, $activeConnectionType);
    }

    public function wsaSalesOrder($customer, $activeConnectionType)
    {
        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                <Body>
                    <meiji_get_SO xmlns="urn:imi.co.id:wsaweb">
                        <inpdomain>10USA</inpdomain>
                        <inpcust>' . $customer . '</inpcust>
                    </meiji_get_SO>
                </Body>
            </Envelope>';

        return $this->sendQdocRequest($qdocRequest, $activeConnectionType);
    }

    public function wsaInventoryDetail($itemCode, $lot, $activeConnectionType)
    {
        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<meiji_xxinv_det xmlns="urn:imi.co.id:wsaweb">' .
            '<inpdomain>10USA</inpdomain>' .
            '<inppart>' . $itemCode . '</inppart>' .
            '<inplot>' . $lot . '</inplot>' .
            '<inpbin></inpbin>' .
            '<inpwrh></inpwrh>' .
            '<inplevel></inplevel>' .
            '</meiji_xxinv_det>' .
            '</Body>' .
            '</Envelope>';

        return $this->sendQdocRequest($qdocRequest, $activeConnectionType);
    }

    public function wsaGetShipperNumber($site, $packingReplenishmentID, $activeConnectionType)
    {
        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                <Body>
                    <meiji_get_shipper_number xmlns="urn:imi.co.id:wsaweb">
                        <inpdomain>10USA</inpdomain>
                        <inpship>' . $site . '</inpship>
                        <inpidref>' . $packingReplenishmentID . '</inpidref>
                    </meiji_get_shipper_number>
                </Body>
            </Envelope>';

        return $this->sendQdocRequest($qdocRequest, $activeConnectionType);
    }

    public function wsaGetWO($wonbr)
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
            '<meiji_get_wo xmlns="urn:imi.co.id:wsaweb">' .
            '<inpdomain>' . $domainCode . '</inpdomain>' .
            '<inpwo>' . $wonbr . '</inpwo>' .
            '</meiji_get_wo>' .
            '</Body>' .
            '</Envelope>';
        return $qdocRequest;
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
}

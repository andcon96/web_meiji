<?php

namespace App\Services;

use App\Models\PurchaseOrder\POMstr;
use App\Models\SalesOrder\SOMstr;
use App\Models\Settings\Domain;
use App\Models\Settings\qxwsa;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class QxtendServices
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

    private function sendQdocRequest($qdocRequest, $activeConnection)
    {
        $timeout = 0;
        $qxUrl = $activeConnection->qx_url;
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
            //
            $curlErrno = curl_errno($curl);
            $curlError = curl_error($curl);
            $first = true;
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

        $xmlResp->registerXPathNamespace('ns1', 'urn:schemas-qad-com:xml-services');

        $qdocResult = (string) $xmlResp->xpath('//ns1:result')[0];

        // $qdocResult = 'success';

        if ($qdocResult == "success" or $qdocResult == "warning") {
            return [true, ''];
        } else {
            $xmlResp->registerXPathNamespace('ns3', 'urn:schemas-qad-com:xml-services:common');
            $qdocMsgDesc = $xmlResp->xpath('//ns3:tt_msg_desc');
            $output = '';
            foreach ($qdocMsgDesc as $datas) {
                if (str_contains($datas, 'ERROR:')) {
                    $output .= $datas . ' - ';
                }
            }
            $output = substr($output, 0, -3);

            return [false, $output];
        }
    }

    public function qxTransferSingleItemWMS($part, $qtyoh, $sitefrom, $siteto, $locfrom, $locto, $lotfrom, $lotto, $buildingfrom, $buildingto, $levelfrom, $levelto, $binfrom, $binto)
    {
        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';
        $qxwsa = Qxwsa::firstOrFail();

        // Var Qxtend
        $qxUrl          = $qxwsa->qx_url;
        $receiver         = 'QADERP';

        $timeout        = 0;

        // XML Qextend
        $qdocHead = '<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services" xmlns:qcom="urn:schemas-qad-com:xml-services:common" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
						<soapenv:Header>
							<wsa:Action/>
							<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
							<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
							<wsa:ReferenceParameters>
								<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
							</wsa:ReferenceParameters>
							<wsa:ReplyTo>
								<wsa:Address>urn:services-qad-com:</wsa:Address>
							</wsa:ReplyTo>
						</soapenv:Header>
						<soapenv:Body>
							<transferSingleItemWMS>
								<qcom:dsSessionContext>
									<qcom:ttContext>
										<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
										<qcom:propertyName>domain</qcom:propertyName>
										<qcom:propertyValue>' . $domainCode . '</qcom:propertyValue>
									</qcom:ttContext>
									<qcom:ttContext>
										<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
										<qcom:propertyName>scopeTransaction</qcom:propertyName>
										<qcom:propertyValue>false</qcom:propertyValue>
									</qcom:ttContext>
									<qcom:ttContext>
										<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
										<qcom:propertyName>version</qcom:propertyName>
										<qcom:propertyValue>CUST_1</qcom:propertyValue>
									</qcom:ttContext>
									<qcom:ttContext>
										<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
										<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
										<qcom:propertyValue>false</qcom:propertyValue>
									</qcom:ttContext>
								<qcom:ttContext>
									<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
									<qcom:propertyName>action</qcom:propertyName>
									<qcom:propertyValue/>
								</qcom:ttContext>
								<qcom:ttContext>
									<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
									<qcom:propertyName>entity</qcom:propertyName>
									<qcom:propertyValue/>
								</qcom:ttContext>
								<qcom:ttContext>
									<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
									<qcom:propertyName>email</qcom:propertyName>
									<qcom:propertyValue/>
								</qcom:ttContext>
								<qcom:ttContext>
									<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
									<qcom:propertyName>emailLevel</qcom:propertyName>
									<qcom:propertyValue/>
								</qcom:ttContext>
							</qcom:dsSessionContext>
							<dsTransWms>
								<transWms>
									<operation>A</operation>
									<vPart>' . $part . '</vPart>
									<vQty>' . $qtyoh . '</vQty>
									<vSiteFrom>' . $sitefrom . '</vSiteFrom>
									<vLocFrom>' . $locfrom . '</vLocFrom>
									<vLotFrom>' . $lotfrom . '</vLotFrom>
									<vWhFrom>' . $buildingfrom . '</vWhFrom>
									<vLevelFrom>' . $levelfrom . '</vLevelFrom>
									<vBinFrom>' . $binfrom . '</vBinFrom>
									<vSiteTo>' . $siteto . '</vSiteTo>
									<vLocTo>' . $locto . '</vLocTo>
									<vWhTo>' . $buildingto . '</vWhTo>
									<vLevelTo>' . $levelto . '</vLevelTo>
									<vBinTo>' . $binto . '</vBinTo>
									<vYn>true</vYn>
								</transWms>
							</dsTransWms>
						</transferSingleItemWMS>
					</soapenv:Body>
					</soapenv:Envelope>';

        $qdocRequest = $qdocHead;

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
            //
            $curlErrno = curl_errno($curl);
            $curlError = curl_error($curl);
            $first = true;
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

        $xmlResp->registerXPathNamespace('ns1', 'urn:schemas-qad-com:xml-services');

        $qdocResult = (string) $xmlResp->xpath('//ns1:result')[0];

        if ($qdocResult == "success" or $qdocResult == "warning") {
            return [true, ''];
        } else {
            $xmlResp->registerXPathNamespace('ns3', 'urn:schemas-qad-com:xml-services:common');
            $qdocMsgDesc = $xmlResp->xpath('//ns3:tt_msg_desc');
            $output = '';
            foreach ($qdocMsgDesc as $datas) {
                if (str_contains($datas, 'ERROR:')) {
                    $output .= $datas . ' - ';
                }
            }
            $output = substr($output, 0, -3);

            return [false, $output];
        }
    }

    public function qxPurchaseOrderReceipt($ponbr, $line, $lotSerialQty, $um, $site, $location, $lotserial)
    {
        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';
        $qxwsa = Qxwsa::firstOrFail();

        // Var Qxtend
        $qxUrl          = $qxwsa->qx_url;
        $receiver         = 'QADERP';

        $timeout        = 0;

        // XML Qextend
        $qdocHead = '<?xml version="1.0" encoding="UTF-8"?>
                        <soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
                        xmlns:qcom="urn:schemas-qad-com:xml-services:common"
                        xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
                        <soapenv:Header>
                            <wsa:Action/>
                            <wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
                            <wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
                            <wsa:ReferenceParameters>
                            <qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
                            </wsa:ReferenceParameters>
                            <wsa:ReplyTo>
                            <wsa:Address>urn:services-qad-com:</wsa:Address>
                            </wsa:ReplyTo>
                        </soapenv:Header>
                        <soapenv:Body>
                            <receivePurchaseOrder>
                            <qcom:dsSessionContext>
                                <qcom:ttContext>
                                <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                                <qcom:propertyName>domain</qcom:propertyName>
                                <qcom:propertyValue>' . $domainCode . '</qcom:propertyValue>
                                </qcom:ttContext>
                                <qcom:ttContext>
                                <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                                <qcom:propertyName>scopeTransaction</qcom:propertyName>
                                <qcom:propertyValue>true</qcom:propertyValue>
                                </qcom:ttContext>
                                <qcom:ttContext>
                                <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                                <qcom:propertyName>version</qcom:propertyName>
                                <qcom:propertyValue>eB_2</qcom:propertyValue>
                                </qcom:ttContext>
                                <qcom:ttContext>
                                <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                                <qcom:propertyName>mnemonicsRaw</qcom:propertyName>
                                <qcom:propertyValue>false</qcom:propertyValue>
                                </qcom:ttContext>
                                <qcom:ttContext>
                                <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                                <qcom:propertyName>action</qcom:propertyName>
                                <qcom:propertyValue/>
                                </qcom:ttContext>
                                <qcom:ttContext>
                                <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                                <qcom:propertyName>entity</qcom:propertyName>
                                <qcom:propertyValue/>
                                </qcom:ttContext>
                                <qcom:ttContext>
                                <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                                <qcom:propertyName>email</qcom:propertyName>
                                <qcom:propertyValue/>
                                </qcom:ttContext>
                                <qcom:ttContext>
                                <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                                <qcom:propertyName>emailLevel</qcom:propertyName>
                                <qcom:propertyValue/>
                                </qcom:ttContext>
                            </qcom:dsSessionContext>
                            <dsPurchaseOrderReceive>';
        $qdocbody = '<purchaseOrderReceive>
                            <ordernum>' . $ponbr . '</ordernum>
                            <yn>true</yn>
                            <yn1>true</yn1>
                            <lineDetail>
                                    <line>' . $line . '</line>
                                    <lotserialQty>' . $lotSerialQty . '</lotserialQty>
                                    <receiptUm>' . $um . '</receiptUm>
                                    <site>' . $site . '</site>
                                    <location>' . $location . '</location>
                                    <lotserial>' . $lotserial . '</lotserial>
                                    <multiEntry>false</multiEntry>
                                    <serialsYn>true</serialsYn>
							</lineDetail>
							</purchaseOrderReceive>';


        $qdocfoot = '
        </dsPurchaseOrderReceive>
        </receivePurchaseOrder>
                        </soapenv:Body>
                    </soapenv:Envelope>';

        $qdocRequest = $qdocHead . $qdocbody . $qdocfoot;

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
            //
            $curlErrno = curl_errno($curl);
            $curlError = curl_error($curl);
            $first = true;
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

        $xmlResp->registerXPathNamespace('ns1', 'urn:schemas-qad-com:xml-services');

        $qdocResult = (string) $xmlResp->xpath('//ns1:result')[0];

        if ($qdocResult == "success" or $qdocResult == "warning") {
            return [true, ''];
        } else {
            $xmlResp->registerXPathNamespace('ns3', 'urn:schemas-qad-com:xml-services:common');
            $qdocMsgDesc = $xmlResp->xpath('//ns3:tt_msg_desc');
            $output = '';
            foreach ($qdocMsgDesc as $datas) {
                if (str_contains($datas, 'ERROR:')) {
                    $output .= $datas . ' - ';
                }
            }
            $output = substr($output, 0, -3);

            return [false, $output];
        }
    }

    public function qxTransferSingleItemPackingReplenishment($packingReplenishment, $locationDetail, $activeConnection)
    {
        $receiver = 'QADERP';

        $qdocRequest = '<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services" xmlns:qcom="urn:schemas-qad-com:xml-services:common" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
						<soapenv:Header>
							<wsa:Action/>
							<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
							<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
							<wsa:ReferenceParameters>
								<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
							</wsa:ReferenceParameters>
							<wsa:ReplyTo>
								<wsa:Address>urn:services-qad-com:</wsa:Address>
							</wsa:ReplyTo>
						</soapenv:Header>
						<soapenv:Body>
							<transferSingleItemWMS>
								<qcom:dsSessionContext>
									<qcom:ttContext>
										<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
										<qcom:propertyName>domain</qcom:propertyName>
										<qcom:propertyValue>' . $activeConnection->wsas_domain . '</qcom:propertyValue>
									</qcom:ttContext>
									<qcom:ttContext>
										<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
										<qcom:propertyName>scopeTransaction</qcom:propertyName>
										<qcom:propertyValue>true</qcom:propertyValue>
									</qcom:ttContext>
									<qcom:ttContext>
										<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
										<qcom:propertyName>version</qcom:propertyName>
										<qcom:propertyValue>CUST_1</qcom:propertyValue>
									</qcom:ttContext>
									<qcom:ttContext>
										<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
										<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
										<qcom:propertyValue>false</qcom:propertyValue>
									</qcom:ttContext>
								<qcom:ttContext>
									<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
									<qcom:propertyName>action</qcom:propertyName>
									<qcom:propertyValue/>
								</qcom:ttContext>
								<qcom:ttContext>
									<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
									<qcom:propertyName>entity</qcom:propertyName>
									<qcom:propertyValue/>
								</qcom:ttContext>
								<qcom:ttContext>
									<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
									<qcom:propertyName>email</qcom:propertyName>
									<qcom:propertyValue/>
								</qcom:ttContext>
								<qcom:ttContext>
									<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
									<qcom:propertyName>emailLevel</qcom:propertyName>
									<qcom:propertyValue/>
								</qcom:ttContext>
							</qcom:dsSessionContext>
							<dsTransWms>
								<transWms>
									<operation>A</operation>
									<vPart>' . $packingReplenishment['sodPart'] . '</vPart>
									<vQty>' . $locationDetail['qtyPick'] . '</vQty>
									<vSiteFrom>' . $locationDetail['site'] . '</vSiteFrom>
									<vLocFrom>' . $locationDetail['loc'] . '</vLocFrom>
									<vLotFrom>' . $locationDetail['lot'] . '</vLotFrom>
									<vWhFrom>' . $locationDetail['wh'] . '</vWhFrom>
									<vLevelFrom>' . $locationDetail['level'] . '</vLevelFrom>
									<vBinFrom>' . $locationDetail['bin'] . '</vBinFrom>
									<vSiteTo>' . $locationDetail['site'] . '</vSiteTo>
									<vLocTo>DOCK</vLocTo>
									<vWhTo></vWhTo>
									<vLevelTo></vLevelTo>
									<vBinTo></vBinTo>
									<vYn>true</vYn>
								</transWms>
							</dsTransWms>
						</transferSingleItemWMS>
					</soapenv:Body>
					</soapenv:Envelope>';

        return $this->sendQdocRequest($qdocRequest, $activeConnection);
    }

    public function qxSalesOrderShipper($action, $packingReplenishments, $id, $activeConnection)
    {
        $receiver = 'QADERP';
        $operation = '';

        switch ($action) {
            case 'delete':
                $operation = 'D';
            break;

            default:
                $operation = 'A';
            break;
        }

        $qdocRequest =
            '<?xml version="1.0" encoding="UTF-8"?>
                <soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
                xmlns:qcom="urn:schemas-qad-com:xml-services:common"
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
                <soapenv:Header>
                    <wsa:Action/>
                    <wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
                    <wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
                    <wsa:ReferenceParameters>
                    <qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
                    </wsa:ReferenceParameters>
                    <wsa:ReplyTo>
                    <wsa:Address>urn:services-qad-com:</wsa:Address>
                    </wsa:ReplyTo>
                </soapenv:Header>
                <soapenv:Body>
                    <maintainSalesOrderShipper>
                    <qcom:dsSessionContext>
                        <qcom:ttContext>
                        <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                        <qcom:propertyName>domain</qcom:propertyName>
                        <qcom:propertyValue>10USA</qcom:propertyValue>
                        </qcom:ttContext>
                        <qcom:ttContext>
                        <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                        <qcom:propertyName>scopeTransaction</qcom:propertyName>
                        <qcom:propertyValue>true</qcom:propertyValue>
                        </qcom:ttContext>
                        <qcom:ttContext>
                        <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                        <qcom:propertyName>version</qcom:propertyName>
                        <qcom:propertyValue>ERP3_1</qcom:propertyValue>
                        </qcom:ttContext>
                        <qcom:ttContext>
                        <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                        <qcom:propertyName>mnemonicsRaw</qcom:propertyName>
                        <qcom:propertyValue>false</qcom:propertyValue>
                        </qcom:ttContext>
                        <!--
                        <qcom:ttContext>
                        <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                        <qcom:propertyName>username</qcom:propertyName>
                        <qcom:propertyValue/>
                        </qcom:ttContext>
                        <qcom:ttContext>
                        <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                        <qcom:propertyName>password</qcom:propertyName>
                        <qcom:propertyValue/>
                        </qcom:ttContext>
                        -->
                        <qcom:ttContext>
                        <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                        <qcom:propertyName>action</qcom:propertyName>
                        <qcom:propertyValue/>
                        </qcom:ttContext>
                        <qcom:ttContext>
                        <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                        <qcom:propertyName>entity</qcom:propertyName>
                        <qcom:propertyValue/>
                        </qcom:ttContext>
                        <qcom:ttContext>
                        <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                        <qcom:propertyName>email</qcom:propertyName>
                        <qcom:propertyValue/>
                        </qcom:ttContext>
                        <qcom:ttContext>
                        <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                        <qcom:propertyName>emailLevel</qcom:propertyName>
                        <qcom:propertyValue/>
                        </qcom:ttContext>
                    </qcom:dsSessionContext>
                    <dsSalesOrderShipper>
                        <salesOrderShipper>
                        <operation>A</operation>
                        <absShipfrom>' . $packingReplenishments[0]['sodSite'] . '</absShipfrom>
                        <absId></absId>
                        <absShipto>' . $packingReplenishments[0]['sodShip'] . '</absShipto>
                        <vInvmov></vInvmov>
                        <vCont>true</vCont>
                        <vCont1>true</vCont1>
                        <multiEntry>false</multiEntry>
                        <absShipvia>UPS</absShipvia>
                        <absVehRef>' . $id . '</absVehRef>
                        <vStatus></vStatus>
                        <cmmts>false</cmmts>
                        <vCmmts>false</vCmmts>
                        <vShipCmmts>true</vShipCmmts>
                        <vPackCmmts>true</vPackCmmts>
                        <vFeatures>false</vFeatures>
                        <vPrintSodet>false</vPrintSodet>
                        <lSoUm>false</lSoUm>
                        <compAddr>10-100</compAddr>
                        <lPrintLotserials>true</lPrintLotserials>
                        <dev>test1</dev>
                        <vOk>true</vOk>';

                        foreach ($packingReplenishments as $packingReplenishment) {
                            $lot = $packingReplenishment['locations'][0]['lot'];

                            $qdocRequest .= '
                            <schedOrderItemDetail>
                                <scxOrder>' . $packingReplenishment['sodNbr'] . '</scxOrder>
                                <scxLine>' . $packingReplenishment['sodLine'] . '</scxLine>
                                <srSite>' . $packingReplenishment['sodSite'] . '</srSite>
                                <srQty>' . $packingReplenishment['totalPickedQty'] . '</srQty>
                                <srLoc>DOCK</srLoc>
                                <srLotser>' . $lot . '</srLotser>
                                <multiple>false</multiple>
                                <vCmmts>false</vCmmts>
                                <yn>true</yn>
                                <answer>true</answer>
                                <lAnswer>true</lAnswer>
                            </schedOrderItemDetail>
                            <discreteOrderItemDetail>
                                <scxOrder>' . $packingReplenishment['sodNbr'] . '</scxOrder>
                                <scxLine>' . $packingReplenishment['sodLine'] . '</scxLine>
                                <srSite>' . $packingReplenishment['sodSite'] . '</srSite>
                                <srQty>' . $packingReplenishment['totalPickedQty'] . '</srQty>
                                <srLoc>DOCK</srLoc>
                                <srLotser>' . $lot . '</srLotser>
                                <multiple>false</multiple>
                                <vCmmts>false</vCmmts>
                                <yn>true</yn>
                                <answer>true</answer>
                                <lAnswer>true</lAnswer>
                            </discreteOrderItemDetail>';
                        }

                        $qdocRequest .= '
                        </salesOrderShipper>
                        </dsSalesOrderShipper>
                    </maintainSalesOrderShipper>
                </soapenv:Body>
            </soapenv:Envelope>';

        return $this->sendQdocRequest($qdocRequest, $activeConnection);
    }

    public function qxShipperConfirm($confirmApproval, $activeConnection)
    {
        $receiver = 'QADERP';
        $shipFrom = $confirmApproval['get_packing_replenishment_mstr']['get_packing_replenishment_det'][0]['get_shipment_schedule_location']['ssl_site'];
        $absID = $confirmApproval['get_packing_replenishment_mstr']['prm_shipper_nbr'];
        $vehicleRefID = $confirmApproval['prm_id'];

        $qdocRequest = '<?xml version="1.0" encoding="UTF-8"?>
                <soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
                xmlns:qcom="urn:schemas-qad-com:xml-services:common"
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
                <soapenv:Header>
                    <wsa:Action/>
                    <wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
                    <wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
                    <wsa:ReferenceParameters>
                    <qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
                    </wsa:ReferenceParameters>
                    <wsa:ReplyTo>
                    <wsa:Address>urn:services-qad-com:</wsa:Address>
                    </wsa:ReplyTo>
                </soapenv:Header>
                <soapenv:Body>
                    <confirmShipper>
                    <qcom:dsSessionContext>
                        <qcom:ttContext>
                        <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                        <qcom:propertyName>domain</qcom:propertyName>
                        <qcom:propertyValue>10USA</qcom:propertyValue>
                        </qcom:ttContext>
                        <qcom:ttContext>
                        <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                        <qcom:propertyName>scopeTransaction</qcom:propertyName>
                        <qcom:propertyValue>true</qcom:propertyValue>
                        </qcom:ttContext>
                        <qcom:ttContext>
                        <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                        <qcom:propertyName>version</qcom:propertyName>
                        <qcom:propertyValue>ERP3_1</qcom:propertyValue>
                        </qcom:ttContext>
                        <qcom:ttContext>
                        <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                        <qcom:propertyName>mnemonicsRaw</qcom:propertyName>
                        <qcom:propertyValue>false</qcom:propertyValue>
                        </qcom:ttContext>
                        <!--
                        <qcom:ttContext>
                        <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                        <qcom:propertyName>username</qcom:propertyName>
                        <qcom:propertyValue/>
                        </qcom:ttContext>
                        <qcom:ttContext>
                        <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                        <qcom:propertyName>password</qcom:propertyName>
                        <qcom:propertyValue/>
                        </qcom:ttContext>
                        -->
                        <qcom:ttContext>
                        <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                        <qcom:propertyName>action</qcom:propertyName>
                        <qcom:propertyValue/>
                        </qcom:ttContext>
                        <qcom:ttContext>
                        <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                        <qcom:propertyName>entity</qcom:propertyName>
                        <qcom:propertyValue/>
                        </qcom:ttContext>
                        <qcom:ttContext>
                        <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                        <qcom:propertyName>email</qcom:propertyName>
                        <qcom:propertyValue/>
                        </qcom:ttContext>
                        <qcom:ttContext>
                        <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                        <qcom:propertyName>emailLevel</qcom:propertyName>
                        <qcom:propertyValue/>
                        </qcom:ttContext>
                    </qcom:dsSessionContext>
                    <dsShipperConfirm>
                        <shipperConfirm>
                            <absShipfrom>' . $shipFrom . '</absShipfrom>
                            <confType>Shipper</confType>
                            <absId>' . $absID . '</absId>
                            <shipDt>' . date('Y-m-d') . '</shipDt>
                            <absVehRef>' . $vehicleRefID . '</absVehRef>
                            <autoPost>false</autoPost>
                            <lPrtinstbase>true</lPrtinstbase>
                            <autoInv>false</autoInv>
                            <consolidate>false</consolidate>
                            <lCalcFreight>true</lCalcFreight>
                            <pconfirm>true</pconfirm>
                        </shipperConfirm>
                    </dsShipperConfirm>
                    </confirmShipper>
                </soapenv:Body>
                </soapenv:Envelope>
        ';

        return $this->sendQdocRequest($qdocRequest, $activeConnection);
    }
}

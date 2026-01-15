<?php

namespace App\Services;

use App\Models\PurchaseOrder\POMstr;
use App\Models\SalesOrder\SOMstr;
use App\Models\Settings\Domain;
use App\Models\Settings\qxwsa;
use App\Models\API\workOrderMaster;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class QxtendServices
{
    private function httpHeader($req)
    {
        return [
            'Content-type: text/xml;charset="utf-8"',
            "Accept: text/xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            'SOAPAction: ""', // jika tidak pakai SOAPAction, isinya harus ada tanda petik 2 --> ""
            "Content-length: " . strlen(preg_replace("/\s+/", " ", $req)),
        ];
    }

    private function sendQdocRequest($qdocRequest, $activeConnection)
    {
        $timeout = 0;
        $qxUrl = $activeConnection->qx_url;
        $curlOptions = [
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout, // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ];

        $getInfo = "";
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = "";

        $qdocResponse = "";

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl); // sending qdocRequest here, the result is qdocResponse.
            //
            $curlErrno = curl_errno($curl);
            $curlError = curl_error($curl);
            $first = true;
            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != "array") {
                    if (!$first) {
                        $getInfo .= ", ";
                    }
                    $getInfo = $getInfo . $key . "=>" . $value;
                    $first = false;
                    if ($key == "http_code") {
                        $httpCode = $value;
                    }
                }
            }
            curl_close($curl);
        }

        if (is_bool($qdocResponse)) {
            Log::channel("otherShipmentPreparation")->error("Qxtend connection failed: {$curlError} ({$curlErrno}), URL: {$qxUrl}");
            return [false, "Qxtend connection failed: {$curlError}"];
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace("ns1", "urn:schemas-qad-com:xml-services");

        $qdocResult = (string) $xmlResp->xpath("//ns1:result")[0];

        // $qdocResult = 'success';

        if ($qdocResult == "success" or $qdocResult == "warning") {
            return [true, ""];
        } else {
            $xmlResp->registerXPathNamespace("ns3", "urn:schemas-qad-com:xml-services:common");
            $qdocMsgDesc = $xmlResp->xpath("//ns3:tt_msg_desc");
            $output = "";
            foreach ($qdocMsgDesc as $datas) {
                if (str_contains($datas, "ERROR:")) {
                    $output .= $datas . " - ";
                }
            }
            $output = substr($output, 0, -3);

            return [false, $output];
        }
    }

    public function qxTransferSingleItemWMS(
        $part,
        $qtyoh,
        $sitefrom,
        $siteto,
        $locfrom,
        $locto,
        $lotfrom,
        $lotto,
        $buildingfrom,
        $buildingto,
        $levelfrom,
        $levelto,
        $binfrom,
        $binto,
    ) {
        $domain = Domain::first();
        $domainCode = $domain->domain ?? "";
        $qxwsa = Qxwsa::firstOrFail();

        // Var Qxtend
        $qxUrl = $qxwsa->qx_url;
        $receiver = "QADERP";

        $timeout = 0;

        // XML Qextend
        $qdocHead =
            '<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services" xmlns:qcom="urn:schemas-qad-com:xml-services:common" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
						<soapenv:Header>
							<wsa:Action/>
							<wsa:To>urn:services-qad-com:' .
            $receiver .
            '</wsa:To>
							<wsa:MessageID>urn:services-qad-com::' .
            $receiver .
            '</wsa:MessageID>
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
										<qcom:propertyValue>' .
            $domainCode .
            '</qcom:propertyValue>
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
									<vPart>' .
            $part .
            '</vPart>
									<vQty>' .
            $qtyoh .
            '</vQty>
									<vSiteFrom>' .
            $sitefrom .
            '</vSiteFrom>
									<vLocFrom>' .
            $locfrom .
            '</vLocFrom>
									<vLotFrom>' .
            $lotfrom .
            '</vLotFrom>
									<vWhFrom>' .
            $buildingfrom .
            '</vWhFrom>
									<vLevelFrom>' .
            $levelfrom .
            '</vLevelFrom>
									<vBinFrom>' .
            $binfrom .
            '</vBinFrom>
									<vSiteTo>' .
            $siteto .
            '</vSiteTo>
									<vLocTo>' .
            $locto .
            '</vLocTo>
									<vWhTo>' .
            $buildingto .
            '</vWhTo>
									<vLevelTo>' .
            $levelto .
            '</vLevelTo>
									<vBinTo>' .
            $binto .
            '</vBinTo>
									<vYn>true</vYn>
								</transWms>
							</dsTransWms>
						</transferSingleItemWMS>
					</soapenv:Body>
					</soapenv:Envelope>';

        $qdocRequest = $qdocHead;

        $curlOptions = [
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout, // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ];

        $getInfo = "";
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = "";

        $qdocResponse = "";

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl); // sending qdocRequest here, the result is qdocResponse.
            //
            $curlErrno = curl_errno($curl);
            $curlError = curl_error($curl);
            $first = true;
            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != "array") {
                    if (!$first) {
                        $getInfo .= ", ";
                    }
                    $getInfo = $getInfo . $key . "=>" . $value;
                    $first = false;
                    if ($key == "http_code") {
                        $httpCode = $value;
                    }
                }
            }
            curl_close($curl);
        }

        if (is_bool($qdocResponse)) {
            return false;
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace("ns1", "urn:schemas-qad-com:xml-services");

        $qdocResult = (string) $xmlResp->xpath("//ns1:result")[0];

        if ($qdocResult == "success" or $qdocResult == "warning") {
            return [true, ""];
        } else {
            $xmlResp->registerXPathNamespace("ns3", "urn:schemas-qad-com:xml-services:common");
            $qdocMsgDesc = $xmlResp->xpath("//ns3:tt_msg_desc");
            $output = "";
            foreach ($qdocMsgDesc as $datas) {
                if (str_contains($datas, "ERROR:")) {
                    $output .= $datas . " - ";
                }
            }
            $output = substr($output, 0, -3);

            return [false, $output];
        }
    }

    public function qxPurchaseOrderReceipt($ponbr, $line, $lotSerialQty, $um, $site, $location, $lotserial, $expireddate,$ref)
    {
        $domain = Domain::first();
        $domainCode = $domain->domain ?? "";
        $qxwsa = Qxwsa::firstOrFail();

        // Var Qxtend
        $qxUrl = $qxwsa->qx_url;
        $receiver = "QADERP";

        $timeout = 0;

        // XML Qextend
        $qdocHead =
            '<?xml version="1.0" encoding="UTF-8"?>
                        <soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
                        xmlns:qcom="urn:schemas-qad-com:xml-services:common"
                        xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
                        <soapenv:Header>
                            <wsa:Action/>
                            <wsa:To>urn:services-qad-com:' .
            $receiver .
            '</wsa:To>
                            <wsa:MessageID>urn:services-qad-com::' .
            $receiver .
            '</wsa:MessageID>
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
                                <qcom:propertyValue>' .
            $domainCode .
            '</qcom:propertyValue>
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
        $qdocbody =
            '<purchaseOrderReceive>
                            <ordernum>' .
            $ponbr .
            '</ordernum>
                            <yn>true</yn>
                            <yn1>true</yn1>
                            <lineDetail>
                                    <line>' .
            $line .
            '</line>
                                    <lotserialQty>' .
            $lotSerialQty .
            '</lotserialQty>
                                    <receiptUm>' .
            $um .
            '</receiptUm>
                                    <site>' .
            $site .
            '</site>
                                    <location>' .
            $location .
            '</location>
                                    <lotserial>' .
            $lotserial .
            '</lotserial>
            <lotref>'.$ref.'</lotref>
                                    <multiEntry>false</multiEntry>
                                    <chgAttr>true</chgAttr>
                                    <chgExpire>' .
            $expireddate .
            '</chgExpire>
                                    <serialsYn>true</serialsYn>
							</lineDetail>
							</purchaseOrderReceive>';

        $qdocfoot = '
        </dsPurchaseOrderReceive>
        </receivePurchaseOrder>
                        </soapenv:Body>
                    </soapenv:Envelope>';

        $qdocRequest = $qdocHead . $qdocbody . $qdocfoot;

        $curlOptions = [
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout, // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ];

        $getInfo = "";
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = "";

        $qdocResponse = "";

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl); // sending qdocRequest here, the result is qdocResponse.
            //
            $curlErrno = curl_errno($curl);
            $curlError = curl_error($curl);
            $first = true;
            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != "array") {
                    if (!$first) {
                        $getInfo .= ", ";
                    }
                    $getInfo = $getInfo . $key . "=>" . $value;
                    $first = false;
                    if ($key == "http_code") {
                        $httpCode = $value;
                    }
                }
            }
            curl_close($curl);
        }

        if (is_bool($qdocResponse)) {
            return false;
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace("ns1", "urn:schemas-qad-com:xml-services");
        log::info($qdocRequest);
        log::info($qdocResponse);
        $qdocResult = (string) $xmlResp->xpath("//ns1:result")[0];

        if ($qdocResult == "success" or $qdocResult == "warning") {
            return [true, ""];
        } else {
            $xmlResp->registerXPathNamespace("ns3", "urn:schemas-qad-com:xml-services:common");
            $qdocMsgDesc = $xmlResp->xpath("//ns3:tt_msg_desc");
            $output = "";
            foreach ($qdocMsgDesc as $datas) {
                if (str_contains($datas, "ERROR:")) {
                    $output .= $datas . " - ";
                }
            }
            $output = substr($output, 0, -3);

            return [false, $output];
        }
    }

    public function qxTransferSingleItemWo($part, $wonbr, $sitefrom, $siteto, $locfrom, $locto, $qty, $bin, $level, $wh, $lot)
    {
        $domain = Domain::first();
        $domainCode = $domain->domain ?? "";
        $qxwsa = Qxwsa::firstOrFail();

        // Var Qxtend
        $qxUrl = $qxwsa->qx_url;
        $receiver = "QADERP";

        $timeout = 0;

        $qdocHead =
            '<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services" xmlns:qcom="urn:schemas-qad-com:xml-services:common" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
						<soapenv:Header>
							<wsa:Action/>
							<wsa:To>urn:services-qad-com:' .
            $receiver .
            '</wsa:To>
							<wsa:MessageID>urn:services-qad-com::' .
            $receiver .
            '</wsa:MessageID>
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
										<qcom:propertyValue>' .
            $domainCode .
            '</qcom:propertyValue>
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
									<vPart>' .
            $part .
            '</vPart>
									<vQty>' .
            $qty .
            '</vQty>
									<vSiteFrom>' .
            $sitefrom .
            '</vSiteFrom>
									<vLocFrom>' .
            $locfrom .
            '</vLocFrom>
									<vLotFrom>' .
            $lot .
            '</vLotFrom>
									<vWhFrom>' .
            $wh .
            '</vWhFrom>
									<vLevelFrom>' .
            $level .
            '</vLevelFrom>
									<vBinFrom>' .
            $bin .
            '</vBinFrom>
									<vSiteTo>' .
            $siteto .
            '</vSiteTo>
									<vLocTo>' .
            $locto .
            '</vLocTo>
									<vWhTo/>
									<vLevelTo/>
									<vBinTo/>
									<vYn>true</vYn>
								</transWms>
							</dsTransWms>
						</transferSingleItemWMS>
					</soapenv:Body>
					</soapenv:Envelope>';
        $qdocRequest = $qdocHead;

        $curlOptions = [
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout, // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ];

        $getInfo = "";
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = "";

        $qdocResponse = "";

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl); // sending qdocRequest here, the result is qdocResponse.
            //
            $curlErrno = curl_errno($curl);
            $curlError = curl_error($curl);
            $first = true;
            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != "array") {
                    if (!$first) {
                        $getInfo .= ", ";
                    }
                    $getInfo = $getInfo . $key . "=>" . $value;
                    $first = false;
                    if ($key == "http_code") {
                        $httpCode = $value;
                    }
                }
            }
            curl_close($curl);
        }

        if (is_bool($qdocResponse)) {
            return false;
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace("ns1", "urn:schemas-qad-com:xml-services");

        $qdocResult = (string) $xmlResp->xpath("//ns1:result")[0];

        if ($qdocResult == "success" or $qdocResult == "warning") {
            return [true, ""];
        } else {
            $xmlResp->registerXPathNamespace("ns3", "urn:schemas-qad-com:xml-services:common");
            $qdocMsgDesc = $xmlResp->xpath("//ns3:tt_msg_desc");
            $output = "";
            foreach ($qdocMsgDesc as $datas) {
                if (str_contains($datas, "ERROR:")) {
                    $output .= $datas . " - ";
                }
            }
            $output = substr($output, 0, -3);

            return [false, $output];
        }
    }
    public function qxWorkOrderBill($wonbr, $lot, $user)
    {
        $domain = Domain::first();
        $domainCode = $domain->domain ?? "";
        $qxwsa = Qxwsa::firstOrFail();

        // Var Qxtend
        $qxUrl = $qxwsa->qx_url;
        $receiver = "QADERP";

        $timeout = 0;

        $dataWo = workOrderMaster::with([
            "getDetail" => function ($query) {
                $query->orderBy("wod_part", "desc");
            },
        ])
            ->where("created_by", $user)
            ->where("wo_nbr", $wonbr)
            ->where("wo_id", $lot)
            ->first();

        $currentpart = "";
        $stringdetail = "";
        $stringalloc = "";
        $qtypick = 0;
        $qtyorder = 0;
        $lastIndex = count($dataWo->getDetail) - 1;
        $qtyreq = 0;

        foreach ($dataWo->getDetail as $index => $detail) {
            if ($currentpart != $detail->wod_part && $currentpart == "") {
                $qtypick = $detail->wod_qty_pick;
                $currentpart = $detail->wod_part;
                $qtyreq = $detail->wod_qty_req;

                $stringalloc =
                    $stringalloc .
                    '<AllocDetail>
                    <ladLoc>' .
                    $detail->wod_loc .
                    '</ladLoc>
                    <ladLot>' .
                    $detail->wod_lot .
                    '</ladLot>
                    <ladRef>' .
                    $detail->wod_ref .
                    '</ladRef>
                    <ladQtyAll/>
                    <ladQtyPick>' .
                    $detail->wod_qty_pick .
                    '</ladQtyPick>
                </AllocDetail>';

                if ($index == $lastIndex) {
                    $stringdetail =
                        $stringdetail .
                        '<CompItem>
                        <wodPart>' .
                        $detail->wod_part .
                        '</wodPart>
                        <wodOp>' .
                        $detail->wod_op .
                        '</wodOp>
                        <wodQtyReq>' .
                        $qtyreq .
                        '</wodQtyReq>
                        <wodQtyAll/>
                        <wodQtyPick>' .
                        $qtypick .
                        '</wodQtyPick>
                        <detailAll>true</detailAll>

                        <wodSite>' .
                        $detail->wod_site .
                        '</wodSite>
                        <wodLoc>' .
                        $detail->wod_loc .
                        "</wodLoc>" .
                        $stringalloc .
                        "</CompItem>";
                }
            } elseif ($currentpart != $detail->wod_part && $currentpart != "") {
                $stringdetail =
                    $stringdetail .
                    '<CompItem>
                        <wodPart>' .
                    $detail->wod_part .
                    '</wodPart>
                        <wodOp>' .
                    $detail->wod_op .
                    '</wodOp>
                        <wodQtyReq>' .
                    $qtyreq .
                    '</wodQtyReq>
                        <wodQtyAll/>
                        <wodQtyPick>' .
                    $qtypick .
                    '</wodQtyPick>
                        <detailAll>true</detailAll>

                        <wodSite>' .
                    $detail->wod_site .
                    '</wodSite>
                        <wodLoc>' .
                    $detail->wod_loc .
                    "</wodLoc>" .
                    $stringalloc .
                    "</CompItem>";

                //reset current part & qty pick
                $qtyreq = $detail->wod_qty_req;
                $qtypick = $detail->wod_qty_pick;
                $currentpart = $detail->wod_part;

                $stringalloc =
                    '<AllocDetail>
                        <ladLoc>' .
                    $detail->wod_loc .
                    '</ladLoc>
                        <ladLot>' .
                    $detail->wod_lot .
                    '</ladLot>
                        <ladRef>' .
                    $detail->wod_ref .
                    '</ladRef>
                        <ladQtyAll/>
                        <ladQtyPick>' .
                    $detail->wod_qty_pick .
                    '</ladQtyPick>
                    </AllocDetail>';

                if ($index == $lastIndex) {
                    $stringdetail =
                        $stringdetail .
                        '<CompItem>
                        <wodPart>' .
                        $detail->wod_part .
                        '</wodPart>
                        <wodOp>' .
                        $detail->wod_op .
                        '</wodOp>
                        <wodQtyReq>' .
                        $qtyreq .
                        '</wodQtyReq>
                        <wodQtyAll/>
                        <wodQtyPick>' .
                        $qtypick .
                        '</wodQtyPick>
                        <detailAll>true</detailAll>

                        <wodSite>' .
                        $detail->wod_site .
                        '</wodSite>
                        <wodLoc>' .
                        $detail->wod_loc .
                        "</wodLoc>" .
                        $stringalloc .
                        "</CompItem>";
                }
            } else {
                $qtypick = $qtypick + $detail->wod_qty_pick;

                $stringalloc =
                    $stringalloc .
                    '<AllocDetail>
                    <ladLoc>' .
                    $detail->wod_loc .
                    '</ladLoc>
                    <ladLot>' .
                    $detail->wod_lot .
                    '</ladLot>
                    <ladRef>' .
                    $detail->wod_ref .
                    '</ladRef>
                    <ladQtyAll/>
                    <ladQtyPick>' .
                    $detail->wod_qty_pick .
                    '</ladQtyPick>
                </AllocDetail>';

                if ($index == $lastIndex) {
                    $stringdetail =
                        $stringdetail .
                        '<CompItem>
                        <wodPart>' .
                        $detail->wod_part .
                        '</wodPart>
                        <wodOp>' .
                        $detail->wod_op .
                        '</wodOp>
                        <wodQtyReq>' .
                        $qtyreq .
                        '</wodQtyReq>
                        <wodQtyAll/>
                        <wodQtyPick>' .
                        $qtypick .
                        '</wodQtyPick>
                        <detailAll>true</detailAll>

                        <wodSite>' .
                        $detail->wod_site .
                        '</wodSite>
                        <wodLoc>' .
                        $detail->wod_loc .
                        "</wodLoc>" .
                        $stringalloc .
                        "</CompItem>";
                }
            }
        }
        // XML Qextend
        $qdocHead =
            '<?xml version="1.0" encoding="UTF-8"?>
                        <soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
                        xmlns:qcom="urn:schemas-qad-com:xml-services:common"
                        xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
                        <soapenv:Header>
                            <wsa:Action/>
                            <wsa:To>urn:services-qad-com:' .
            $receiver .
            '</wsa:To>
                            <wsa:MessageID>urn:services-qad-com::' .
            $receiver .
            '</wsa:MessageID>
                            <wsa:ReferenceParameters>
                            <qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
                            </wsa:ReferenceParameters>
                            <wsa:ReplyTo>
                            <wsa:Address>urn:services-qad-com:</wsa:Address>
                            </wsa:ReplyTo>
                        </soapenv:Header>
                        <soapenv:Body>
                            <maintainWorkOrderBill>
                            <qcom:dsSessionContext>
                                <qcom:ttContext>
                                <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                                <qcom:propertyName>domain</qcom:propertyName>
                                <qcom:propertyValue>' .
            $domainCode .
            '</qcom:propertyValue>
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
                            <dsWorkOrder>';
        $qdocbody =
            '<WorkOrder>

						<woNbr>' .
            $wonbr .
            '</woNbr>
						<woLot>' .
            $lot .
            "</woLot>";
        $qdocbody = $qdocbody . $stringdetail . "</WorkOrder>";

        $qdocfoot = '
        </dsWorkOrder>
        </maintainWorkOrderBill>
                        </soapenv:Body>
                    </soapenv:Envelope>';

        $qdocRequest = $qdocHead . $qdocbody . $qdocfoot;

        $curlOptions = [
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout, // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ];

        $getInfo = "";
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = "";

        $qdocResponse = "";

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl); // sending qdocRequest here, the result is qdocResponse.
            //
            $curlErrno = curl_errno($curl);
            $curlError = curl_error($curl);
            $first = true;
            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != "array") {
                    if (!$first) {
                        $getInfo .= ", ";
                    }
                    $getInfo = $getInfo . $key . "=>" . $value;
                    $first = false;
                    if ($key == "http_code") {
                        $httpCode = $value;
                    }
                }
            }
            curl_close($curl);
        }

        if (is_bool($qdocResponse)) {
            return false;
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace("ns1", "urn:schemas-qad-com:xml-services");

        $qdocResult = (string) $xmlResp->xpath("//ns1:result")[0];

        if ($qdocResult == "success" or $qdocResult == "warning") {
            return [true, ""];
        } else {
            $xmlResp->registerXPathNamespace("ns3", "urn:schemas-qad-com:xml-services:common");
            $qdocMsgDesc = $xmlResp->xpath("//ns3:tt_msg_desc");
            $output = "";
            foreach ($qdocMsgDesc as $datas) {
                if (str_contains($datas, "ERROR:")) {
                    $output .= $datas . " - ";
                }
            }
            $output = substr($output, 0, -3);

            return [false, $output];
        }
    }

    public function qxTransferSingleItemPackingReplenishment(
        $packingReplenishment,
        $qtyTransfer,
        $locationDetail,
        $location,
        $activeConnection,
    ) {
        $receiver = "QADERP";

        $qdocRequest =
            '<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services" xmlns:qcom="urn:schemas-qad-com:xml-services:common" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
						<soapenv:Header>
							<wsa:Action/>
							<wsa:To>urn:services-qad-com:' .
            $receiver .
            '</wsa:To>
							<wsa:MessageID>urn:services-qad-com::' .
            $receiver .
            '</wsa:MessageID>
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
										<qcom:propertyValue>' .
            $activeConnection->wsas_domain .
            '</qcom:propertyValue>
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
									<vPart>' .
            $packingReplenishment["sodPart"] .
            '</vPart>
									<vQty>' .
            $qtyTransfer .
            '</vQty>
									<vSiteFrom>' .
            $locationDetail["site"] .
            '</vSiteFrom>
									<vLocFrom>' .
            $locationDetail["loc"] .
            '</vLocFrom>
									<vLotFrom>' .
            $locationDetail["lot"] .
            '</vLotFrom>
									<vWhFrom>' .
            $locationDetail["wh"] .
            '</vWhFrom>
									<vLevelFrom>' .
            $locationDetail["level"] .
            '</vLevelFrom>
									<vBinFrom>' .
            $locationDetail["bin"] .
            '</vBinFrom>
									<vSiteTo>' .
            $locationDetail["site"] .
            '</vSiteTo>
									<vLocTo>' .
            $location .
            '</vLocTo>
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

    public function qxSalesOrderShipper($action, $location, $shipmentScheduleDetails, $id, $activeConnection)
    {
        $receiver = "QADERP";
        $operation = "";

        switch ($action) {
            case "delete":
                $operation = "D";
                break;

            default:
                $operation = "A";
                break;
        }

        $domain = Domain::first();
        $domainCode = $domain->domain ?? "";
        // $receiver = 'eB21_2';
        $receiver = 'ERP3_1';

        $qdocRequest =
            '<?xml version="1.0" encoding="UTF-8"?>
                <soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
                xmlns:qcom="urn:schemas-qad-com:xml-services:common"
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
                <soapenv:Header>
                    <wsa:Action/>
                    <wsa:To>urn:services-qad-com:' .
            $receiver .
            '</wsa:To>
                    <wsa:MessageID>urn:services-qad-com::' .
            $receiver .
            '</wsa:MessageID>
                    <wsa:ReferenceParameters>
                    <qcom:suppressResponseDetail>false</qcom:suppressResponseDetail>
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
                        <qcom:propertyValue>' .
            $domainCode .
            '</qcom:propertyValue>
                        </qcom:ttContext>
                        <qcom:ttContext>
                        <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                        <qcom:propertyName>scopeTransaction</qcom:propertyName>
                        <qcom:propertyValue>true</qcom:propertyValue>
                        </qcom:ttContext>
                        <qcom:ttContext>
                        <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                        <qcom:propertyName>version</qcom:propertyName>
                        <qcom:propertyValue>' . $receiver . '</qcom:propertyValue>
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
                        <absShipfrom>' .
            $shipmentScheduleDetails[0]->ssd_sod_site .
            '</absShipfrom>
                        <absId></absId>
                        <absShipto>' .
            $shipmentScheduleDetails[0]->ssd_sod_shipto .
            '</absShipto>
                        <vInvmov></vInvmov>
                        <vCont>true</vCont>
                        <vCont1>true</vCont1>
                        <multiEntry>false</multiEntry>
                        <absShipvia>UPS</absShipvia>
                        <absVehRef>' .
            $id .
            '</absVehRef>
                        <vStatus></vStatus>
                        <cmmts>false</cmmts>
                        <vCmmts>false</vCmmts>
                        <vShipCmmts>true</vShipCmmts>
                        <vPackCmmts>true</vPackCmmts>
                        <vFeatures>false</vFeatures>
                        <vPrintSodet>false</vPrintSodet>
                        <lSoUm>false</lSoUm>
                        <compAddr>' .
            $shipmentScheduleDetails[0]->ssd_sod_site .
            '</compAddr>
                        <lPrintLotserials>true</lPrintLotserials>
                        <dev>test1</dev>
                        <vOk>true</vOk>';

        foreach ($shipmentScheduleDetails as $shipmentScheduleDetail) {
            $soNumber = $shipmentScheduleDetail->ssd_sod_nbr;
            $soLine = $shipmentScheduleDetail->ssd_sod_line;
            $soSite = $shipmentScheduleDetail->ssd_sod_site;
            $qdocRequest .=
                '
                                    <schedOrderItemDetail>
                                        <scxOrder>' .
                $soNumber .
                '</scxOrder>
                                        <scxLine>' .
                $soLine .
                '</scxLine>
                                        <srSite>' .
                $soSite .
                '</srSite>
                                        <srQty>0</srQty>
                                        <srLoc>' .
                $location .
                '</srLoc>
                                        <srLotser></srLotser>
                                        <multiple>true</multiple>
                                        <vCmmts>false</vCmmts>
                                        <yn>true</yn>
                                        <answer>true</answer>
                                        <lAnswer>true</lAnswer>';

            foreach ($shipmentScheduleDetail->getShipmentScheduleLocation as $locationDetail) {
                $lot = $locationDetail->ssl_lotserial;
                $pickedQty = $locationDetail->ssl_qty_pick;

                $qdocRequest .=
                    '<schedOrderIssueDetail>
                                            <site>' .
                    $soSite .
                    '</site>
                                            <location>' .
                    $location .
                    '</location>
                                            <lotserial>' .
                    $lot .
                    '</lotserial>
                                            <lotref></lotref>
                                            <lotserialQty>' .
                    $pickedQty .
                    '</lotserialQty>
                                            <lContinue>true</lContinue>
                                            <yn>true</yn>
                                        </schedOrderIssueDetail>';
            }

            $qdocRequest .=
                '
                                    </schedOrderItemDetail>
                                    <discreteOrderItemDetail>
                                        <scxOrder>' .
                $soNumber .
                '</scxOrder>
                                        <scxLine>' .
                $soLine .
                '</scxLine>
                                        <srSite>' .
                $soSite .
                '</srSite>
                                        <srQty>0</srQty>
                                        <srLoc>' .
                $location .
                '</srLoc>
                                        <srLotser></srLotser>
                                        <multiple>true</multiple>
                                        <vCmmts>false</vCmmts>
                                        <yn>true</yn>
                                        <answer>true</answer>
                                        <lAnswer>true</lAnswer>';

            foreach ($shipmentScheduleDetail->getShipmentScheduleLocation as $locationDetail) {
                $lot = $locationDetail->ssl_lotserial;
                $pickedQty = $locationDetail->ssl_qty_pick;

                $qdocRequest .=
                    '<discreteOrderIssueDetail>
                                            <site>' .
                    $soSite .
                    '</site>
                                            <location>' .
                    $location .
                    '</location>
                                            <lotserial>' .
                    $lot .
                    '</lotserial>
                                            <lotref></lotref>
                                            <lotserialQty>' .
                    $pickedQty .
                    '</lotserialQty>
                                            <yn>true</yn>
                                        </discreteOrderIssueDetail>';
            }
            $qdocRequest .= "</discreteOrderItemDetail>";
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
        $receiver = "QADERP";
        $shipFrom =
            $confirmApproval["get_packing_replenishment_master"]["get_packing_replenishment_det"][0]["get_shipment_schedule_location"][
                "ssl_site"
            ];
        $absID = $confirmApproval["get_packing_replenishment_master"]["prm_shipper_nbr"];
        $vehicleRefID = $confirmApproval["prm_id"];

        $domain = Domain::first();
        $domainCode = $domain->domain ?? "";

        $qdocRequest =
            '<?xml version="1.0" encoding="UTF-8"?>
                <soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
                xmlns:qcom="urn:schemas-qad-com:xml-services:common"
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
                <soapenv:Header>
                    <wsa:Action/>
                    <wsa:To>urn:services-qad-com:' .
            $receiver .
            '</wsa:To>
                    <wsa:MessageID>urn:services-qad-com::' .
            $receiver .
            '</wsa:MessageID>
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
                        <qcom:propertyValue>' .
            $domainCode .
            '</qcom:propertyValue>
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
                            <absShipfrom>' .
            $shipFrom .
            '</absShipfrom>
                            <confType>Shipper</confType>
                            <absId>' .
            $absID .
            '</absId>
                            <shipDt>' .
            date("Y-m-d") .
            '</shipDt>
                            <absVehRef>' .
            $vehicleRefID .
            '</absVehRef>
                            <autoPost>false</autoPost>
                            <lPrtinstbase>false</lPrtinstbase>
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

    public function qxTransferSingleItemTransfer(
        $part,
        $qtyoh,
        $sitefrom,
        $siteto,
        $locfrom,
        $locto,
        $lotfrom,
        $lotto,
        $buildingfrom,
        $buildingto,
        $levelfrom,
        $levelto,
        $binfrom,
        $binto,
    ) {
        $domain = Domain::first();
        $domainCode = $domain->domain ?? "";
        $qxwsa = Qxwsa::firstOrFail();

        // Var Qxtend
        $qxUrl = $qxwsa->qx_url;
        $receiver = "QADERP";

        $timeout = 0;

        // XML Qextend
        $qdocHead =
            '<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services" xmlns:qcom="urn:schemas-qad-com:xml-services:common" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
						<soapenv:Header>
							<wsa:Action/>
							<wsa:To>urn:services-qad-com:' .
            $receiver .
            '</wsa:To>
							<wsa:MessageID>urn:services-qad-com::' .
            $receiver .
            '</wsa:MessageID>
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
										<qcom:propertyValue>' .
            $domainCode .
            '</qcom:propertyValue>
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
									<vPart>' .
            $part .
            '</vPart>
									<vQty>' .
            $qtyoh .
            '</vQty>
									<vSiteFrom>' .
            $sitefrom .
            '</vSiteFrom>
									<vLocFrom>' .
            $locfrom .
            '</vLocFrom>
									<vLotFrom>' .
            $lotfrom .
            '</vLotFrom>
									<vWhFrom>' .
            $buildingfrom .
            '</vWhFrom>
									<vLevelFrom>' .
            $levelfrom .
            '</vLevelFrom>
									<vBinFrom>' .
            $binfrom .
            '</vBinFrom>
									<vSiteTo>' .
            $siteto .
            '</vSiteTo>
									<vLocTo>' .
            $locto .
            '</vLocTo>
									<vWhTo>' .
            $buildingto .
            '</vWhTo>
									<vLevelTo>' .
            $levelto .
            '</vLevelTo>
									<vBinTo>' .
            $binto .
            '</vBinTo>
									<vYn>true</vYn>
								</transWms>
							</dsTransWms>
						</transferSingleItemWMS>
					</soapenv:Body>
					</soapenv:Envelope>';

        $qdocRequest = $qdocHead;

        $curlOptions = [
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout, // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ];

        $getInfo = "";
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = "";

        $qdocResponse = "";

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl); // sending qdocRequest here, the result is qdocResponse.
            //
            $curlErrno = curl_errno($curl);
            $curlError = curl_error($curl);
            $first = true;
            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != "array") {
                    if (!$first) {
                        $getInfo .= ", ";
                    }
                    $getInfo = $getInfo . $key . "=>" . $value;
                    $first = false;
                    if ($key == "http_code") {
                        $httpCode = $value;
                    }
                }
            }
            curl_close($curl);
        }

        if (is_bool($qdocResponse)) {
            return [false,'Qxtend connection error'];
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace("ns1", "urn:schemas-qad-com:xml-services");

        $qdocResult = (string) $xmlResp->xpath("//ns1:result")[0];

        if ($qdocResult == "success" or $qdocResult == "warning") {
            return [true, ""];
        } else {
            $xmlResp->registerXPathNamespace("ns3", "urn:schemas-qad-com:xml-services:common");
            $qdocMsgDesc = $xmlResp->xpath("//ns3:tt_msg_desc");
            $output = "";
            foreach ($qdocMsgDesc as $datas) {
                if (str_contains($datas, "ERROR:")) {
                    $output .= $datas . " - ";
                }
            }
            $output = substr($output, 0, -3);

            return [false, $output];
        }
    }

    public function qxWorkOrderIssue($masterdata, $wodata)
    {
        $domain = Domain::first();
        $domainCode = $domain->domain ?? "";
        $qxwsa = Qxwsa::firstOrFail();

        // Var Qxtend
        $qxUrl = $qxwsa->qx_url;
        $receiver = "QADERP";

        $timeout = 0;
        $currentpart = "";
        // XML Qextend
        $qdocHead =
            '<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services" xmlns:qcom="urn:schemas-qad-com:xml-services:common" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
    <soapenv:Header>
        <wsa:Action/>
        <wsa:To>urn:services-qad-com:' .
            $receiver .
            '</wsa:To>
        <wsa:MessageID>urn:services-qad-com::' .
            $receiver .
            '</wsa:MessageID>
        <wsa:ReferenceParameters>
            <qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
        </wsa:ReferenceParameters>
        <wsa:ReplyTo>
            <wsa:Address>urn:services-qad-com:</wsa:Address>
        </wsa:ReplyTo>
    </soapenv:Header>
    <soapenv:Body>
        <issueWorkOrderComponent>
            <qcom:dsSessionContext>
                <qcom:ttContext>
                    <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                    <qcom:propertyName>domain</qcom:propertyName>
                    <qcom:propertyValue>' .
            $domain .
            '<qcom:propertyValue>
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
        <dsWorkOrderComponent>';
        $qdocBody = "";
        foreach ($wodata as $data) {
            $qdocBody .=
                '
            <workOrderComponent>
                <woNbr>' .
                $data["wonbrnbr"] .
                '</woNbr>
                <woLot>' .
                $data["woid"] .
                '</woLot>
                <effDate>' .
                $data["wonbrnbr"] .
                '</effDate>
                <fillAll>false</fillAll>
                <fillPick>true</fillPick>
                <yn>true</yn>
                <yn1>true</yn1>
                <yn2>true</yn2>
                <yn3>true</yn3>
                ';
            foreach ($data["detail"] as $detail) {
                if ($currentpart != $detail["wodpart"]) {
                    $currentpart = $detail["wodpart"];
                    $qdocBody .=
                        '
                <itemDetail>
                    <operation>A</operation>
                    <part>' .
                        $detail["wodpart"] .
                        '</part>

                    <site>' .
                        $detail["wodpart"] .
                        '</site>
                    <location>' .
                        $masterdata["loc"] .
                        '</location>
                    <lotserial>' .
                        $detail["lot"] .
                        '</lotserial>
                    <lotserialQty>' .
                        $masterdata["site"] .
                        '</lotserialQty>
                    <multiEntry>false</multiEntry>
                    <issueDetail>
                        <operation>A</operation>
                        <site>' .
                        $masterdata["site"] .
                        '</site>
                        <location>' .
                        $masterdata["loc"] .
                        '</location>
                        <lotserial>' .
                        $detail["lot"] .
                        '</lotserial>
                        <lotref></lotref>
                        <lotserialQty>' .
                        $detail["qtyreq"] .
                        '</lotserialQty>
                    </issueDetail>
                </itemDetail>';
                }
            }
            $qdocBody .= "</workOrderComponent>";
        }

        $qdocFoot = '

        </dsWorkOrderComponent>
    </issueWorkOrderComponent>
</soapenv:Body>
</soapenv:Envelope>';

        $qdocRequest = $qdocHead . $qdocBody . $qdocFoot;

        $curlOptions = [
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout, // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ];

        $getInfo = "";
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = "";

        $qdocResponse = "";

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl); // sending qdocRequest here, the result is qdocResponse.
            //
            $curlErrno = curl_errno($curl);
            $curlError = curl_error($curl);
            $first = true;
            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != "array") {
                    if (!$first) {
                        $getInfo .= ", ";
                    }
                    $getInfo = $getInfo . $key . "=>" . $value;
                    $first = false;
                    if ($key == "http_code") {
                        $httpCode = $value;
                    }
                }
            }
            curl_close($curl);
        }

        if (is_bool($qdocResponse)) {
            return [false, "WSA Connection Error"];
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace("ns1", "urn:schemas-qad-com:xml-services");

        $qdocResult = (string) $xmlResp->xpath("//ns1:result")[0];

        if ($qdocResult == "success" or $qdocResult == "warning") {
            return [true, ""];
        } else {
            $xmlResp->registerXPathNamespace("ns3", "urn:schemas-qad-com:xml-services:common");
            $qdocMsgDesc = $xmlResp->xpath("//ns3:tt_msg_desc");
            $output = "";
            foreach ($qdocMsgDesc as $datas) {
                if (str_contains($datas, "ERROR:")) {
                    $output .= $datas . " - ";
                }
            }
            $output = substr($output, 0, -3);

            return [false, $output];
        }
    }

    public function qxTransferSingleItemOtherShipmentPreparation(
        $shipmentPreparation,
        $qtyTransfer,
        $locationDetail,
        $location,
        $activeConnection,
    ) {
        $receiver = "QADERP";

        $qdocRequest =
            '<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services" xmlns:qcom="urn:schemas-qad-com:xml-services:common" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
						<soapenv:Header>
							<wsa:Action/>
							<wsa:To>urn:services-qad-com:' .
            $receiver .
            '</wsa:To>
							<wsa:MessageID>urn:services-qad-com::' .
            $receiver .
            '</wsa:MessageID>
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
										<qcom:propertyValue>' .
            $activeConnection->wsas_domain .
            '</qcom:propertyValue>
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
									<vPart>' .
            $shipmentPreparation["ossdPart"] .
            '</vPart>
									<vQty>' .
            $qtyTransfer .
            '</vQty>
									<vSiteFrom>' .
            $locationDetail["site"] .
            '</vSiteFrom>
									<vLocFrom>' .
            $locationDetail["loc"] .
            '</vLocFrom>
									<vLotFrom>' .
            $locationDetail["lot"] .
            '</vLotFrom>
									<vWhFrom>' .
            $locationDetail["wh"] .
            '</vWhFrom>
									<vLevelFrom>' .
            $locationDetail["level"] .
            '</vLevelFrom>
									<vBinFrom>' .
            $locationDetail["bin"] .
            '</vBinFrom>
									<vSiteTo>' .
            $locationDetail["site"] .
            '</vSiteTo>
									<vLocTo>' .
            $location .
            '</vLocTo>
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

    public function qxShipmentPreparationIssuesUnplanned(
        $otherShipmentScheduleDetail,
        $location,
        $locationDetail,
        $otherShipmentPreparationNumber,
        $activeConnection,
    ) {
        $receiver = "QADERP";

        $qdocRequest =
            '<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services" xmlns:qcom="urn:schemas-qad-com:xml-services:common" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
						<soapenv:Header>
							<wsa:Action/>
							<wsa:To>urn:services-qad-com:' .
            $receiver .
            '</wsa:To>
							<wsa:MessageID>urn:services-qad-com::' .
            $receiver .
            '</wsa:MessageID>
							<wsa:ReferenceParameters>
								<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
							</wsa:ReferenceParameters>
							<wsa:ReplyTo>
								<wsa:Address>urn:services-qad-com:</wsa:Address>
							</wsa:ReplyTo>
						</soapenv:Header>
						<soapenv:Body>
							<MJIInventoryIssue>
								<qcom:dsSessionContext>
									<qcom:ttContext>
										<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
										<qcom:propertyName>domain</qcom:propertyName>
										<qcom:propertyValue>' .
            $activeConnection->wsas_domain .
            '</qcom:propertyValue>
									</qcom:ttContext>
									<qcom:ttContext>
										<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
										<qcom:propertyName>scopeTransaction</qcom:propertyName>
										<qcom:propertyValue>true</qcom:propertyValue>
									</qcom:ttContext>
									<qcom:ttContext>
										<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
										<qcom:propertyName>version</qcom:propertyName>
										<qcom:propertyValue>CustV1</qcom:propertyValue>
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
							<dsMJIiIventoryIssue>
								<MJIiIventoryIssue>
									<vPart>' .
            $otherShipmentScheduleDetail->ossd_part .
            '</vPart>
									<vQty>' .
            $locationDetail->ossl_qty_pick .
            '</vQty>
            <vRmks>' .
            $otherShipmentPreparationNumber .
            '</vRmks>
									<vSiteFrom>' .
            $locationDetail->ossl_site .
            '</vSiteFrom>
									<vLocFrom>' .
            $location .
            '</vLocFrom>
									<vLotFrom>' .
            $locationDetail->ossl_lotserial .
            '</vLotFrom>
									<vYn>true</vYn>
								</MJIiIventoryIssue>
							</dsMJIiIventoryIssue>
						</MJIInventoryIssue>
					</soapenv:Body>
					</soapenv:Envelope>';

        return $this->sendQdocRequest($qdocRequest, $activeConnection);
    }
public function qxWorkOrderComponentIssue(
        $wonbr, $location, $lot, $effdate,
         $part, $qty, $site, $lotserial
    ){

            $domain = Domain::first();
        $domainCode = $domain->domain ?? "";
        $qxwsa = Qxwsa::firstOrFail();

        // Var Qxtend
        $qxUrl = $qxwsa->qx_url;
        $receiver = "QADERP";

        $timeout = 0;
        // XML Qxtend
        $qdocHead = '
            <soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services" xmlns:qcom="urn:schemas-qad-com:xml-services:common" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
            <soapenv:Header>
            <wsa:Action/>
            <wsa:To>urn:services-qad-com:'.$receiver.'</wsa:To>
            <wsa:MessageID>urn:services-qad-com::'.$receiver.'</wsa:MessageID>
            <wsa:ReferenceParameters>
            <qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
            </wsa:ReferenceParameters>
            <wsa:ReplyTo>
            <wsa:Address>urn:services-qad-com:</wsa:Address>
            </wsa:ReplyTo>
            </soapenv:Header>
            <soapenv:Body>
            <issueWorkOrderComponent>
            <qcom:dsSessionContext>
            <qcom:ttContext>
            <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
            <qcom:propertyName>domain</qcom:propertyName>
            <qcom:propertyValue>'.$domainCode.'</qcom:propertyValue>
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
            <dsWorkOrderComponent>
            <workOrderComponent>';
        $qdocBody = '
            <woNbr>'.$wonbr.'</woNbr>
            <woLot>'.$lot.'</woLot>
            <effDate>'.$effdate.'</effDate>
            <fillAll>false</fillAll>
            <fillPick>true</fillPick>
            <yn>true</yn>
            <yn1>true</yn1>
            <yn2>true</yn2>
            <yn3>true</yn3>
            ';
       
            $qdocBody = $qdocBody . 
            '<itemDetail>
                <part>'.$part.'</part>                
                <lotserialQty>'.$qty.'</lotserialQty>
                <site>'.$site.'</site>
                <location>'.$location.'</location>
                <lotserial>'.$lotserial.'</lotserial>            
            <issueDetail>            
                <site>'.$site.'</site>
                <location>'.$location.'</location>
                <lotserial>'.$lotserial.'</lotserial>
                <lotserialQty>'.$qty.'</lotserialQty>
            </issueDetail>
            </itemDetail>
            ';
       
        $qdocFoot = '            
            </workOrderComponent>
            </dsWorkOrderComponent>
            </issueWorkOrderComponent>
            </soapenv:Body>
            </soapenv:Envelope>
        ';
        $qdocRequest = $qdocHead . $qdocBody . $qdocFoot;
                $curlOptions = [
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout, // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
            CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
            CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ];

        $getInfo = "";
        $httpCode = 0;
        $curlErrno = 0;
        $curlError = "";

        $qdocResponse = "";

        $curl = curl_init();
        if ($curl) {
            curl_setopt_array($curl, $curlOptions);
            $qdocResponse = curl_exec($curl); // sending qdocRequest here, the result is qdocResponse.
            //
            $curlErrno = curl_errno($curl);
            $curlError = curl_error($curl);
            $first = true;
            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != "array") {
                    if (!$first) {
                        $getInfo .= ", ";
                    }
                    $getInfo = $getInfo . $key . "=>" . $value;
                    $first = false;
                    if ($key == "http_code") {
                        $httpCode = $value;
                    }
                }
            }
            curl_close($curl);
        }

        if (is_bool($qdocResponse)) {
            return [false, "WSA Connection Error"];
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace("ns1", "urn:schemas-qad-com:xml-services");

        $qdocResult = (string) $xmlResp->xpath("//ns1:result")[0];

        if ($qdocResult == "success" or $qdocResult == "warning") {
            return [true, ""];
        } else {
            $xmlResp->registerXPathNamespace("ns3", "urn:schemas-qad-com:xml-services:common");
            $qdocMsgDesc = $xmlResp->xpath("//ns3:tt_msg_desc");
            $output = "";
            foreach ($qdocMsgDesc as $datas) {
                if (str_contains($datas, "ERROR:")) {
                    $output .= $datas . " - ";
                }
            }
            $output = substr($output, 0, -3);

            return [false, $output];
        }

    }
}

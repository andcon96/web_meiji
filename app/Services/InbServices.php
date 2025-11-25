<?php

namespace App\Services;

use App\Models\Settings\Domain;
use App\Models\Settings\qxwsa;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InbServices
{
    private function httpHeader($req)
    {
        return array(
            'Content-type: text/xml;charset="utf-8"',
            'Accept: text/xml',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'SOAPAction: ""', /* jika tidak pakai SOAPAction, isinya harus ada tanda petik 2 --> "" */
            'Content-length: ' . strlen(preg_replace("/\s+/", " ", $req)) /* hapus whitespace (\s) karakter*/
        );
    }

    public function inbrctunp(array $req)
    {
        $part      = $req['part'];
        $qty       = $req['qty'];
        $location  = $req['location'];
        $lotserial = $req['lotserial'];
        $site      = $req['site'] ?? '';
        $warehouse = $req['warehouse'] ?? '';
        $level     = $req['level'] ?? '';
        $bin       = $req['bin'] ?? '';

        //get domain dan settingan koneksi ke QAD
        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';
        $qxwsa = qxwsa::firstOrFail();

        $username = Auth::user()->username ?? '';

        // Var Qxtend
        $qxUrl    = $qxwsa->qx_url;
        // $receiver = $qxwsa->qx_receiver;
        $receiver = 'QADERP';

        $errMsg    = [];
        $xmlResp   = '';
        $curlErrno = '';
        $curlError = '';
        $getInfo   = '';
        $timeout   = 0;

        $xmlReq = "<soapenv:Envelope xmlns='urn:schemas-qad-com:xml-services' xmlns:qcom='urn:schemas-qad-com:xml-services:common' xmlns:soapenv='http://schemas.xmlsoap.org/soap/envelope/' xmlns:wsa='http://www.w3.org/2005/08/addressing'>
                    <soapenv:Header>
                        <wsa:Action/>
                        <wsa:To>urn:services-qad-com:$receiver</wsa:To>
                        <wsa:MessageID>urn:services-qad-com::$receiver</wsa:MessageID>
                        <wsa:ReferenceParameters>
                            <qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
                        </wsa:ReferenceParameters>
                        <wsa:ReplyTo>
                            <wsa:Address>urn:services-qad-com:</wsa:Address>
                        </wsa:ReplyTo>
                    </soapenv:Header>
                    <soapenv:Body>
                        <scrcptunpmji>
                            <qcom:dsSessionContext>
                                <qcom:ttContext>
                                    <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                                    <qcom:propertyName>domain</qcom:propertyName>
                                    <qcom:propertyValue>$domainCode</qcom:propertyValue>
                                </qcom:ttContext>
                                <qcom:ttContext>
                                    <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                                    <qcom:propertyName>scopeTransaction</qcom:propertyName>
                                    <qcom:propertyValue>true</qcom:propertyValue>
                                </qcom:ttContext>
                                <qcom:ttContext>
                                    <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                                    <qcom:propertyName>version</qcom:propertyName>
                                    <qcom:propertyValue>cust_2</qcom:propertyValue>
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
                        <dsReceiptUnplanned>
                            <ReceiptUnplanned>
                                <vPart>$part</vPart>
                                <vQty>$qty</vQty>
                                <vLocFrom>$location</vLocFrom>
                                <vLotFrom>$lotserial</vLotFrom>
                                <vSiteFrom>$site</vSiteFrom>
                                
                                <multiEntry>false</multiEntry>
                                <vRmks>$username</vRmks>
                                <vWhFrom>$warehouse</vWhFrom>
                                <vLevelFrom>$level</vLevelFrom>
                                <vBinFrom>$bin</vBinFrom>
                                <vYn>true</vYn>
                            </ReceiptUnplanned>
                        </dsReceiptUnplanned>
                    </scrcptunpmji>
                </soapenv:Body>
                </soapenv:Envelope>";

        $options = array(
            CURLOPT_URL            => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT        => $timeout + 120,
            CURLOPT_HTTPHEADER     => $this->httpHeader($xmlReq),
            CURLOPT_POSTFIELDS     => preg_replace("/\s+/", " ", $xmlReq), /* hapus whitespace(\s) karakter */
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $qdocResponse = '';

        $curl = curl_init();

        if ($curl) {
            curl_setopt_array($curl, $options);
            $qdocResponse = curl_exec($curl); // sending qdocRequest here, the result is qdocResponse.
            $curlErrno    = curl_errno($curl);
            $curlError    = curl_error($curl);
            $first        = true;

            if ($curlErrno) {/* dd("Curl error: ". $curlError) */
                Log::info("Curl error: " . $curlError);
            }

            foreach (curl_getinfo($curl) as $key => $value) {
                if (gettype($value) != 'array') {
                    if (!$first) {
                        $getInfo .= ", ";
                    }
                    $getInfo = $getInfo . $key . '=>' . $value;
                    $first = false;
                }
            }

            Log::info($getInfo);

            curl_close($curl);
        }

        if (is_bool($qdocResponse)) {
            return false;
        }

        $xmlResp = simplexml_load_string($qdocResponse); /* output: SimpleXMLElement */

        /*didapat dari template xml response:
        <ns1:KontrakMaintResponse xmlns="urn:schemas-qad-com:xml-services"> &
        <ns3:dsExceptions xmlns:ns3="urn:schemas-qad-com:xml-services:common">,
        output: true/false */
        $xmlResp->registerXPathNamespace('ns1' /*penamaan bebas*/, 'urn:schemas-qad-com:xml-services');
        $xmlResp->registerXPathNamespace('ns3', 'urn:schemas-qad-com:xml-services:common');

        $qdocResult = (string) $xmlResp->xpath('//ns1:result')[0]; /*output: array, error/success*/

        if ($qdocResult == 'error') {
            $xmlResp->registerXPathNamespace('ns3', 'urn:schemas-qad-com:xml-services:common');
            $qdocMsgDesc = $xmlResp->xpath('//ns3:tt_msg_desc');
            $output = '';

            foreach ($qdocMsgDesc as $datas) {
                if (str_contains($datas, 'ERROR:')) {
                    $output .= substr($datas, 7) . ' - ';
                }
            }

            $output = substr($output, 0, -3);
            return [false, $output];
        };


        return [true, ''];
    }

    public function inbissunp(array $req)
    {
        $part = $req['part'];
        $qty = $req['qty'];
        // $site = $req['site'];
        $location = $req['location'];
        $lotserial = $req['lotserial'];
        // $lotref = $req['lotref'];
        $site = $req['site'] ?? '';
        $warehouse = $req['warehouse'] ?? '';
        $level = $req['level'] ?? '';
        $bin = $req['bin'] ?? '';

        //get domain dan settingan koneksi ke QAD
        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';
        $qxwsa = qxwsa::firstOrFail();

        $username = Auth::user()->username ?? '';

        // Var Qxtend
        $qxUrl              = $qxwsa->qx_url;
        $receiver           = $qxwsa->qx_receiver;

        $errMsg    = [];
        $xmlResp   = '';
        $curlErrno = '';
        $curlError = '';
        $getInfo   = '';
        $timeout   = 0;

        $xmlReq = '<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
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
                            <MJIInventoryIssue>
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
                                    <qcom:propertyValue>CustV1</qcom:propertyValue>
                                </qcom:ttContext>
                                <qcom:ttContext>
                                    <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                                    <qcom:propertyName>mnemonicsRaw</qcom:propertyName>
                                    <qcom:propertyValue>false</qcom:propertyValue>
                                </qcom:ttContext>
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
                                        <vPart>' . $part . '</vPart>
                                        <vQty>' . $qty . '</vQty>
                                        <vLocFrom>' . $location . '</vLocFrom>
                                        <vLotFrom>' . $lotserial . '</vLotFrom>
                                        <vSiteFrom>' . $site . '</vSiteFrom>
                                        
                                        <vWhFrom>' . $warehouse . '</vWhFrom>
                                        <vLevelFrom>' . $level . '</vLevelFrom>
                                        <vBinFrom>' . $bin . '</vBinFrom>
                                        <vRmks>' . $username . '</vRmks>
                                    </MJIiIventoryIssue>
                                    </dsMJIiIventoryIssue>
                                    </MJIInventoryIssue>
                                </soapenv:Body>
                            </soapenv:Envelope>';

        $curlOptions = array(
            CURLOPT_URL            => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT        => $timeout + 120,
            CURLOPT_HTTPHEADER     => $this->httpHeader($xmlReq),
            CURLOPT_POSTFIELDS     => preg_replace("/\s+/", " ", $xmlReq), /* hapus whitespace(\s) karakter */
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

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
}

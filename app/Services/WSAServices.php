<?php

namespace App\Services;

use App\Models\PurchaseOrder\POMstr;
use App\Models\Settings\Domain;
use App\Models\Settings\qxwsa;

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

    public function wsasupp($domain_id, $domain)
    {

        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        // Validasi WSA
        $qxUrl          = $wsa->wsa_url;
        $qxReceiver     = '';
        $qxSuppRes      = 'false';
        $qxScopeTrx     = '';
        $qdocName       = '';
        $qdocVersion    = '';
        $dsName         = '';
        $timeout        = 0;

        $qdocRequest =
        '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <supplier_mstr xmlns="' . $wsa->wsa_path . '">
                    <inpdomain>' . $domain . '</inpdomain>
                </supplier_mstr>
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

        $dataloop   = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [$qdocResult, $dataloop];
    }

    public function wsaAddress($domain_id, $ship_to)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();
        $domain = Domain::where('id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
        '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <risis_ad_mstr xmlns="' . $wsa->wsa_path . '">
                    <inpdomain>' . $domain->domain . '</inpdomain>
                    <inpcust>' . $ship_to . '</inpcust>
                </risis_ad_mstr>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        // dd($qdocResponse);
        $dataloop = $xmlResp->xpath('//ns1:tempRow');

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsacustomer($domain_id, $domain)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<risis_cust_mstr xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domain . '</inpdomain>' .
            '</risis_cust_mstr>' .
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
        // dd($qdocResponse);
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaitem($domain_id, $domain)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<item_mstr xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domain . '</inpdomain>' .
            '</item_mstr>' .
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
        // dd($qdocResponse);
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaLocation($domain_id, $domain)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<risis_loc_mstr xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domain . '</inpdomain>' .
            '</risis_loc_mstr>' .
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
        // dd($qdocResponse);
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaPRShipTo($domain, $type, $shipTo)
    {
        $wsa = qxwsa::where('domain_id', $domain->id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<risis_pr_ship_to xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domain->domain . '</inpdomain>' .
            '<inpship>' . $shipTo . '</inpship>' .
            '</risis_pr_ship_to>' .
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
        // dd($qdocResponse);
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsasite($domain_id, $domain)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<risis_site_mstr xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domain . '</inpdomain>' .
            '</risis_site_mstr>' .
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
        // dd($qdocResponse);
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaCreditTerms($domain_id, $domain)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<risis_ct_mstr xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domain . '</inpdomain>' .
            '</risis_ct_mstr>' .
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
        // dd($qdocResponse);
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaSalesPerson($domain_id, $domain)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<risis_sales_person_mstr xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domain . '</inpdomain>' .
            '</risis_sales_person_mstr>' .
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
        // dd($qdocResponse);
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaTaxClass($domain_id, $domain)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<risis_tax_class_mstr xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domain . '</inpdomain>' .
            '</risis_tax_class_mstr>' .
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
        // dd($qdocResponse);
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaFrLists($domain_id, $domain)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<risis_freight_mstr xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domain . '</inpdomain>' .
            '</risis_freight_mstr>' .
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
        // dd($qdocResponse);
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaFrTerms($domain_id, $domain)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<risis_freight_terms xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domain . '</inpdomain>' .
            '</risis_freight_terms>' .
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
        // dd($qdocResponse);
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaSupplierItem($domain_id, $domain, $item, $itemSite, $supplierCode)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<risis_supplier_by_item xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domain . '</inpdomain>' .
            '<inppart>' . $item . '</inppart>' .
            '<inpsite>' . $itemSite . '</inpsite>' .
            '<inpsite>' . $supplierCode . '</inpsite>' .
            '</risis_supplier_by_item>' .
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
        // dd($qdocResponse);
        $dataArray    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        $dataloop = [];
        foreach ($dataArray as $data) {
            array_push($dataloop, [
                't_vp_domain' => (string)$data->t_vp_domain,
                't_vp_part' => (string)$data->t_vp_part,
                't_vp_vend' => (string)$data->t_vp_vend,
                't_vp_vend_desc' => (string)$data->t_vp_vend_desc,
                't_vp_price' => (string)$data->t_vp_price,
                't_vp_um' => (string)$data->t_vp_um,
            ]);
        }


        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaSupplier($domain_id, $domain)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<supp_supp_mstr xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domain . '</inpdomain>' .
            '</supp_supp_mstr>' .
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
        // dd($qdocResponse);
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaInventoryLookup($domain_id, $item, $site, $loc, $lot)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();
        $domain = Domain::where('id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<risis_system_qoh xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domain->domain . '</inpdomain>' .
            '<inppart>' . $item . '</inppart>' .
            '<inpsite>' . $site . '</inpsite>' .
            '<inploc>' . $loc . '</inploc>' .
            '<inplot>' . $lot . '</inplot>' .
            '</risis_system_qoh>' .
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
        // dd($qdocResponse);
        $dataloop = [];
        $dataArray    = $xmlResp->xpath('//ns1:tempRow');
        foreach ($dataArray as $data) {
            array_push($dataloop, [
                't_ld_domain' => (string)$data->t_ld_domain,
                't_ld_site' => (string)$data->t_ld_site,
                't_ld_loc' => (string)$data->t_ld_loc,
                't_lot' => (string)$data->t_lot,
                't_qty_oh' => (string)$data->t_qty_oh,
            ]);
        }

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    // AD API
    public function getLocationDetail($domain, $item, $site, $loc , $lot)
    {
        $wsa = QxWsa::firstOrFail();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 10;

        $arrayloop = [];
        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                <Body>
                    <risis_system_qoh xmlns="'.$wsa->wsa_path.'">
                        <inpdomain>'.$domain.'</inpdomain>
                        <inppart>'.$item.'</inppart>
                        <inpsite>'.$site.'</inpsite>
                        <inploc>'.$loc.'</inploc>
                        <inplot>'.$lot.'</inplot>
                    </risis_system_qoh>
                </Body>
            </Envelope>';

        $curlOptions = array(
            CURLOPT_URL => $qxUrl,
            CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
            CURLOPT_TIMEOUT => $timeout + 5, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
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

        if ($qdocResult == 'true') {
            $output = [];
            foreach($dataloop as $key => $dataloops){
                $output[] = [
                    'domain' => (string)$dataloops->t_ld_domain,
                    'part' => (string)$dataloops->t_ld_part,
                    'site' => (string)$dataloops->t_ld_site,
                    'loc' => (string)$dataloops->t_ld_loc,
                    'lot' => (string)$dataloops->t_lot,
                    'qoh' => (string)$dataloops->t_qty_oh,
                ];
            }

            return [true, $output];
        } else {
            return [false, ''];
        }
    }

    public function wsaShipToByCustomer($domain_id, $domain, $customer)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
        '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <risis_shipto_by_customer xmlns="' .  $wsa->wsa_path . '">
                    <inpdomain>' . $domain . '</inpdomain>
                    <inpsoldto>' . $customer . '</inpsoldto>
                </risis_shipto_by_customer>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        // dd($qdocResponse);
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaTermsAndTaxByCustomer($domain_id, $domain, $customer)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
        '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <risis_terms_tax_by_customer xmlns="' .  $wsa->wsa_path . '">
                    <inpdomain>' . $domain . '</inpdomain>
                    <inpcustomer>' . $customer . '</inpcustomer>
                </risis_terms_tax_by_customer>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        // dd($qdocResponse);
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaTermsAndTaxBySupplier($domain_id, $domain, $supplier)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <risis_terms_tax_by_supplier xmlns="' .  $wsa->wsa_path . '">
                    <inpdomain>' . $domain . '</inpdomain>
                    <inpsupplier>' . $supplier . '</inpsupplier>
                </risis_terms_tax_by_supplier>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        // dd($qdocResponse);
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaLocationLookup($domain_id, $site)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();
        $domain = Domain::where('id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
        '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <risis_location_by_site xmlns="' .  $wsa->wsa_path . '">
                    <inpdomain>' . $domain->domain . '</inpdomain>
                    <inpsite>' . $site . '</inpsite>
                </risis_location_by_site>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        // dd($qdocResponse);
        $dataArray    = $xmlResp->xpath('//ns1:tempRow');
        $dataloop = [];
        foreach ($dataArray as $dataLoc) {
            array_push($dataloop, [
                't_loc_name' => (string)$dataLoc->t_loc_name,
                't_loc_desc' => (string)$dataLoc->t_loc_desc,
            ]);
        }

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaLotLookup($domain_id, $site, $loc)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();
        $domain = Domain::where('id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
        '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <risis_lot_by_loc xmlns="' .  $wsa->wsa_path . '">
                    <inpdomain>' . $domain->domain . '</inpdomain>
                    <inpsite>' . $site . '</inpsite>
                    <inploc>' . $loc . '</inploc>
                </risis_lot_by_loc>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        // dd($qdocResponse);
        $dataArray    = $xmlResp->xpath('//ns1:tempRow');
        $dataloop = [];
        foreach ($dataArray as $dataLot) {
            array_push($dataloop, [
                't_lot_name' => (string)$dataLot->t_lot_name
            ]);
        }

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaGenCodeByField($domain_id, $domain, $field)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();
        $domain = Domain::where('id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
        '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <risis_code_mstr_by_field xmlns="' . $wsa->wsa_path . '">
                    <inpdomain>' . $domain->domain . '</inpdomain>
                    <inpfield>' . $field . '</inpfield>
                </risis_code_mstr_by_field>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        // dd($qdocResponse);
        $dataArray    = $xmlResp->xpath('//ns1:tempRow');
        $dataloop = [];
        foreach ($dataArray as $dataGroup) {
            array_push($dataloop, [
                't_domain' => (string)$dataGroup->t_domain,
                't_code_fldname' => (string)$dataGroup->t_code_fldname,
                't_code_value' => (string)$dataGroup->t_code_value,
                't_code_desc' => (string)$dataGroup->t_code_desc,
            ]);
        }

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaActiveSupplier($domain_id, $domain)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();
        $domain = Domain::where('id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
        '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <risis_supplier_mstr xmlns="' . $wsa->wsa_path . '">
                    <inpdomain>' . $domain->domain . '</inpdomain>
                </risis_supplier_mstr>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        // dd($qdocResponse);
        $dataArray    = $xmlResp->xpath('//ns1:tempRow');
        $dataloop = [];
        foreach ($dataArray as $dataSupplier) {
            array_push($dataloop, [
                't_vd_domain' => (string)$dataSupplier->t_vd_domain,
                't_vd_addr' => (string)$dataSupplier->t_vd_addr,
                't_vd_vend_desc' => (string)$dataSupplier->t_vd_vend_desc,
            ]);
        }

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaPlannedOrder($domain_id, $domain, $item_nbr_from, $item_nbr_to, $site_from, $site_to, $release_date_from, $release_date_to, $item_group, $item_type, $supplier, $buyerOrPlanner)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();
        $domain = Domain::where('id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
        '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <risis_planned_order xmlns="' . $wsa->wsa_path . '">
                    <inpdomain>' . $domain->domain . '</inpdomain>
                    <inppartfrom>' . $item_nbr_from . '</inppartfrom>
                    <inppartto>' . $item_nbr_to . '</inppartto>
                    <inpsitefrom>' . $site_from . '</inpsitefrom>
                    <inpsiteto>' . $site_to . '</inpsiteto>
                    <inpreldatefrom>' . $release_date_from . '</inpreldatefrom>
                    <inpreldateto>' . $release_date_to . '</inpreldateto>
                    <inpitemgroup>' . $item_group . '</inpitemgroup>
                    <inpitemtype>' . $item_type . '</inpitemtype>
                    <inpsupplier>' . $supplier . '</inpsupplier>
                    <inpbuyerplanner>' . $buyerOrPlanner . '</inpbuyerplanner>
                </risis_planned_order>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        // dd($qdocResponse);
        $dataArray    = $xmlResp->xpath('//ns1:tempRow');
        $dataloop = [];
        foreach ($dataArray as $dataPlannedOrder) {
            array_push($dataloop, [
                't_wo_part' => (string)$dataPlannedOrder->t_wo_part,
                't_wo_part_desc' => (string)$dataPlannedOrder->t_wo_part_desc,
                't_wo_part_prod_line' => (string)$dataPlannedOrder->t_wo_part_prod_line,
                't_wo_part_group' => (string)$dataPlannedOrder->t_wo_part_group,
                't_wo_part_type' => (string)$dataPlannedOrder->t_wo_part_type,
                't_wo_site' => (string)$dataPlannedOrder->t_wo_site,
                't_wo_nbr' => (string)$dataPlannedOrder->t_wo_nbr,
                't_wo_due_date' => (string)$dataPlannedOrder->t_wo_due_date,
                't_wo_lot' => (string)$dataPlannedOrder->t_wo_lot,
                't_buyer_planner' => (string)$dataPlannedOrder->t_buyer_planner,
                't_pt_vend' => (string)$dataPlannedOrder->t_pt_vend,
		't_vd_taxable' => (string)$dataPlannedOrder->t_vd_taxable,
                't_vd_taxclass' => (string)$dataPlannedOrder->t_vd_taxc,
                't_vd_tax_rate' => (string)$dataPlannedOrder->t_vd_tax_rate,
                't_pm_code' => (string)$dataPlannedOrder->t_pm_code,
                't_wo_qty_ord' => (string)$dataPlannedOrder->t_wo_qty_ord,
                't_pt_um' => (string)$dataPlannedOrder->t_pt_um,
                't_wo_rel_date' => (string)$dataPlannedOrder->t_wo_rel_date,
                't_pt_cost' => (string)$dataPlannedOrder->t_pt_cost,
                't_base_curr' => (string)$dataPlannedOrder->t_base_curr,
                't_curr' => (string)$dataPlannedOrder->t_curr,
                't_exr_rate' => (string)$dataPlannedOrder->t_exr_rate,
                't_exr_rate2' => (string)$dataPlannedOrder->t_exr_rate2,
                't_operator' => (string)$dataPlannedOrder->t_operator,
            ]);
        }

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaAccount($domain_id, $domain)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();
        $domain = Domain::where('id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <risis_account xmlns="' . $wsa->wsa_path . '">
                    <inpdomain>' . $domain->domain . '</inpdomain>
                </risis_account>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        // dd($qdocResponse);
        $dataArray    = $xmlResp->xpath('//ns1:tempRow');
        $dataloop = [];
        foreach ($dataArray as $dataAccount) {
            array_push($dataloop, [
                't_ac_code' => (string)$dataAccount->t_ac_code,
                't_ac_desc' => (string)$dataAccount->t_ac_desc,
                't_ac_has_sub' => (string)$dataAccount->t_ac_has_sub,
                't_ac_has_cc' => (string)$dataAccount->t_ac_has_cc,
                't_ac_has_project' => (string)$dataAccount->t_ac_has_project
            ]);
        }

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaSubAccount($domain_id, $domain)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();
        $domain = Domain::where('id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
        '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <risis_sub_account xmlns="' . $wsa->wsa_path . '">
                    <inpdomain>' . $domain->domain . '</inpdomain>
                </risis_sub_account>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        // dd($qdocResponse);
        $dataArray    = $xmlResp->xpath('//ns1:tempRow');
        $dataloop = [];
        foreach ($dataArray as $dataSubAcc) {
            array_push($dataloop, [
                't_sub_acc' => (string)$dataSubAcc->t_sub_acc,
                't_sub_desc' => (string)$dataSubAcc->t_sub_desc,
            ]);
        }

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaCostCenter($domain_id, $domain)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();
        $domain = Domain::where('id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <risis_cost_center xmlns="' . $wsa->wsa_path . '">
                    <inpdomain>' . $domain->domain . '</inpdomain>
                </risis_cost_center>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        // dd($qdocResponse);
        $dataArray    = $xmlResp->xpath('//ns1:tempRow');
        $dataloop = [];
        foreach ($dataArray as $dataCC) {
            array_push($dataloop, [
                't_cc_ctr' => (string)$dataCC->t_cc_ctr,
                't_cc_desc' => (string)$dataCC->t_cc_desc,
            ]);
        }

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaProject($domain_id, $domain)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();
        $domain = Domain::where('id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <risis_project xmlns="' . $wsa->wsa_path . '">
                    <inpdomain>' . $domain->domain . '</inpdomain>
                </risis_project>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        // dd($qdocResponse);
        $dataArray    = $xmlResp->xpath('//ns1:tempRow');
        $dataloop = [];
        foreach ($dataArray as $dataProject) {
            array_push($dataloop, [
                't_pj_project' => (string)$dataProject->t_pj_project,
                't_pj_desc' => (string)$dataProject->t_pj_desc,
            ]);
        }

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaGetPO($domain_id, $domain, $poNumber)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();
        $domain = Domain::where('id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <risis_get_po xmlns="' . $wsa->wsa_path . '">
                    <inpdomain>' . $domain->domain . '</inpdomain>
                    <inpponbr>' . $poNumber . '</inpponbr>
                </risis_get_po>
            </Body>
        </Envelope>';
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
        // dd($qdocResponse);
        $dataArray    = $xmlResp->xpath('//ns1:tempRow');
        $dataloop = [];
        $dataArray = collect($dataArray);
        $dataloop = $dataArray->map(function ($dataPO) {
            return [
                't_po_domain' => (string)$dataPO->t_po_domain,
                't_po_nbr' => (string)$dataPO->t_po_nbr,
                't_po_supplier' => (string)$dataPO->t_po_supplier,
                't_po_supplier_desc' => (string)$dataPO->t_po_supplier_desc,
                't_po_site' => (string)$dataPO->t_po_site,
                't_po_ship_to' => (string)$dataPO->t_po_ship_to,
                't_po_ord_date' => (string)$dataPO->t_po_ord_date,
                't_po_due_date' => (string)$dataPO->t_po_due_date,
                't_po_print_status' => (string)$dataPO->t_po_print_status,
                't_po_remark' => (string)$dataPO->t_po_remark,
                't_pod_line' => (string)$dataPO->t_pod_line,
                't_pod_item_code' => (string)$dataPO->t_pod_item_code,
                't_pod_item_desc' => (string)$dataPO->t_pod_item_desc,
                't_pod_um' => (string)$dataPO->t_pod_um,
                't_pod_due_date' => (string)$dataPO->t_pod_due_date,
                't_pod_need_date' => (string)$dataPO->t_pod_need_date,
                't_pod_qty_ord' => (string)$dataPO->t_pod_qty_ord,
                't_pod_qty_open' => (string)$dataPO->t_pod_qty_open,
                't_pod_pur_cost' => (string)$dataPO->t_pod_pur_cost,
                't_pod_account' => (string)$dataPO->t_pod_account,
                't_pod_sub_acc' => (string)$dataPO->t_pod_sub_acc,
                't_pod_cost_center' => (string)$dataPO->t_pod_cost_center,
                't_pod_project' => (string)$dataPO->t_pod_project,
                't_attention' => (string)$dataPO->t_attention,
                't_item_supplier' => (string)$dataPO->t_item_supplier,
                't_currency' => (string) $dataPO->t_currency,
                't_is_taxable' => (string)$dataPO->t_is_taxable,
                't_tax_class' => (string)$dataPO->t_tax_class,
                't_exchange_rate' => (string)$dataPO->t_exchange_rate,
                't_cr_terms' => (string)$dataPO->t_cr_terms,
                't_rev' => (string)$dataPO->t_rev,
            ];
        });

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaPOPrint($domain_id, $poNumber, $chr06)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();
        $domain = Domain::where('id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <risis_update_po_print xmlns="' . $wsa->wsa_path . '">
                    <inpdomain>' . $domain->domain . '</inpdomain>
                    <inpponbr>' . $poNumber . '</inpponbr>
                    <inpstatus>' . $chr06 . '</inpstatus>
                </risis_update_po_print>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return $qdocResult;
    }

    public function wsaTotalCostPO($domain_id, $poNumber)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();
        $domain = Domain::where('id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <risis_total_po xmlns="' . $wsa->wsa_path . '">
                    <inpdomain>' . $domain->domain . '</inpdomain>
                    <inpponbr>' . $poNumber . '</inpponbr>
                </risis_total_po>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        $dataloop = $xmlResp->xpath('//ns1:tempRow');

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaPOMonthly($domain_id)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();
        $domain = Domain::where('id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <risis_monthly_po xmlns="' . $wsa->wsa_path . '">
                    <inpdomain>' . $domain->domain . '</inpdomain>
                </risis_monthly_po>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        $dataArray    = $xmlResp->xpath('//ns1:tempRow');
        $dataloop = [];
        $dataArray = collect($dataArray);
        $dataloop = $dataArray->map(function ($dataPO) {
            return [
                't_po_domain' => (string)$dataPO->t_po_domain,
                't_po_nbr' => (string)$dataPO->t_po_nbr,
                't_po_ord_date' => (string)$dataPO->t_po_ord_date,
                't_po_line' => (string)$dataPO->t_po_line,
            ];
        });

        return [$qdocResult, $dataloop];
    }

    public function wsaSOMonthly($domain_id)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();
        $domain = Domain::where('id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <risis_monthly_so xmlns="' . $wsa->wsa_path . '">
                    <inpdomain>' . $domain->domain . '</inpdomain>
                </risis_monthly_so>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return $qdocResult;
    }

    public function wsaUpdatePOLog01($domain_id, $poNumber)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();
        $domain = Domain::where('id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <risis_update_po_type xmlns="' . $wsa->wsa_path . '">
                    <inpdomain>' . $domain->domain . '</inpdomain>
                    <inpponbr>' . $poNumber . '</inpponbr>
                </risis_update_po_type>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return $qdocResult;
    }

    public function wsaUpdateSOLog01($domain_id, $poNumber)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();
        $domain = Domain::where('id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <risis_update_so_type xmlns="' . $wsa->wsa_path . '">
                    <inpdomain>' . $domain->domain . '</inpdomain>
                    <inpsonbr>' . $poNumber . '</inpsonbr>
                </risis_update_so_type>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return $qdocResult;
    }

    public function wsaItemPrice($prMaster, $prDetail)
    {
        $wsa = qxwsa::where('domain_id', $prMaster->domain_id)->first();
        $domain = Domain::where('id', $prMaster->domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <risis_get_list_price_by_item xmlns="' . $wsa->wsa_path . '">
                    <inpdomain>' . $domain->domain . '</inpdomain>
                    <inpsite>' . $prMaster->pr_site . '</inpsite>
                    <inpitem>' . $prDetail->prd_item_code . '</inpitem>
                </risis_get_list_price_by_item>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        $dataArray    = $xmlResp->xpath('//ns1:tempRow');
        $dataloop = [];
        $dataArray = collect($dataArray);
        $dataloop = $dataArray->map(function ($data) {
            return [
                't_domain' => (string)$data->t_domain,
                't_site' => (string)$data->t_site,
                't_item' => (string)$data->t_item,
                't_price' => $data->t_price,
            ];
        });

        return [$qdocResult, $dataloop];
    }

    public function getPOLastMonth($domain_id)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();
        $domain = Domain::where('id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $wsaPath = $wsa->wsa_path;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
        '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <risis_get_po_last_month xmlns="' . $wsaPath . '">
                    <inpdomain>' . $domain->domain . '</inpdomain>
                </risis_get_po_last_month>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        $dataArray    = $xmlResp->xpath('//ns1:tempRow');
        $dataloop = [];
        $dataArray = collect($dataArray);
        $dataloop = $dataArray->map(function ($data) {
            return [
                't_po_domain' => (string) $data->t_po_domain,
                't_po_nbr' => (string) $data->t_po_nbr,
                't_po_supplier' => (string) $data->t_po_supplier,
                't_po_site' => (string) $data->t_po_site,
                't_po_shipto' => (string) $data->t_po_shipto,
                't_po_ord_date' => (string) $data->t_po_ord_date,
                't_po_need_date' => (string) $data->t_po_need_date,
                't_po_due_date' => (string) $data->t_po_due_date,
                't_po_curr' => (string) $data->t_po_curr,
                't_po_exc_rate' => (string) $data->t_po_exc_rate,
                't_po_cr_terms' => (string) $data->t_po_cr_terms,
                't_pod_line' => (string) $data->t_pod_line,
                't_pod_item_code' => (string) $data->t_pod_item_code,
                't_pod_item_desc' => (string) $data->t_pod_item_desc,
                't_pod_type' => (string) $data->t_pod_type,
                't_pod_um' => (string) $data->t_pod_um,
                't_pod_due_date' => (string) $data->t_pod_due_date,
                't_pod_need_date' => (string) $data->t_pod_need_date,
                't_pod_qty_ord' => (float) $data->t_pod_qty_ord,
                't_pod_qty_rcvd' => (float) $data->t_pod_qty_rcvd,
                't_pod_qty_open' => (float) $data->t_pod_qty_open,
                't_pod_pur_cost' => (float) $data->t_pod_pur_cost,
                't_pod_sub_acc' => (string) $data->t_pod_sub_acc,
                't_pod_cc' => (string) $data->t_pod_cc,
                't_pod_project' => (string) $data->t_pod_project,
                't_pod_taxable' => (string) $data->t_pod_taxable,
                't_pod_taxc' => (string) $data->t_pod_taxc,
                't_pod_tax_in' => (string) $data->t_pod_tax_in,
		't_pod_total_cost_det' => (string) $data->t_pod_total_cost,
            ];
        });

        return [$qdocResult, $dataloop];
    }

    public function getSOLastMonth($domain_id)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();
        $domain = Domain::where('id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $wsaPath = $wsa->wsa_path;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <risis_get_so_last_month xmlns="' . $wsaPath . '">
                    <inpdomain>' . $domain->domain . '</inpdomain>
                </risis_get_so_last_month>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        $dataArray    = $xmlResp->xpath('//ns1:tempRow');
        $dataloop = [];
        $dataArray = collect($dataArray);
        $dataloop = $dataArray->map(function ($data) {
            return [
                't_so_domain' => (string) $data->t_so_domain,
                't_so_nbr' => (string) $data->t_so_nbr,
                't_so_sold_to' => (string) $data->t_so_sold_to,
                't_so_bill_to' => (string) $data->t_so_bill_to,
                't_so_ship_to' => (string) $data->t_so_ship_to,
                't_so_ord_date' => (string) $data->t_so_ord_date,
                't_so_need_date' => (string) $data->t_so_need_date,
                't_so_due_date' => (string) $data->t_so_due_date,
                't_sod_line' => (string) $data->t_sod_line,
                't_sod_item_code' => (string) $data->t_sod_item_code,
                't_sod_item_desc' => (string) $data->t_sod_item_desc,
                't_sod_um' => (string) $data->t_sod_um,
                't_sod_loc' => (string) $data->t_sod_loc,
                't_sod_list_pr' => (float) $data->t_sod_list_pr,
                't_sod_price' => (float) $data->t_sod_price,
                't_sod_qty_ord' => (float) $data->t_sod_qty_ord,
                't_sod_qty_ship' => (float) $data->t_sod_qty_ship,
                't_sod_qty_open' => (float) $data->t_sod_qty_open,
            ];
        });

        return [$qdocResult, $dataloop];
    }

    public function wsaUpdateStatusPOMonthly($domain_id, $poNumber)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();
        $domain = Domain::where('id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <risis_update_po_status xmlns="' . $wsa->wsa_path . '">
                    <inpdomain>' . $domain->domain . '</inpdomain>
                    <inpponbr>' . $poNumber . '</inpponbr>
                </risis_update_po_status>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return $qdocResult;
    }

    public function wsaUpdateStatusSOMonthly($domain_id, $soNumber)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();
        $domain = Domain::where('id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <risis_update_so_status xmlns="' . $wsa->wsa_path . '">
                    <inpdomain>' . $domain->domain . '</inpdomain>
                    <inpsonbr>' . $soNumber . '</inpsonbr>
                </risis_update_so_status>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return $qdocResult;
    }

    public function wsaItemByFirstInput($domain_id, $domain, $item)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<risis_get_item_by_first xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domain . '</inpdomain>' .
            '<inppart>' . $item . '</inppart>' .
            '</risis_get_item_by_first>' .
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
        // dd($qdocResponse);
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaGetWOBill($domain_id, $domain)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $wsaPath = $wsa->wsa_path;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                <Body>
                    <risis_get_wo_bill xmlns="' . $wsaPath . '">
                        <inpdomain>' . $domain . '</inpdomain>
                    </risis_get_wo_bill>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        // dd($qdocResponse);
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        $dataArray    = $xmlResp->xpath('//ns1:tempRow');
        $dataloop = [];
        $dataArray = collect($dataArray);

        $dataloop = $dataArray->map(function ($dataWO) {
            return [
                't_wo_domain' => (string)$dataWO->t_wo_domain,
                't_wo_nbr' => (string)$dataWO->t_wo_nbr,
                't_wo_lot' => (string)$dataWO->t_wo_lot,
                't_component_item' => (string)$dataWO->t_component_item,
                't_component_desc' => (string)$dataWO->t_component_desc,
                't_component_um' => (string)$dataWO->t_component_um,
                't_site' => (string)$dataWO->t_site,
                't_loc' => (string)$dataWO->t_loc,
                't_operation' => (string)$dataWO->t_operation,
                't_work_center' => (string)$dataWO->t_work_center,
                't_work_status' => (string)$dataWO->t_component_um,
                't_qty_required' => (float)$dataWO->t_qty_required,
                't_qty_pick' => (float)$dataWO->t_qty_pick,
                't_qty_open' => (float)$dataWO->t_qty_open,
                't_ord_date' => (string)$dataWO->t_ord_date,
                't_rel_date' => (string)$dataWO->t_rel_date,
            ];
        });

        return [$qdocResult, $dataloop];
    }

    public function wsaGetWRRoute($domain_id, $domain, $workCenter)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $wsaPath = $wsa->wsa_path;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                <Body>
                    <risis_get_wr_route xmlns="' . $wsaPath . '">
                        <inpdomain>' . $domain . '</inpdomain>
                        <inpworkcenter>' . $workCenter . '</inpworkcenter>
                    </risis_get_wr_route>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        // dd($qdocResponse);
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        $dataArray    = $xmlResp->xpath('//ns1:tempRow');
        $dataloop = [];
        $dataArray = collect($dataArray);

        $dataloop = $dataArray->map(function ($dataWR) {
            return [
                't_wr_nbr' => (string)$dataWR->t_wr_nbr,
                't_wr_lot' => (string)$dataWR->t_wr_lot,
                't_wr_part' => (string)$dataWR->t_wr_part,
                't_item_desc' => (string)$dataWR->t_item_desc,
                't_item_draw' => (string)$dataWR->t_item_draw,
                't_wr_op' => (string)$dataWR->t_wr_op,
                't_wr_wkctr' => (string)$dataWR->t_wr_wkctr,
                't_wr_qty_ord' => (string)$dataWR->t_wr_qty_ord,
                't_wr_std_time' => (string)$dataWR->t_wr_std_time,
                't_wr_status' => (string)$dataWR->t_wr_status,
            ];
        });

        return [$qdocResult, $dataloop];
    }

    public function wsaGetItemGroup($wsa, $domain)
    {
        $qxUrl = $wsa->wsa_url;
        $wsaPath = $wsa->wsa_path;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                <Body>
                    <risis_item_group_for_price xmlns="' . $wsaPath . '">
                        <inpdomain>' . $domain . '</inpdomain>
                    </risis_item_group_for_price>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        // dd($qdocResponse);
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        $dataArray    = $xmlResp->xpath('//ns1:tempRow');
        $dataloop = [];
        $dataArray = collect($dataArray);

        $dataloop = $dataArray->map(function ($dataWR) {
            return [
                't_pt_prod_line' => (string)$dataWR->t_pt_prod_line,
                't_pt_group' => (string)$dataWR->t_pt_group,
                't_pt_part_type' => (string)$dataWR->t_pt_part_type,
                't_pt_promo' => (string)$dataWR->t_pt_promo,
            ];
        });

        return [$qdocResult, $dataloop];
    }

    public function wsaGetTaxIn($domain_id, $domain, $taxClass, $orderDate)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();
        $qxUrl = $wsa->wsa_url;
        $wsaPath = $wsa->wsa_path;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                <Body>
                    <risis_get_taxin xmlns="' . $wsaPath . '">
                        <inpdomain>' . $domain . '</inpdomain>
                        <inptaxclass>' . $taxClass . '</inptaxclass>
                        <inporderdate>' . $orderDate . '</inporderdate>
                    </risis_get_taxin>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        // dd($qdocResponse);
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        $dataArray    = $xmlResp->xpath('//ns1:tempRow');
        $dataloop = [];
        $dataArray = collect($dataArray);

        $dataloop = $dataArray->map(function ($data) {
            return [
                't_domain' => (string)$data->t_domain,
                't_tax_class' => (string)$data->t_tax_class,
                't_effdate' => (string)$data->t_effdate,
                't_exp_date' => (string)$data->t_exp_date,
                't_tax_pct' => (string)$data->t_tax_pct,
            ];
        });

        return [$qdocResult, $dataloop];
    }

    public function getAddress($domain_id, $domain, $addr, $type)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();
        $qxUrl = $wsa->wsa_url;
        $wsaPath = $wsa->wsa_path;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                <Body>
                    <get_address_by_domain xmlns="' . $wsaPath . '">
                        <inpdomain>' . $domain . '</inpdomain>
                        <inpaddr>' . $addr . '</inpaddr>
                        <inptype>' . $type . '</inptype>
                    </get_address_by_domain>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        // dd($qdocResponse);
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        $dataArray    = $xmlResp->xpath('//ns1:tempRow');
        $dataloop = [];
        $dataArray = collect($dataArray);

        $dataloop = $dataArray->map(function ($data) {
            return [
                't_addr' => (string)$data->t_addr,
                't_addr_line1' => (string)$data->t_addr_line1,
                't_addr_line2' => (string)$data->t_addr_line2,
                't_addr_line3' => (string)$data->t_addr_line3,
                't_name' => (string)$data->t_name,
                't_phone' => (string)$data->t_phone,
                't_email' => (string)$data->t_email,
                't_city' => (string)$data->t_city,
                't_country' => (string)$data->t_country,
                't_postal' => (string)$data->t_postal,
            ];
        });

        return [$qdocResult, $dataloop];
    }

    public function getCreditTermsBySupplier($domain_id, $domain, $supplier)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();
        $qxUrl = $wsa->wsa_url;
        $wsaPath = $wsa->wsa_path;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                <Body>
                    <get_cr_terms_by_supplier xmlns="' . $wsaPath . '">
                        <inpdomain>' . $domain . '</inpdomain>
                        <inpsupplier>' . $supplier . '</inpsupplier>
                    </get_cr_terms_by_supplier>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        // dd($qdocResponse);
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        $dataArray    = $xmlResp->xpath('//ns1:tempRow');
        $dataloop = [];
        $dataArray = collect($dataArray);

        $dataloop = $dataArray->map(function ($data) {
            return [
                't_domain' => (string)$data->t_domain,
                't_cr_code' => (string)$data->t_cr_code,
                't_cr_desc' => (string)$data->t_cr_desc,
            ];
        });

        return [$qdocResult, $dataloop];
    }

    public function wsaBaseCurrency($domain_id, $domain)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();
        $qxUrl = $wsa->wsa_url;
        $wsaPath = $wsa->wsa_path;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                <Body>
                    <get_base_currency xmlns="' . $wsaPath . '">
                        <inpdomain>' . $domain . '</inpdomain>
                    </get_base_currency>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        // dd($qdocResponse);
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        $dataArray    = $xmlResp->xpath('//ns1:tempRow');
        $dataloop = [];
        $dataArray = collect($dataArray);

        $dataloop = $dataArray->map(function ($data) {
            return [
                't_entity' => (string)$data->t_entity,
                't_base_curr' => (string)$data->t_base_curr,
            ];
        });

        return [$qdocResult, $dataloop];
    }

    public function wsaCurrencyBySupplier($domain_id, $domain, $supplier)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();
        $qxUrl = $wsa->wsa_url;
        $wsaPath = $wsa->wsa_path;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                <Body>
                    <get_currency_by_supplier xmlns="' . $wsaPath . '">
                        <inpdomain>' . $domain . '</inpdomain>
                        <inpsupplier>' . $supplier . '</inpsupplier>
                    </get_currency_by_supplier>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        // dd($qdocResponse);
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        $dataArray    = $xmlResp->xpath('//ns1:tempRow');
        $dataloop = [];
        $dataArray = collect($dataArray);

        $dataloop = $dataArray->map(function ($data) {
            return [
                't_domain' => (string)$data->t_domain,
                't_currency_code' => (string)$data->t_currency_code,
                't_currency_desc' => (string)$data->t_currency_desc,
            ];
        });

        return [$qdocResult, $dataloop];
    }

    public function wsaExchangeRate($domain_id, $domain, $currFrom, $currTo)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();
        $qxUrl = $wsa->wsa_url;
        $wsaPath = $wsa->wsa_path;

        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                <Body>
                    <get_exchange_rate xmlns="' . $wsaPath . '">
                        <inpdomain>' . $domain . '</inpdomain>
                        <inpbasecurr>' . $currFrom . '</inpbasecurr>
                        <inptocurr>' . $currTo . '</inptocurr>
                    </get_exchange_rate>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        // dd($qdocResponse);
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        $dataArray    = $xmlResp->xpath('//ns1:tempRow');
        $dataloop = [];
        $dataArray = collect($dataArray);

        $dataloop = $dataArray->map(function ($data) {
            return [
                't_domain' => (string)$data->t_domain,
                't_basecurr' => (string)$data->t_basecurr,
                't_tocurr' => (string)$data->t_tocurr,
                't_start_date' => (string)$data->t_start_date,
                't_end_date' => (string)$data->t_end_date,
                't_rate1' => (string)$data->t_rate1,
                't_rate2' => (string)$data->t_rate2,
		't_higher_curr' => (string)$data->t_higher_curr,
            ];
        });

        return [$qdocResult, $dataloop];
    }

    public function wsaAccountChild($domain_id, $account)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();
        $domain = Domain::where('id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
        '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <get_account_childs xmlns="' .  $wsa->wsa_path . '">
                    <inpgl>' . $account . '</inpgl>
                </get_account_childs>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        // dd($qdocResponse);
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function checkItemOnSilvador($domainSSB, $item)
    {
        $wsa = qxwsa::where('domain_id', $domainSSB->id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
        '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <check_item_on_silvador xmlns="' .  $wsa->wsa_path . '">
                    <inpdomain>' . $domainSSB->domain . '</inpdomain>
                    <inpitem>' . $item . '</inpitem>
                </check_item_on_silvador>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        // dd($qdocResponse);
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return $qdocResult;
    }

    public function wsaCountry($domain_id)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
        '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <get_country xmlns="' .  $wsa->wsa_path . '">
                </get_country>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];
        // dd($qdocResult);

        return [$qdocResult, $dataloop];
    }

    public function getCreditTermsDesc($domain_id, $domain, $ct_code)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
        '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <get_ct_desc xmlns="' .  $wsa->wsa_path . '">
                    <inpdomain>' . $domain . '</inpdomain>
                    <inpctcode>' . $ct_code . '</inpctcode>
                </get_ct_desc>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];
        // dd($qdocResult);

        return [$qdocResult, $dataloop];
    }

    public function getGLDescription($domain_id, $domain, $account, $subAccount, $costCenter, $project)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <get_gl_description xmlns="' .  $wsa->wsa_path . '">
                    <inpdomain>' . $domain . '</inpdomain>
                    <inpaccount>' . $account . '</inpaccount>
                    <inpsubaccount>' . $subAccount . '</inpsubaccount>
                    <inpcostcenter>' . $costCenter . '</inpcostcenter>
                    <inpproject>' . $project . '</inpproject>
                </get_gl_description>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];
        // dd($qdocResult);

        return [$qdocResult, $dataloop];
    }

    public function getWorkCenter($domain_id, $domain)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <get_work_center xmlns="' .  $wsa->wsa_path . '">
                    <inpdomain>' . $domain . '</inpdomain>
                </get_work_center>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];
        // dd($qdocResult);

        return [$qdocResult, $dataloop];
    }

    public function wsaGetAllCurrency($domain_id)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <get_all_currency xmlns="' .  $wsa->wsa_path . '">
                </get_all_currency>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];
        // dd($qdocResult);

        return [$qdocResult, $dataloop];
    }

    public function wsaItemMemo($domain_id, $domain, $item)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<risis_get_item_memo xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domain . '</inpdomain>' .
            '<inppart>' . $item . '</inppart>' .
            '</risis_get_item_memo>' .
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
        // dd($qdocResponse);
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaSearchSpecificCustomer($domain_id, $domain, $cust_code)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<risis_search_specific_cust xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domain . '</inpdomain>' .
            '<inpcust>' . $cust_code . '</inpcust>' .
            '</risis_search_specific_cust>' .
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

    public function wsaGetEmployee($domain_id, $domain)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<get_employee xmlns="' . $wsa->wsa_path . '">' .
                '<inpdomain>' . $domain . '</inpdomain>
            </get_employee>' .
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
        // dd($qdocResponse);
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        $dataArray    = $xmlResp->xpath('//ns1:tempRow');
        $dataloop = [];
        $dataArray = collect($dataArray);

        $dataloop = $dataArray->map(function ($data) {
            return [
                'employee_code' => (string)$data->employee_code,
                'employee_first_name' => (string)$data->employee_first_name,
            ];
        });

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaCheckLastOperation($domain_id, $domain, $woNbr, $woLot, $operation)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<check_last_operation xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domain . '</inpdomain>
            <inpwonbr>' . $woNbr . '</inpwonbr>
            <inpwolot>' . $woLot . '</inpwolot>
            <inpoperation>' . $operation . '</inpoperation>
            </check_last_operation>' .
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
        // dd($qdocResponse);
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');

        return $qdocResult;
    }

    public function wsaGetMovement($domain_id, $domain)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<get_item_movement xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domain . '</inpdomain>
            </get_item_movement>' .
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
        // dd($qdocResponse);
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        $dataArray    = $xmlResp->xpath('//ns1:tempRow');
        $dataloop = [];
        $dataArray = collect($dataArray);

        $dataloop = $dataArray->map(function ($data) {
            return [
                't_wr_nbr' => (string)$data->t_wr_nbr,
                't_wr_lot' => (string)$data->t_wr_lot,
                't_wr_part' => (string)$data->t_wr_part,
                't_item_desc' => (string)$data->t_item_desc,
                't_wr_op' => (string)$data->t_wr_op,
                't_wr_wkctr' => (string)$data->t_wr_wkctr,
            ];
        });

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaTaxRate($domain, $taxClass)
    {
        $wsa = qxwsa::where('domain_id', $domain->id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<get_tax_rate_by_tax_class xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domain->domain . '</inpdomain>
            <inptaxclass>' . $taxClass . '</inptaxclass>
            </get_tax_rate_by_tax_class>' .
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
        // dd($qdocResponse);
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        $dataArray    = $xmlResp->xpath('//ns1:tempRow');
        $dataloop = [];
        $dataArray = collect($dataArray);

        $dataloop = $dataArray->map(function ($data) {
            return [
                't_tax_clas' => (string)$data->t_tax_class,
                't_tax_rate' => (string)$data->t_tax_rate,
            ];
        });

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaItemDescription($domain, $itemCode)
    {
        $wsa = qxwsa::where('domain_id', $domain->id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<get_item_description xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domain->domain . '</inpdomain>
            <inpitem>' . $itemCode . '</inpitem>
            </get_item_description>' .
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
        // dd($qdocResponse);
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        $dataArray    = $xmlResp->xpath('//ns1:tempRow');
        $dataloop = [];
        $dataArray = collect($dataArray);

        $dataloop = $dataArray->map(function ($data) {
            return [
                't_item_desc' => (string)$data->t_item_desc,
            ];
        });

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function getSalesPersonName($domainMaster, $salesPerson)
    {
        $wsa = qxwsa::where('domain_id', $domainMaster->id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<get_sales_person_name xmlns="' . $wsa->wsa_path . '">
            <inpdomain>' . $domainMaster->domain . '</inpdomain>
            <inpsalesperson>' . $salesPerson . '</inpsalesperson>
            </get_sales_person_name>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        // dd($qdocResponse);
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        $dataArray    = $xmlResp->xpath('//ns1:tempRow');
        $dataloop = [];
        $dataArray = collect($dataArray);

        $dataloop = $dataArray->map(function ($data) {
            return [
                't_sp_code' => (string)$data->t_sp_code,
                't_sp_name' => (string)$data->t_sp_name,
            ];
        });

        return $dataloop;
    }

    public function wsaPONbr($ponbr){
        $pomstr = POMstr::withoutGlobalScopes()->where('po_nbr',$ponbr)->first();
        $domain = Domain::where('id',$pomstr->po_domain)->first();
        $wsa = qxwsa::where('domain_id', $domain->id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<get_po_nbr xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>'. $domain->domain .'</inpdomain>
            <inpponbr>'.$pomstr->po_nbr.'</inpponbr>
            </get_po_nbr>' .
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
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOk')[0];

        return $qdocResult;
    }

    public function wsaAccountBySupplier($domain, $supplierCode)
    {
        $wsa = qxwsa::where('domain_id', $domain->id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <get_default_accounts_by_supplier xmlns="' .  $wsa->wsa_path . '">
                    <inpdomain>' . $domain->domain . '</inpdomain>
                    <inpsupplier>' . $supplierCode . '</inpsupplier>
                </get_default_accounts_by_supplier>
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

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');

        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];
        // dd($qdocResult);

        return [$qdocResult, $dataloop];
    }

    public function wsaItemMemoForService($domain_id, $domain)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<risis_get_item_memo_for_service xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domain . '</inpdomain>' .
            '</risis_get_item_memo_for_service>' .
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
        // dd($qdocResponse);
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaSummaryPR($domainMaster, $itemCode, $dateFrom, $dateTo)
    {
        $wsa = qxwsa::where('domain_id', $domainMaster->id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<getSummaryForPR xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainMaster->domain . '</inpdomain>' .
            '<inppart>' . $itemCode . '</inppart>' .
            '<inpdatefrom>' . $dateFrom . '</inpdatefrom>' .
            '<inpdateto>' . $dateTo . '</inpdateto>' .
            '</getSummaryForPR>' .
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
        // dd($qdocResponse);
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaGetRouteByNbrLot($domainMaster, $woNbr, $lot)
    {
        $wsa = qxwsa::where('domain_id', $domainMaster->id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<get_route_by_nbr_lot xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainMaster->domain . '</inpdomain>' .
            '<inpwonbr>' . $woNbr . '</inpwonbr>' .
            '<inpwolot>' . $lot . '</inpwolot>' .
            '</get_route_by_nbr_lot>' .
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
        // dd($qdocResponse);
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function getInventoryStatus($domainMaster, $location)
    {
        $wsa = qxwsa::where('domain_id', $domainMaster->id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<getInventoryStatus xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domainMaster->domain . '</inpdomain>' .
            '<inploc>' . $location . '</inploc>' .
            '</getInventoryStatus>' .
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
        // dd($qdocResponse);
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function insertPodDesc($domainid, $ponbr,$item,$itemdesc,$line,$action)
    {
        $domainMaster = Domain::where('id',$domainid)->first();
        $wsa = qxwsa::where('domain_id', $domainMaster->id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<insert_item_desc xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>'.$domainMaster->domain .'</inpdomain>'.
            '<inpnbr>'.$ponbr.'</inpnbr>' .
            '<inpline>'.$line.'</inpline>' .
            '<inpitem>'.$item.'</inpitem>'.
            '<inpdesc>'.$itemdesc.'</inpdesc>'.
            '<inpaction>'.$action.'</inpaction>'.
            '</insert_item_desc>' .
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

        // dd($qdocResponse);

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOk')[0];



        return $qdocResult;

    }

    public function wsaPackagingItemByFirstInput($domain_id, $domain, $item)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<get_package_item xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domain . '</inpdomain>' .
            '<inppart>' . $item . '</inppart>' .
            '</get_package_item>' .
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
        // dd($qdocResponse);
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [
            $qdocResult,
            $dataloop,
        ];
    }

    public function wsaCheckLocation($domain_id, $domain, $loc)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<checkLocation xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domain . '</inpdomain>' .
            '<inploc>' . $loc . '</inploc>' .
            '</checkLocation>' .
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
        // dd($qdocResponse);
        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return $qdocResult;
    }
     public function wsaGetUploadDetail($domain_id, $domain, $loc, $part, $site)
    {
        $wsa = qxwsa::where('domain_id', $domain_id)->first();

        $qxUrl = $wsa->wsa_url;
        $qxReceiver = '';
        $qxSuppRes = 'false';
        $qxScopeTrx = '';
        $qdocName = '';
        $qdocVersion = '';
        $dsName = '';
        $timeout = 0;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<get_item_for_complain_upload xmlns="' . $wsa->wsa_path . '">' .
            '<inpdomain>' . $domain . '</inpdomain>' .
            '<inppart>' . $part . '</inppart>' .
            '<inploc>' . $loc . '</inploc>' .
            '<inpsite>' . $site . '</inpsite>' .
            '</get_item_for_complain_upload>' .
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
        // dd($qdocResponse);

        if (is_bool($qdocResponse)) {
            return false;
        }

        $dataloop    = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];
        if($qdocResult == false){
            $qdocMsg = (string) $xmlResp->xpath('//ns1:outMsg')[0];
            return [$qdocResult,$qdocMsg];
        }
        else if ($qdocResult == true){

            $dataArray    = $xmlResp->xpath('//ns1:tempRow');
            $dataloop = [];
            $dataArray = collect($dataArray);

            $dataloop = $dataArray->map(function ($data) {
                return [
                    't_part' => (string)$data->t_part,
                    't_desc' => (string)$data->t_desc,
                    't_qty_oh' => (string)$data->t_qty_oh
                ];
            });

            return [
                $qdocResult,
                $dataloop,
            ];
        }
    }

}

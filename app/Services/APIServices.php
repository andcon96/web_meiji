<?php

namespace App\Services;

use App\Models\Settings\Domain;
use App\Models\Settings\qxwsa;
use App\Models\Settings\Prefix;
use App\Services\WSAServices;
use Illuminate\Support\Facades\DB;
use App\Models\SalesOrder\SODet;
use App\Models\SalesOrder\SOHist;
use App\Models\SalesOrder\SOMstr;
use App\Models\CustomerShipTo\CustomerShipTo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\API\EpointSetting;
use Illuminate\Support\Facades\Http;

class APIServices
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

    public function createShipTo($data, $userid)
    {
        $domain_id = 1; // Hardcode RISIS
        $domain_name = "RISIS";

        $wsaCustomer = (new WSAServices())->wsaSearchSpecificCustomer($domain_id, $domain_name, $data->header->cstmr_code) ?? '';
        $customerName = $data->customer->first_name . ' ' . $data->customer->last_name ?? '';

        $taxzone = (string)$wsaCustomer[1][0]->t_cust_tax_zone ?? '';
        $taxable = (string)$wsaCustomer[1][0]->t_cust_taxable ?? '';
        $taxincity = (string)$wsaCustomer[1][0]->t_cust_tax_in_city ?? '';
        $taxincluded = (string)$wsaCustomer[1][0]->t_cust_tax_is_included ?? '';
        $language = (string)$wsaCustomer[1][0]->t_cust_language ?? '';
        $langdesc = (string)$wsaCustomer[1][0]->t_cust_language_desc ?? '';

        DB::beginTransaction();

        try {
            // Get Prefix for Ship To
            $prefix = Prefix::where('domain_id', $domain_id)->first();
            $prefixShipTo = $prefix->prefix_ship_to_shopify;
            $prefixLength = strlen($prefixShipTo);
            $lengthAvailable = 8 - $prefixLength; // 8 is the maximum length of SO Number in QAD
            $runningNumber = (string) $prefix->running_nbr_ship_to_shopify;
            if (strlen($runningNumber) < $lengthAvailable) {
                $runningNumber = str_pad($runningNumber, $lengthAvailable, '0', STR_PAD_LEFT);
            }

            $customerCode = $data->header->cstmr_code;
            $shipToCode = $prefixShipTo . $runningNumber;

            $splitAddress =  str_split($data->customer->default_address, 36); // 36 by Max Char in DB
            $address1 = $splitAddress[0] ?? '';
            $address2 = $splitAddress[1] ?? '';
            $address3 = $splitAddress[2] ?? '';

            $postal = $data->customer->zip ?? '';
            $city = $data->customer->city ?? '';
            $countryCode = $data->customer->country_code == 'SG' ? 'SGP' : $data->customer->country_code ?? '';
            $countryDesc = $data->customer->country ?? '';
            $telephone = $data->customer->phone ?? '';
            $email = $data->customer->email ?? '';
            $shopify_cust_id = $data->customer->cstmr_id ?? '';

            $operation = 'Create';

            $user = $userid;

            $customerShipTo = new CustomerShipTo();
            $customerShipTo->domain_id = $domain_id;
            // $customerShipTo->cst_is_temp = $temporary;
            $customerShipTo->cst_customer_code = $customerCode;
            $customerShipTo->cst_ship_to_code = $shipToCode;
            $customerShipTo->cst_ship_to_name = $customerName;
            $customerShipTo->cst_search_name = $customerName;
            $customerShipTo->cst_address_name = $customerName;
            $customerShipTo->cst_ad_line1 = $address1;
            $customerShipTo->cst_ad_line2 = $address2;
            $customerShipTo->cst_ad_line3 = $address3;
            $customerShipTo->cst_postal = $postal;
            $customerShipTo->cst_address_city = $city;
            $customerShipTo->cst_country_code = $countryCode;
            $customerShipTo->cst_country_desc = $countryDesc;
            // $customerShipTo->cst_format;
            // $customerShipTo->cst_is_temp = $temporary;
            $customerShipTo->cst_tax_zone = $taxzone;
            $customerShipTo->cst_is_taxable = $taxable == 'true' ? 'Yes' : 'No';
            $customerShipTo->cst_is_tax_in_city = $taxincity == 'true' ? 'Yes' : 'No';
            $customerShipTo->cst_is_tax_included = $taxincluded == 'true' ? 'Yes' : 'No';
            // $customerShipTo->cst_tax_declaration = ;
            $customerShipTo->cst_language_code = $language;
            $customerShipTo->cst_language_desc = $langdesc;
            $customerShipTo->cst_telephone = $telephone;
            // $customerShipTo->cst_fax = $fax;
            $customerShipTo->cst_email = $email;
            // $customerShipTo->cst_internet = $internet;
            $customerShipTo->cst_created_by = $user;
            $customerShipTo->cst_updated_by = $user;
            $customerShipTo->cst_shopify_id = $shopify_cust_id;
            $customerShipTo->save();

            $prefix->running_nbr_ship_to_shopify = $prefix->running_nbr_ship_to_shopify + 1;
            $prefix->save();

            // Qxtend
            $qxtendServices = (new QxtendServices())->qxCustomerShipTo($customerShipTo, $domain_name, $operation);
            if ($qxtendServices[0] == 'success') {
                DB::commit();
                return [true, $shipToCode];
            } else {
                DB::rollBack();
                return [false, $qxtendServices[1]];
            }
        } catch (\Exception $err) {
            DB::rollBack();
            return ['Error', $err];
        }
    }

    public function saveSalesOrderShopify($data, $shipto, $userid, $sendQAD, $shopfiyID)
    {
        if ($shopfiyID == null || $shopfiyID == '') {
            Log::channel('ShopifySO')->info('Shopify ID empty, Ship To : ' . $shipto);
            return [false, 'Shopify Id Empty'];
        }
        $domain_id = '1'; // Always Risis (1), Info Brandon

        DB::beginTransaction();

        try {
            $domain = Domain::where('id', $domain_id)->first();
            $newLine = [];

            $defaultPrefix = Prefix::where('domain_id', $domain_id)->first();
            $prefixSO = $defaultPrefix->prefix_so_shopify;
            $runningNumber = (string) $defaultPrefix->running_nbr_so_shopify;
            $tahun = substr($runningNumber, 0, 2);
            $rnNumber = substr($runningNumber, 2, 8);

            if ($tahun != date('y')) {
                // Ganti Tahun, Reset RN ke 1
                // $runningNumber = date('y').str_pad($rnNumber, 4, '0', STR_PAD_LEFT).'1';
                $runningNumber = date('y') . '00001';
            }

            $soNumber = $prefixSO . $runningNumber;
            $soldTo = $data->header->shop == 'risis' ? 'E1021' : 'E1021A';
            $shipTo = $shipto;
            $orderDate = $data->header->date_buy;
            $needDate = $data->header->date_buy;

            $termsAndTax = (new WSAServices())->wsaTermsAndTaxByCustomer($domain_id, $domain->domain, $soldTo);
            $creditTerm = (string)$termsAndTax[1][0]->t_cm_cr_terms ?? '';
            $salesPerson = '21'; // Hardcode to `Crystal`;
            $so_po = $data->header->order_no;

            // $tax_class = (String)$termsAndTax[1][0]->t_cm_tax_class ?? '';
            // $taxable = (String)$termsAndTax[1][0]->t_cm_taxable == 'true' ? 'Yes' : 'No' ?? '';
            // $currency = $data->header->cur_deal;
            $tax_class = $data->header->cur_deal == 'SGD' ? 'S' : 'Z'; // Update 100225 --> Tax Class from JSON file
            $taxable = $data->header->cur_deal == 'SGD' ? 'Yes' : 'No'; // Update 100225 --> Taxable from JSON file
            $currency = 'SGD'; // Update 100225 --> Currency Default SGD

            $tax_rate = (string)$termsAndTax[1][0]->t_cm_tax_rate ?? '';

            $currencyFrom = (new WSAServices())->wsaBaseCurrency($domain->id, $domain->domain);
            $currencyTo = $currency;
            $wsaExchangeRate = (new WSAServices())->wsaExchangeRate($domain->id, $domain->domain, $currencyFrom[1][0]['t_base_curr'] ?? '', $currencyTo);
            $exchangeRate = $wsaExchangeRate[0] == 'false' ? 0 : $wsaExchangeRate[1][0]['t_rate2'];
            $so_trl_amt_1 = $data->header->freight ?? 0; // Field Freight
            $so_trl_amt_2 = 0; // Field Duty & Tax
            $totalPrice = 0;
            $totalQtyOrder = 0;

            // Calculate total qty order and total net price
            if (count($data->lines) > 0) {
                foreach ($data->lines as $key => $detail) {
                    $totalQtyOrder = $totalQtyOrder + $detail->qty;
                    $totalPrice = $totalPrice + $detail->unitprice;
                }
            }

            // Save to SO Master
            $salesOrder = new SOMstr();
            $salesOrder->domain_id = $domain_id;
            $salesOrder->so_nbr = $soNumber;
            $salesOrder->sold_to = $soldTo;
            $salesOrder->ship_to = $shipTo;
            $salesOrder->total_qty_ord = $totalQtyOrder;
            $salesOrder->total_qty_open = $totalQtyOrder;
            $salesOrder->total_price = $totalPrice;
            $salesOrder->total_qty_ship = 0;
            $salesOrder->order_date = $orderDate;
            $salesOrder->need_date = $needDate;
            $salesOrder->so_ct_code = $creditTerm;
            // $salesOrder->so_fr_list = $fr_list;
            // $salesOrder->so_fr_terms = $fr_term;
            $salesOrder->so_po = $so_po;
            $salesOrder->so_sales_person = $salesPerson;
            $salesOrder->so_taxc = $tax_class;
            $salesOrder->so_is_taxable = $taxable;
            $salesOrder->so_tax_rate = $tax_rate;
            $salesOrder->so_currency = $currency;
            $salesOrder->so_exchange_rate = $exchangeRate;
            // $salesOrder->so_is_taxin = $so_taxin;
            $salesOrder->so_trl_amt_1 = $so_trl_amt_1;
            $salesOrder->so_trl_amt_2 = $so_trl_amt_2;
            // $salesOrder->so_trl_amt_3 = $so_trl_amt_3;
            // $salesOrder->remark = $remark;
            // $salesOrder->comment = $comment;
            $salesOrder->so_status = 'New';
            $salesOrder->created_by = $userid;
            $salesOrder->shopify_id = $shopfiyID;
            $salesOrder->save();

            // Update SO running number
            $defaultPrefix->running_nbr_so_shopify = $runningNumber + 1;
            $defaultPrefix->save();

            // Save to SO Detail
            if (count($data->lines) > 0) {
                foreach ($data->lines as $key => $details) {
                    $soDetail = new SODet();
                    $soDetail->so_mstr_id = $salesOrder->id;
                    $soDetail->line_detail = $details->line;
                    $soDetail->item_code = $details->sku;
                    $soDetail->item_desc = $details->descs;


                    $wsaItemCode = (new WSAServices())->wsaItemByFirstInput($domain->id, $domain->domain, $details->sku);

                    $itemProdLine = '';
                    $itemGroup = '';
                    $itemType = '';
                    $itemPromo = '';
                    $itemUm = '';
                    $itemTaxClass = $tax_class;
                    $itemPrice = '0';
                    if ($wsaItemCode[0] == 'true') {
                        $itemProdLine = (string)$wsaItemCode[1][0]->t_prod_line;
                        $itemGroup = (string)$wsaItemCode[1][0]->t_group;
                        $itemType = (string)$wsaItemCode[1][0]->t_part_type;
                        $itemPromo = (string)$wsaItemCode[1][0]->t_promo;
                        $itemUm = (string)$wsaItemCode[1][0]->t_um;
                        $itemTaxClass = (string)$wsaItemCode[1][0]->t_pt_taxc;
                        $itemPrice = (string)$wsaItemCode[1][0]->t_price;
                    }

                    $soDetail->item_prod_line = $itemProdLine;
                    $soDetail->item_group = $itemGroup;
                    $soDetail->item_type = $itemType;
                    $soDetail->item_promo = $itemPromo;
                    $soDetail->qty_order = $details->qty;
                    $soDetail->qty_open = $details->qty;
                    $soDetail->qty_ship = 0;
                    $soDetail->item_um = $itemUm;
                    $soDetail->sod_loc = "ECOMM";

                    // Update 100225 --> Currency Default SGD
                    // $soDetail->list_price = $details->unitprice + $details->discount;
                    // $soDetail->item_discount = $details->discount;
                    // $soDetail->item_net_price = $details->unitprice;
                    $soDetail->list_price = (float)$itemPrice;
                    // $soDetail->item_discount = (float)$itemPrice - ($details->unitprice - $details->discount);
                    // Update 100325 --> By Request User
                    $pembagi = (float)$itemPrice == 0 ? 1 : (float)$itemPrice;
                    $soDetail->item_discount = abs(((float)$details->unitprice - (float)$details->discount - (float)$itemPrice) / $pembagi * 100);
                    $soDetail->item_net_price = (float)$details->unitprice - (float)$details->discount;

                    $soDetail->sod_taxc = $itemTaxClass;
                    $soDetail->sod_is_taxable = $details->taxable == 1 ? 'Yes' : 'No';
                    $soDetail->sod_tax_in = $details->taxable == 1 ? 'Yes' : 'No';
                    // $soDetail->sod_tax_pct = $hidden_tax_rate[$key];
                    // $soDetail->comment_det = $commentDet[$key];
                    $soDetail->created_by = $userid;
                    $soDetail->save();

                    // Save to SO History
                    $soHistory = new SOHist();
                    $soHistory->domain_id = $domain_id;
                    $soHistory->so_nbr = $soNumber;
                    $soHistory->sold_to = $soldTo;
                    $soHistory->ship_to = $shipTo;
                    $soHistory->total_qty_ord = $totalQtyOrder;
                    $soHistory->total_qty_open = $totalQtyOrder;
                    $soHistory->total_qty_ship = 0;
                    $soHistory->total_price = $totalPrice;
                    $soHistory->order_date = $orderDate;
                    $soHistory->need_date = $needDate;
                    $soHistory->so_ct_code = $creditTerm;
                    // $soHistory->so_fr_list = $fr_list;
                    // $soHistory->so_fr_terms = $fr_term;
                    $soHistory->so_po = $so_po;
                    $soHistory->so_sales_person = $salesPerson;
                    $soHistory->so_taxc = $tax_class;
                    $soHistory->so_is_taxable = $taxable == NULL ? 'No' : $taxable;
                    $soHistory->so_tax_rate = $tax_rate;
                    $soHistory->so_currency = $currency;
                    $soHistory->so_exchange_rate = $exchangeRate;
                    // $soHistory->so_is_taxin = $so_taxin;
                    $soHistory->so_trl_amt_1 = $so_trl_amt_1;
                    $soHistory->so_trl_amt_2 = $so_trl_amt_2;
                    // $soHistory->so_trl_amt_3 = $so_trl_amt_3;

                    $soHistory->line_detail = $details->line;
                    $soHistory->item_code = $details->sku;
                    $soHistory->item_desc = $details->descs;

                    $soHistory->item_prod_line = $itemProdLine;
                    $soHistory->item_group = $itemGroup;
                    $soHistory->item_type = $itemType;
                    $soHistory->item_promo = $itemPromo;

                    $soHistory->qty_order = $details->qty;
                    $soHistory->qty_open = $details->qty;
                    $soHistory->qty_ship = 0;

                    $soHistory->item_um = $itemUm;
                    $soHistory->sod_loc = "ECOMM";

                    // Update 100225 --> Currency Default SGD
                    // $soHistory->list_price = $details->unitprice + $details->discount;
                    // $soHistory->item_discount = $details->discount;
                    // $soHistory->item_net_price = $details->unitprice;

                    $soHistory->list_price = (float)$itemPrice;
                    // $soHistory->item_discount = (float)$itemPrice - ($details->unitprice - $details->discount);

                    $pembagi = (float)$itemPrice == 0 ? 1 : (float)$itemPrice;
                    $soHistory->item_discount = abs(((float)$details->unitprice - (float)$details->discount - (float)$itemPrice) / $pembagi * 100);
                    $soHistory->item_net_price = (float)$details->unitprice - (float)$details->discount;

                    $soHistory->sod_taxc = $itemTaxClass;
                    $soHistory->sod_is_taxable = $details->taxable == 1 ? 'Yes' : 'No';
                    $soHistory->sod_tax_in = $details->taxable == 1 ? 'Yes' : 'No';

                    // $soHistory->sod_tax_pct = $hidden_tax_rate[$key];
                    // $soHistory->comment_det = $commentDet[$key];
                    $soHistory->created_by = $userid;
                    $soHistory->action = 'Add';
                    $soHistory->save();
                }
            }

            // send SO to QAD, for now post to qad manually in addon so.
            if ($sendQAD == true) {
                $action = 'create';
                $qxtendSO = (new QxtendServices())->qxSalesOrder($domain_id, $soNumber, $newLine, $action);
                if ($qxtendSO[0] == 'error') {
                    DB::commit();
                    Log::channel('ShopifySO')->info('saveSalesOrderShopify return error : ' . $data->header->order_id);
                    return [false, 'Qxtend Sales Order Failed'];
                } else {
                    // Log::channel('ShopifySO')->info('saveSalesOrderShopify Sukses : '.$data->header->order_id);
                    // $salesOrder = SOMstr::where('id',$salesOrder->id)->first();
                    $salesOrder->so_sent_flag = 'Y';
                    $salesOrder->save();
                    DB::commit();
                    return [true, 'Sales Order Data Created'];
                }
            } else {
                DB::commit();
                return [true, 'Sales Order Data Created'];
            }
        } catch (\Exception $err) {
            DB::rollBack();
            // dd($err);
            Log::channel('ShopifySO')->info('saveSalesOrderShopify return error : ' . $err);
            return [false, $err];
        }
    }

    public function qxPendingInvoice($data, $domainid, $salesPsn)
    {
        // $newdata = $data['response'];
        $newdata = $data;

        $qxwsa = qxwsa::where('domain_id', $domainid)->first();
        $domain = Domain::find($qxwsa->domain_id)->first();
        $domain_name = $domain->domain;
        $qxUrl = $qxwsa->qx_url;
        $timeout = 0;
        $receiver = 'risis';

        // $customer = $newdata[0]['LOC_INFO1'];
        $getCustomer = $this->getCustomerCodeEPoint(trim($newdata[0]['LOCATION']));
        $customer = $getCustomer == false ? $newdata[0]['LOC_INFO1'] : $getCustomer;
        $salesPerson = $salesPsn;

        $date = substr($newdata[0]['SHIFTCODE'], 0, 8);
        $formattedDate = Carbon::createFromFormat('Ymd', $date)->format('Y-m-d');

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
				<maintainPendingInvoice>
                    <qcom:dsSessionContext>
                        <qcom:ttContext>
                            <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                            <qcom:propertyName>domain</qcom:propertyName>
                            <qcom:propertyValue>' . $domain_name . '</qcom:propertyValue>
                        </qcom:ttContext>
                        <qcom:ttContext>
                            <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                            <qcom:propertyName>scopeTransaction</qcom:propertyName>
                            <qcom:propertyValue>true</qcom:propertyValue>
                        </qcom:ttContext>
                        <qcom:ttContext>
                            <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
                            <qcom:propertyName>version</qcom:propertyName>
                            <qcom:propertyValue>eB2_4</qcom:propertyValue>
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
                    <dsPendingInvoice>
                        <pendingInvoice>
                            <operation>A</operation>
                            <soCust>' . $customer . '</soCust>
                            <soBill>' . $customer . '</soBill>
                            <soShip>' . $customer . '</soShip>
                            <yn>true</yn>
                            <soOrdDate>' . $formattedDate . '</soOrdDate>
                            <soReqDate>' . $formattedDate . '</soReqDate>
                            <soDueDate>' . $formattedDate . '</soDueDate>
                            <soShipDate>' . $formattedDate . '</soShipDate>
                            <soSlspsn>' . $salesPerson . '</soSlspsn>
                            <taxEdit>true</taxEdit>';

        foreach ($newdata as $key => $newdatas) {
            $part = trim($newdatas['STOCK']);
            $location = trim($newdatas['LOCATION']);
            $amount = $newdatas['AMOUNT'] == '.00' ? 1 : $newdatas['AMOUNT'];
            $price = 0;
            $discPersentage = 0;

            if ((float) $newdatas['QUANTITY'] !== 0.0) {
                // Ammount dibuat 0 karena kalo ga masuk ke qad $1 instead of $0, tapi buat pembagi dibuat 1 biar ga error division by 0
                $amount = $newdatas['AMOUNT'] == '.00' ? 0 : $newdatas['AMOUNT'];
                $price = number_format($amount / $newdatas['QUANTITY'], 4, '.', '');
                if ($amount != 0) {
                    $discPersentage = $newdatas['DISCTTL'] / $amount * 100;
                } else {
                    $discPersentage = $newdatas['DISCTTL'] / 1 * 100;
                }
            }

            $qdocRequest .= '<salesLine>
                                        <operation>A</operation>
                                        <line>' . $key + 1 . '</line>
                                        <sodPart>' . $part . '</sodPart>
                                        <sodQtyChg>' . $newdatas['QUANTITY'] . '</sodQtyChg>
                                        <sodListPr>' . $price . '</sodListPr>
                                        <discount>' . $discPersentage . '</discount>
                                        <sodLoc>' . $location . '</sodLoc>';

            if (substr($location, 0, 3) != 'APT') {
                // Validasi dari xxpinv2.p
                $qdocRequest .= '<sodTaxable>true</sodTaxable>';
            } else {
                $qdocRequest .= '<sodTaxable>false</sodTaxable>';
            }

            $qdocRequest .= '<sodTaxIn>true</sodTaxIn>';
            $qdocRequest .= '</salesLine>';
        }

        foreach ($newdata as $key => $newdatas) {
            if ((float) $newdatas['QUANTITY'] !== 0.0) {
                // Manual isi Tax Trailer
                $qdocRequest .= '<taxDetailRecord>
                                        <taxLine>' . $key + 1 . '</taxLine>
                                        <tx2dTotamt>' . $newdatas['NETAMT'] . '</tx2dTotamt>
                                        <tx2dTottax>' . $newdatas['NETAMT'] . '</tx2dTottax>
                                        <tx2dCurTaxAmt>' . $newdatas['TAXAMT2'] . '</tx2dCurTaxAmt>
                                    </taxDetailRecord>';
            }
        }


        $qdocRequest .= '</pendingInvoice>
                    </dsPendingInvoice>
                </maintainPendingInvoice>
            </soapenv:Body>
        </soapenv:Envelope>';

        $qdocRequest = str_replace('&', '&amp;', $qdocRequest);

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

        $errorMessage = '';

        if ($qdocResult != 'success' || $qdocResult != 'warning') {
            $xmlResp->registerXPathNamespace('ns3', 'urn:schemas-qad-com:xml-services:common');
            $errMsgs = $xmlResp->xpath('//ns3:tt_msg_desc');
            $errorMessage = '';
            foreach ($errMsgs as $err) {
                $errorMessage .= $err;
            }
        }

        return [$qdocResult, $errorMessage];
    }

    public function wsaGetMailPOS($domain_id, $domain, $lang, $ref)
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
                <get_email_pos xmlns="' . $wsa->wsa_path . '">
                    <inpdomain>' . $domain . '</inpdomain>
                    <inplang>' . $lang . '</inplang>
                    <inpref>' . $ref . '</inpref>
                </get_email_pos>
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

    public function wsaValidasiMailPOS($domain_id, $domain, $data)
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

        $date = substr($data['SHIFTCODE'], 0, 8);
        $formattedDate = Carbon::createFromFormat('Ymd', $date)->format('Y-m-d');

        $getSalesPerson = $this->getSalesPersonEPoint(trim($data['LOCATION']));
        $salesPerson = trim($data['LOC_INFO2']) == "" ? $getSalesPerson : trim($data['LOC_INFO2']);

        $getCustomer = $this->getCustomerCodeEPoint(trim($data['LOCATION']));
        $customerCode = $getCustomer == false ? trim($data['LOC_INFO1']) : $getCustomer;

        $qdocRequest =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
            <Body>
                <get_email_pos_validation xmlns="' . $wsa->wsa_path . '">
                    <inpdomain>' . $domain . '</inpdomain>
                    <inpsoldto>' . $customerCode . '</inpsoldto>
                    <inpsite>' . $domain . '</inpsite>
                    <inpbillto>' . $customerCode . '</inpbillto>
                    <inpshipto>' . $customerCode . '</inpshipto>
                    <inporddate>' . $formattedDate . '</inporddate>
                    <inpreqdate>' . $formattedDate . '</inpreqdate>
                    <inpduedate>' . $formattedDate . '</inpduedate>
                    <inpshipdate>' . $formattedDate . '</inpshipdate>
                    <inpslsprn>' . $salesPerson . '</inpslsprn>
                    <inppart>' . trim($data['STOCK']) . '</inppart>
                    <inploc>' . trim($data['LOCATION']) . '</inploc>
                    <inpqty>' . $data['QUANTITY'] . '</inpqty>
                </get_email_pos_validation>
            </Body>
        </Envelope>';
        // SHIFCODE => 4 DIGIT TAHUN 2 DIGIT BULAN 2 DIGIT HARI 1 DIGIT SHIFT

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
            Log::channel('SummaryPendingInvoice')->info('WSA Validasi Failed');
            return false;
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);
        $dataloop   = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOK')[0];

        return [$qdocResult, $dataloop];
    }

    public function wsaGetNextPendingInvoice($domain_id, $domain)
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
                <get_next_pending_invoice xmlns="' . $wsa->wsa_path . '">
                    <inpdomain>' . $domain . '</inpdomain>
                </get_next_pending_invoice>
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
            Log::channel('SummaryPendingInvoice')->info('WSA Next SO Failed');
            return false;
        }

        $xmlResp = simplexml_load_string($qdocResponse);

        $xmlResp->registerXPathNamespace('ns1', $wsa->wsa_path);

        $dataloop   = $xmlResp->xpath('//ns1:tempRow');
        $qdocResult = (string) $xmlResp->xpath('//ns1:outOk')[0];

        return [$qdocResult, $dataloop];
    }

    public function getEpointAPI($dateFilter)
    {
        $setting = EpointSetting::firstOrFail();
        $endpoint = $setting->eas_url_endpoint;
        $comp_code = $setting->eas_comp_code;
        $app_id = $setting->eas_app_id;
        $hash = $setting->eas_hash;
        $authorise_code = $setting->eas_authorise_code;

        $response = Http::asForm()->post($endpoint, [
            'comp_code' => $comp_code,  // Other form data
            'app_id' => $app_id,
            'hash' => $hash,
            'authorise_code' => $authorise_code,
            'start_date' => $dateFilter,
            'end_date' => $dateFilter,
        ]);

        $status = $response->getStatusCode();
        $data = $response->json();

        return [$status, $data];
    }

    public function getSalesPersonEPoint($location)
    {
        $data = [
            "ROC" => "01",
            "GNS" => "33",
            "GTG" => "34",
            "ION" => "26",
            "TAKA" => "44",
            "TANGS" => "32",
            "WISMA" => "36"
        ];

        if (isset($data[$location])) {
            $value = $data[$location];
            return $value;
        } else {
            return false;
        }
    }

    public function getCustomerCodeEPoint($location)
    {
        $data = [
            "ROC" => "R1004",
            "GNS" => "R1059",
            "GTG" => "R1081",
            "ION" => "R1115",
            "TAKA" => "T1063",
            "TANGS" => "C1001",
            "WISMA" => "R1119"
        ];

        if (isset($data[$location])) {
            $value = $data[$location];
            return $value;
        } else {
            return false;
        }
    }
}
